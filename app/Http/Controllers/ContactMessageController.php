<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactMessageRequest;
use App\Mail\NewContactMessage;
use App\Models\BusinessSetting;
use App\Models\User;
use App\Notifications\NewContactMessageNotification;
use App\Services\BusinessProfile;
use App\Services\Contact\StoreContactMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Throwable;

class ContactMessageController extends Controller
{
    public function store(StoreContactMessageRequest $request, StoreContactMessage $storeContactMessage, BusinessProfile $businessProfile): JsonResponse
    {
        $contactMessage = $storeContactMessage->store($request->validated());

        if ($contactMessage->wasRecentlyCreated && ($companyEmail = $businessProfile->notificationEmail())) {
            try {
                Mail::to($companyEmail)->send(new NewContactMessage($contactMessage));
            } catch (Throwable) {
                Log::warning('Company notification for a contact message failed.', [
                    'reference_number' => $contactMessage->reference_number,
                ]);
            }
        }

        if ($contactMessage->wasRecentlyCreated && (! Schema::hasTable('business_settings') || BusinessSetting::query()->where('key', 'contact_notifications_enabled')->value('value') !== '0')) {
            $recipients = User::query()->where('is_active', true)->with('adminRole')->get()
                ->filter(fn (User $user): bool => $user->hasAdminPermission('contact_messages.view'));
            Notification::send($recipients, new NewContactMessageNotification($contactMessage));
        }

        return response()->json([
            'message' => 'Your message has been received.',
            'reference_number' => $contactMessage->reference_number,
        ], $contactMessage->wasRecentlyCreated ? 201 : 200);
    }
}
