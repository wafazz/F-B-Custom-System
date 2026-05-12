<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Filament\Widgets\LiveOrdersWidget;
use App\Filament\Widgets\LowStockWidget;
use App\Filament\Widgets\RecentOrdersWidget;
use App\Filament\Widgets\RevenueByBranchWidget;
use App\Filament\Widgets\RevenueChartWidget;
use App\Filament\Widgets\SalesOverviewWidget;
use App\Filament\Widgets\TopProductsWidget;
use App\Models\Branch;
use App\Models\BranchStock;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('super_admin');
});

test('every dashboard widget mounts without error', function () {
    $branch = Branch::factory()->create();
    foreach (OrderStatus::cases() as $status) {
        Order::factory()->create([
            'branch_id' => $branch->id,
            'status' => $status->value,
            'payment_status' => PaymentStatus::Paid->value,
        ]);
    }

    $this->actingAs($this->admin);

    foreach ([
        LiveOrdersWidget::class,
        SalesOverviewWidget::class,
        RevenueChartWidget::class,
        RevenueByBranchWidget::class,
        TopProductsWidget::class,
        LowStockWidget::class,
        RecentOrdersWidget::class,
    ] as $widget) {
        Livewire::test($widget)->assertSuccessful();
    }
});

test('SalesOverviewWidget computes revenue + delta from real orders', function () {
    $branch = Branch::factory()->create();
    Order::factory()->count(3)->create([
        'branch_id' => $branch->id,
        'total' => 50.00,
        'payment_status' => PaymentStatus::Paid->value,
        'status' => OrderStatus::Completed->value,
        'created_at' => today()->addHours(10),
    ]);

    $this->actingAs($this->admin);

    Livewire::test(SalesOverviewWidget::class)
        ->assertSuccessful()
        ->assertSeeText('RM 150.00')
        ->assertSeeText('3 orders');
});

test('LiveOrdersWidget reflects in-flight order counts', function () {
    $branch = Branch::factory()->create();
    Order::factory()->count(2)->create(['branch_id' => $branch->id, 'status' => OrderStatus::Pending->value]);
    Order::factory()->count(1)->create(['branch_id' => $branch->id, 'status' => OrderStatus::Preparing->value]);

    $this->actingAs($this->admin);

    Livewire::test(LiveOrdersWidget::class)
        ->assertSuccessful()
        ->assertSeeText('Pending')
        ->assertSeeText('Preparing');
});

test('LowStockWidget surfaces stocks at or below threshold', function () {
    $branch = Branch::factory()->create();
    $hot = Product::factory()->create(['name' => 'Hot Bean']);
    $cool = Product::factory()->create(['name' => 'Cool Bean']);

    BranchStock::factory()->create([
        'branch_id' => $branch->id,
        'product_id' => $hot->id,
        'quantity' => 2,
        'low_threshold' => 5,
        'track_quantity' => true,
    ]);
    BranchStock::factory()->create([
        'branch_id' => $branch->id,
        'product_id' => $cool->id,
        'quantity' => 50,
        'low_threshold' => 5,
        'track_quantity' => true,
    ]);

    $this->actingAs($this->admin);

    Livewire::test(LowStockWidget::class)
        ->assertSuccessful()
        ->assertSeeText('Hot Bean')
        ->assertDontSeeText('Cool Bean');
});

test('RevenueByBranchWidget is hidden from cashier role', function () {
    $cashier = User::factory()->create();
    $cashier->assignRole('cashier');
    $this->actingAs($cashier);

    expect(RevenueByBranchWidget::canView())->toBeFalse();
});
