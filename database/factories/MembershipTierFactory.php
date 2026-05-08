<?php

namespace Database\Factories;

use App\Models\MembershipTier;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<MembershipTier>
 */
class MembershipTierFactory extends Factory
{
    public function definition(): array
    {
        $name = $this->faker->randomElement(['Bronze', 'Silver', 'Gold', 'Platinum']).' '.Str::random(3);

        return [
            'name' => $name,
            'min_lifetime_spend' => $this->faker->numberBetween(0, 1000),
            'earn_multiplier' => 1.00,
            'sort_order' => 0,
        ];
    }
}
