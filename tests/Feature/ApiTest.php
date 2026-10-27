<?php

use App\Models\Order;
use App\Models\User;

// no actingAs(): these endpoints must work without login

test('users api is public, paginated by 100 and hides password', function () {
    User::factory()->count(101)->create();

    $this->getJson(route('api.users'))
        ->assertOk()
        ->assertJsonPath('per_page', 100)
        ->assertJsonPath('total', 101)
        ->assertJsonCount(100, 'data')
        ->assertJsonMissingPath('data.0.password')
        ->assertJsonMissingPath('data.0.remember_token');

    $this->getJson(route('api.users', ['page' => 2]))
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

test('orders api is public, paginated by 100 and includes items', function () {
    $pen = createProduct('Pen', '10.50', 1000);

    for ($i = 1; $i <= 101; $i++) {
        $order = Order::create(['name' => "Order $i", 'party_name' => 'P', 'total_amount' => 21, 'status' => Order::ACTIVE]);
        $order->items()->create(['product_id' => $pen->id, 'quantity' => 2, 'unit_price' => '10.50']);
    }

    $this->getJson(route('api.orders'))
        ->assertOk()
        ->assertJsonPath('per_page', 100)
        ->assertJsonPath('total', 101)
        ->assertJsonCount(100, 'data')
        ->assertJsonPath('data.0.status', 'Active')
        ->assertJsonPath('data.0.items.0.product', 'Pen')
        ->assertJsonPath('data.0.items.0.line_total', '21.00');

    $this->getJson(route('api.orders', ['page' => 2]))->assertJsonCount(1, 'data');
});

test('orders api can filter by status', function () {
    Order::create(['name' => 'A', 'party_name' => 'P', 'status' => Order::ACTIVE]);
    Order::create(['name' => 'B', 'party_name' => 'P', 'status' => Order::CANCELLED]);

    $this->getJson(route('api.orders', ['status' => Order::CANCELLED]))
        ->assertOk()
        ->assertJsonPath('total', 1)
        ->assertJsonPath('data.0.status', 'Cancelled');
});
