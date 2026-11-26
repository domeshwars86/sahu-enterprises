<?php

namespace Database\Factories;

use App\Models\LoanCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<LoanCategory>
 */
class LoanCategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'slug' => Str::slug($name),
            'name' => Str::title($name),
            'description' => fake()->sentence(),
            'icon' => '↗',
            'enquiry_fields' => [],
            'is_enabled' => true,
            'display_order' => fake()->numberBetween(0, 100),
        ];
    }
}
