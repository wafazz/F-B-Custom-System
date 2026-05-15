<?php

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PaymentStatus;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\Orders\OrderLinePayload;
use App\Services\Orders\OrderPayload;
use App\Services\Orders\OrderService;
use App\Services\Payments\BillplzGateway;
use App\Services\Payments\PaymentGateway;
use Database\Seeders\LoyaltySeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(LoyaltySeeder::class);
});

function makeBillplzOrder(): Order
{
    $branch = Branch::factory()->create();
    $cat = Category::factory()->create();
    $product = Product::factory()->create(['category_id' => $cat->id, 'base_price' => 12.50]);
    $branch->products()->attach($product->id, ['is_available' => true]);

    return app(OrderService::class)->place(new OrderPayload(
        branchId: $branch->id, userId: null, orderType: OrderType::Pickup,
        lines: [new OrderLinePayload(productId: $product->id, quantity: 1)],
        customerSnapshot: ['name' => 'Aiman', 'email' => 'aiman@example.com', 'phone' => '+60123'],
    ));
}

test('signature computation follows Billplz keys-sorted-pipe-joined scheme', function () {
    $g = new BillplzGateway('apikey', 'col-1', 'sig-secret', sandbox: true);

    $payload = ['amount' => 200, 'paid' => true, 'state' => 'paid', 'id' => 'B1'];
    $sig = $g->computeSignature($payload);

    $expected = hash_hmac('sha256', 'amount200|idB1|paidtrue|statepaid', 'sig-secret');
    expect($sig)->toBe($expected);
});

test('signature ignores x_signature key when computing', function () {
    $g = new BillplzGateway('apikey', 'col-1', 'sig', sandbox: true);

    $a = $g->computeSignature(['id' => 'B1', 'paid' => true]);
    $b = $g->computeSignature(['id' => 'B1', 'paid' => true, 'x_signature' => 'whatever']);

    expect($a)->toBe($b);
});

test('createBill posts to sandbox endpoint and returns the bill url', function () {
    Http::fake([
        '*billplz-sandbox.com/api/v3/bills' => Http::response([
            'id' => 'BILL-XYZ',
            'url' => 'https://www.billplz-sandbox.com/bills/BILL-XYZ',
            'paid' => false,
            'state' => 'due',
        ], 200),
    ]);

    $order = makeBillplzOrder();
    $g = new BillplzGateway('apikey', 'col-1', 'sig', sandbox: true);

    $bill = $g->createBill($order);

    expect($bill->reference)->toBe('BILL-XYZ');
    expect($bill->url)->toContain('billplz-sandbox.com/bills/BILL-XYZ');
    expect($bill->method)->toBe('billplz');

    Http::assertSent(function ($request) {
        $body = $request->body();

        return str_contains((string) $request->url(), 'billplz-sandbox.com/api/v3/bills')
            && str_contains($body, 'collection_id=col-1')
            && str_contains($body, 'amount=1325') // 13.25 = 12.50 + 6% SST → 1325 sen
            && str_contains($body, 'reference_1');
    });
});

test('createBill switches to live endpoint when sandbox=false', function () {
    Http::fake([
        '*billplz.com/api/v3/bills' => Http::response(['id' => 'L1', 'url' => 'https://www.billplz.com/bills/L1'], 200),
    ]);

    $g = new BillplzGateway('apikey', 'col-1', 'sig', sandbox: false);
    $g->createBill(makeBillplzOrder());

    Http::assertSent(fn ($r) => str_contains((string) $r->url(), 'https://www.billplz.com/api/v3/bills'));
});

test('createBill throws when API key is missing', function () {
    $g = new BillplzGateway(null, 'col-1', 'sig');
    expect(fn () => $g->createBill(makeBillplzOrder()))
        ->toThrow(RuntimeException::class, 'not configured');
});

