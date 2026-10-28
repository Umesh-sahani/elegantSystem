<?php

use App\Http\Controllers\ApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/** PUBLIC API (no login), 100 records per page */
Route::controller(ApiController::class)->group(function () {
    Route::get('/users', 'users')->name('api.users');
    Route::get('/orders', 'orders')->name('api.orders');
});
