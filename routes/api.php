<?php

use App\Http\Controllers\Api\BranchMenuController;
use App\Http\Controllers\Api\OrderController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/branches/{branch}/menu', BranchMenuController::class)
    ->name('api.branches.menu');

Route::post('/orders', [OrderController::class, 'store'])->name('api.orders.store');
Route::get('/orders/{order}', [OrderController::class, 'show'])->name('api.orders.show');
