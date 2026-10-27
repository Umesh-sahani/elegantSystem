<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

// redirect to login page
Route::redirect('/', '/login');

// Guest routes
Route::middleware(['guest'])->group(function () {
    Route::get('/login', [LoginController::class, 'index'])->name('login');
    Route::post('/authenticate', [LoginController::class, 'authenticate'])->name('login.authenticate');
});

// Protected routes
Route::middleware(['auth'])->group(function () {

    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [HomeController::class, 'dashboard'])->name('dashboard');

    /** PRODUCT ROUTES START */
    Route::controller(ProductController::class)->group(function () {
        Route::get('/product-list', 'index')->name('product.list');
        Route::get('/product-create', 'create')->name('product.create');
        Route::post('/product-store', 'store')->name('product.store');
        Route::get('/product-edit/{id}/edit', 'edit')->name('product.edit');
        Route::put('/product-update/{id}', 'update')->name('product.update');
    });
    /** PRODUCT ROUTES END */

    /** ORDER ROUTES START */
    Route::controller(OrderController::class)->group(function () {
        Route::get('/order-list', 'index')->name('order.list');
        Route::get('/order-create', 'create')->name('order.create');
        Route::get('/order-check-stock', 'checkStock')->name('order.check-stock');
        Route::post('/order-store', 'store')->name('order.store');
        Route::get('/order-show/{id}', 'show')->name('order.show');
        Route::put('/order-complete/{id}', 'complete')->name('order.complete');
        Route::put('/order-cancel/{id}', 'cancel')->name('order.cancel');
    });
    /** ORDER ROUTES END */
});
