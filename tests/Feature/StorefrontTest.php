<?php

use App\Models\Branch;
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
