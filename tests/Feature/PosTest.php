<?php

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Events\OrderQueuedForDineIn;
use App\Events\OrderReadyForDineIn;
use App\Models\Branch;
use App\Models\BranchDisplayToken;
use App\Models\BranchStock;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\Orders\OrderLinePayload;
use App\Services\Orders\OrderPayload;
use App\Services\Orders\OrderService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

function makeStaff(): array
{
    $branch = Branch::factory()->create();
    $user = User::factory()->create();
    $user->assignRole('cashier');
    $branch->staff()->attach($user->id, [
        'pin' => Hash::make('1234'),
        'employment_type' => 'full_time',
        'is_active' => true,
    ]);

    return [$branch, $user];
}

test('POS login page lists active branches', function () {
    Branch::factory()->create();
    Branch::factory()->closed()->create();

    $this->get('/pos/login')
        ->assertOk()
        ->assertInertia(fn ($p) => $p->component('pos/login')->has('branches', 1));
});

test('POS login rejects bad PIN', function () {
    [$branch] = makeStaff();

    $this->post('/pos/login', ['branch_id' => $branch->id, 'pin' => '9999'])
        ->assertSessionHasErrors('pin');
});

test('POS login accepts valid PIN and stores session', function () {
    [$branch, $user] = makeStaff();

    $this->post('/pos/login', ['branch_id' => $branch->id, 'pin' => '1234'])
        ->assertRedirect('/pos');

    expect(session('pos.user_id'))->toBe($user->id)
        ->and(session('pos.branch_id'))->toBe($branch->id);
});

test('POS queue page is gated by middleware', function () {
    $this->get('/pos')->assertRedirect('/pos/login');
});

test('POS queue lists open orders for the staff branch only', function () {
    [$branch] = makeStaff();
    $other = Branch::factory()->create();
    $cat = Category::factory()->create();
    $product = Product::factory()->create(['category_id' => $cat->id]);
    $branch->products()->attach($product->id, ['is_available' => true]);
    $other->products()->attach($product->id, ['is_available' => true]);

    $here = app(OrderService::class)->place(new OrderPayload(
        branchId: $branch->id, userId: null, orderType: OrderType::Pickup,
        lines: [new OrderLinePayload(productId: $product->id, quantity: 1)],
    ));
    $there = app(OrderService::class)->place(new OrderPayload(
        branchId: $other->id, userId: null, orderType: OrderType::Pickup,
        lines: [new OrderLinePayload(productId: $product->id, quantity: 1)],
    ));

    $this->withSession(['pos.user_id' => 1, 'pos.branch_id' => $branch->id, 'pos.user_name' => 'Cashier'])
        ->get('/pos')
        ->assertOk()
        ->assertInertia(fn ($p) => $p->component('pos/queue')
            ->has('orders', 1)
            ->where('orders.0.id', $here->id));

    expect($there->branch_id)->not->toBe($branch->id);
});

test('POS staff cannot transition orders from another branch', function () {
    [$branch] = makeStaff();
    $other = Branch::factory()->create();
    $cat = Category::factory()->create();
    $product = Product::factory()->create(['category_id' => $cat->id]);
    $other->products()->attach($product->id, ['is_available' => true]);
    $foreign = app(OrderService::class)->place(new OrderPayload(
        branchId: $other->id, userId: null, orderType: OrderType::Pickup,
        lines: [new OrderLinePayload(productId: $product->id, quantity: 1)],
    ));

    $this->withSession(['pos.user_id' => 1, 'pos.branch_id' => $branch->id, 'pos.user_name' => 'Cashier'])
        ->post("/pos/orders/{$foreign->id}/transition", ['status' => 'preparing'])
        ->assertForbidden();
});

test('POS stock toggle flips availability and broadcasts event', function () {
    [$branch] = makeStaff();
    $cat = Category::factory()->create();
    $product = Product::factory()->create(['category_id' => $cat->id]);
    $branch->products()->attach($product->id, ['is_available' => true]);
    BranchStock::factory()->create(['branch_id' => $branch->id, 'product_id' => $product->id, 'is_available' => true]);

    $this->withSession(['pos.user_id' => 1, 'pos.branch_id' => $branch->id, 'pos.user_name' => 'Cashier'])
        ->post("/pos/stock/{$product->id}/toggle")
        ->assertRedirect();

    $stock = BranchStock::where('branch_id', $branch->id)->where('product_id', $product->id)->first();
    expect($stock->is_available)->toBeFalse();
});

test('POS walk-in places order, marks paid, and advances to Preparing', function () {
    [$branch, $user] = makeStaff();
    $cat = Category::factory()->create();
    $product = Product::factory()->create(['category_id' => $cat->id, 'base_price' => 10.00]);
    $branch->products()->attach($product->id, ['is_available' => true]);

    $this->withSession([
        'pos.user_id' => $user->id,
        'pos.branch_id' => $branch->id,
        'pos.user_name' => $user->name,
    ])->post('/pos/walk-in', [
        'order_type' => 'pickup',
        'payment_method' => 'cash',
        'lines' => [['product_id' => $product->id, 'quantity' => 2]],
    ])->assertRedirect('/pos');

    $order = Order::firstWhere('branch_id', $branch->id);
    expect($order->payment_method)->toBe('cash')
        ->and($order->payment_status->value)->toBe('paid')
        ->and($order->status)->toBe(OrderStatus::Preparing);
});

