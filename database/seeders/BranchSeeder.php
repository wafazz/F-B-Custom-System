<?php

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    public function run(): void
    {
        $branches = [
            ['code' => 'SC-KLCC', 'name' => 'Star Coffee — KLCC', 'city' => 'Kuala Lumpur', 'state' => 'Kuala Lumpur', 'lat' => 3.1579, 'lng' => 101.7117],
            ['code' => 'SC-PVL', 'name' => 'Star Coffee — Pavilion KL', 'city' => 'Kuala Lumpur', 'state' => 'Kuala Lumpur', 'lat' => 3.1490, 'lng' => 101.7141],
            ['code' => 'SC-MID', 'name' => 'Star Coffee — Mid Valley', 'city' => 'Kuala Lumpur', 'state' => 'Kuala Lumpur', 'lat' => 3.1177, 'lng' => 101.6770],
        ];

        foreach ($branches as $i => $b) {
            Branch::firstOrCreate(
                ['code' => $b['code']],
                [
                    'name' => $b['name'],
                    'phone' => '+60312345'.str_pad((string) ($i + 100), 3, '0', STR_PAD_LEFT),
                    'email' => strtolower($b['code']).'@starcoffee.test',
                    'address' => 'Lot '.($i + 1).', '.$b['city'],
                    'city' => $b['city'],
                    'state' => $b['state'],
                    'postal_code' => '50'.str_pad((string) ($i * 100), 3, '0', STR_PAD_LEFT),
                    'latitude' => $b['lat'],
                    'longitude' => $b['lng'],
                    'operating_hours' => Branch::defaultOperatingHours(),
                    'pickup_radius_meters' => 1500,
                    'sst_rate' => 6.00,
                    'sst_enabled' => true,
                    'status' => 'active',
                    'accepts_orders' => true,
                    'sort_order' => $i,
                ]
            );
        }
    }
}
