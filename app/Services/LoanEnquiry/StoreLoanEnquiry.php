<?php

namespace App\Services\LoanEnquiry;

use App\Models\BusinessSetting;
use App\Models\Customer;
use App\Models\LoanEnquiry;
use App\Models\LoanProduct;
use App\Models\User;
use App\Notifications\NewLoanEnquiryNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class StoreLoanEnquiry
{
    /** @param array<string, mixed> $validatedData */
    public function store(array $validatedData): LoanEnquiry
    {
        $idempotencyKey = $validatedData['idempotency_key'];
        $productSlug = $validatedData['product_slug'] ?? null;
        $product = $productSlug ? LoanProduct::query()->where('slug', $productSlug)->where('is_enabled', true)->with('categories')->firstOrFail() : null;
        $details = $validatedData['details'] ?? [];
        unset($validatedData['idempotency_key'], $validatedData['contact_consent'], $validatedData['product_slug'], $validatedData['details']);

        if ($product) {
            $validatedData['loan_category'] = $product->categories()->orderBy('display_order')->value('slug') ?? $validatedData['loan_category'];
        }

        $enquiry = LoanEnquiry::firstOrCreate(
            ['idempotency_key' => $idempotencyKey],
            [
                ...$validatedData,
                'loan_product_id' => $product?->id,
                'product_details' => $details,
                'idempotency_key' => $idempotencyKey,
                'reference_number' => 'SAH-'.now()->format('Ymd').'-'.Str::upper(Str::random(8)),
                'enquiry_source' => 'website',
                'contact_consent' => true,
                'consented_at' => now(),
                'marketing_consent' => (bool) ($validatedData['marketing_consent'] ?? false),
                'privacy_notice_version' => config('services.sahu.privacy_notice_version'),
            ],
        );

        if ($enquiry->wasRecentlyCreated) {
            $key = (string) config('app.key');
            $mobile = preg_replace('/\D+/', '', (string) $enquiry->mobile_number);
            $email = mb_strtolower(trim((string) $enquiry->email));
            $enquiry->forceFill([
                'name_lookup' => hash_hmac('sha256', mb_strtolower(trim((string) $enquiry->customer_name)), $key),
                'mobile_lookup' => hash_hmac('sha256', preg_replace('/\D+/', '', (string) $enquiry->mobile_number), $key),
                'email_lookup' => hash_hmac('sha256', mb_strtolower(trim((string) $enquiry->email)), $key),
                'city_lookup' => hash_hmac('sha256', mb_strtolower(trim((string) $enquiry->city)), $key),
                'customer_id' => Customer::query()->create([
                    'contact_fingerprint' => hash_hmac('sha256', $mobile.'|'.$email.'|enquiry:'.$enquiry->id, $key),
                ])->id,
            ])->save();

            if (! Schema::hasTable('business_settings') || BusinessSetting::query()->where('key', 'new_enquiry_notifications_enabled')->value('value') !== '0') {
                $recipients = User::query()->where('is_active', true)->with('adminRole')->get()
                    ->filter(fn (User $user): bool => $user->hasAdminPermission('enquiries.view') && ! in_array($user->adminRole?->slug, ['dsa-agent', 'customer-support'], true));
                Notification::send($recipients, new NewLoanEnquiryNotification($enquiry));
            }
        }

        return $enquiry;
    }
}
