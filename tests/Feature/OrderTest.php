<?php

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PaymentStatus;
use App\Events\OrderStatusChanged;
use App\Models\Branch;
use App\Models\BranchStock;
use App\Models\Category;
use App\Models\ModifierGroup;
use App\Models\ModifierOption;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\Orders\OrderLinePayload;
use App\Services\Orders\OrderPayload;
use App\Services\Orders\OrderService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

function makeMenu(?array $stock = null): array
{
    $branch = Branch::factory()->create();
    $cat = Category::factory()->create();
    $product = Product::factory()->create(['category_id' => $cat->id, 'base_price' => 12.00]);
    $branch->products()->attach($product->id, ['is_available' => true]);
    if ($stock !== null) {
        BranchStock::factory()->create(array_merge([
            'branch_id' => $branch->id,
            'product_id' => $product->id,
        ], $stock));
    }

    return ['branch' => $branch, 'product' => $product];
}

test('order placement creates order, items, and computes SST', function () {
    [$branch, $product] = array_values(makeMenu());
    $service = app(OrderService::class);

    $order = $service->place(new OrderPayload(
        branchId: $branch->id,
        userId: null,
        orderType: OrderType::Pickup,
        lines: [new OrderLinePayload(productId: $product->id, quantity: 2)],
    ));

    expect($order->total)->toEqual('25.44'); // 24.00 + 6% SST
    expect($order->items)->toHaveCount(1);
    expect($order->items->first()->quantity)->toBe(2);
    expect($order->status)->toBe(OrderStatus::Pending);
    expect($order->number)->toStartWith('SC');
});

test('order placement applies branch price override over base price', function () {
    [$branch, $product] = array_values(makeMenu());
    $branch->products()->updateExistingPivot($product->id, ['price_override' => 18.00]);
    $service = app(OrderService::class);

    $order = $service->place(new OrderPayload(
        branchId: $branch->id, userId: null, orderType: OrderType::Pickup,
        lines: [new OrderLinePayload(productId: $product->id, quantity: 1)],
    ));

    expect($order->subtotal)->toEqual('18.00');
});

test('order placement decrements tracked stock', function () {
    [$branch, $product] = array_values(makeMenu(['track_quantity' => true, 'quantity' => 10, 'is_available' => true]));
    $service = app(OrderService::class);

    $service->place(new OrderPayload(
        branchId: $branch->id, userId: null, orderType: OrderType::Pickup,
        lines: [new OrderLinePayload(productId: $product->id, quantity: 3)],
    ));

    $stock = BranchStock::query()->where('branch_id', $branch->id)->where('product_id', $product->id)->first();
    expect($stock->quantity)->toBe(7);
});

test('order placement rejects insufficient stock', function () {
    [$branch, $product] = array_values(makeMenu(['track_quantity' => true, 'quantity' => 1, 'is_available' => true]));
    $service = app(OrderService::class);

    expect(fn () => $service->place(new OrderPayload(
        branchId: $branch->id, userId: null, orderType: OrderType::Pickup,
        lines: [new OrderLinePayload(productId: $product->id, quantity: 5)],
    )))->toThrow(RuntimeException::class, 'Insufficient stock');
});

test('order placement includes modifier prices in subtotal', function () {
    [$branch, $product] = array_values(makeMenu());
    $size = ModifierGroup::factory()->create();
    $large = ModifierOption::factory()->create(['modifier_group_id' => $size->id, 'price_delta' => 3.00]);
    $product->modifierGroups()->attach($size->id, ['sort_order' => 1]);
    $service = app(OrderService::class);

    $order = $service->place(new OrderPayload(
        branchId: $branch->id, userId: null, orderType: OrderType::Pickup,
        lines: [new OrderLinePayload(productId: $product->id, quantity: 1, modifierOptionIds: [$large->id])],
    ));

    expect($order->subtotal)->toEqual('15.00');
    expect($order->items->first()->modifiers)->toHaveCount(1);
});

test('order rejects when branch is not accepting orders', function () {
    [$branch, $product] = array_values(makeMenu());
    $branch->update(['accepts_orders' => false]);
    $service = app(OrderService::class);

    expect(fn () => $service->place(new OrderPayload(
        branchId: $branch->id, userId: null, orderType: OrderType::Pickup,
        lines: [new OrderLinePayload(productId: $product->id, quantity: 1)],
    )))->toThrow(RuntimeException::class, 'not accepting orders');
});

test('order state machine advances Pending → Preparing → Ready → Completed', function () {
    [$branch, $product] = array_values(makeMenu());
    $service = app(OrderService::class);
    $order = $service->place(new OrderPayload(
        branchId: $branch->id, userId: null, orderType: OrderType::Pickup,
        lines: [new OrderLinePayload(productId: $product->id, quantity: 1)],
    ));

    Event::fake([OrderStatusChanged::class]);

    $service->transition($order, OrderStatus::Preparing);
    $service->transition($order->fresh(), OrderStatus::Ready);
    $service->transition($order->fresh(), OrderStatus::Completed);

    expect($order->fresh()->status)->toBe(OrderStatus::Completed);
    expect($order->fresh()->preparing_at)->not->toBeNull();
    expect($order->fresh()->ready_at)->not->toBeNull();
    expect($order->fresh()->completed_at)->not->toBeNull();
    Event::assertDispatched(OrderStatusChanged::class, 3);
});

