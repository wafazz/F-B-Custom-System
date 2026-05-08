<?php

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Models\Branch;
use App\Models\Category;
use App\Models\CustomerTier;
use App\Models\Order;
use App\Models\PointTransaction;
use App\Models\Product;
use App\Models\User;
use App\Models\Voucher;
use App\Models\VoucherRedemption;
use App\Services\Loyalty\LoyaltyService;
use App\Services\Orders\OrderLinePayload;
use App\Services\Orders\OrderPayload;
use App\Services\Orders\OrderService;
use App\Services\Vouchers\VoucherService;
use Database\Seeders\LoyaltySeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(LoyaltySeeder::class);
});

function buildBranchProduct(float $basePrice = 12.00): array
{
    $branch = Branch::factory()->create();
    $cat = Category::factory()->create();
    $product = Product::factory()->create(['category_id' => $cat->id, 'base_price' => $basePrice]);
    $branch->products()->attach($product->id, ['is_available' => true]);

    return [$branch, $product];
}

test('order completion earns 1 point per RM subtotal', function () {
    [$branch, $product] = buildBranchProduct(20.00);
    $user = User::factory()->create();

    $order = app(OrderService::class)->place(new OrderPayload(
        branchId: $branch->id, userId: $user->id, orderType: OrderType::Pickup,
        lines: [new OrderLinePayload(productId: $product->id, quantity: 1)],
    ));

    app(OrderService::class)->transition($order, OrderStatus::Preparing);
    app(OrderService::class)->transition($order->fresh(), OrderStatus::Ready);
    app(OrderService::class)->transition($order->fresh(), OrderStatus::Completed);

    $balance = app(LoyaltyService::class)->balance($user->id);
    expect($balance)->toBe(20);
});

test('points balance after redemption goes down accordingly', function () {
    [$branch, $product] = buildBranchProduct(50.00);
    $user = User::factory()->create();

    // seed 500 points by completing one order
    $first = app(OrderService::class)->place(new OrderPayload(
        branchId: $branch->id, userId: $user->id, orderType: OrderType::Pickup,
        lines: [new OrderLinePayload(productId: $product->id, quantity: 10)],
    ));
    foreach ([OrderStatus::Preparing, OrderStatus::Ready, OrderStatus::Completed] as $s) {
        app(OrderService::class)->transition($first->fresh(), $s);
    }
    expect(app(LoyaltyService::class)->balance($user->id))->toBe(500);

    // redeem 200 points (= RM 2.00 discount) on next order
    $second = app(OrderService::class)->place(new OrderPayload(
        branchId: $branch->id, userId: $user->id, orderType: OrderType::Pickup,
        lines: [new OrderLinePayload(productId: $product->id, quantity: 1)],
        loyaltyRedeemPoints: 200,
    ));

    expect((float) $second->discount_amount)->toEqual(2.00);
    expect(app(LoyaltyService::class)->balance($user->id))->toBe(300);
});

test('redeeming more points than balance throws', function () {
    [$branch, $product] = buildBranchProduct();
    $user = User::factory()->create();

    expect(fn () => app(OrderService::class)->place(new OrderPayload(
        branchId: $branch->id, userId: $user->id, orderType: OrderType::Pickup,
        lines: [new OrderLinePayload(productId: $product->id, quantity: 1)],
        loyaltyRedeemPoints: 500,
    )))->toThrow(RuntimeException::class, 'Insufficient loyalty points');
});

test('percentage voucher applies and records redemption', function () {
    [$branch, $product] = buildBranchProduct(20.00);
    $voucher = Voucher::factory()->create(['code' => 'SAVE10', 'discount_value' => 10]);

    $order = app(OrderService::class)->place(new OrderPayload(
        branchId: $branch->id, userId: null, orderType: OrderType::Pickup,
        lines: [new OrderLinePayload(productId: $product->id, quantity: 1)],
        voucherCode: 'SAVE10',
    ));

    expect((float) $order->discount_amount)->toEqual(2.00);
    expect((float) $order->subtotal)->toEqual(20.00);
    expect(VoucherRedemption::count())->toBe(1);
    expect($voucher->fresh()->used_count)->toBe(1);
});

test('fixed voucher applies a flat discount and caps at subtotal', function () {
    [$branch, $product] = buildBranchProduct(5.00);
    Voucher::factory()->fixed(20.00)->create(['code' => 'STAR20']);

    $order = app(OrderService::class)->place(new OrderPayload(
        branchId: $branch->id, userId: null, orderType: OrderType::Pickup,
        lines: [new OrderLinePayload(productId: $product->id, quantity: 1)],
        voucherCode: 'STAR20',
    ));

    expect((float) $order->discount_amount)->toEqual(5.00);
});

test('voucher branch scope blocks other branches', function () {
    [$branch, $product] = buildBranchProduct();
    $other = Branch::factory()->create();
    Voucher::factory()->create(['code' => 'KLCC10', 'branch_ids' => [$other->id]]);

    expect(fn () => app(OrderService::class)->place(new OrderPayload(
        branchId: $branch->id, userId: null, orderType: OrderType::Pickup,
        lines: [new OrderLinePayload(productId: $product->id, quantity: 1)],
        voucherCode: 'KLCC10',
    )))->toThrow(RuntimeException::class, 'not valid for this branch');
});

