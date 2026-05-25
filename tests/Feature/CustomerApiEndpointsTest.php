<?php

use App\Models\Branch;
use App\Models\BranchReview;
use App\Models\Category;
use App\Models\HomeSlide;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

// ── Favourites ───────────────────────────────────────────────────────────────

test('GET /api/favourites requires authentication', function () {
    $this->getJson('/api/favourites')->assertUnauthorized();
});

test('toggling a favourite adds then removes the product', function () {
    $user = User::factory()->create();
    $cat = Category::factory()->create();
    $product = Product::factory()->create(['category_id' => $cat->id]);
    Sanctum::actingAs($user);

    $this->postJson("/api/favourites/{$product->id}/toggle")
        ->assertOk()
        ->assertJson(['favourited' => true]);

    $this->getJson('/api/favourites')
        ->assertOk()
        ->assertJsonCount(1, 'products')
        ->assertJsonPath('products.0.id', $product->id);

    $this->postJson("/api/favourites/{$product->id}/toggle")
        ->assertOk()
        ->assertJson(['favourited' => false]);

    $this->getJson('/api/favourites')
        ->assertOk()
        ->assertJsonCount(0, 'products');
});

// ── Notifications ─────────────────────────────────────────────────────────────

test('GET /api/notifications returns items and unread count', function () {
    $user = User::factory()->create();
    $user->notifications()->create([
        'id' => (string) Str::uuid(),
        'type' => 'App\\Notifications\\OrderReady',
        'data' => ['type' => 'order', 'title' => 'Order ready', 'body' => 'Come collect it', 'url' => '/orders/1'],
    ]);
    Sanctum::actingAs($user);

    $this->getJson('/api/notifications')
        ->assertOk()
        ->assertJsonPath('unread_count', 1)
        ->assertJsonCount(1, 'notifications')
        ->assertJsonPath('notifications.0.title', 'Order ready')
        ->assertJsonPath('notifications.0.url', '/orders/1');
});

test('marking a single notification read clears it', function () {
    $user = User::factory()->create();
    $id = (string) Str::uuid();
    $user->notifications()->create([
        'id' => $id,
        'type' => 'App\\Notifications\\OrderReady',
        'data' => ['title' => 'Order ready'],
    ]);
    Sanctum::actingAs($user);

    $this->postJson("/api/notifications/{$id}/read")
        ->assertOk()
        ->assertJsonPath('unread_count', 0);

    expect($user->unreadNotifications()->count())->toBe(0);
});

test('mark-all-read clears every unread notification', function () {
    $user = User::factory()->create();
    foreach (range(1, 3) as $i) {
        $user->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'App\\Notifications\\OrderReady',
            'data' => ['title' => "N{$i}"],
        ]);
    }
    Sanctum::actingAs($user);

    expect($user->unreadNotifications()->count())->toBe(3);

    $this->postJson('/api/notifications/mark-all-read')
        ->assertOk()
        ->assertJsonPath('unread_count', 0);

    expect($user->fresh()->unreadNotifications()->count())->toBe(0);
});

// ── Branch home ───────────────────────────────────────────────────────────────

test('GET /api/branches/{branch}/home returns managed slides, categories and featured', function () {
    $branch = Branch::factory()->create();
    $cat = Category::factory()->create(['status' => 'active', 'parent_id' => null]);
    $product = Product::factory()->featured()->create([
        'category_id' => $cat->id,
        'status' => 'active',
        'base_price' => 12.00,
    ]);
    $branch->products()->attach($product->id, ['is_available' => true]);

    HomeSlide::create([
        'title' => 'Promo',
        'subtitle' => 'Buy one free one',
        'placement' => 'hero',
        'is_global' => true,
        'is_active' => true,
        'sort_order' => 1,
    ]);

    $this->getJson("/api/branches/{$branch->id}/home")
        ->assertOk()
        ->assertJsonPath('branch.id', $branch->id)
        ->assertJsonCount(1, 'slides')
        ->assertJsonPath('slides.0.title', 'Promo')
        ->assertJsonPath('slides.0.type', 'managed')
        ->assertJsonCount(1, 'categories')
        ->assertJsonPath('categories.0.id', $cat->id)
        ->assertJsonCount(1, 'featured')
        ->assertJsonPath('featured.0.id', $product->id);
});

test('branch home is publicly accessible without authentication', function () {
    $branch = Branch::factory()->create();

    $this->getJson("/api/branches/{$branch->id}/home")
        ->assertOk()
        ->assertJsonStructure(['branch', 'slides', 'rewards_slides', 'popup_slides', 'categories', 'featured']);
});

// ── Branches index (mobile parity: ratings, today's hours, open status) ───────

test('GET /api/branches returns open status, todays hours and rating fields', function () {
    Branch::factory()->create([
        'operating_hours' => collect(Branch::defaultOperatingHours())
            ->map(fn ($h) => array_merge($h, ['enabled' => true, 'open' => '00:00', 'close' => '23:59']))
            ->all(),
        'avg_rating' => 4.5,
        'reviews_count' => 12,
    ]);

    $this->getJson('/api/branches')
        ->assertOk()
        ->assertJsonPath('branches.0.is_open_now', true)
        ->assertJsonPath('branches.0.todays_hours', '00:00 – 23:59')
        ->assertJsonPath('branches.0.avg_rating', 4.5)
        ->assertJsonPath('branches.0.reviews_count', 12);
});

// ── Branch reviews (mobile parity) ────────────────────────────────────────────

test('GET /api/branches/{branch}/reviews returns visible reviews and rating summary', function () {
    $branch = Branch::factory()->create(['avg_rating' => 4.5, 'reviews_count' => 2]);

    BranchReview::create([
        'user_id' => User::factory()->create(['name' => 'Faiz Niseng'])->id,
        'branch_id' => $branch->id,
        'rating' => 5,
        'comment' => 'Terbaikk, boleh repeat lagi',
        'is_hidden' => false,
    ]);
    BranchReview::create([
        'user_id' => User::factory()->create()->id,
        'branch_id' => $branch->id,
        'rating' => 4,
        'comment' => 'Good',
        'is_hidden' => false,
    ]);
    BranchReview::create([
        'user_id' => User::factory()->create()->id,
        'branch_id' => $branch->id,
        'rating' => 1,
        'comment' => 'Hidden one',
        'is_hidden' => true,
    ]);

    $this->getJson("/api/branches/{$branch->id}/reviews")
        ->assertOk()
        ->assertJsonPath('branch.id', $branch->id)
        ->assertJsonPath('branch.avg_rating', 4.5)
        ->assertJsonPath('branch.reviews_count', 2)
        ->assertJsonCount(2, 'reviews')
        ->assertJsonStructure(['reviews' => [['id', 'rating', 'comment', 'user_name', 'created_at']]])
        ->assertJsonFragment(['user_name' => 'Faiz Niseng', 'comment' => 'Terbaikk, boleh repeat lagi'])
        ->assertJsonMissing(['comment' => 'Hidden one']);
});

test('branch reviews is publicly accessible without authentication', function () {
    $branch = Branch::factory()->create();

    $this->getJson("/api/branches/{$branch->id}/reviews")
        ->assertOk()
        ->assertJsonStructure(['branch' => ['id', 'name', 'avg_rating', 'reviews_count'], 'reviews']);
});
