<?php

namespace Database\Factories;

use App\Models\Voucher;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Voucher>
 */
class VoucherFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => 'TEST-'.strtoupper(Str::random(6)),
            'name' => $this->faker->words(2, true),
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'min_subtotal' => 0,
            'max_uses' => null,
            'max_uses_per_user' => 1,
            'used_count' => 0,
            'status' => 'active',
        ];
    }

    public function fixed(float $value): static
    {
        return $this->state([
            'discount_type' => 'fixed',
            'discount_value' => $value,
        ]);
    }
}
