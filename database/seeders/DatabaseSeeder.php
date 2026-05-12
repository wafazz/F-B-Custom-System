<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            UserSeeder::class,
            BranchSeeder::class,
            MenuSeeder::class,
            LoyaltySeeder::class,
        ]);
    }
}
