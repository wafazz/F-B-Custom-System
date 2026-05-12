<?php

use App\Filament\Pages\WebPushSettings;
use App\Models\Setting;
use App\Models\User;
use App\Services\Settings\SettingsRepository;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    Cache::forget(SettingsRepository::CACHE_KEY);
});

test('guest is redirected to login', function () {
    $this->get('/admin/web-push-settings')->assertRedirect('/admin/login');
});

test('customer cannot access the page', function () {
    $customer = User::factory()->create();
    $customer->assignRole('customer');

    $this->actingAs($customer)
        ->get('/admin/web-push-settings')
        ->assertForbidden();
});

test('super admin can render the page', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');

    $this->actingAs($admin)
        ->get('/admin/web-push-settings')
        ->assertOk()
        ->assertSee('VAPID identity')
        ->assertSee('VAPID keypair')
        ->assertSee('Generate new keypair');
});

test('hq admin can render the page', function () {
    $admin = User::factory()->create();
    $admin->assignRole('hq_admin');

    $this->actingAs($admin)->get('/admin/web-push-settings')->assertOk();
});

test('generate button fills the form with a valid keypair', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');

    $this->actingAs($admin);

    Livewire::test(WebPushSettings::class)
        ->call('generateKeys')
        ->assertHasNoErrors()
        ->assertSet('data.public_key', fn (string $v) => strlen($v) >= 80)
        ->assertSet('data.private_key', fn (string $v) => strlen($v) >= 40);
});

test('saving persists subject + keys and encrypts the private key', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');

    $this->actingAs($admin);

    Livewire::test(WebPushSettings::class)
        ->fillForm([
            'subject' => 'mailto:owner@starcoffee.my',
            'public_key' => 'BNPORHTvk0EUk8tFqCzND6skGPZ65No9Qs58cT_p_velweIGVevPPz6KuJDruxDaD4xY74qVcibVtsk33BGlaoc',
            'private_key' => 'super-secret-private-key-value-here',
        ])
        ->call('save')
        ->assertHasNoErrors();

    $repo = app(SettingsRepository::class);
    expect($repo->get('webpush.subject'))->toBe('mailto:owner@starcoffee.my');
    expect($repo->get('webpush.public_key'))->toStartWith('BNPORHTvk0EU');
    expect($repo->get('webpush.private_key'))->toBe('super-secret-private-key-value-here');

    $row = Setting::where('key', 'webpush.private_key')->first();
    expect($row->is_encrypted)->toBeTrue();
    expect($row->getRawOriginal('value'))->not->toBe('super-secret-private-key-value-here');
});

test('public vapid key endpoint serves the DB value over .env', function () {
    config(['services.webpush.public_key' => 'env-fallback-key']);

    $repo = app(SettingsRepository::class);
    $repo->set('webpush.public_key', 'db-stored-public-key');
    config(['services.webpush.public_key' => 'db-stored-public-key']);

    $this->get('/api/push/vapid-key')
        ->assertOk()
        ->assertJson(['public_key' => 'db-stored-public-key']);
});
