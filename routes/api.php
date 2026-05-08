<?php

use App\Http\Controllers\Api\BranchMenuController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PushSubscriptionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/branches/{branch}/menu', BranchMenuController::class)
    ->name('api.branches.menu');

Route::post('/orders', [OrderController::class, 'store'])
    ->middleware(['web', 'auth:web', 'throttle:30,1'])
    ->name('api.orders.store');
Route::get('/orders/{order}', [OrderController::class, 'show'])
    ->middleware(['web', 'can:view,order'])
    ->name('api.orders.show');

Route::get('/push/vapid-key', [PushSubscriptionController::class, 'vapidKey'])->name('api.push.vapid');
Route::post('/push/subscribe', [PushSubscriptionController::class, 'subscribe'])
    ->middleware('web')
    ->name('api.push.subscribe');
Route::delete('/push/subscribe', [PushSubscriptionController::class, 'unsubscribe'])
    ->middleware('web')
    ->name('api.push.unsubscribe');
