<?php

use App\Http\Controllers\ApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

/** PUBLIC API (no login), 100 records per page */
Route::controller(ApiController::class)->group(function () {
    Route::get('/users', 'users')->name('api.users');
    Route::get('/orders', 'orders')->name('api.orders');
});