test('createBill throws when neither email nor phone is on the order', function () {
    $branch = Branch::factory()->create();
    $cat = Category::factory()->create();
    $product = Product::factory()->create(['category_id' => $cat->id]);
    $branch->products()->attach($product->id, ['is_available' => true]);

    $order = app(OrderService::class)->place(new OrderPayload(
        branchId: $branch->id, userId: null, orderType: OrderType::Pickup,
        lines: [new OrderLinePayload(productId: $product->id, quantity: 1)],
        customerSnapshot: ['name' => 'No Contact'],
    ));

    $g = new BillplzGateway('apikey', 'col-1', 'sig');
    expect(fn () => $g->createBill($order))
        ->toThrow(RuntimeException::class, 'email or a mobile');
});

test('createBill throws when Billplz returns an error', function () {
    Http::fake(['*' => Http::response(['error' => 'unauthorized'], 401)]);

    $g = new BillplzGateway('badkey', 'col-1', 'sig');
    expect(fn () => $g->createBill(makeBillplzOrder()))
        ->toThrow(RuntimeException::class, 'refused');
});

test('verifyWebhook accepts payload with valid x_signature inside body', function () {
    $g = new BillplzGateway('apikey', 'col-1', 'sig-secret');
    $payload = ['id' => 'BILL-1', 'paid' => 'true', 'state' => 'paid', 'amount' => '1000'];
    $payload['x_signature'] = $g->computeSignature($payload);

    $update = $g->verifyWebhook($payload, null);

    expect($update)->not->toBeNull();
    expect($update->reference)->toBe('BILL-1');
    expect($update->status)->toBe(PaymentStatus::Paid);
});

test('verifyWebhook accepts header signature when body lacks x_signature', function () {
    $g = new BillplzGateway('apikey', 'col-1', 'sig');
    $payload = ['id' => 'BILL-2', 'paid' => 'false', 'state' => 'due'];
    $sig = $g->computeSignature($payload);

    $update = $g->verifyWebhook($payload, $sig);

    expect($update)->not->toBeNull();
    expect($update->status)->toBe(PaymentStatus::Unpaid);
});

test('verifyWebhook rejects tampered signature', function () {
    $g = new BillplzGateway('apikey', 'col-1', 'sig');
    $payload = ['id' => 'BILL-3', 'paid' => 'true', 'x_signature' => 'definitely-wrong'];

    expect($g->verifyWebhook($payload, null))->toBeNull();
});

test('computeSignature flattens nested billplz[] for browser redirects', function () {
    $g = new BillplzGateway('apikey', 'col-1', 'sig-secret');

    $payload = ['billplz' => ['id' => 'B1', 'paid' => 'true', 'paid_at' => '2026-05-15T10:00:00Z']];
    $sig = $g->computeSignature($payload);

    $expected = hash_hmac('sha256', 'billplzidB1|billplzpaidtrue|billplzpaid_at2026-05-15T10:00:00Z', 'sig-secret');
    expect($sig)->toBe($expected);
});

test('verifyWebhook accepts redirect-style nested payload', function () {
    $g = new BillplzGateway('apikey', 'col-1', 'sig');
    $payload = ['billplz' => ['id' => 'BILL-R', 'paid' => 'true', 'paid_at' => '2026-05-15T10:00:00Z']];
    $sig = $g->computeSignature($payload);

    $update = $g->verifyWebhook($payload, $sig);

    expect($update)->not->toBeNull();
    expect($update->reference)->toBe('BILL-R');
    expect($update->status)->toBe(PaymentStatus::Paid);
});

test('ping returns balance in sen on success', function () {
    Http::fake([
        '*billplz-sandbox.com/api/v3/check_balance' => Http::response(['balance' => 12345], 200),
    ]);

    $g = new BillplzGateway('apikey', 'col-1', 'sig', sandbox: true);
    expect($g->ping())->toBe(12345);
});

test('ping throws a useful error on 401', function () {
    Http::fake(['*check_balance' => Http::response(['error' => 'unauthorized'], 401)]);

    $g = new BillplzGateway('badkey', 'col-1', 'sig');
    expect(fn () => $g->ping())->toThrow(RuntimeException::class, 'rejected the API key');
});

test('verifyCollection returns the collection payload', function () {
    Http::fake([
        '*api/v3/collections/col-1' => Http::response(['id' => 'col-1', 'title' => 'Star Coffee', 'status' => 'active'], 200),
    ]);

    $g = new BillplzGateway('apikey', 'col-1', 'sig', sandbox: true);
    $data = $g->verifyCollection();

    expect($data['title'])->toBe('Star Coffee');
    expect($data['status'])->toBe('active');
});

