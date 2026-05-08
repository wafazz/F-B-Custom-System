<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\BranchStock;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BranchStock>
 */
class BranchStockFactory extends Factory
{
    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'product_id' => Product::factory(),
            'quantity' => 100,
            'low_threshold' => 10,
            'is_available' => true,
            'track_quantity' => false,
        ];
    }

    public function tracked(int $qty = 100, int $low = 10): static
    {
        return $this->state([
            'track_quantity' => true,
            'quantity' => $qty,
            'low_threshold' => $low,
        ]);
    }

    public function outOfStock(): static
    {
        return $this->state([
            'track_quantity' => true,
            'quantity' => 0,
            'is_available' => false,
        ]);
    }
}
