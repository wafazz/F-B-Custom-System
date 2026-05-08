<?php

namespace Database\Seeders;

use App\Models\MembershipTier;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class LoyaltySeeder extends Seeder
{
    public function run(): void
    {
        $tiers = [
            ['name' => 'Bronze', 'min_lifetime_spend' => 0, 'earn_multiplier' => 1.00, 'color' => '#cd7f32'],
            ['name' => 'Silver', 'min_lifetime_spend' => 200, 'earn_multiplier' => 1.25, 'color' => '#c0c0c0'],
            ['name' => 'Gold', 'min_lifetime_spend' => 500, 'earn_multiplier' => 1.50, 'color' => '#ffd700'],
            ['name' => 'Platinum', 'min_lifetime_spend' => 1500, 'earn_multiplier' => 2.00, 'color' => '#e5e4e2'],
        ];

        foreach ($tiers as $i => $row) {
            MembershipTier::firstOrCreate(
                ['slug' => Str::slug($row['name'])],
                array_merge($row, ['sort_order' => $i]),
            );
        }
    }
}
