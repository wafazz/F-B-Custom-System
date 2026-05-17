<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BillplzWebhookController;
use App\Http\Controllers\Api\BranchController;
use App\Http\Controllers\Api\BranchMenuController;
use App\Http\Controllers\Api\DeviceTokenController;
use App\Http\Controllers\Api\LoyaltyController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\PushSubscriptionController;
use App\Http\Controllers\Api\VoucherController;
use App\Http\Controllers\Api\WalletController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public endpoints
|--------------------------------------------------------------------------
*/

Route::get('/branches', [BranchController::class, 'index'])->name('api.branches.index');
Route::get('/branches/{branch}', [BranchController::class, 'show'])->name('api.branches.show');
Route::get('/branches/{branch}/menu', BranchMenuController::class)->name('api.branches.menu');

Route::post('/auth/register', [AuthController::class, 'register'])
    ->middleware('throttle:6,1')
    ->name('api.auth.register');
Route::post('/auth/login', [AuthController::class, 'login'])
    ->middleware('throttle:6,1')
    ->name('api.auth.login');

// Billplz server-to-server callback (signature-verified inside the controller)
Route::post('/billplz/webhook', [BillplzWebhookController::class, 'webhook'])
    ->name('billplz.webhook');

// Web Push (still used by the PWA storefront)
Route::get('/push/vapid-key', [PushSubscriptionController::class, 'vapidKey'])->name('api.push.vapid');
Route::post('/push/subscribe', [PushSubscriptionController::class, 'subscribe'])
    ->middleware('web')
    ->name('api.push.subscribe');
Route::delete('/push/subscribe', [PushSubscriptionController::class, 'unsubscribe'])
    ->middleware('web')
    ->name('api.push.unsubscribe');

/*
|--------------------------------------------------------------------------
| Order placement — Sanctum token (mobile) OR session cookie (web/PWA)
|--------------------------------------------------------------------------
*/

Route::post('/orders', [OrderController::class, 'store'])
    ->middleware(['auth:sanctum,web', 'throttle:30,1'])
    ->name('api.orders.store');

/*
|--------------------------------------------------------------------------
| Authenticated endpoints (Sanctum bearer tokens for native apps)
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', fn (Request $request) => $request->user());

    // Auth lifecycle
    Route::get('/auth/me', [AuthController::class, 'me'])->name('api.auth.me');
    Route::post('/auth/logout', [AuthController::class, 'logout'])->name('api.auth.logout');
    Route::post('/auth/logout-all', [AuthController::class, 'logoutAll'])->name('api.auth.logout-all');
    Route::post('/auth/password', [AuthController::class, 'changePassword'])
        ->middleware('throttle:5,1')
        ->name('api.auth.password');

    // Profile
    Route::get('/profile', [ProfileController::class, 'show'])->name('api.profile.show');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('api.profile.update');
    Route::get('/profile/data-export', [ProfileController::class, 'dataExport'])
        ->name('api.profile.data-export');
    Route::delete('/profile', [ProfileController::class, 'destroy'])
        ->middleware('throttle:3,60')
        ->name('api.profile.destroy');

    // Orders
    Route::get('/orders', [OrderController::class, 'index'])->name('api.orders.index');
    Route::get('/orders/{order}', [OrderController::class, 'show'])
        ->middleware('can:view,order')
        ->name('api.orders.show');
    Route::post('/orders/{order}/pay', [OrderController::class, 'payAgain'])
        ->middleware(['can:view,order', 'throttle:10,1'])
        ->name('api.orders.pay-again');

    // Loyalty
    Route::get('/loyalty', [LoyaltyController::class, 'show'])->name('api.loyalty.show');
    Route::get('/loyalty/history', [LoyaltyController::class, 'history'])
        ->name('api.loyalty.history');

    // Wallet
    Route::get('/wallet', [WalletController::class, 'show'])->name('api.wallet.show');
    Route::get('/wallet/transactions', [WalletController::class, 'transactions'])
        ->name('api.wallet.transactions');
    Route::post('/wallet/topup', [WalletController::class, 'topup'])
        ->middleware('throttle:10,1')
        ->name('api.wallet.topup');

    // Vouchers
    Route::get('/vouchers', [VoucherController::class, 'index'])->name('api.vouchers.index');
    Route::post('/vouchers/{voucher}/claim', [VoucherController::class, 'claim'])
        ->middleware('throttle:20,1')
        ->name('api.vouchers.claim');

    // Device tokens (FCM / APNS)
    Route::post('/devices', [DeviceTokenController::class, 'store'])->name('api.devices.store');
    Route::delete('/devices', [DeviceTokenController::class, 'destroy'])->name('api.devices.destroy');
});
