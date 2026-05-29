<?php

use App\Jobs\SendScheduledCampaign;
use App\Models\BranchReview;
use App\Models\Branch;
use App\Models\Category;
use App\Models\CustomerTier;
use App\Models\MembershipTier;
use App\Models\Order;
use App\Models\PointReward;
use App\Models\PointTransaction;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\ScheduledCampaign;
use App\Models\User;
use App\Services\Push\PushService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Mockery\MockInterface;
use Database\Seeders\LoyaltySeeder;
use Database\Seeders\RolesAndPermissionsSeeder;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(LoyaltySeeder::class);
});

/** Mock PushService and capture every sendToUser call into $sink. */
function capturePush(array &$sink): void
{
    test()->mock(PushService::class, function (MockInterface $m) use (&$sink) {
        $m->shouldReceive('sendToUser')->andReturnUsing(function (int $userId, array $payload) use (&$sink) {
            $sink[] = ['user_id' => $userId, 'payload' => $payload];

            return ['sent' => 1, 'pruned' => 0, 'delivered' => [], 'failures' => []];
        });
    });
}

function makeCampaign(array $attrs): ScheduledCampaign
{
    return ScheduledCampaign::create(array_merge([
        'name' => 'Test',
        'trigger_type' => 'schedule',
        'title' => 'T',
        'body' => 'B',
        'url' => '/',
        'is_active' => true,
    ], $attrs));
}

function setBalance(User $user, int $balance): void
{
    PointTransaction::create([
        'user_id' => $user->id,
        'type' => 'earn',
        'points' => $balance,
        'balance_after' => $balance,
    ]);
}

test('redeemable audience targets members who can afford the cheapest reward', function () {
    PointReward::create(['name' => 'Free Latte', 'points_cost' => 100, 'kind' => 'product', 'status' => 'active']);

    $rich = User::factory()->create();
    $poor = User::factory()->create();
    setBalance($rich, 150);
    setBalance($poor, 40);

    $calls = [];
    capturePush($calls);

    $campaign = makeCampaign([
        'audience' => 'redeemable',
        'body' => 'You have {points} points to spend!',
        'frequency' => 'daily',
        'run_time' => '09:00',
        'inactivity_cooldown_days' => 7,
    ]);
    SendScheduledCampaign::dispatchSync($campaign->id);

    $ids = collect($calls)->pluck('user_id')->all();
    expect($ids)->toContain($rich->id)
        ->and($ids)->not->toContain($poor->id);

    $sent = collect($calls)->firstWhere('user_id', $rich->id);
    expect($sent['payload']['body'])->toBe('You have 150 points to spend!');
});

test('near_redeemable targets members within N points and fills {needed}', function () {
    PointReward::create(['name' => 'Free Latte', 'points_cost' => 100, 'kind' => 'product', 'status' => 'active']);

    $close = User::factory()->create();   // 70 → 30 away, within 50
    $far = User::factory()->create();     // 20 → 80 away, outside 50
    $there = User::factory()->create();   // 120 → already enough, excluded
    setBalance($close, 70);
    setBalance($far, 20);
    setBalance($there, 120);

    $calls = [];
    capturePush($calls);

    $campaign = makeCampaign([
        'audience' => 'near_redeemable',
        'body' => 'Just {needed} more points!',
        'inactivity_days' => 50,
        'frequency' => 'daily',
        'run_time' => '09:00',
        'inactivity_cooldown_days' => 7,
    ]);
    SendScheduledCampaign::dispatchSync($campaign->id);

    $ids = collect($calls)->pluck('user_id')->all();
    expect($ids)->toContain($close->id)
        ->and($ids)->not->toContain($far->id)
        ->and($ids)->not->toContain($there->id);

    expect(collect($calls)->firstWhere('user_id', $close->id)['payload']['body'])->toBe('Just 30 more points!');
});

test('tier_upgrade targets members within N ringgit of the next tier', function () {
    // Seeded tiers: Bronze 0, Silver 200, Gold 500, Platinum 1500.
    $silverId = MembershipTier::where('name', 'Bronze')->value('id');

    $close = User::factory()->create();   // 160 spend → 40 to Silver (within 50)
    $far = User::factory()->create();     // 100 spend → 100 to Silver (outside 50)
    CustomerTier::create(['user_id' => $close->id, 'membership_tier_id' => $silverId, 'lifetime_spend' => 160]);
    CustomerTier::create(['user_id' => $far->id, 'membership_tier_id' => $silverId, 'lifetime_spend' => 100]);

    $calls = [];
    capturePush($calls);

    $campaign = makeCampaign([
        'audience' => 'tier_upgrade',
        'body' => 'RM{needed} more to {tier}!',
        'inactivity_days' => 50,
        'frequency' => 'daily',
        'run_time' => '09:00',
        'inactivity_cooldown_days' => 7,
    ]);
    SendScheduledCampaign::dispatchSync($campaign->id);

    $ids = collect($calls)->pluck('user_id')->all();
    expect($ids)->toContain($close->id)
        ->and($ids)->not->toContain($far->id);

    expect(collect($calls)->firstWhere('user_id', $close->id)['payload']['body'])->toBe('RM40 more to Silver!');
});

test('review audience targets completed orders with no review N days ago', function () {
    $reviewed = User::factory()->create();
    $pending = User::factory()->create();

    $reviewedOrder = Order::factory()->create([
        'user_id' => $reviewed->id, 'status' => 'completed', 'completed_at' => now()->subDays(2),
    ]);
    $pendingOrder = Order::factory()->create([
        'user_id' => $pending->id, 'status' => 'completed', 'completed_at' => now()->subDays(2),
    ]);

    $branch = Branch::factory()->create();
    BranchReview::create([
        'user_id' => $reviewed->id, 'branch_id' => $branch->id, 'order_id' => $reviewedOrder->id, 'rating' => 5,
    ]);

    $calls = [];
    capturePush($calls);

    $campaign = makeCampaign([
        'audience' => 'review',
        'body' => 'How was your order, {name}?',
        'inactivity_days' => 2,
        'frequency' => 'daily',
        'run_time' => '09:00',
        'inactivity_cooldown_days' => 7,
    ]);
    SendScheduledCampaign::dispatchSync($campaign->id);

    $ids = collect($calls)->pluck('user_id')->all();
    expect($ids)->toContain($pending->id)
        ->and($ids)->not->toContain($reviewed->id);
});

test('monthly payday schedule is due only on its day-of-month at run time', function () {
    $campaign = new ScheduledCampaign([
        'trigger_type' => 'schedule',
        'audience' => 'payday',
        'frequency' => 'monthly',
        'run_time' => '09:00',
        'run_days' => [25],
        'is_active' => true,
    ]);

    expect($campaign->isDue(Carbon::create(2026, 6, 25, 9, 30)))->toBeTrue();
    expect($campaign->isDue(Carbon::create(2026, 6, 25, 8, 30)))->toBeFalse(); // before run time
    expect($campaign->isDue(Carbon::create(2026, 6, 24, 9, 30)))->toBeFalse(); // wrong day
});

test('monthly day 31 fires on the last day of a short month', function () {
    $campaign = new ScheduledCampaign([
        'trigger_type' => 'schedule', 'audience' => 'payday', 'frequency' => 'monthly',
        'run_time' => '09:00', 'run_days' => [31], 'is_active' => true,
    ]);

    // February 2026 has 28 days — day 31 clamps to the 28th.
    expect($campaign->isDue(Carbon::create(2026, 2, 28, 10, 0)))->toBeTrue();
    expect($campaign->isDue(Carbon::create(2026, 2, 27, 10, 0)))->toBeFalse();
});
