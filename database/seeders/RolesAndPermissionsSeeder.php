<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /** Resource models that get the standard 12 permission slugs. */
    public const RESOURCES = ['branch', 'branch::staff', 'user', 'role'];

    public const ACTIONS = [
        'view', 'view_any', 'create', 'update',
        'restore', 'restore_any', 'replicate', 'reorder',
        'delete', 'delete_any', 'force_delete', 'force_delete_any',
    ];

    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (self::roles() as $name => $description) {
            Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        foreach (self::permissionSlugs() as $slug) {
            Permission::firstOrCreate(['name' => $slug, 'guard_name' => 'web']);
        }

        foreach (self::rolePermissionMatrix() as $role => $perms) {
            Role::findByName($role, 'web')->syncPermissions($perms);
        }

        if (User::find(1) && app()->runningInConsole() && ! app()->runningUnitTests()) {
            Artisan::call('shield:super-admin', [
                '--user' => 1,
                '--panel' => 'admin',
            ], $this->command->getOutput());
        }
    }

    public static function roles(): array
    {
        return [
            'super_admin' => 'Full system access — Star Coffee owner / lead developer',
            'hq_admin' => 'HQ-level access except billing',
            'ops_manager' => 'Operations: branches, staff, orders, stock',
            'mkt_manager' => 'Marketing: promo, voucher, loyalty, segments',
            'branch_manager' => 'Manages a specific branch — own branch only',
            'cashier' => 'POS access + own branch orders',
            'barista' => 'Order queue access only',
            'customer' => 'Default customer role (no admin access)',
        ];
    }

    /** @return list<string> */
    public static function permissionSlugs(): array
    {
        $slugs = [];
        foreach (self::RESOURCES as $resource) {
            foreach (self::ACTIONS as $action) {
                $slugs[] = "{$action}_{$resource}";
            }
        }

        return $slugs;
    }

    /** @return array<string, list<string>> */
    public static function rolePermissionMatrix(): array
    {
        $allBranch = self::slugsFor('branch');
        $allBranchStaff = self::slugsFor('branch::staff');
        $allUser = self::slugsFor('user');
        $allRole = self::slugsFor('role');

        $readBranch = ['view_any_branch', 'view_branch'];
        $readBranchStaff = ['view_any_branch::staff', 'view_branch::staff'];
        $readUser = ['view_any_user', 'view_user'];

        $branchManagerScope = array_merge(
            $readBranch,
            ['update_branch'],
            $allBranchStaff,
            $readUser,
        );

        return [
            'hq_admin' => array_merge(
                $allBranch, $allBranchStaff, $allUser,
                ['view_any_role', 'view_role'],
            ),
            'ops_manager' => array_merge($allBranch, $allBranchStaff, $allUser),
            'mkt_manager' => array_merge($readBranch, $readUser),
            'branch_manager' => $branchManagerScope,
            'cashier' => array_merge($readBranch, $readBranchStaff),
            'barista' => $readBranch,
            'customer' => [],
        ];
    }

    /** @return list<string> */
    protected static function slugsFor(string $resource): array
    {
        return array_map(fn (string $action) => "{$action}_{$resource}", self::ACTIONS);
    }
}
