<?php

namespace Tests\Feature;

use App\Mail\LoanEnquiryReceived;
use App\Mail\NewLoanEnquiry;
use App\Models\LoanEnquiry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class StoreLoanEnquiryTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_enquiry_is_persisted_and_notifies_company_and_customer(): void
    {
        Mail::fake();
        config(['services.sahu.enquiry_to' => 'enquiries@sahu.example']);

        $response = $this->postJson('/apply', $this->validPayload());

        $response->assertCreated()
            ->assertJsonStructure(['message', 'reference_number'])
            ->assertJsonPath('message', 'Your enquiry has been received.');

        $this->assertDatabaseHas('loan_enquiries', [
            'reference_number' => $response->json('reference_number'),
            'loan_category' => 'new_car',
            'contact_consent' => true,
            'marketing_consent' => false,
        ]);
        $this->assertDatabaseMissing('loan_enquiries', ['customer_name' => 'Asha Patel']);
        $this->assertSame('Asha Patel', LoanEnquiry::query()->firstOrFail()->customer_name);

        Mail::assertSent(NewLoanEnquiry::class);
        Mail::assertSent(LoanEnquiryReceived::class);
    }

    public function test_enquiry_requires_contact_consent(): void
    {
        Mail::fake();
        $payload = $this->validPayload();
        $payload['contact_consent'] = false;

        $this->postJson('/apply', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('contact_consent');

        $this->assertDatabaseCount('loan_enquiries', 0);
        Mail::assertNothingSent();
    }

    public function test_repeated_idempotency_key_returns_original_enquiry_without_resending_mail(): void
    {
        Mail::fake();
        config(['services.sahu.enquiry_to' => 'enquiries@sahu.example']);
        $payload = $this->validPayload();

        $firstResponse = $this->postJson('/apply', $payload)->assertCreated();
        $secondResponse = $this->postJson('/apply', $payload)->assertOk();

        $this->assertSame($firstResponse->json('reference_number'), $secondResponse->json('reference_number'));
        $this->assertDatabaseCount('loan_enquiries', 1);
        Mail::assertSent(NewLoanEnquiry::class, 1);
        Mail::assertSent(LoanEnquiryReceived::class, 1);
    }

    public function test_invalid_mobile_number_is_rejected(): void
    {
        $payload = $this->validPayload();
        $payload['mobile_number'] = '12345';

        $this->postJson('/apply', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('mobile_number');

        $this->assertDatabaseCount('loan_enquiries', 0);
    }

    /** @return array<string, bool|int|string> */
    private function validPayload(): array
    {
        return [
            'idempotency_key' => 'b99ecf0e-f589-4e59-8cb0-7d8e8020c873',
            'customer_name' => 'Asha Patel',
            'mobile_number' => '9876543210',
            'email' => 'asha@example.com',
            'city' => 'Pune',
            'loan_category' => 'new_car',
            'requested_amount' => 850000,
            'preferred_tenure' => '3–5 years',
            'employment_category' => 'salaried',
            'vehicle_type' => 'car',
            'vehicle_condition' => 'new',
            'vehicle_price' => 1000000,
            'down_payment' => 150000,
            'preferred_callback_time' => 'Morning',
            'contact_consent' => true,
            'marketing_consent' => false,
        ];
    }
}
