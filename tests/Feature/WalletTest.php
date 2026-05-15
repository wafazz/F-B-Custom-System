<?php

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PaymentStatus;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTopup;
use App\Models\WalletTransaction;
use App\Services\Orders\OrderLinePayload;
use App\Services\Orders\OrderPayload;
use App\Services\Orders\OrderService;
use App\Services\Payments\BillplzGateway;
use App\Services\Payments\PaymentBill;
use App\Services\Wallet\WalletService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

function setupMenuForWallet(): array
{
    $branch = Branch::factory()->create();
    $cat = Category::factory()->create();
    $product = Product::factory()->create(['category_id' => $cat->id, 'base_price' => 10.00]);
    $branch->products()->attach($product->id, ['is_available' => true]);

    return ['branch' => $branch, 'product' => $product];
}

test('credit increases balance and records a transaction', function () {
    $user = User::factory()->create();
    $service = app(WalletService::class);

    $service->credit($user->id, 50.0, 'topup', null, 'Test top-up');

    expect($service->balance($user->id))->toBe(50.0);
    $tx = WalletTransaction::where('user_id', $user->id)->first();
    expect((float) $tx->amount)->toBe(50.0)
        ->and((float) $tx->balance_after)->toBe(50.0)
        ->and($tx->type)->toBe('topup');
});

test('debit decreases balance and records negative transaction', function () {
    $user = User::factory()->create();
    $service = app(WalletService::class);
    $service->credit($user->id, 100.0);

    $service->debit($user->id, 30.0, 'spend');

    expect($service->balance($user->id))->toBe(70.0);
    $tx = WalletTransaction::where('user_id', $user->id)->where('type', 'spend')->first();
    expect((float) $tx->amount)->toBe(-30.0);
});

test('debit throws when insufficient balance', function () {
    $user = User::factory()->create();
    $service = app(WalletService::class);
    $service->credit($user->id, 10.0);

    expect(fn () => $service->debit($user->id, 50.0))
        ->toThrow(RuntimeException::class, 'Insufficient wallet balance');

    expect($service->balance($user->id))->toBe(10.0);
});

test('applyTopupPaid is idempotent', function () {
    $user = User::factory()->create();
    $topup = WalletTopup::create([
        'user_id' => $user->id,
        'amount' => 25.00,
        'status' => 'pending',
        'billplz_reference' => 'BP-test-1',
    ]);
    $service = app(WalletService::class);

    $service->applyTopupPaid($topup);
    $service->applyTopupPaid($topup->fresh());

    expect($service->balance($user->id))->toBe(25.0);
    expect(WalletTransaction::where('user_id', $user->id)->count())->toBe(1);
});

test('order placement debits wallet and marks order paid', function () {
    [$branch, $product] = array_values(setupMenuForWallet());
    $user = User::factory()->create();
    app(WalletService::class)->credit($user->id, 100.0);

    $order = app(OrderService::class)->place(new OrderPayload(
        branchId: $branch->id,
        userId: $user->id,
        orderType: OrderType::Pickup,
        lines: [new OrderLinePayload(productId: $product->id, quantity: 1)],
        paymentMethod: 'wallet',
    ));

    expect($order->payment_status)->toBe(PaymentStatus::Paid)
        ->and($order->paid_at)->not->toBeNull();

    $remaining = app(WalletService::class)->balance($user->id);
    expect($remaining)->toBe(100.0 - (float) $order->total);
});

test('order placement rejects wallet payment when balance is insufficient', function () {
    [$branch, $product] = array_values(setupMenuForWallet());
    $user = User::factory()->create();
    app(WalletService::class)->credit($user->id, 1.0);

    expect(fn () => app(OrderService::class)->place(new OrderPayload(
        branchId: $branch->id,
        userId: $user->id,
        orderType: OrderType::Pickup,
        lines: [new OrderLinePayload(productId: $product->id, quantity: 1)],
        paymentMethod: 'wallet',
    )))->toThrow(RuntimeException::class, 'Wallet balance is insufficient');

    expect(Order::count())->toBe(0);
    expect(app(WalletService::class)->balance($user->id))->toBe(1.0);
});

test('cancelling a wallet-paid order refunds the wallet', function () {
    [$branch, $product] = array_values(setupMenuForWallet());
    $user = User::factory()->create();
    $service = app(WalletService::class);
    $service->credit($user->id, 100.0);

    $order = app(OrderService::class)->place(new OrderPayload(
        branchId: $branch->id,
        userId: $user->id,
        orderType: OrderType::Pickup,
        lines: [new OrderLinePayload(productId: $product->id, quantity: 1)],
        paymentMethod: 'wallet',
    ));

    $balanceAfterPay = $service->balance($user->id);

    app(OrderService::class)->transition($order->fresh(), OrderStatus::Cancelled);

    expect($service->balance($user->id))->toBe($balanceAfterPay + (float) $order->total);
    $refundTx = WalletTransaction::where('user_id', $user->id)->where('type', 'refund')->first();
    expect($refundTx)->not->toBeNull();
});

test('POST /api/orders pays from wallet when payment_method=wallet', function () {
    [$branch, $product] = array_values(setupMenuForWallet());
    $user = User::factory()->create();
    app(WalletService::class)->credit($user->id, 50.0);

    $response = $this->actingAs($user)->postJson('/api/orders', [
        'branch_id' => $branch->id,
        'order_type' => 'pickup',
        'payment_method' => 'wallet',
        'lines' => [['product_id' => $product->id, 'quantity' => 1]],
    ]);

    $response->assertCreated()
        ->assertJsonPath('payment.method', 'wallet')
        ->assertJsonPath('order.payment_status', 'paid');
});

