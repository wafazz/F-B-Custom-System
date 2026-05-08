<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Web\AccountController;
use App\Http\Controllers\Pos\PinLoginController;
use App\Http\Controllers\Pos\QueueController as PosQueueController;
use App\Http\Controllers\Pos\StockController as PosStockController;
use App\Http\Controllers\Pos\WalkInController;
use App\Http\Controllers\Web\DisplayController;
use App\Http\Controllers\Web\InfoPagesController;
use App\Http\Controllers\Web\LoyaltyController;
use App\Http\Controllers\Web\OrderPagesController;
use App\Http\Controllers\Web\ReferralController;
use App\Http\Controllers\Web\StorefrontController;
use Illuminate\Support\Facades\Route;

Route::get('/', [StorefrontController::class, 'splash'])->name('home');
Route::get('/branches', [StorefrontController::class, 'selectBranch'])->name('branches.select');
Route::get('/branches/{branch}/menu', [StorefrontController::class, 'menu'])->name('branches.menu');
Route::get('/branches/{branch}/cart', [OrderPagesController::class, 'cart'])->name('branches.cart');
Route::get('/branches/{branch}/checkout', [OrderPagesController::class, 'checkout'])->name('branches.checkout');

Route::get('/orders', [OrderPagesController::class, 'index'])->middleware('auth')->name('orders.index');
Route::get('/orders/{order}', [OrderPagesController::class, 'show'])->middleware('can:view,order')->name('orders.show');
Route::get('/orders/{order}/simulate-paid', [OrderPagesController::class, 'simulatePaid'])->name('orders.simulate-paid');

Route::get('/loyalty', [LoyaltyController::class, 'show'])->middleware('auth')->name('loyalty');
Route::get('/referral', [ReferralController::class, 'show'])->middleware('auth')->name('referral');

Route::get('/terms', [InfoPagesController::class, 'terms'])->name('info.terms');
Route::get('/privacy', [InfoPagesController::class, 'privacy'])->name('info.privacy');
Route::get('/faq', [InfoPagesController::class, 'faq'])->name('info.faq');

Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store'])->middleware('throttle:6,1');

    Route::get('register', [RegisterController::class, 'create'])->name('register');
    Route::post('register', [RegisterController::class, 'store'])->middleware('throttle:6,1');
});

Route::middleware('auth')->group(function () {
    Route::post('logout', LogoutController::class)->name('logout');
    Route::get('account/data-export', [AccountController::class, 'dataExport'])->name('account.data-export');
    Route::delete('account', [AccountController::class, 'destroy'])->middleware('throttle:3,60')->name('account.destroy');
});

// POS (Branch staff)
Route::prefix('pos')->name('pos.')->group(function () {
    Route::get('login', [PinLoginController::class, 'show'])->name('login');
    Route::post('login', [PinLoginController::class, 'store'])->middleware('throttle:5,1')->name('login.store');
    Route::post('logout', [PinLoginController::class, 'destroy'])->name('logout');

    Route::middleware('pos')->group(function () {
        Route::get('/', [PosQueueController::class, 'index'])->name('queue');
        Route::post('orders/{order}/transition', [PosQueueController::class, 'transition'])->name('orders.transition');
        Route::get('stock', [PosStockController::class, 'index'])->name('stock');
        Route::post('stock/{product}/toggle', [PosStockController::class, 'toggle'])->name('stock.toggle');
        Route::get('walk-in', [WalkInController::class, 'index'])->name('walk-in');
        Route::post('walk-in', [WalkInController::class, 'store'])->name('walk-in.store');
    });
});

// TV Display (token-authed, no user session)
Route::get('branch/{branch}/display', [DisplayController::class, 'show'])->name('display.show');
Route::post('branch/{branch}/display/heartbeat', [DisplayController::class, 'heartbeat'])->name('display.heartbeat');
