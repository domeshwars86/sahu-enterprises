<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactMessageRequest;
use App\Mail\NewContactMessage;
use App\Services\Contact\StoreContactMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class ContactMessageController extends Controller
{
    public function store(StoreContactMessageRequest $request, StoreContactMessage $storeContactMessage): JsonResponse
    {
        $contactMessage = $storeContactMessage->store($request->validated());

        if ($contactMessage->wasRecentlyCreated && ($companyEmail = config('services.sahu.enquiry_to'))) {
            try {
                Mail::to($companyEmail)->send(new NewContactMessage($contactMessage));
            } catch (Throwable) {
                Log::warning('Company notification for a contact message failed.', [
                    'reference_number' => $contactMessage->reference_number,
                ]);
            }
        }

        return response()->json([
            'message' => 'Your message has been received.',
            'reference_number' => $contactMessage->reference_number,
        ], $contactMessage->wasRecentlyCreated ? 201 : 200);
    }
}
