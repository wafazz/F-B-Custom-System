<?php

namespace Database\Factories;

use App\Models\ModifierGroup;
use App\Models\ModifierOption;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ModifierOption>
 */
class ModifierOptionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'modifier_group_id' => ModifierGroup::factory(),
            'name' => $this->faker->word(),
            'price_delta' => $this->faker->randomFloat(2, 0, 5),
            'is_default' => false,
            'is_available' => true,
            'sort_order' => 0,
        ];
    }
}
