<?php

namespace Database\Factories;

use App\Models\LoanProductFaq;
use App\Models\LoanProduct;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LoanProductFaq>
 */
class LoanProductFaqFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'loan_product_id' => LoanProduct::factory(),
            'question' => fake()->sentence().'?',
            'answer' => fake()->paragraph(),
            'display_order' => fake()->numberBetween(0, 100),
        ];
    }
}
