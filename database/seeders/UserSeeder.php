<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $superAdmin = User::updateOrCreate(
            ['email' => 'superadmin@starcoffee.my'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('MoHd20188!'),
                'email_verified_at' => now(),
            ],
        );
        $superAdmin->syncRoles(['super_admin']);

        $admin = User::updateOrCreate(
            ['email' => 'admin@starcoffee.my'],
            [
                'name' => 'Admin',
                'password' => Hash::make('MoHd20188!'),
                'email_verified_at' => now(),
            ],
        );
        $admin->syncRoles(['hq_admin']);

        if (app()->runningInConsole() && ! app()->runningUnitTests()) {
            Artisan::call('shield:super-admin', [
                '--user' => $superAdmin->id,
                '--panel' => 'admin',
            ], $this->command->getOutput());
        }
    }
}
