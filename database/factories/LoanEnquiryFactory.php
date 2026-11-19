<?php

namespace Database\Factories;

use App\Models\LoanEnquiry;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<LoanEnquiry>
 */
class LoanEnquiryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reference_number' => 'SAH-'.now()->format('Ymd').'-'.Str::upper(Str::random(8)),
            'status' => 'new',
            'idempotency_key' => (string) Str::uuid(),
            'customer_name' => fake()->name(),
            'mobile_number' => '9876543210',
            'email' => fake()->safeEmail(),
            'city' => fake()->city(),
            'loan_category' => 'new_car',
            'requested_amount' => 850000,
            'preferred_tenure' => '3–5 years',
            'employment_category' => 'salaried',
            'vehicle_type' => 'car',
            'vehicle_condition' => 'new',
            'vehicle_price' => 1000000,
            'down_payment' => 150000,
            'preferred_callback_time' => 'Morning',
            'enquiry_source' => 'website',
            'contact_consent' => true,
            'consented_at' => now(),
            'marketing_consent' => false,
            'privacy_notice_version' => '1.0',
        ];
    }
}
