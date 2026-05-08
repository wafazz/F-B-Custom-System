<?php

use App\Enums\OrderType;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\PushSubscription;
use App\Models\User;
use App\Services\Orders\OrderLinePayload;
use App\Services\Orders\OrderPayload;
use App\Services\Orders\OrderService;
use Database\Seeders\LoyaltySeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(LoyaltySeeder::class);
});

test('security headers are present on web responses', function () {
    $response = $this->get('/branches');

    $response->assertOk();
    expect($response->headers->get('X-Content-Type-Options'))->toBe('nosniff');
    expect($response->headers->get('X-Frame-Options'))->toBe('SAMEORIGIN');
    expect($response->headers->get('Referrer-Policy'))->toBe('strict-origin-when-cross-origin');
});

test('login is rate-limited after 6 attempts per minute', function () {
    User::factory()->create(['email' => 'rate@example.com']);

    foreach (range(1, 7) as $i) {
        $r = $this->post('/login', ['email' => 'rate@example.com', 'password' => 'wrong']);
        if ($i === 7) {
            expect($r->status())->toBe(429);
        }
    }
});

test('customer can view their own order', function () {
    $branch = Branch::factory()->create();
    $cat = Category::factory()->create();
    $product = Product::factory()->create(['category_id' => $cat->id]);
    $branch->products()->attach($product->id, ['is_available' => true]);
    $user = User::factory()->create();

    $order = app(OrderService::class)->place(new OrderPayload(
        branchId: $branch->id, userId: $user->id, orderType: OrderType::Pickup,
        lines: [new OrderLinePayload(productId: $product->id, quantity: 1)],
    ));

    $this->actingAs($user)->get("/orders/{$order->id}")->assertOk();
});

test('user cannot view another customer order without permission (IDOR guard)', function () {
    $branch = Branch::factory()->create();
    $cat = Category::factory()->create();
    $product = Product::factory()->create(['category_id' => $cat->id]);
    $branch->products()->attach($product->id, ['is_available' => true]);

    $owner = User::factory()->create();
    $stranger = User::factory()->create();

    $order = app(OrderService::class)->place(new OrderPayload(
        branchId: $branch->id, userId: $owner->id, orderType: OrderType::Pickup,
        lines: [new OrderLinePayload(productId: $product->id, quantity: 1)],
    ));

    $this->actingAs($stranger)->get("/orders/{$order->id}")->assertForbidden();
});

test('PDPA data export returns json with user + orders + points', function () {
    $branch = Branch::factory()->create();
    $cat = Category::factory()->create();
    $product = Product::factory()->create(['category_id' => $cat->id]);
    $branch->products()->attach($product->id, ['is_available' => true]);

    $user = User::factory()->create(['name' => 'Aiman']);
    app(OrderService::class)->place(new OrderPayload(
        branchId: $branch->id, userId: $user->id, orderType: OrderType::Pickup,
        lines: [new OrderLinePayload(productId: $product->id, quantity: 1)],
    ));

    $response = $this->actingAs($user)->getJson('/account/data-export');
    $response->assertOk()
        ->assertJsonPath('user.name', 'Aiman')
        ->assertJsonStructure(['user', 'orders', 'point_transactions', 'exported_at']);
    expect($response->json('orders'))->toHaveCount(1);
});

test('PDPA account deletion anonymises user + drops push subscriptions', function () {
    $branch = Branch::factory()->create();
    $cat = Category::factory()->create();
    $product = Product::factory()->create(['category_id' => $cat->id]);
    $branch->products()->attach($product->id, ['is_available' => true]);

    $user = User::factory()->create(['name' => 'Will Delete', 'email' => 'wd@example.com']);
    PushSubscription::create([
        'user_id' => $user->id,
        'endpoint' => 'https://fcm.example/del',
        'public_key' => 'pk', 'auth_token' => 'at',
    ]);
    app(OrderService::class)->place(new OrderPayload(
        branchId: $branch->id, userId: $user->id, orderType: OrderType::Pickup,
        lines: [new OrderLinePayload(productId: $product->id, quantity: 1)],
    ));

    $this->actingAs($user)->delete('/account')->assertRedirect('/');

    $deleted = User::withTrashed()->find($user->id);
    expect($deleted->trashed())->toBeTrue()
        ->and($deleted->name)->toBe('Deleted User')
        ->and($deleted->email)->not->toBe('wd@example.com');

    expect(PushSubscription::count())->toBe(0);
    expect(Order::firstWhere('user_id', $user->id)->customer_snapshot)->toBeNull();
});

test('guest cannot hit account endpoints', function () {
    $this->getJson('/account/data-export')->assertUnauthorized();
    $this->get('/account/data-export')->assertRedirect('/login');
});

test('Scribe API docs page renders', function () {
    $this->get('/docs')->assertOk();
});