test('verifyCollection throws on 404', function () {
    Http::fake(['*collections/missing' => Http::response([], 404)]);

    $g = new BillplzGateway('apikey', 'missing', 'sig');
    expect(fn () => $g->verifyCollection())->toThrow(RuntimeException::class, 'not found');
});

test('webhook endpoint marks paid order + auto-advances to Preparing', function () {
    config()->set('services.payment.driver', 'billplz');
    config()->set('services.billplz.api_key', 'apikey');
    config()->set('services.billplz.collection_id', 'col-1');
    config()->set('services.billplz.x_signature', 'sig-secret');
    config()->set('services.billplz.sandbox', true);

    Http::fake(['*' => Http::response(['id' => 'BILL-W', 'url' => 'https://x'], 200)]);

    // Place an order while billplz driver is active so payment_reference is set
    $branch = Branch::factory()->create();
    $cat = Category::factory()->create();
    $product = Product::factory()->create(['category_id' => $cat->id]);
    $branch->products()->attach($product->id, ['is_available' => true]);
    $user = User::factory()->create(['email' => 'u@x.com']);

    $createResponse = $this->actingAs($user)->postJson('/api/orders', [
        'branch_id' => $branch->id,
        'order_type' => 'pickup',
        'lines' => [['product_id' => $product->id, 'quantity' => 1]],
    ]);
    $createResponse->assertCreated();
    $orderId = $createResponse->json('order.id');
    $reference = $createResponse->json('payment.reference');
    expect($reference)->toBe('BILL-W');

    // Send Billplz webhook
    $g = app(PaymentGateway::class);
    $payload = ['id' => $reference, 'paid' => 'true', 'state' => 'paid', 'amount' => '1325'];
    $payload['x_signature'] = $g->computeSignature($payload);

    $this->postJson('/api/billplz/webhook', $payload)
        ->assertOk()
        ->assertJson(['ok' => true]);

    $order = Order::find($orderId);
    expect($order->payment_status)->toBe(PaymentStatus::Paid)
        ->and($order->status)->toBe(OrderStatus::Preparing);
});

test('webhook endpoint rejects bad signature', function () {
    config()->set('services.payment.driver', 'billplz');
    config()->set('services.billplz.x_signature', 'sig');

    $this->postJson('/api/billplz/webhook', ['id' => 'X', 'x_signature' => 'wrong'])
        ->assertStatus(422);
});

test('webhook is idempotent — second hit does not re-transition', function () {
    config()->set('services.payment.driver', 'billplz');
    config()->set('services.billplz.api_key', 'apikey');
    config()->set('services.billplz.collection_id', 'col-1');
    config()->set('services.billplz.x_signature', 'sig');
    Http::fake(['*' => Http::response(['id' => 'BILL-IDEM', 'url' => 'https://x'], 200)]);

    $branch = Branch::factory()->create();
    $cat = Category::factory()->create();
    $product = Product::factory()->create(['category_id' => $cat->id]);
    $branch->products()->attach($product->id, ['is_available' => true]);
    $user = User::factory()->create(['email' => 'idem@x.com']);

    $created = $this->actingAs($user)->postJson('/api/orders', [
        'branch_id' => $branch->id, 'order_type' => 'pickup',
        'lines' => [['product_id' => $product->id, 'quantity' => 1]],
    ]);
    $orderId = $created->json('order.id');

    $g = app(PaymentGateway::class);
    $payload = ['id' => 'BILL-IDEM', 'paid' => 'true', 'state' => 'paid'];
    $payload['x_signature'] = $g->computeSignature($payload);

    $this->postJson('/api/billplz/webhook', $payload)->assertOk();
    $this->postJson('/api/billplz/webhook', $payload)->assertOk();

    $order = Order::find($orderId);
    expect($order->payment_status)->toBe(PaymentStatus::Paid)
        ->and($order->status)->toBe(OrderStatus::Preparing); // not advanced past Preparing
});
