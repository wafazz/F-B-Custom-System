<?php

use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        foreach (RolesAndPermissionsSeeder::ACTIONS as $action) {
            Permission::firstOrCreate([
                'name' => "{$action}_wallet",
                'guard_name' => 'web',
            ]);
        }

        $walletPerms = Permission::query()
            ->where('name', 'like', '%_wallet')
            ->pluck('name')
            ->all();

        // findByName throws when the role doesn't exist; on a fresh DB
        // (e.g. RefreshDatabase in tests) the seeder hasn't run yet, so use
        // a null-returning query.
        $hqAdmin = Role::query()->where('name', 'hq_admin')->where('guard_name', 'web')->first();
        if ($hqAdmin && $walletPerms !== []) {
            $hqAdmin->givePermissionTo($walletPerms);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::query()->where('name', 'like', '%_wallet')->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