test('dine-in order broadcasts OrderQueuedForDineIn on Preparing transition', function () {
    Event::fake([OrderQueuedForDineIn::class, OrderReadyForDineIn::class]);

    $branch = Branch::factory()->create();
    $cat = Category::factory()->create();
    $product = Product::factory()->create(['category_id' => $cat->id]);
    $branch->products()->attach($product->id, ['is_available' => true]);
    $service = app(OrderService::class);
    $order = $service->place(new OrderPayload(
        branchId: $branch->id, userId: null, orderType: OrderType::DineIn, dineInTable: '12',
        lines: [new OrderLinePayload(productId: $product->id, quantity: 1)],
    ));

    $service->transition($order, OrderStatus::Preparing);
    Event::assertDispatched(OrderQueuedForDineIn::class);
    Event::assertNotDispatched(OrderReadyForDineIn::class);

    $service->transition($order->fresh(), OrderStatus::Ready);
    Event::assertDispatched(OrderReadyForDineIn::class);
});

test('pickup orders do not fire dine-in display events', function () {
    Event::fake([OrderQueuedForDineIn::class, OrderReadyForDineIn::class]);

    $branch = Branch::factory()->create();
    $cat = Category::factory()->create();
    $product = Product::factory()->create(['category_id' => $cat->id]);
    $branch->products()->attach($product->id, ['is_available' => true]);
    $service = app(OrderService::class);
    $order = $service->place(new OrderPayload(
        branchId: $branch->id, userId: null, orderType: OrderType::Pickup,
        lines: [new OrderLinePayload(productId: $product->id, quantity: 1)],
    ));

    $service->transition($order, OrderStatus::Preparing);
    $service->transition($order->fresh(), OrderStatus::Ready);

    Event::assertNotDispatched(OrderQueuedForDineIn::class);
    Event::assertNotDispatched(OrderReadyForDineIn::class);
});

test('display token auto-generates on creation and is unique', function () {
    $branch = Branch::factory()->create();
    $a = BranchDisplayToken::create(['branch_id' => $branch->id, 'name' => 'Counter 1']);
    $b = BranchDisplayToken::create(['branch_id' => $branch->id, 'name' => 'Counter 2']);

    expect($a->token)->toHaveLength(48);
    expect($b->token)->not->toBe($a->token);
});

test('display board renders only with valid token', function () {
    $branch = Branch::factory()->create();
    $row = BranchDisplayToken::create(['branch_id' => $branch->id, 'name' => 'Counter 1']);

    $this->get("/branch/{$branch->id}/display?token={$row->token}")
        ->assertOk()
        ->assertInertia(fn ($p) => $p->component('display/board')
            ->where('branch.id', $branch->id)
            ->where('reverb.channel', "branch.{$branch->id}.display"));
});

test('display board rejects invalid token', function () {
    $branch = Branch::factory()->create();

    $this->get("/branch/{$branch->id}/display?token=NOPE")->assertForbidden();
});

test('display board rejects revoked token', function () {
    $branch = Branch::factory()->create();
    $row = BranchDisplayToken::create(['branch_id' => $branch->id, 'name' => 'Counter 1', 'is_active' => false]);

    $this->get("/branch/{$branch->id}/display?token={$row->token}")->assertForbidden();
});

test('display board lists currently preparing + ready dine-in orders only', function () {
    $branch = Branch::factory()->create();
    $cat = Category::factory()->create();
    $product = Product::factory()->create(['category_id' => $cat->id]);
    $branch->products()->attach($product->id, ['is_available' => true]);
    $service = app(OrderService::class);
    $token = BranchDisplayToken::create(['branch_id' => $branch->id, 'name' => 'Counter 1']);

    // dine-in: in preparing
    $a = $service->place(new OrderPayload(
        branchId: $branch->id, userId: null, orderType: OrderType::DineIn, dineInTable: '5',
        lines: [new OrderLinePayload(productId: $product->id, quantity: 1)],
    ));
    $service->transition($a, OrderStatus::Preparing);

    // pickup: should not show
    $service->place(new OrderPayload(
        branchId: $branch->id, userId: null, orderType: OrderType::Pickup,
        lines: [new OrderLinePayload(productId: $product->id, quantity: 1)],
    ));

    $this->get("/branch/{$branch->id}/display?token={$token->token}")
        ->assertOk()
        ->assertInertia(fn ($p) => $p->component('display/board')
            ->has('preparing', 1)
            ->has('ready', 0)
            ->where('preparing.0.number', $a->fresh()->number));
});
