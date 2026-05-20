<?php

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PaymentStatus;
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

/** Mark an order paid so the loyalty + tier upgrade hooks fire on Completed. */
function markPaid(Order $order): Order
{
    $order->forceFill([
        'payment_status' => PaymentStatus::Paid,
        'paid_at' => now(),
    ])->save();

    return $order->fresh() ?? $order;
}

test('order completion earns 1 point per RM subtotal', function () {
    [$branch, $product] = buildBranchProduct(20.00);
    $user = User::factory()->create();

    $order = app(OrderService::class)->place(new OrderPayload(
        branchId: $branch->id, userId: $user->id, orderType: OrderType::Pickup,
        lines: [new OrderLinePayload(productId: $product->id, quantity: 1)],
    ));
    markPaid($order);

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
    markPaid($first);
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
    markPaid($order);
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
    markPaid($order);
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

// ----- Buy X Get Y free -----

test('buy x get y: picker-driven bundle discounts exactly the marked-free lines', function () {
    [, $product] = buildBranchProduct(10.00);
    $voucher = Voucher::factory()->create([
        'discount_type' => 'buy_x_get_y',
        'discount_value' => 0,
        'product_ids' => [$product->id],
        'bxgy_buy_qty' => 2,
        'bxgy_free_qty' => 1,
        'bxgy_free_product_ids' => null,
        'bxgy_free_combo_ids' => null,
    ]);

    $items = [
        ['product_id' => $product->id, 'combo_id' => null, 'line_total' => 20.0, 'quantity' => 2, 'unit_price' => 10.0,
            'voucher_code' => $voucher->code, 'voucher_role' => 'paid'],
        ['product_id' => $product->id, 'combo_id' => null, 'line_total' => 10.0, 'quantity' => 1, 'unit_price' => 10.0,
            'voucher_code' => $voucher->code, 'voucher_role' => 'free'],
    ];

    $discount = app(VoucherService::class)->discountFor($voucher, 30.0, $items);

    expect($discount)->toBe(10.0);
});

test('buy x get y: rejects a tampered bundle that doesnt meet the buy quantity', function () {
    [, $product] = buildBranchProduct(8.00);
    $voucher = Voucher::factory()->create([
        'discount_type' => 'buy_x_get_y',
        'discount_value' => 0,
        'product_ids' => [$product->id],
        'bxgy_buy_qty' => 3,
        'bxgy_free_qty' => 1,
        'bxgy_free_product_ids' => null,
        'bxgy_free_combo_ids' => null,
    ]);

    // Bundle only has 1 paid item but voucher requires 3.
    $items = [
        ['product_id' => $product->id, 'combo_id' => null, 'line_total' => 8.0, 'quantity' => 1, 'unit_price' => 8.0,
            'voucher_code' => $voucher->code, 'voucher_role' => 'paid'],
        ['product_id' => $product->id, 'combo_id' => null, 'line_total' => 8.0, 'quantity' => 1, 'unit_price' => 8.0,
            'voucher_code' => $voucher->code, 'voucher_role' => 'free'],
    ];

    expect(fn () => app(VoucherService::class)->discountFor($voucher, 16.0, $items))
        ->toThrow(RuntimeException::class, 'exactly');
});

test('buy x get y: applying without a picker bundle redirects via error', function () {
    [, $product] = buildBranchProduct(10.00);
    $voucher = Voucher::factory()->create([
        'discount_type' => 'buy_x_get_y',
        'discount_value' => 0,
        'product_ids' => [$product->id],
        'bxgy_buy_qty' => 2,
        'bxgy_free_qty' => 1,
        'bxgy_free_product_ids' => null,
        'bxgy_free_combo_ids' => null,
    ]);

    // No voucher_code on the line — customer hasn't gone through the picker.
    $items = [
        ['product_id' => $product->id, 'combo_id' => null, 'line_total' => 30.0, 'quantity' => 3, 'unit_price' => 10.0],
    ];

    expect(fn () => app(VoucherService::class)->discountFor($voucher, 30.0, $items))
        ->toThrow(RuntimeException::class, 'promo page');
});

test('buy x get y: redemption persists discount + role through OrderService::place', function () {
    [$branch, $product] = buildBranchProduct(10.00);
    $user = User::factory()->create();
    $user->assignRole('customer');

    $voucher = Voucher::factory()->create([
        'discount_type' => 'buy_x_get_y',
        'discount_value' => 0,
        'product_ids' => [$product->id],
        'bxgy_buy_qty' => 2,
        'bxgy_free_qty' => 1,
        'bxgy_free_product_ids' => null,
        'bxgy_free_combo_ids' => null,
    ]);

    $payload = new OrderPayload(
        branchId: $branch->id,
        userId: $user->id,
        orderType: OrderType::Pickup,
        lines: [
            new OrderLinePayload(
                productId: $product->id,
                quantity: 2,
                modifierOptionIds: [],
                voucherCode: $voucher->code,
                voucherRole: 'paid',
            ),
            new OrderLinePayload(
                productId: $product->id,
                quantity: 1,
                modifierOptionIds: [],
                voucherCode: $voucher->code,
                voucherRole: 'free',
            ),
        ],
        paymentMethod: 'gateway',
        voucherCode: $voucher->code,
    );

    $order = app(OrderService::class)->place($payload);

    // 3x RM10 = RM30 subtotal, the 1 marked-free unit costs RM10 → discount RM10.
    expect((float) $order->discount_amount)->toBe(10.0);
    expect((float) $order->subtotal)->toBe(30.0);
    // Free line persisted with the role tag.
    expect(
        $order->items()->where('voucher_role', 'free')->where('voucher_code', $voucher->code)->count(),
    )->toBe(1);
    expect(
        $order->items()->where('voucher_role', 'paid')->where('voucher_code', $voucher->code)->count(),
    )->toBe(1);
    expect(VoucherRedemption::where('voucher_id', $voucher->id)->count())->toBe(1);
});

// ----- Daily time window -----

test('daily window: in-window voucher is found', function () {
    $branch = Branch::factory()->create();
    // Set window covering "now" — give a 12 hour buffer either side.
    $start = now()->copy()->subHours(2)->format('H:i:s');
    $end = now()->copy()->addHours(2)->format('H:i:s');
    Voucher::factory()->create([
        'code' => 'HAPPYHOUR',
        'valid_from_time' => $start,
        'valid_until_time' => $end,
    ]);

    $voucher = app(VoucherService::class)->find('HAPPYHOUR', $branch->id);

    expect($voucher->code)->toBe('HAPPYHOUR');
});

test('daily window: out-of-window voucher is rejected', function () {
    $branch = Branch::factory()->create();
    // Set window for a 30-min slot that doesn't include "now".
    $start = now()->copy()->subHours(5)->format('H:i:s');
    $end = now()->copy()->subHours(4)->subMinutes(30)->format('H:i:s');
    Voucher::factory()->create([
        'code' => 'BREAKFAST',
        'valid_from_time' => $start,
        'valid_until_time' => $end,
    ]);

    expect(fn () => app(VoucherService::class)->find('BREAKFAST', $branch->id))
        ->toThrow(RuntimeException::class, 'can only be used between');
});

test('daily window: null bounds mean no restriction', function () {
    $branch = Branch::factory()->create();
    Voucher::factory()->create([
        'code' => 'ANYTIME',
        'valid_from_time' => null,
        'valid_until_time' => null,
    ]);

    $voucher = app(VoucherService::class)->find('ANYTIME', $branch->id);

    expect($voucher->code)->toBe('ANYTIME');
});

test('daily window: wrap-around past midnight stays open during late hours', function () {
    // Build a Voucher row without persisting, so we can directly drive the
    // static helper without touching the database clock.
    $voucher = new Voucher();
    $voucher->valid_from_time = '22:00:00';
    $voucher->valid_until_time = '02:00:00';

    // Freeze "now" inside the wrap window.
    \Illuminate\Support\Carbon::setTestNow(\Illuminate\Support\Carbon::create(2026, 5, 20, 23, 30));
    expect(VoucherService::isWithinDailyWindow($voucher))->toBeTrue();

    // And just before the window opens.
    \Illuminate\Support\Carbon::setTestNow(\Illuminate\Support\Carbon::create(2026, 5, 20, 21, 30));
    expect(VoucherService::isWithinDailyWindow($voucher))->toBeFalse();

    \Illuminate\Support\Carbon::setTestNow(null);
});