test('GET /wallet renders Inertia page with balance and history', function () {
    $user = User::factory()->create();
    app(WalletService::class)->credit($user->id, 30.0, 'topup', null, 'Seed top-up');

    $this->actingAs($user)->get('/wallet')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('storefront/wallet')
            ->where('balance', 30)
            ->has('history', 1));
});

test('POST /wallet/topup creates a pending top-up and redirects to gateway', function () {
    $user = User::factory()->create();

    $fake = new class extends BillplzGateway
    {
        public function __construct() {}

        public function createTopupBill(WalletTopup $topup, User $user): PaymentBill
        {
            return new PaymentBill(reference: 'BP-fake-'.$topup->id, url: 'https://billplz.test/bills/abc', method: 'billplz');
        }
    };
    $this->app->instance(BillplzGateway::class, $fake);

    $response = $this->actingAs($user)
        ->withHeader('X-Inertia', 'true')
        ->post('/wallet/topup', ['amount' => 20]);

    expect(WalletTopup::where('user_id', $user->id)->count())->toBe(1);
    $topup = WalletTopup::where('user_id', $user->id)->first();
    expect($topup->status)->toBe('pending')
        ->and($topup->billplz_reference)->toStartWith('BP-fake-');

    // Inertia external redirect: 409 + X-Inertia-Location header.
    $response->assertStatus(409);
    $response->assertHeader('X-Inertia-Location', 'https://billplz.test/bills/abc');
});

test('wallet schema rows seed correctly', function () {
    $user = User::factory()->create();
    Wallet::create(['user_id' => $user->id, 'balance' => 12.34]);

    expect((float) Wallet::where('user_id', $user->id)->value('balance'))->toBe(12.34);
});

test('Billplz return route credits wallet when signed redirect arrives first', function () {
    config()->set('services.billplz.x_signature', 'sig-secret');

    $user = User::factory()->create();
    $topup = WalletTopup::create([
        'user_id' => $user->id,
        'amount' => 25.00,
        'status' => 'pending',
        'billplz_reference' => 'BILL-RET-1',
    ]);

    $g = new BillplzGateway('apikey', 'col-1', 'sig-secret');
    $payload = ['billplz' => ['id' => 'BILL-RET-1', 'paid' => 'true', 'paid_at' => '2026-05-15T10:00:00Z']];
    $payload['x_signature'] = $g->computeSignature($payload);

    $this->app->instance(BillplzGateway::class, $g);

    $this->actingAs($user)
        ->get(route('wallet.topup-return', ['topup' => $topup, ...$payload]))
        ->assertRedirect(route('wallet'));

    expect($topup->fresh()->status)->toBe('paid');
    expect(app(WalletService::class)->balance($user->id))->toBe(25.0);
});

test('Billplz return route rejects mismatched user', function () {
    config()->set('services.billplz.x_signature', 'sig');

    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $topup = WalletTopup::create([
        'user_id' => $owner->id,
        'amount' => 10.00,
        'status' => 'pending',
        'billplz_reference' => 'BILL-RET-2',
    ]);

    $this->actingAs($intruder)
        ->get(route('wallet.topup-return', ['topup' => $topup]))
        ->assertForbidden();

    expect($topup->fresh()->status)->toBe('pending');
});

test('Billplz return route ignores tampered redirect signature', function () {
    config()->set('services.billplz.x_signature', 'sig-secret');

    $user = User::factory()->create();
    $topup = WalletTopup::create([
        'user_id' => $user->id,
        'amount' => 10.00,
        'status' => 'pending',
        'billplz_reference' => 'BILL-RET-3',
    ]);

    $this->actingAs($user)
        ->get(route('wallet.topup-return', [
            'topup' => $topup,
            'billplz' => ['id' => 'BILL-RET-3', 'paid' => 'true'],
            'x_signature' => 'definitely-not-the-right-signature',
        ]))
        ->assertRedirect(route('wallet'));

    expect($topup->fresh()->status)->toBe('pending');
    expect(app(WalletService::class)->balance($user->id))->toBe(0.0);
});

test('webhook endpoint credits wallet for matching top-up', function () {
    config()->set('services.payment.driver', 'billplz');
    config()->set('services.billplz.api_key', 'apikey');
    config()->set('services.billplz.collection_id', 'col-1');
    config()->set('services.billplz.x_signature', 'sig-secret');

    $user = User::factory()->create();
    $topup = WalletTopup::create([
        'user_id' => $user->id,
        'amount' => 50.00,
        'status' => 'pending',
        'billplz_reference' => 'BILL-WH-TOPUP',
    ]);

    $g = app(BillplzGateway::class);
    $payload = ['id' => 'BILL-WH-TOPUP', 'paid' => 'true', 'state' => 'paid', 'amount' => '5000'];
    $payload['x_signature'] = $g->computeSignature($payload);

    $this->postJson('/api/billplz/webhook', $payload)
        ->assertOk()
        ->assertJson(['ok' => true, 'kind' => 'topup']);

    expect($topup->fresh()->status)->toBe('paid');
    expect(app(WalletService::class)->balance($user->id))->toBe(50.0);
});
