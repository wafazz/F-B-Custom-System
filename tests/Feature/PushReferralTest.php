<?php

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Order;
use App\Models\PointTransaction;
use App\Models\Product;
use App\Models\PushSubscription;
use App\Models\ReferralReward;
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

function makeBranchWithProduct(): array
{
    $branch = Branch::factory()->create();
    $cat = Category::factory()->create();
    $product = Product::factory()->create(['category_id' => $cat->id, 'base_price' => 20.00]);
    $branch->products()->attach($product->id, ['is_available' => true]);

    return [$branch, $product];
}

test('GET /api/push/vapid-key returns the public key', function () {
    config()->set('services.webpush.public_key', 'BKEY');

    $this->getJson('/api/push/vapid-key')
        ->assertOk()
        ->assertJson(['public_key' => 'BKEY']);
});

test('authenticated user can subscribe + unsubscribe to push', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->postJson('/api/push/subscribe', [
        'endpoint' => 'https://fcm.example/abc',
        'keys' => ['p256dh' => 'pkey', 'auth' => 'atok'],
    ])->assertCreated();

    expect(PushSubscription::count())->toBe(1);

    $this->actingAs($user)->deleteJson('/api/push/subscribe', [
        'endpoint' => 'https://fcm.example/abc',
    ])->assertOk();

    expect(PushSubscription::count())->toBe(0);
});

test('subscribe is idempotent on the same endpoint (updateOrCreate)', function () {
    $user = User::factory()->create();

    $payload = [
        'endpoint' => 'https://fcm.example/dup',
        'keys' => ['p256dh' => 'a', 'auth' => 'b'],
    ];
    $this->actingAs($user)->postJson('/api/push/subscribe', $payload)->assertCreated();
    $this->actingAs($user)->postJson('/api/push/subscribe', $payload)->assertCreated();

    expect(PushSubscription::count())->toBe(1);
});

test('guest cannot subscribe to push', function () {
    $this->postJson('/api/push/subscribe', [
        'endpoint' => 'https://fcm.example/x',
        'keys' => ['p256dh' => 'a', 'auth' => 'b'],
    ])->assertUnauthorized();
});

test('referral reward is granted on referee first completed order', function () {
    config()->set('services.referral.referrer_bonus_points', 100);
    config()->set('services.referral.referee_bonus_points', 150);

    $referrer = User::factory()->create(['referral_code' => 'STAR-AAA']);
    $referee = User::factory()->create(['referred_by' => $referrer->id]);

    [$branch, $product] = makeBranchWithProduct();

    $order = app(OrderService::class)->place(new OrderPayload(
        branchId: $branch->id, userId: $referee->id, orderType: OrderType::Pickup,
        lines: [new OrderLinePayload(productId: $product->id, quantity: 1)],
    ));
    foreach ([OrderStatus::Preparing, OrderStatus::Ready, OrderStatus::Completed] as $s) {
        app(OrderService::class)->transition($order->fresh(), $s);
    }

    $reward = ReferralReward::firstWhere('referee_user_id', $referee->id);
    expect($reward)->not->toBeNull()
        ->and($reward->referrer_points)->toBe(100)
        ->and($reward->referee_points)->toBe(150);

    $referrerLatest = PointTransaction::where('user_id', $referrer->id)->latest('id')->first();
    $refereeLatest = PointTransaction::where('user_id', $referee->id)->latest('id')->first();
    expect($referrerLatest->balance_after)->toBe(100);
    expect($refereeLatest->balance_after)->toBeGreaterThan(150); // 150 referral + earn from order
});

test('referral reward is not duplicated on second completed order', function () {
    $referrer = User::factory()->create(['referral_code' => 'STAR-DUP']);
    $referee = User::factory()->create(['referred_by' => $referrer->id]);

    [$branch, $product] = makeBranchWithProduct();

    foreach ([1, 2] as $_n) {
        $order = app(OrderService::class)->place(new OrderPayload(
            branchId: $branch->id, userId: $referee->id, orderType: OrderType::Pickup,
            lines: [new OrderLinePayload(productId: $product->id, quantity: 1)],
        ));
        foreach ([OrderStatus::Preparing, OrderStatus::Ready, OrderStatus::Completed] as $s) {
            app(OrderService::class)->transition($order->fresh(), $s);
        }
    }

    expect(ReferralReward::where('referee_user_id', $referee->id)->count())->toBe(1);
});

test('referral reward not granted when user has no referrer', function () {
    $user = User::factory()->create(['referred_by' => null]);
    [$branch, $product] = makeBranchWithProduct();

    $order = app(OrderService::class)->place(new OrderPayload(
        branchId: $branch->id, userId: $user->id, orderType: OrderType::Pickup,
        lines: [new OrderLinePayload(productId: $product->id, quantity: 1)],
    ));
    foreach ([OrderStatus::Preparing, OrderStatus::Ready, OrderStatus::Completed] as $s) {
        app(OrderService::class)->transition($order->fresh(), $s);
    }

    expect(ReferralReward::count())->toBe(0);
});

test('GET /referral renders the page with code + share url', function () {
    $user = User::factory()->create(['referral_code' => 'STAR-SHOW']);

    $this->actingAs($user)
        ->get('/referral')
        ->assertOk()
        ->assertInertia(fn ($p) => $p->component('storefront/referral')
            ->where('code', 'STAR-SHOW')
            ->where('share_url', fn ($v) => str_contains((string) $v, 'ref=STAR-SHOW')));
});

test('static info pages render', function () {
    $this->get('/terms')->assertOk()->assertInertia(fn ($p) => $p->component('info/terms'));
    $this->get('/privacy')->assertOk()->assertInertia(fn ($p) => $p->component('info/privacy'));
    $this->get('/faq')->assertOk()->assertInertia(fn ($p) => $p->component('info/faq'));
});
