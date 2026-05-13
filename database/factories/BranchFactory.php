<?php

namespace Database\Factories;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Branch>
 */
class BranchFactory extends Factory
{
    public function definition(): array
    {
        $name = $this->faker->randomElement(['Pavilion KL', 'Mid Valley', 'Sunway Pyramid', 'KLCC', 'IOI City Mall', 'Bangsar Village', 'TRX', 'Damansara Uptown']);

        return [
            'name' => 'Star Coffee — '.$name,
            'code' => 'SC-'.strtoupper(Str::random(4)),
            'phone' => '+60'.$this->faker->numerify('1#-####-####'),
            'email' => Str::slug($name).'@starcoffee.test',
            'address' => $this->faker->streetAddress(),
            'city' => $this->faker->randomElement(['Kuala Lumpur', 'Petaling Jaya', 'Subang Jaya', 'Shah Alam']),
            'state' => $this->faker->randomElement(['Selangor', 'Kuala Lumpur']),
            'postal_code' => $this->faker->numerify('#####'),
            'latitude' => $this->faker->latitude(2.9, 3.3),
            'longitude' => $this->faker->longitude(101.5, 101.8),
            // Factory default = 24h open so tests pass regardless of wall-clock.
            // Production branches use their own hours via the admin form.
            'operating_hours' => collect(Branch::defaultOperatingHours())
                ->map(fn ($h) => array_merge($h, ['open' => '00:00', 'close' => '23:59']))
                ->all(),
            'pickup_radius_meters' => 1000,
            'sst_rate' => 6.00,
            'sst_enabled' => true,
            'status' => 'active',
            'accepts_orders' => true,
            'sort_order' => 0,
        ];
    }

    public function closed(): static
    {
        return $this->state(['status' => 'closed', 'accepts_orders' => false]);
    }

    public function maintenance(): static
    {
        return $this->state(['status' => 'maintenance', 'accepts_orders' => false]);
    }
}
