<?php

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

// 1. Successful order creation
test('order is created, stock is reduced and total is calculated on the server', function () {
    $pen = createProduct('Pen', '10.50', 10);
    $book = createProduct('Notebook', '40.00', 5);

    $response = $this->postJson(route('order.store'), [
        'name' => 'Office supplies',
        'party_name' => 'ABC Traders',
        'total' => 1, // client total must be ignored
        'items' => [
            ['product_id' => $pen->id, 'quantity' => 2],
            ['product_id' => $book->id, 'quantity' => 1],
            ['product_id' => $pen->id, 'quantity' => 1], // same product again -> merged
        ],
    ]);

    $response->assertOk()->assertJson(['status' => true]);

    $order = Order::with('items')->first();
    expect($order->status)->toBe(Order::ACTIVE)
        ->and($order->order_no)->toBe('ORD-' . str_pad($order->id, 6, '0', STR_PAD_LEFT))
        ->and($order->total_amount)->toBe('71.50') // 3 x 10.50 + 1 x 40.00
        ->and($order->items)->toHaveCount(2);      // pen rows merged into one

    expect($order->items->firstWhere('product_id', $pen->id)->quantity)->toBe(3)
        ->and($pen->fresh()->stock_qty)->toBe(7)
        ->and($book->fresh()->stock_qty)->toBe(4);
});

test('live stock check counts all rows of the same product', function () {
    $pen = createProduct('Pen', '10.50', 5);

    // product chosen, qty not typed yet: just price and stock
    $this->getJson(route('order.check-stock', ['product_id' => $pen->id]))
        ->assertOk()
        ->assertJson(['available' => true, 'price' => '10.50', 'stock' => 5, 'line_total' => 0]);

    // one row of 3: fine, line total for this row only
    $this->getJson(route('order.check-stock', ['product_id' => $pen->id, 'quantity' => 3, 'total_quantity' => 3]))
        ->assertOk()
        ->assertJson(['available' => true, 'line_total' => 31.5]);

    // this row 3, but 7 in total across Pen rows: not enough
    $this->getJson(route('order.check-stock', ['product_id' => $pen->id, 'quantity' => 3, 'total_quantity' => 7]))
        ->assertOk()
        ->assertJson(['available' => false, 'message' => 'Only 5 Pen in stock. You already added 7 in total (all Pen rows).']);
});

// 2. Insufficient stock
test('order is rejected when stock is not enough', function () {
    $pen = createProduct('Pen', '10.00', 2);

    $response = $this->postJson(route('order.store'), [
        'name' => 'Big order',
        'party_name' => 'XYZ',
        'items' => [['product_id' => $pen->id, 'quantity' => 5]],
    ]);

    $response->assertStatus(422)->assertJsonPath('errors.items.0', 'Pen: only 2 in stock, you asked 5.');

    expect(Order::count())->toBe(0)
        ->and($pen->fresh()->stock_qty)->toBe(2);
});

// 3. Order cancellation and inventory restoration
test('cancelling an order restores its stock', function () {
    $pen = createProduct('Pen', '10.00', 10);
    $book = createProduct('Notebook', '40.00', 5);

    $this->postJson(route('order.store'), [
        'name' => 'A',
        'party_name' => 'P',
        'items' => [
            ['product_id' => $pen->id, 'quantity' => 4],
            ['product_id' => $book->id, 'quantity' => 2],
        ],
    ])->assertOk();

    expect($pen->fresh()->stock_qty)->toBe(6);

    $order = Order::first();
    $this->putJson(route('order.cancel', $order->id))->assertOk();

    expect($order->fresh()->status)->toBe(Order::CANCELLED)
        ->and($pen->fresh()->stock_qty)->toBe(10)
        ->and($book->fresh()->stock_qty)->toBe(5);
});

// 4. Preventing duplicate inventory restoration
test('a cancelled order cannot be cancelled again, so stock is restored only once', function () {
    $pen = createProduct('Pen', '10.00', 10);

    $this->postJson(route('order.store'), [
        'name' => 'A',
        'party_name' => 'P',
        'items' => [['product_id' => $pen->id, 'quantity' => 3]],
    ])->assertOk();

    $order = Order::first();

    $this->putJson(route('order.cancel', $order->id))->assertOk();
    $this->putJson(route('order.cancel', $order->id))
        ->assertStatus(422)
        ->assertJson(['message' => 'Order is already cancelled.']);

    expect($pen->fresh()->stock_qty)->toBe(10); // not 13
});

