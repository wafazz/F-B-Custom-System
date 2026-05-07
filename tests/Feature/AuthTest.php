<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('login screen renders', function () {
    $this->get('/login')->assertOk();
});

test('register screen renders', function () {
    $this->get('/register')->assertOk();
});

test('user can register', function () {
    $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'phone' => '+60121234567',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ])->assertSessionHasNoErrors();

    expect(User::where('email', 'test@example.com')->exists())->toBeTrue();
    $this->assertAuthenticated();
});

test('user gets unique referral code on signup', function () {
    $user = User::factory()->create();

    expect($user->referral_code)->not->toBeNull()->toHaveLength(8);
});

test('user can login with email', function () {
    $user = User::factory()->create([
        'email' => 'me@example.com',
        'password' => Hash::make('secret123'),
    ]);

    $this->post('/login', [
        'identifier' => 'me@example.com',
        'password' => 'secret123',
    ])->assertRedirect('/');

    $this->assertAuthenticatedAs($user);
});

test('user can login with phone', function () {
    $user = User::factory()->create([
        'phone' => '+60177777777',
        'password' => Hash::make('secret123'),
    ]);

    $this->post('/login', [
        'identifier' => '+60177777777',
        'password' => 'secret123',
    ])->assertRedirect('/');

    $this->assertAuthenticatedAs($user);
});

test('login fails with wrong password', function () {
    User::factory()->create(['email' => 'real@example.com', 'password' => Hash::make('right')]);

    $this->post('/login', [
        'identifier' => 'real@example.com',
        'password' => 'wrong',
    ])->assertSessionHasErrors('identifier');

    $this->assertGuest();
});
