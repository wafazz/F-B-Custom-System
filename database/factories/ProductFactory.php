<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        $name = $this->faker->randomElement([
            'Cappuccino', 'Latte', 'Flat White', 'Americano', 'Cortado',
            'Mocha', 'Macchiato', 'Espresso Shot', 'Croissant', 'Pain au Chocolat',
            'Chocolate Cake', 'Cheesecake', 'Tiramisu', 'Iced Latte', 'Cold Brew',
        ]).' '.$this->faker->randomElement(['Original', 'Signature', 'Special', 'Classic']).' '.Str::random(4);

        return [
            'category_id' => Category::factory(),
            'name' => $name,
            'description' => $this->faker->sentence(),
            'sku' => 'SKU-'.strtoupper(Str::random(6)),
            'base_price' => $this->faker->randomFloat(2, 6, 35),
            'sst_applicable' => true,
            'calories' => $this->faker->numberBetween(50, 500),
            'prep_time_minutes' => $this->faker->numberBetween(2, 10),
            'status' => 'active',
            'is_featured' => false,
            'sort_order' => $this->faker->numberBetween(0, 50),
        ];
    }

    public function featured(): static
    {
        return $this->state(['is_featured' => true]);
    }

    public function hidden(): static
    {
        return $this->state(['status' => 'hidden']);
    }
}
