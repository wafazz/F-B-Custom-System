<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    public function definition(): array
    {
        $name = $this->faker->randomElement([
            'Coffee', 'Espresso', 'Tea', 'Pastries', 'Sandwiches', 'Cakes', 'Cold Brew', 'Smoothies',
        ]).' '.Str::random(4);

        return [
            'name' => $name,
            'description' => $this->faker->sentence(),
            'sort_order' => $this->faker->numberBetween(0, 100),
            'status' => 'active',
        ];
    }

    public function hidden(): static
    {
        return $this->state(['status' => 'hidden']);
    }
}
