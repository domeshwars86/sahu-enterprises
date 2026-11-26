<?php

namespace Database\Factories;

use App\Models\LoanProduct;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<LoanProduct>
 */
class LoanProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);

        return [
            'slug' => 'loans/'.Str::slug($name),
            'name' => Str::title($name),
            'category' => 'personal_finance',
            'description' => fake()->sentence(),
            'is_enabled' => false,
            'is_featured' => false,
            'display_order' => fake()->numberBetween(0, 100),
            'eligibility' => null,
            'documentation' => [],
            'enquiry_fields' => [],
        ];
    }
}
