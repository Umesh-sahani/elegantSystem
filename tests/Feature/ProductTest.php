<?php

use App\Models\Product;
use App\Models\User;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('product is created with a SKU made from its name', function () {
    $this->postJson(route('product.store'), [
        'name' => 'Wireless Mouse',
        'price' => '499.50',
        'stock_qty' => 10,
    ])->assertOk();

    $product = Product::first();
    expect($product->sku)->toBe('WIRELESS-MOUSE')
        ->and($product->price)->toBe('499.50')
        ->and($product->stock_qty)->toBe(10);
});

test('same product name cannot be added twice', function () {
    createProduct('RAM', '1500', 5);

    $this->postJson(route('product.store'), ['name' => 'RAM', 'price' => '1500', 'stock_qty' => 5])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['name', 'sku']);

    expect(Product::count())->toBe(1);
});

test('name in different case gives the same SKU, so it is blocked too', function () {
    createProduct('RAM', '1500', 5);

    // SKU is always uppercase, so "ram" -> RAM is blocked on every database
    $this->postJson(route('product.store'), ['name' => 'ram', 'price' => '1500', 'stock_qty' => 5])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['sku']);

    expect(Product::count())->toBe(1);
});

test('product and order lists show 25 records per page', function () {
    for ($i = 1; $i <= 26; $i++) {
        createProduct("Product $i", '10', 5);
        App\Models\Order::create(['name' => "Order $i", 'party_name' => 'P', 'status' => App\Models\Order::ACTIVE]);
    }

    expect($this->get(route('product.list'))->viewData('records')->count())->toBe(25)
        ->and($this->get(route('product.list', ['page' => 2]))->viewData('records')->count())->toBe(1)
        ->and($this->get(route('order.list'))->viewData('records')->count())->toBe(25)
        ->and($this->get(route('order.list', ['page' => 2]))->viewData('records')->count())->toBe(1);
});

test('price and stock cannot be negative', function () {
    $this->postJson(route('product.store'), ['name' => 'Bad', 'price' => -1, 'stock_qty' => -5])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['price', 'stock_qty']);

    expect(Product::count())->toBe(0);
});
