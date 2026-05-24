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

// PWA install + active-device tracking
Route::post('/pwa/installed', [\App\Http\Controllers\Api\PwaInstallController::class, 'record'])
    ->middleware('web')
    ->name('api.pwa.installed');
Route::post('/pwa/heartbeat', [\App\Http\Controllers\Api\PwaInstallController::class, 'heartbeat'])
    ->middleware('web')
    ->name('api.pwa.heartbeat');

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

/*
|--------------------------------------------------------------------------
| Native POS (React Native app) — Sanctum-token authed
|
| Token name is `pos:{branch_code}` and the `pos.token` middleware
| enforces that any {branch} URL binding matches that scope. Endpoints
| that don't take {branch} (orders, shifts, pickups) check the resource
| owner's branch against the token's branch instead.
|--------------------------------------------------------------------------
*/
Route::post('/pos/login', [\App\Http\Controllers\Api\PosApiController::class, 'login'])
    ->middleware('throttle:10,1')
    ->name('api.pos.login');

Route::middleware(['auth:sanctum', 'pos.token'])->group(function () {
    // Session
    Route::post('/pos/logout', [\App\Http\Controllers\Api\PosApiController::class, 'logout'])->name('api.pos.logout');
    Route::get('/pos/me', [\App\Http\Controllers\Api\PosApiController::class, 'me'])->name('api.pos.me');

    // Queue + orders
    Route::get('/pos/branches/{branch}/queue', [\App\Http\Controllers\Api\PosApiController::class, 'queue'])->name('api.pos.queue');
    Route::get('/pos/branches/{branch}/recent', [\App\Http\Controllers\Api\PosApiController::class, 'recent'])->name('api.pos.recent');
    Route::post('/pos/orders/{order}/transition', [\App\Http\Controllers\Api\PosApiController::class, 'transition'])->name('api.pos.transition');
    Route::post('/pos/orders/{order}/print', [\App\Http\Controllers\Api\PosApiController::class, 'print'])->name('api.pos.print');
    Route::get('/pos/orders/{order}/receipt', [\App\Http\Controllers\Api\PosApiController::class, 'receipt'])->name('api.pos.receipt');
    Route::patch('/pos/orders/{order}/notes', [\App\Http\Controllers\Api\PosApiController::class, 'updateNotes'])->name('api.pos.orders.notes');
    Route::patch('/pos/orders/{order}/items/{item}/notes', [\App\Http\Controllers\Api\PosApiController::class, 'updateItemNotes'])->name('api.pos.orders.items.notes');

    // Walk-in
    Route::get('/pos/branches/{branch}/menu', [\App\Http\Controllers\Api\Pos\WalkInController::class, 'menu'])->name('api.pos.menu');
    Route::get('/pos/branches/{branch}/customers/search', [\App\Http\Controllers\Api\Pos\WalkInController::class, 'searchCustomers'])->name('api.pos.customers.search');
    Route::post('/pos/branches/{branch}/walk-in', [\App\Http\Controllers\Api\Pos\WalkInController::class, 'store'])
        ->middleware('throttle:60,1')
        ->name('api.pos.walk-in');
    Route::post('/pos/branches/{branch}/walk-in/voucher-preview', [\App\Http\Controllers\Api\Pos\WalkInController::class, 'voucherPreview'])
        ->middleware('throttle:120,1')
        ->name('api.pos.walk-in.voucher-preview');

    // Shift
    Route::get('/pos/branches/{branch}/shift', [\App\Http\Controllers\Api\Pos\ShiftController::class, 'current'])->name('api.pos.shift.current');
    Route::post('/pos/branches/{branch}/shift/open', [\App\Http\Controllers\Api\Pos\ShiftController::class, 'open'])->name('api.pos.shift.open');
    Route::post('/pos/shifts/{shift}/close', [\App\Http\Controllers\Api\Pos\ShiftController::class, 'close'])->name('api.pos.shift.close');
    Route::post('/pos/shifts/{shift}/movements', [\App\Http\Controllers\Api\Pos\ShiftController::class, 'recordMovement'])->name('api.pos.shift.movements');
    Route::get('/pos/shifts/{shift}', [\App\Http\Controllers\Api\Pos\ShiftController::class, 'report'])->name('api.pos.shift.report');

    // Stock
    Route::get('/pos/branches/{branch}/stock', [\App\Http\Controllers\Api\Pos\StockController::class, 'index'])->name('api.pos.stock.index');
    Route::post('/pos/branches/{branch}/stock/{product}/toggle', [\App\Http\Controllers\Api\Pos\StockController::class, 'toggle'])->name('api.pos.stock.toggle');

    // Push notification device registry (FCM tokens)
    Route::post('/pos/devices', [\App\Http\Controllers\Api\Pos\DeviceController::class, 'store'])->name('api.pos.devices.store');
    Route::delete('/pos/devices/{token}', [\App\Http\Controllers\Api\Pos\DeviceController::class, 'destroy'])
        ->where('token', '.*')
        ->name('api.pos.devices.destroy');

    // Reward pickups (cross-branch, scoped by pickup code)
    Route::get('/pos/reward-pickups', [\App\Http\Controllers\Api\Pos\RewardPickupController::class, 'index'])->name('api.pos.reward-pickups.index');
    Route::get('/pos/reward-pickups/lookup', [\App\Http\Controllers\Api\Pos\RewardPickupController::class, 'lookup'])->name('api.pos.reward-pickups.lookup');
    Route::post('/pos/reward-pickups/{pickup}/fulfil', [\App\Http\Controllers\Api\Pos\RewardPickupController::class, 'fulfil'])->name('api.pos.reward-pickups.fulfil');
});