test('voucher rejected when min subtotal not met', function () {
    [$branch, $product] = buildBranchProduct(5.00);
    Voucher::factory()->create(['code' => 'BIG30', 'discount_value' => 30, 'min_subtotal' => 50]);

    expect(fn () => app(OrderService::class)->place(new OrderPayload(
        branchId: $branch->id, userId: null, orderType: OrderType::Pickup,
        lines: [new OrderLinePayload(productId: $product->id, quantity: 1)],
        voucherCode: 'BIG30',
    )))->toThrow(RuntimeException::class, 'Minimum subtotal');
});

test('voucher per-user usage cap is enforced', function () {
    [$branch, $product] = buildBranchProduct();
    Voucher::factory()->create(['code' => 'ONCE', 'max_uses_per_user' => 1]);
    $user = User::factory()->create();

    app(OrderService::class)->place(new OrderPayload(
        branchId: $branch->id, userId: $user->id, orderType: OrderType::Pickup,
        lines: [new OrderLinePayload(productId: $product->id, quantity: 1)],
        voucherCode: 'ONCE',
    ));

    expect(fn () => app(OrderService::class)->place(new OrderPayload(
        branchId: $branch->id, userId: $user->id, orderType: OrderType::Pickup,
        lines: [new OrderLinePayload(productId: $product->id, quantity: 1)],
        voucherCode: 'ONCE',
    )))->toThrow(RuntimeException::class, 'already used this voucher');
});

test('voucher max-uses cap stops further redemptions', function () {
    [$branch, $product] = buildBranchProduct();
    Voucher::factory()->create(['code' => 'LIMITED', 'max_uses' => 1, 'used_count' => 1]);

    expect(fn () => app(OrderService::class)->place(new OrderPayload(
        branchId: $branch->id, userId: null, orderType: OrderType::Pickup,
        lines: [new OrderLinePayload(productId: $product->id, quantity: 1)],
        voucherCode: 'LIMITED',
    )))->toThrow(RuntimeException::class, 'usage cap');
});

test('SST is recomputed proportionally on the discounted subtotal', function () {
    [$branch, $product] = buildBranchProduct(100.00);
    Voucher::factory()->create(['code' => 'HALF', 'discount_value' => 50]);

    $order = app(OrderService::class)->place(new OrderPayload(
        branchId: $branch->id, userId: null, orderType: OrderType::Pickup,
        lines: [new OrderLinePayload(productId: $product->id, quantity: 1)],
        voucherCode: 'HALF',
    ));

    // subtotal 100 - 50 discount = 50; SST 6% on 50 = 3.00; total = 53.00
    expect((float) $order->discount_amount)->toEqual(50.00)
        ->and((float) $order->sst_amount)->toEqual(3.00)
        ->and((float) $order->total)->toEqual(53.00);
});

test('completion auto-upgrades tier when lifetime spend crosses threshold', function () {
    // Bronze 0, Silver 200, Gold 500, Platinum 1500 — see LoyaltySeeder
    [$branch, $product] = buildBranchProduct(250.00);
    $user = User::factory()->create();

    $order = app(OrderService::class)->place(new OrderPayload(
        branchId: $branch->id, userId: $user->id, orderType: OrderType::Pickup,
        lines: [new OrderLinePayload(productId: $product->id, quantity: 1)],
    ));
    foreach ([OrderStatus::Preparing, OrderStatus::Ready, OrderStatus::Completed] as $s) {
        app(OrderService::class)->transition($order->fresh(), $s);
    }

    $tier = CustomerTier::with('tier')->where('user_id', $user->id)->first();
    expect($tier->tier->name)->toBe('Silver');
    expect((float) $tier->lifetime_spend)->toEqual(250.00);
});

test('refund of completed order reverses earned points', function () {
    [$branch, $product] = buildBranchProduct(30.00);
    $user = User::factory()->create();

    $order = app(OrderService::class)->place(new OrderPayload(
        branchId: $branch->id, userId: $user->id, orderType: OrderType::Pickup,
        lines: [new OrderLinePayload(productId: $product->id, quantity: 1)],
    ));
    foreach ([OrderStatus::Preparing, OrderStatus::Ready, OrderStatus::Completed] as $s) {
        app(OrderService::class)->transition($order->fresh(), $s);
    }
    expect(app(LoyaltyService::class)->balance($user->id))->toBe(30);

    app(OrderService::class)->transition($order->fresh(), OrderStatus::Refunded);
    expect(app(LoyaltyService::class)->balance($user->id))->toBe(0);
});

test('GET /loyalty page renders for auth users', function () {
    $user = User::factory()->create();
    PointTransaction::create([
        'user_id' => $user->id,
        'type' => 'earn', 'points' => 50, 'balance_after' => 50, 'reason' => 'test',
    ]);

    $this->actingAs($user)
        ->get('/loyalty')
        ->assertOk()
        ->assertInertia(fn ($p) => $p->component('storefront/loyalty')
            ->where('balance', 50)
            ->has('history', 1));
});

test('VoucherService.find normalises code to uppercase', function () {
    Voucher::factory()->create(['code' => 'SAVE10']);

    $voucher = app(VoucherService::class)->find('save10', 1);
    expect($voucher->code)->toBe('SAVE10');
});
