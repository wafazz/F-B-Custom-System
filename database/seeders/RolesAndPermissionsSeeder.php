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
    public const RESOURCES = [
        'branch', 'branch::staff', 'user', 'role',
        'category', 'product', 'modifier::group', 'branch::stock',
        'order',
    ];

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
        $allCategory = self::slugsFor('category');
        $allProduct = self::slugsFor('product');
        $allModifier = self::slugsFor('modifier::group');
        $allStock = self::slugsFor('branch::stock');
        $allOrder = self::slugsFor('order');

        $readBranch = ['view_any_branch', 'view_branch'];
        $readBranchStaff = ['view_any_branch::staff', 'view_branch::staff'];
        $readUser = ['view_any_user', 'view_user'];
        $readCatalog = [
            'view_any_category', 'view_category',
            'view_any_product', 'view_product',
            'view_any_modifier::group', 'view_modifier::group',
        ];
        $readStock = ['view_any_branch::stock', 'view_branch::stock'];
        $readOrder = ['view_any_order', 'view_order'];
        $manageOrder = array_merge($readOrder, ['update_order']);

        $branchManagerScope = array_merge(
            $readBranch,
            ['update_branch'],
            $allBranchStaff,
            $readUser,
            $readCatalog,
            $allStock,
            $allOrder,
        );

        return [
            'hq_admin' => array_merge(
                $allBranch, $allBranchStaff, $allUser,
                $allCategory, $allProduct, $allModifier, $allStock,
                $allOrder,
                ['view_any_role', 'view_role'],
            ),
            'ops_manager' => array_merge(
                $allBranch, $allBranchStaff, $allUser,
                $allCategory, $allProduct, $allModifier, $allStock,
                $allOrder,
            ),
            'mkt_manager' => array_merge(
                $readBranch, $readUser,
                $allCategory, $allProduct,
                $readOrder,
            ),
            'branch_manager' => $branchManagerScope,
            'cashier' => array_merge($readBranch, $readBranchStaff, $readCatalog, $readStock, $manageOrder),
            'barista' => array_merge($readBranch, $readCatalog, $manageOrder),
            'customer' => [],
        ];
    }

    /** @return list<string> */
    protected static function slugsFor(string $resource): array
    {
        return array_map(fn (string $action) => "{$action}_{$resource}", self::ACTIONS);
    }
}
