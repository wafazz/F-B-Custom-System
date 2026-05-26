<?php

use App\Models\DeviceToken;
use App\Models\User;
use App\Services\Push\ExpoPushService;
use App\Services\Push\PushService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    config()->set('services.expo.push_enabled', true);
});

function makeCustomerToken(User $user, string $token = 'ExponentPushToken[abc123]'): DeviceToken
{
    return DeviceToken::create([
        'user_id' => $user->id,
        'scope' => DeviceToken::SCOPE_CUSTOMER,
        'platform' => 'ios',
        'token' => $token,
    ]);
}

test('sends to a consented user mobile token and maps the deep-link', function () {
    Http::fake(['exp.host/*' => Http::response(['data' => [['status' => 'ok', 'id' => 'r1']]], 200)]);

    $user = User::factory()->create(['push_consent' => true]);
    makeCustomerToken($user);

    $report = app(ExpoPushService::class)->sendToUser($user->id, [
        'title' => 'Your order is ready!',
        'body' => 'Order SC-5 is ready for pickup.',
        'url' => 'https://starcoffee.my/orders/5',
        'tag' => 'order-5',
    ]);

    expect($report['sent'])->toBe(1);
    expect($report['failures'])->toBe([]);

    Http::assertSent(function ($request) {
        $body = $request->data();

        return str_contains($request->url(), 'exp.host')
            && ($body[0]['to'] ?? null) === 'ExponentPushToken[abc123]'
            && ($body[0]['title'] ?? null) === 'Your order is ready!'
            && ($body[0]['channelId'] ?? null) === 'default'
            && ($body[0]['data']['path'] ?? null) === '/orders/5'
            && ($body[0]['data']['order_id'] ?? null) === 5
            && ($body[0]['data']['tag'] ?? null) === 'order-5';
    });
});

test('does not send to a user who turned push off', function () {
    Http::fake();

    $user = User::factory()->create(['push_consent' => false]);
    makeCustomerToken($user);

    $report = app(ExpoPushService::class)->sendToUser($user->id, ['title' => 'Hi', 'body' => 'There']);

    expect($report['sent'])->toBe(0);
    Http::assertNothingSent();
});

test('prunes tokens Expo reports as DeviceNotRegistered', function () {
    Http::fake(['exp.host/*' => Http::response(['data' => [[
        'status' => 'error',
        'message' => 'is not a registered push notification recipient',
        'details' => ['error' => 'DeviceNotRegistered'],
    ]]], 200)]);

    $user = User::factory()->create(['push_consent' => true]);
    makeCustomerToken($user, 'ExponentPushToken[dead]');

    $report = app(ExpoPushService::class)->sendToUser($user->id, ['title' => 'x', 'body' => 'y']);

    expect($report['sent'])->toBe(0);
    expect($report['pruned'])->toBe(1);
    $this->assertDatabaseMissing('device_tokens', ['token' => 'ExponentPushToken[dead]']);
});

test('no-ops when the user has no mobile tokens', function () {
    Http::fake();

    $user = User::factory()->create(['push_consent' => true]);

    $report = app(ExpoPushService::class)->sendToUser($user->id, ['title' => 'x', 'body' => 'y']);

    expect($report['sent'])->toBe(0);
    Http::assertNothingSent();
});

test('respects the expo.push_enabled config switch', function () {
    config()->set('services.expo.push_enabled', false);
    Http::fake();

    $user = User::factory()->create(['push_consent' => true]);
    makeCustomerToken($user);

    $report = app(ExpoPushService::class)->sendToUser($user->id, ['title' => 'x', 'body' => 'y']);

    expect($report['sent'])->toBe(0);
    Http::assertNothingSent();
});

test('PushService fans out to Expo when web-push is unconfigured', function () {
    // No VAPID keys → web-push side no-ops, Expo side still delivers.
    config()->set('services.webpush.public_key', '');
    config()->set('services.webpush.private_key', '');
    Http::fake(['exp.host/*' => Http::response(['data' => [['status' => 'ok', 'id' => 'r1']]], 200)]);

    $user = User::factory()->create(['push_consent' => true]);
    makeCustomerToken($user);

    $report = app(PushService::class)->sendToUser($user->id, ['title' => 'Promo', 'body' => 'Today only', 'url' => '/rewards']);

    expect($report['sent'])->toBe(1);
    Http::assertSent(fn ($request) => str_contains($request->url(), 'exp.host'));
});