test('a completed order cannot be cancelled', function () {
    $pen = createProduct('Pen', '10.00', 10);

    $this->postJson(route('order.store'), [
        'name' => 'A',
        'party_name' => 'P',
        'items' => [['product_id' => $pen->id, 'quantity' => 3]],
    ])->assertOk();

    $order = Order::first();
    $this->putJson(route('order.complete', $order->id))->assertOk();

    $this->putJson(route('order.cancel', $order->id))
        ->assertStatus(422)
        ->assertJson(['message' => 'Completed order cannot be cancelled.']);

    expect($pen->fresh()->stock_qty)->toBe(7);
});

// Completed and Cancelled are final. The buttons are disabled in the UI,
// and even if someone enables them with browser inspect, the server refuses.
test('a completed or cancelled order cannot be completed or cancelled again', function (int $finalStatus) {
    $pen = createProduct('Pen', '10.00', 10);

    $this->postJson(route('order.store'), [
        'name' => 'A',
        'party_name' => 'P',
        'items' => [['product_id' => $pen->id, 'quantity' => 2]],
    ])->assertOk();

    $order = Order::first();
    $route = $finalStatus == Order::COMPLETED ? 'order.complete' : 'order.cancel';
    $this->putJson(route($route, $order->id))->assertOk();
    $stock = $pen->fresh()->stock_qty;

    $this->putJson(route('order.complete', $order->id))->assertStatus(422);
    $this->putJson(route('order.cancel', $order->id))->assertStatus(422);

    expect($order->fresh()->status)->toBe($finalStatus)
        ->and($pen->fresh()->stock_qty)->toBe($stock); // stock not restored twice
})->with([
    'completed' => [Order::COMPLETED],
    'cancelled' => [Order::CANCELLED],
]);

test('completing or cancelling an order that does not exist returns 404', function () {
    $this->putJson(route('order.cancel', 9999))->assertNotFound();
    $this->putJson(route('order.complete', 9999))->assertNotFound();
});

// 5. A failed order leaves no partial database changes
test('if one product is short, nothing is saved and no stock changes', function () {
    $pen = createProduct('Pen', '10.00', 10);   // enough
    $book = createProduct('Notebook', '40.00', 1); // short

    $this->postJson(route('order.store'), [
        'name' => 'Mixed',
        'party_name' => 'P',
        'items' => [
            ['product_id' => $pen->id, 'quantity' => 5],
            ['product_id' => $book->id, 'quantity' => 3],
        ],
    ])->assertStatus(422);

    expect(Order::count())->toBe(0)
        ->and(OrderItem::count())->toBe(0)
        ->and($pen->fresh()->stock_qty)->toBe(10)
        ->and($book->fresh()->stock_qty)->toBe(1);
});

test('if saving fails half way, the transaction rolls everything back', function () {
    $pen = createProduct('Pen', '10.00', 10);
    $book = createProduct('Notebook', '40.00', 10);

    // simulate a DB failure while saving the 2nd item (1st item + its stock change already done)
    $saved = 0;
    OrderItem::creating(function () use (&$saved) {
        if (++$saved === 2) {
            throw new RuntimeException('Simulated failure');
        }
    });

    $this->postJson(route('order.store'), [
        'name' => 'Half way',
        'party_name' => 'P',
        'items' => [
            ['product_id' => $pen->id, 'quantity' => 2],
            ['product_id' => $book->id, 'quantity' => 2],
        ],
    ])
        ->assertStatus(500)
        ->assertJson(['message' => 'Something went wrong, please try again.'])
        ->assertDontSee('Simulated failure'); // real error is logged, never sent to the user

    OrderItem::flushEventListeners();

    expect(Order::count())->toBe(0)
        ->and(OrderItem::count())->toBe(0)
        ->and($pen->fresh()->stock_qty)->toBe(10)
        ->and($book->fresh()->stock_qty)->toBe(10);
});
