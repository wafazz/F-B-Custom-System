<?php

use App\Models\Branch;
use App\Models\BranchReview;
use App\Models\Category;
use App\Models\CustomerCart;
use App\Models\HomeSlide;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\Voucher;
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

// ── Product reviews (modifier-sheet slider parity) ────────────────────────────

test('GET /api/products/{product}/reviews returns visible reviews and rating summary', function () {
    $cat = Category::factory()->create();
    $product = Product::factory()->create([
        'category_id' => $cat->id,
        'avg_rating' => 4.5,
        'reviews_count' => 2,
    ]);

    ProductReview::create([
        'user_id' => User::factory()->create(['name' => 'Aisyah'])->id,
        'product_id' => $product->id,
        'rating' => 5,
        'comment' => 'Best latte ever',
        'is_hidden' => false,
    ]);
    ProductReview::create([
        'user_id' => User::factory()->create()->id,
        'product_id' => $product->id,
        'rating' => 4,
        'comment' => 'Good',
        'is_hidden' => false,
    ]);
    ProductReview::create([
        'user_id' => User::factory()->create()->id,
        'product_id' => $product->id,
        'rating' => 1,
        'comment' => 'Hidden one',
        'is_hidden' => true,
    ]);

    $this->getJson("/api/products/{$product->id}/reviews")
        ->assertOk()
        ->assertJsonPath('avg_rating', 4.5)
        ->assertJsonPath('reviews_count', 2)
        ->assertJsonCount(2, 'reviews')
        ->assertJsonStructure(['avg_rating', 'reviews_count', 'reviews' => [['id', 'rating', 'comment', 'user_name', 'created_at']]])
        ->assertJsonFragment(['user_name' => 'Aisyah', 'comment' => 'Best latte ever'])
        ->assertJsonMissing(['comment' => 'Hidden one']);
});

// ── Cart sync (abandoned-cart, mobile via Sanctum token) ──────────────────────

test('POST /api/cart/sync requires authentication', function () {
    $this->postJson('/api/cart/sync', ['item_count' => 1])->assertUnauthorized();
});

test('POST /api/cart/sync mirrors a logged-in cart via a Sanctum token', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->postJson('/api/cart/sync', [
        'branch_id' => null,
        'item_count' => 2,
        'subtotal' => 25.5,
        'items' => [['name' => 'Latte', 'quantity' => 2]],
    ])
        ->assertOk()
        ->assertJsonPath('ok', true);

    expect(CustomerCart::query()->where('user_id', $user->id)->where('item_count', 2)->exists())
        ->toBeTrue();
});

test('POST /api/cart/sync with an empty cart clears the stored row', function () {
    $user = User::factory()->create();
    CustomerCart::create([
        'user_id' => $user->id,
        'branch_id' => null,
        'item_count' => 3,
        'subtotal' => 30,
        'items' => [['name' => 'Mocha', 'quantity' => 3]],
    ]);
    Sanctum::actingAs($user);

    $this->postJson('/api/cart/sync', ['item_count' => 0])
        ->assertOk()
        ->assertJsonPath('cleared', true);

    expect(CustomerCart::query()->where('user_id', $user->id)->exists())->toBeFalse();
});

// ── Vouchers (eligible-only + banner image, mobile parity) ────────────────────

test('GET /api/vouchers returns eligible vouchers with banner image and points balance', function () {
    $user = User::factory()->create();
    Voucher::factory()->create([
        'code' => 'WELCOME10',
        'banner_image' => 'vouchers/welcome.jpg',
        'is_spin_only' => false,
        'is_check_in_only' => false,
    ]);
    Sanctum::actingAs($user);

    $this->getJson('/api/vouchers')
        ->assertOk()
        ->assertJsonStructure([
            'available' => [
                ['id', 'code', 'name', 'banner_image', 'discount_type', 'valid_from', 'max_uses_per_user', 'tier_names', 'product_names', 'combo_names', 'new_users_only', 'points_cost'],
            ],
            'claimed',
            'points_balance',
        ])
        ->assertJsonPath('available.0.code', 'WELCOME10')
        ->assertJsonPath('available.0.banner_image', 'vouchers/welcome.jpg');
});

test('GET /api/vouchers hides vouchers the customer is not eligible for', function () {
    $user = User::factory()->create();
    \App\Models\Order::factory()->create(['user_id' => $user->id]);
    Voucher::factory()->create([
        'code' => 'NEWBIE',
        'new_users_only' => true,
        'is_spin_only' => false,
        'is_check_in_only' => false,
    ]);
    Sanctum::actingAs($user);

    $this->getJson('/api/vouchers')
        ->assertOk()
        ->assertJsonMissing(['code' => 'NEWBIE']);
});

// ── BxGy promo picker (mobile parity) ─────────────────────────────────────────

test('GET /api/branches/{branch}/promos/{code} returns paid + free pools for a bxgy voucher', function () {
    $branch = Branch::factory()->create();
    $cat = Category::factory()->create();
    $product = Product::factory()->create([
        'category_id' => $cat->id,
        'status' => 'active',
        'base_price' => 10,
    ]);
    $branch->products()->attach($product->id, ['is_available' => true]);

    Voucher::factory()->create([
        'code' => 'B1F1',
        'discount_type' => 'buy_x_get_y',
        'status' => 'active',
        'bxgy_buy_qty' => 1,
        'bxgy_free_qty' => 1,
        'product_ids' => [$product->id],
    ]);

    $this->getJson("/api/branches/{$branch->id}/promos/B1F1")
        ->assertOk()
        ->assertJsonPath('voucher.code', 'B1F1')
        ->assertJsonPath('voucher.bxgy_buy_qty', 1)
        ->assertJsonPath('voucher.free_scope_mode', 'same')
        ->assertJsonStructure([
            'branch' => ['id', 'code', 'name'],
            'voucher' => ['code', 'name', 'bxgy_buy_qty', 'bxgy_free_qty', 'free_scope_mode'],
            'paid_products' => [['id', 'name', 'price', 'sku', 'modifier_groups']],
            'free_products',
        ])
        ->assertJsonPath('paid_products.0.id', $product->id);
});

test('promo picker 404s for a non-bxgy voucher', function () {
    $branch = Branch::factory()->create();
    Voucher::factory()->create(['code' => 'FLAT5', 'discount_type' => 'percentage', 'status' => 'active']);

    $this->getJson("/api/branches/{$branch->id}/promos/FLAT5")->assertNotFound();
});

// ── Loyalty page (mobile Membership tab parity) ───────────────────────────────

test('GET /api/loyalty/page requires authentication', function () {
    $this->getJson('/api/loyalty/page')->assertUnauthorized();
});

test('GET /api/loyalty/page returns slides, referral, balance, tiers and history', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->getJson('/api/loyalty/page')
        ->assertOk()
        ->assertJsonStructure([
            'slides' => [['type', 'title', 'image', 'subtitle', 'cta_label', 'cta_url']],
            'referral' => ['code', 'share_url', 'referrer_bonus'],
            'balance',
            'redeem_value',
            'lifetime_spend',
            'current_tier',
            'next_tier',
            'history',
            'membership_tiers',
        ])
        ->assertJsonPath('referral.code', $user->referral_code);
});
