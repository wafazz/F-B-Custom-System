<?php

namespace Database\Seeders;

use App\Models\SpinWheelSegment;
use Illuminate\Database\Seeder;

class SpinWheelSeeder extends Seeder
{
    public function run(): void
    {
        if (SpinWheelSegment::query()->exists()) {
            return;
        }

        $defaults = [
            ['label' => '5 pts', 'color' => '#f59e0b', 'weight' => 30, 'prize_type' => 'points', 'prize_points' => 5, 'sort_order' => 1],
            ['label' => '10 pts', 'color' => '#fb923c', 'weight' => 25, 'prize_type' => 'points', 'prize_points' => 10, 'sort_order' => 2],
            ['label' => '20 pts', 'color' => '#e11d48', 'weight' => 15, 'prize_type' => 'points', 'prize_points' => 20, 'sort_order' => 3],
            ['label' => 'Try again', 'color' => '#737373', 'weight' => 20, 'prize_type' => 'none', 'sort_order' => 4],
            ['label' => '50 pts', 'color' => '#16a34a', 'weight' => 8, 'prize_type' => 'points', 'prize_points' => 50, 'sort_order' => 5],
            ['label' => '+1 day', 'color' => '#0ea5e9', 'weight' => 18, 'prize_type' => 'points', 'prize_points' => 15, 'sort_order' => 6],
            ['label' => 'Try again', 'color' => '#a3a3a3', 'weight' => 20, 'prize_type' => 'none', 'sort_order' => 7],
            ['label' => '100 pts', 'color' => '#7c3aed', 'weight' => 4, 'prize_type' => 'points', 'prize_points' => 100, 'sort_order' => 8],
        ];

        foreach ($defaults as $row) {
            SpinWheelSegment::create($row);
        }
    }
}
