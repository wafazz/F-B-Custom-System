<?php

namespace Database\Factories;

use App\Models\ModifierGroup;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ModifierGroup>
 */
class ModifierGroupFactory extends Factory
{
    public function definition(): array
    {
        $name = $this->faker->randomElement(['Size', 'Sugar Level', 'Milk Type', 'Add-ons', 'Temperature']).' '.Str::random(4);

        return [
            'name' => $name,
            'selection_type' => 'single',
            'is_required' => false,
            'min_select' => 0,
            'max_select' => 1,
            'sort_order' => 0,
        ];
    }

    public function multiple(): static
    {
        return $this->state(['selection_type' => 'multiple', 'max_select' => 5]);
    }

    public function required(): static
    {
        return $this->state(['is_required' => true, 'min_select' => 1]);
    }
}
