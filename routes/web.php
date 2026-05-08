<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Web\OrderPagesController;
use App\Http\Controllers\Web\StorefrontController;
use Illuminate\Support\Facades\Route;

Route::get('/', [StorefrontController::class, 'splash'])->name('home');
Route::get('/branches', [StorefrontController::class, 'selectBranch'])->name('branches.select');
Route::get('/branches/{branch}/menu', [StorefrontController::class, 'menu'])->name('branches.menu');
Route::get('/branches/{branch}/cart', [OrderPagesController::class, 'cart'])->name('branches.cart');
Route::get('/branches/{branch}/checkout', [OrderPagesController::class, 'checkout'])->name('branches.checkout');

Route::get('/orders', [OrderPagesController::class, 'index'])->middleware('auth')->name('orders.index');
Route::get('/orders/{order}', [OrderPagesController::class, 'show'])->name('orders.show');
Route::get('/orders/{order}/simulate-paid', [OrderPagesController::class, 'simulatePaid'])->name('orders.simulate-paid');

Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store']);

    Route::get('register', [RegisterController::class, 'create'])->name('register');
    Route::post('register', [RegisterController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::post('logout', LogoutController::class)->name('logout');
});
