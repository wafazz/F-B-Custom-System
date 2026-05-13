<?php

use App\Models\Branch;
use App\Models\Category;
use App\Models\Product;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('splash page renders for guests', function () {
    $response = $this->get('/');

    $response->assertOk()
        ->assertInertia(fn ($p) => $p->component('storefront/splash')
            ->where('hasBranches', false));
});

test('splash page reports presence of active branches', function () {
    Branch::factory()->create();

    $response = $this->get('/');

    $response->assertOk()
        ->assertInertia(fn ($p) => $p->component('storefront/splash')
            ->where('hasBranches', true));
});

test('branch selection page lists active branches with open status', function () {
    $active = Branch::factory()->create(['name' => 'KLCC']);
    Branch::factory()->closed()->create();

    $response = $this->get('/branches');

    $response->assertOk()
        ->assertInertia(fn ($p) => $p->component('storefront/branch-select')
            ->has('branches', 1)
            ->where('branches.0.id', $active->id)
            ->where('branches.0.name', 'KLCC')
            ->where('branches.0.is_open_now', fn ($v) => is_bool($v)));
});

test('storefront menu page exposes branch context and reverb channel', function () {
    $branch = Branch::factory()->create();

    $response = $this->get("/branches/{$branch->id}/menu");

    $response->assertOk()
        ->assertInertia(fn ($p) => $p->component('storefront/menu')
            ->where('branch.id', $branch->id)
            ->where('reverb.channel', "branch.{$branch->id}.stock")
            ->where('reverb.event', 'stock.changed'));
});

test('branch menu API filters products by X-Channel header (web)', function () {
    $branch = Branch::factory()->create();
    $cat = Category::factory()->create();
    $webOnly = Product::factory()->create(['category_id' => $cat->id, 'available_web' => true, 'available_pwa' => false, 'available_mobile' => false]);
    $pwaOnly = Product::factory()->create(['category_id' => $cat->id, 'available_web' => false, 'available_pwa' => true, 'available_mobile' => false]);
    $branch->products()->attach([$webOnly->id, $pwaOnly->id], ['is_available' => true]);

    $response = $this->getJson("/api/branches/{$branch->id}/menu", ['X-Channel' => 'web']);
    $response->assertOk();
    $ids = collect($response->json('categories.0.products', []))->pluck('id')->all();
    expect($ids)->toContain($webOnly->id)->and($ids)->not->toContain($pwaOnly->id);
});

test('branch menu API filters products by X-Channel header (pwa)', function () {
    $branch = Branch::factory()->create();
    $cat = Category::factory()->create();
    $webOnly = Product::factory()->create(['category_id' => $cat->id, 'available_web' => true, 'available_pwa' => false]);
    $pwaOnly = Product::factory()->create(['category_id' => $cat->id, 'available_web' => false, 'available_pwa' => true]);
    $branch->products()->attach([$webOnly->id, $pwaOnly->id], ['is_available' => true]);

    $response = $this->getJson("/api/branches/{$branch->id}/menu", ['X-Channel' => 'pwa']);
    $response->assertOk();
    $ids = collect($response->json('categories.0.products', []))->pluck('id')->all();
    expect($ids)->toContain($pwaOnly->id)->and($ids)->not->toContain($webOnly->id);
});
