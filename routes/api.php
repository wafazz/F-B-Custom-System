<?php

use App\Http\Controllers\Api\BranchMenuController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/branches/{branch}/menu', BranchMenuController::class)
    ->name('api.branches.menu');
