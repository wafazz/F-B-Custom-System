<?php

use App\Events\BranchStockChanged;
use App\Models\Branch;
use App\Models\BranchStock;
use App\Models\Category;
use App\Models\ModifierGroup;
use App\Models\ModifierOption;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('category auto-generates slug from name', function () {
    $cat = Category::create(['name' => 'Hot Drinks']);

    expect($cat->slug)->toBe('hot-drinks');
});

test('product auto-generates slug and belongs to category', function () {
    $cat = Category::factory()->create(['name' => 'Coffee']);
    $product = Product::factory()->create(['category_id' => $cat->id, 'name' => 'Latte Test']);

    expect($product->slug)->toContain('latte-test');
    expect($product->category->id)->toBe($cat->id);
});

test('product modifier groups attach with sort order', function () {
    $product = Product::factory()->create();
    $size = ModifierGroup::factory()->create();
    $milk = ModifierGroup::factory()->create();

    $product->modifierGroups()->attach([
        $size->id => ['sort_order' => 1],
        $milk->id => ['sort_order' => 2],
    ]);

    expect($product->modifierGroups()->count())->toBe(2);
});

test('availableAtBranch scope returns products tied to branch and in-stock', function () {
    $branch = Branch::factory()->create();
    $other = Branch::factory()->create();
    $available = Product::factory()->create();
    $hidden = Product::factory()->create();
    $offMenu = Product::factory()->create();

    $branch->products()->attach([
        $available->id => ['is_available' => true],
        $hidden->id => ['is_available' => false],
    ]);
    $other->products()->attach([$offMenu->id => ['is_available' => true]]);

    $ids = Product::availableAtBranch($branch->id)->pluck('id')->all();

    expect($ids)->toContain($available->id);
    expect($ids)->not->toContain($hidden->id);
    expect($ids)->not->toContain($offMenu->id);
});

test('tracked stock at zero quantity excludes product from menu', function () {
    $branch = Branch::factory()->create();
    $product = Product::factory()->create();
    $branch->products()->attach($product->id, ['is_available' => true]);
    BranchStock::factory()->outOfStock()->create([
        'branch_id' => $branch->id,
        'product_id' => $product->id,
    ]);

    expect(Product::availableAtBranch($branch->id)->pluck('id')->all())
        ->not->toContain($product->id);
});

test('untracked stock keeps product available regardless of quantity', function () {
    $branch = Branch::factory()->create();
    $product = Product::factory()->create();
    $branch->products()->attach($product->id, ['is_available' => true]);
    BranchStock::factory()->create([
        'branch_id' => $branch->id,
        'product_id' => $product->id,
        'track_quantity' => false,
        'quantity' => 0,
    ]);

    expect(Product::availableAtBranch($branch->id)->pluck('id')->all())
        ->toContain($product->id);
});

test('branch price override is applied via priceForBranch', function () {
    $branch = Branch::factory()->create();
    $product = Product::factory()->create(['base_price' => 12.00]);
    $branch->products()->attach($product->id, ['is_available' => true, 'price_override' => 15.50]);

    $product->load('branches');

    expect($product->priceForBranch($branch->id))->toBe(15.50);
});

test('branch falls back to base price when no override', function () {
    $branch = Branch::factory()->create();
    $product = Product::factory()->create(['base_price' => 9.50]);
    $branch->products()->attach($product->id, ['is_available' => true]);
    $product->load('branches');

    expect($product->priceForBranch($branch->id))->toBe(9.50);
});

test('stock movement updates quantity and broadcasts event when availability flips', function () {
    Event::fake([BranchStockChanged::class]);
    $stock = BranchStock::factory()->tracked(qty: 1, low: 0)->create();

    $stock->applyMovement('sale', -1, 'order checkout');

    expect($stock->fresh()->quantity)->toBe(0);
    Event::assertDispatched(BranchStockChanged::class, function (BranchStockChanged $e) use ($stock) {
        return $e->branchId === $stock->branch_id
            && $e->productId === $stock->product_id
            && $e->isAvailable === false
            && $e->quantity === 0;
    });
});

test('stock movement records audit row', function () {
    $stock = BranchStock::factory()->tracked(qty: 50)->create();

    $stock->applyMovement('restock', 20, 'morning delivery');

    expect($stock->movements()->count())->toBe(1);
    $movement = $stock->movements()->first();
    expect($movement->type)->toBe('restock')
        ->and($movement->quantity_change)->toBe(20)
        ->and($movement->quantity_after)->toBe(70)
        ->and($stock->fresh()->last_restocked_at)->not->toBeNull();
});

test('branch menu API returns categories with products and modifiers', function () {
    $branch = Branch::factory()->create();
    $cat = Category::factory()->create(['name' => 'Coffee']);
    $product = Product::factory()->create(['category_id' => $cat->id, 'base_price' => 12.00]);
    $size = ModifierGroup::factory()->create(['name' => 'Size']);
    ModifierOption::factory()->create(['modifier_group_id' => $size->id, 'name' => 'Large']);
    $product->modifierGroups()->attach($size->id, ['sort_order' => 1]);
    $branch->products()->attach($product->id, ['is_available' => true]);

    $response = $this->getJson("/api/branches/{$branch->id}/menu");

    $response->assertOk()
        ->assertJsonPath('branch.id', $branch->id)
        ->assertJsonPath('categories.0.products.0.id', $product->id)
        ->assertJsonPath('categories.0.products.0.modifier_groups.0.name', $size->name);
    expect($response->json('categories.0.name'))->toContain('Coffee');
    expect((float) $response->json('categories.0.products.0.price'))->toBe(12.0);
});

test('branch menu API returns empty when branch is not accepting orders', function () {
    $branch = Branch::factory()->closed()->create();

    $response = $this->getJson("/api/branches/{$branch->id}/menu");

    $response->assertOk()
        ->assertJsonPath('categories', [])
        ->assertJsonPath('branch.status', 'closed');
});

test('hq_admin has full catalog permissions', function () {
    $user = User::factory()->create();
    $user->assignRole('hq_admin');

    expect($user->can('create_product'))->toBeTrue();
    expect($user->can('update_category'))->toBeTrue();
    expect($user->can('delete_modifier::group'))->toBeTrue();
});

test('barista can read catalog but not modify', function () {
    $user = User::factory()->create();
    $user->assignRole('barista');

    expect($user->can('view_any_product'))->toBeTrue();
    expect($user->can('view_any_category'))->toBeTrue();
    expect($user->can('create_product'))->toBeFalse();
    expect($user->can('update_category'))->toBeFalse();
});

test('branch_manager can manage stock at their branch', function () {
    $user = User::factory()->create();
    $user->assignRole('branch_manager');

    expect($user->can('view_any_branch::stock'))->toBeTrue();
    expect($user->can('update_branch::stock'))->toBeTrue();
});
