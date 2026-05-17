<?php

use App\Http\Controllers\Api\BillplzWebhookController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Pos\PinLoginController;
use App\Http\Controllers\Pos\QueueController as PosQueueController;
use App\Http\Controllers\Pos\ShiftController as PosShiftController;
use App\Http\Controllers\Pos\StockController as PosStockController;
use App\Http\Controllers\Pos\WalkInController;
use App\Http\Controllers\Web\AccountController;
use App\Http\Controllers\Web\DisplayController;
use App\Http\Controllers\Web\InfoPagesController;
use App\Http\Controllers\Web\LoyaltyController;
use App\Http\Controllers\Web\OrderPagesController;
use App\Http\Controllers\Web\ProfileController;
use App\Http\Controllers\Web\ReferralController;
use App\Http\Controllers\Web\StorefrontController;
use App\Http\Controllers\Web\VoucherClaimController;
use App\Http\Controllers\Web\WalletController;
use Illuminate\Support\Facades\Route;

Route::get('/', [StorefrontController::class, 'splash'])->name('home');
Route::get('/branches', [StorefrontController::class, 'selectBranch'])->name('branches.select');
Route::get('/branches/{branch}', [StorefrontController::class, 'branchHome'])->name('branches.home');
Route::get('/branches/{branch}/menu', [StorefrontController::class, 'menu'])->name('branches.menu');
Route::get('/branches/{branch}/cart', [OrderPagesController::class, 'cart'])->name('branches.cart');
Route::get('/branches/{branch}/checkout', [OrderPagesController::class, 'checkout'])
    ->middleware('auth')
    ->name('branches.checkout');

Route::get('/orders', [OrderPagesController::class, 'index'])->middleware('auth')->name('orders.index');
Route::get('/orders/{order}', [OrderPagesController::class, 'show'])->middleware('can:view,order')->name('orders.show');
Route::post('/orders/{order}/pay', [OrderPagesController::class, 'payAgain'])
    ->middleware(['auth', 'can:view,order', 'throttle:10,1'])
    ->name('orders.pay-again');
Route::get('/orders/{order}/simulate-paid', [OrderPagesController::class, 'simulatePaid'])->name('orders.simulate-paid');

// Billplz hosted-page redirect (customer's browser, not server-to-server)
Route::get('/payments/billplz/return/{order}', [BillplzWebhookController::class, 'return'])
    ->name('billplz.return');

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
    Route::get('profile', [ProfileController::class, 'show'])->name('profile');
    Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('profile/password', [ProfileController::class, 'updatePassword'])
        ->middleware('throttle:5,1')
        ->name('profile.password');
    Route::get('account/data-export', [AccountController::class, 'dataExport'])->name('account.data-export');
    Route::delete('account', [AccountController::class, 'destroy'])->middleware('throttle:3,60')->name('account.destroy');

    Route::get('wallet', [WalletController::class, 'show'])->name('wallet');
    Route::post('wallet/topup', [WalletController::class, 'topup'])
        ->middleware('throttle:10,1')
        ->name('wallet.topup');
    Route::get('wallet/topup/{topup}/return', [WalletController::class, 'topupReturn'])
        ->name('wallet.topup-return');

    Route::get('vouchers', [VoucherClaimController::class, 'index'])->name('vouchers.index');
    Route::post('vouchers/{voucher}/claim', [VoucherClaimController::class, 'store'])
        ->middleware('throttle:20,1')
        ->name('vouchers.claim');
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
        Route::get('customers/search', [WalkInController::class, 'searchCustomers'])->name('customers.search');
        Route::get('customer-display', [WalkInController::class, 'customerDisplay'])->name('customer-display');

        Route::get('shift', [PosShiftController::class, 'index'])->name('shift');
        Route::post('shift/open', [PosShiftController::class, 'open'])->name('shift.open');
        Route::post('shift/{shift}/close', [PosShiftController::class, 'close'])->name('shift.close');
        Route::post('shift/{shift}/movements', [PosShiftController::class, 'recordMovement'])->name('shift.movements');
        Route::get('shift/{shift}/report', [PosShiftController::class, 'report'])->name('shift.report');
    });
});

// TV Display (token-authed, no user session)
Route::get('branch/{branch}/display', [DisplayController::class, 'show'])->name('display.show');
Route::post('branch/{branch}/display/heartbeat', [DisplayController::class, 'heartbeat'])->name('display.heartbeat');

// PWA: serve manifest + service worker from origin root so they get full scope
Route::get('/manifest.webmanifest', function () {
    return response()->file(public_path('build/manifest.webmanifest'), [
        'Content-Type' => 'application/manifest+json',
        'Cache-Control' => 'public, max-age=3600',
    ]);
})->name('pwa.manifest');

Route::get('/sw.js', function () {
    return response()->file(public_path('build/sw.js'), [
        'Content-Type' => 'application/javascript',
        'Service-Worker-Allowed' => '/',
        'Cache-Control' => 'no-cache',
    ]);
})->name('pwa.sw');
