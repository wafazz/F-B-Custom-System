<?php

use App\Models\Branch;
use App\Models\BranchStaff;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('branch can be created with operating hours', function () {
    $branch = Branch::factory()->create([
        'operating_hours' => Branch::defaultOperatingHours(),
    ]);

    expect($branch->operating_hours)->toBeArray()
        ->and($branch->operating_hours)->toHaveKey('monday')
        ->and($branch->operating_hours['monday'])->toMatchArray(['enabled' => true, 'open' => '08:00', 'close' => '22:00']);
});

test('branch has unique code', function () {
    Branch::factory()->create(['code' => 'SC-DUP']);

    $this->expectException(QueryException::class);
    Branch::factory()->create(['code' => 'SC-DUP']);
});

test('branch active scope filters correctly', function () {
    Branch::factory()->create(['status' => 'active', 'accepts_orders' => true]);
    Branch::factory()->closed()->create();
    Branch::factory()->maintenance()->create();

    expect(Branch::active()->count())->toBe(1);
});

test('branch isOpenNow respects operating hours', function () {
    $branch = Branch::factory()->create([
        'operating_hours' => collect(Branch::defaultOperatingHours())
            ->map(fn ($h) => array_merge($h, ['open' => '00:00', 'close' => '23:59']))
            ->all(),
    ]);

    expect($branch->isOpenNow())->toBeTrue();

    $branch->update(['status' => 'closed']);
    expect($branch->fresh()->isOpenNow())->toBeFalse();
});

test('staff can be assigned to multiple branches with pin', function () {
    $user = User::factory()->create();
    [$b1, $b2] = Branch::factory()->count(2)->create();

    $user->branches()->attach($b1->id, [
        'pin' => bcrypt('1234'),
        'employment_type' => 'full_time',
        'hired_at' => now(),
        'is_active' => true,
        'is_primary' => true,
    ]);
    $user->branches()->attach($b2->id, [
        'pin' => bcrypt('5678'),
        'employment_type' => 'part_time',
        'is_active' => true,
    ]);

    expect($user->branches()->count())->toBe(2);
    expect($b1->staff()->count())->toBe(1);
});

test('branch staff pivot enforces unique user-branch pair', function () {
    $user = User::factory()->create();
    $branch = Branch::factory()->create();

    $user->branches()->attach($branch->id, ['employment_type' => 'full_time']);

    $this->expectException(QueryException::class);
    $user->branches()->attach($branch->id, ['employment_type' => 'part_time']);
});

test('roles seeder creates 8 roles', function () {
    expect(Role::count())->toBeGreaterThanOrEqual(8);
    expect(Role::where('name', 'super_admin')->exists())->toBeTrue();
    expect(Role::where('name', 'cashier')->exists())->toBeTrue();
});

test('user with no role cannot access admin panel', function () {
    $user = User::factory()->create();
    expect($user->canAccessPanel(Filament::getPanel('admin')))->toBeFalse();
});

test('user with cashier role can access admin panel', function () {
    $user = User::factory()->create();
    $user->assignRole('cashier');

    expect($user->canAccessPanel(Filament::getPanel('admin')))->toBeTrue();
});

test('referral code is auto-generated and unique', function () {
    $u1 = User::factory()->create();
    $u2 = User::factory()->create();

    expect($u1->referral_code)->not->toBeNull();
    expect($u2->referral_code)->not->toBeNull();
    expect($u1->referral_code)->not->toBe($u2->referral_code);
});

test('hq_admin can update any branch', function () {
    $user = User::factory()->create();
    $user->assignRole('hq_admin');
    $branch = Branch::factory()->create();

    expect($user->can('update', $branch))->toBeTrue();
});

test('branch_manager can only update branches they are assigned to', function () {
    $manager = User::factory()->create();
    $manager->assignRole('branch_manager');

    $own = Branch::factory()->create();
    $other = Branch::factory()->create();

    $manager->branches()->attach($own->id, ['employment_type' => 'full_time']);

    expect($manager->can('update', $own))->toBeTrue();
    expect($manager->can('update', $other))->toBeFalse();
});

test('cashier cannot update branches', function () {
    $user = User::factory()->create();
    $user->assignRole('cashier');
    $branch = Branch::factory()->create();

    expect($user->can('update', $branch))->toBeFalse();
    expect($user->can('view', $branch))->toBeTrue();
});

test('barista has no branch staff access', function () {
    $user = User::factory()->create();
    $user->assignRole('barista');

    expect($user->can('viewAny', BranchStaff::class))->toBeFalse();
});

test('users cannot delete themselves', function () {
    $admin = User::factory()->create();
    $admin->assignRole('hq_admin');

    expect($admin->can('delete', $admin))->toBeFalse();
});

test('permissions seeder creates all 48 resource permissions', function () {
    expect(Permission::count())->toBe(48);
});
