<?php

namespace App\Console\Commands;

use App\Models\LoanEnquiry;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('crm:index-enquiries')]
#[Description('Backfill encrypted-field lookup fingerprints and one enquiry-linked customer profile')]
class IndexLoanEnquiries extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $processed = 0;
        $key = (string) config('app.key');

        LoanEnquiry::query()->whereNull('name_lookup')->orWhereNull('mobile_lookup')->orderBy('id')->chunkById(100, function ($enquiries) use (&$processed, $key): void {
            foreach ($enquiries as $enquiry) {
                DB::transaction(function () use ($enquiry, $key, &$processed): void {
                    $mobile = preg_replace('/\D+/', '', (string) $enquiry->mobile_number);
                    $email = mb_strtolower(trim((string) $enquiry->email));
                    $enquiry->forceFill([
                        'name_lookup' => hash_hmac('sha256', mb_strtolower(trim((string) $enquiry->customer_name)), $key),
                        'mobile_lookup' => hash_hmac('sha256', $mobile, $key),
                        'email_lookup' => hash_hmac('sha256', $email, $key),
                        'city_lookup' => hash_hmac('sha256', mb_strtolower(trim((string) $enquiry->city)), $key),
                    ])->save();

                    if (! $enquiry->customer_id) {
                        $fingerprint = hash_hmac('sha256', $mobile.'|'.$email.'|enquiry:'.$enquiry->id, $key);
                        $customerId = DB::table('customers')->insertGetId(['contact_fingerprint' => $fingerprint, 'created_at' => now(), 'updated_at' => now()]);
                        $enquiry->forceFill(['customer_id' => $customerId])->save();
                    }

                    $processed++;
                });
            }
        });

        $this->info("Indexed {$processed} existing enquiries. No enquiries were merged or deleted.");

        return self::SUCCESS;
    }
}