test('cancellation restores tracked stock', function () {
    [$branch, $product] = array_values(makeMenu(['track_quantity' => true, 'quantity' => 10]));
    $service = app(OrderService::class);

    $order = $service->place(new OrderPayload(
        branchId: $branch->id, userId: null, orderType: OrderType::Pickup,
        lines: [new OrderLinePayload(productId: $product->id, quantity: 3)],
    ));

    $service->transition($order, OrderStatus::Cancelled);

    $stock = BranchStock::query()->where('branch_id', $branch->id)->where('product_id', $product->id)->first();
    expect($stock->quantity)->toBe(10);
});

test('illegal status transitions are rejected', function () {
    [$branch, $product] = array_values(makeMenu());
    $service = app(OrderService::class);
    $order = $service->place(new OrderPayload(
        branchId: $branch->id, userId: null, orderType: OrderType::Pickup,
        lines: [new OrderLinePayload(productId: $product->id, quantity: 1)],
    ));

    expect(fn () => $service->transition($order, OrderStatus::Completed))
        ->toThrow(RuntimeException::class, 'Cannot transition pending');
});

test('POST /api/orders creates an order and returns payment stub URL', function () {
    [$branch, $product] = array_values(makeMenu());
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/orders', [
        'branch_id' => $branch->id,
        'order_type' => 'pickup',
        'lines' => [['product_id' => $product->id, 'quantity' => 1]],
    ]);

    $response->assertCreated()
        ->assertJsonPath('order.status', 'pending')
        ->assertJsonPath('payment.method', 'stub');
    expect(Order::count())->toBe(1);
});

test('POST /api/orders rolls back order when gateway createBill fails', function () {
    [$branch, $product] = array_values(makeMenu());
    $user = User::factory()->create();

    $boom = new class implements \App\Services\Payments\PaymentGateway
    {
        public function createBill(\App\Models\Order $order): \App\Services\Payments\PaymentBill
        {
            throw new RuntimeException('Billplz is not configured: missing API key or collection ID.');
        }

        public function verifyWebhook(array $payload, ?string $signature): ?\App\Services\Payments\PaymentBillUpdate
        {
            return null;
        }
    };
    $this->app->instance(\App\Services\Payments\PaymentGateway::class, $boom);

    $response = $this->actingAs($user)->postJson('/api/orders', [
        'branch_id' => $branch->id,
        'order_type' => 'pickup',
        'lines' => [['product_id' => $product->id, 'quantity' => 1]],
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('message', 'Payment gateway error: Billplz is not configured: missing API key or collection ID.');

    expect(Order::count())->toBe(1);
    expect(Order::first()->status->value)->toBe('cancelled');
});

test('POST /api/orders is rejected for guests', function () {
    [$branch, $product] = array_values(makeMenu());

    $this->postJson('/api/orders', [
        'branch_id' => $branch->id,
        'order_type' => 'pickup',
        'lines' => [['product_id' => $product->id, 'quantity' => 1]],
    ])->assertUnauthorized();
});

test('order number format is unique per branch per day', function () {
    [$branch, $product] = array_values(makeMenu());
    $service = app(OrderService::class);

    $a = $service->place(new OrderPayload(
        branchId: $branch->id, userId: null, orderType: OrderType::Pickup,
        lines: [new OrderLinePayload(productId: $product->id, quantity: 1)],
    ));
    $b = $service->place(new OrderPayload(
        branchId: $branch->id, userId: null, orderType: OrderType::Pickup,
        lines: [new OrderLinePayload(productId: $product->id, quantity: 1)],
    ));

    expect($a->number)->not->toBe($b->number);
    expect($a->number)->toMatch('/^[A-Z0-9]{2,6}-\d{6}-\d{4}$/');
});

test('simulate-paid stub callback marks order paid and advances to Preparing', function () {
    [$branch, $product] = array_values(makeMenu());
    $user = User::factory()->create();

    $createResponse = $this->actingAs($user)->postJson('/api/orders', [
        'branch_id' => $branch->id,
        'order_type' => 'pickup',
        'lines' => [['product_id' => $product->id, 'quantity' => 1]],
    ]);
    $orderId = $createResponse->json('order.id');
    $reference = $createResponse->json('payment.reference');

    $this->actingAs($user)->get("/orders/{$orderId}/simulate-paid?reference={$reference}")
        ->assertRedirect("/orders/{$orderId}");

    $order = Order::find($orderId);
    expect($order->payment_status)->toBe(PaymentStatus::Paid)
        ->and($order->status)->toBe(OrderStatus::Preparing)
        ->and($order->paid_at)->not->toBeNull();
});

test('hq_admin can manage all orders', function () {
    $user = User::factory()->create();
    $user->assignRole('hq_admin');
    expect($user->can('view_any_order'))->toBeTrue();
    expect($user->can('update_order'))->toBeTrue();
});

test('cashier can update orders at their branch', function () {
    $user = User::factory()->create();
    $user->assignRole('cashier');
    expect($user->can('update_order'))->toBeTrue();
});

test('mkt_manager has read-only access to orders', function () {
    $user = User::factory()->create();
    $user->assignRole('mkt_manager');
    expect($user->can('view_any_order'))->toBeTrue();
    expect($user->can('update_order'))->toBeFalse();
});
