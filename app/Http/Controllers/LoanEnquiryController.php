<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLoanEnquiryRequest;
use App\Mail\LoanEnquiryReceived;
use App\Mail\NewLoanEnquiry;
use App\Services\LoanEnquiry\StoreLoanEnquiry;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class LoanEnquiryController extends Controller
{
    public function store(StoreLoanEnquiryRequest $request, StoreLoanEnquiry $storeLoanEnquiry): JsonResponse
    {
        $enquiry = $storeLoanEnquiry->store($request->validated());

        if ($enquiry->wasRecentlyCreated) {
            $companyEmail = config('services.sahu.enquiry_to');

            if ($companyEmail) {
                try {
                    Mail::to($companyEmail)->send(new NewLoanEnquiry($enquiry));
                } catch (Throwable) {
                    Log::warning('Company notification for a loan enquiry failed.', [
                        'reference_number' => $enquiry->reference_number,
                    ]);
                }
            }

            if ($enquiry->email) {
                try {
                    Mail::to($enquiry->email)->send(new LoanEnquiryReceived($enquiry));
                } catch (Throwable) {
                    Log::warning('Customer acknowledgement for a loan enquiry failed.', [
                        'reference_number' => $enquiry->reference_number,
                    ]);
                }
            }
        }

        return response()->json([
            'message' => 'Your enquiry has been received.',
            'reference_number' => $enquiry->reference_number,
        ], $enquiry->wasRecentlyCreated ? 201 : 200);
    }
}
