<?php

use App\Models\Product;
use App\Models\User;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('product is created with the SKU the user entered', function () {
    $this->postJson(route('product.store'), [
        'name' => 'Wireless Mouse',
        'sku' => '  wm-black-01 ', // trimmed and saved in upper case
        'price' => '499.50',
        'stock_qty' => 10,
    ])->assertOk();

    $product = Product::first();
    expect($product->sku)->toBe('WM-BLACK-01')
        ->and($product->name)->toBe('Wireless Mouse')
        ->and($product->price)->toBe('499.50')
        ->and($product->stock_qty)->toBe(10);
});

test('SKU is required', function () {
    $this->postJson(route('product.store'), ['name' => 'Pen', 'price' => '10', 'stock_qty' => 5])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['sku']);

    expect(Product::count())->toBe(0);
});

test('duplicate SKU is rejected on create, in any case', function () {
    createProduct('Pen', '10', 5, 'PEN-01');

    // same SKU in lower case counts as the same SKU
    $this->postJson(route('product.store'), ['name' => 'Pencil', 'sku' => 'pen-01', 'price' => '5', 'stock_qty' => 5])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['sku' => 'This SKU already exists.']);

    expect(Product::count())->toBe(1);
});

test('two products may share a name when their SKUs differ', function () {
    createProduct('Pen', '10', 5, 'PEN-BLUE-01');

    $this->postJson(route('product.store'), ['name' => 'Pen', 'sku' => 'PEN-BLUE-02', 'price' => '12', 'stock_qty' => 3])
        ->assertOk();

    expect(Product::where('name', 'Pen')->count())->toBe(2)
        ->and(Product::pluck('sku')->all())->toBe(['PEN-BLUE-01', 'PEN-BLUE-02']);
});

test('update keeps its own SKU but cannot take the SKU of another product', function () {
    $pen = createProduct('Pen', '10', 5, 'PEN-01');
    $book = createProduct('Notebook', '40', 2, 'NB-01');

    // same SKU as before: allowed (own row is ignored by the unique rule)
    $this->putJson(route('product.update', $pen->id), ['name' => 'Pen Blue', 'sku' => 'PEN-01', 'price' => '11', 'stock_qty' => 5])
        ->assertOk();
    expect($pen->fresh()->name)->toBe('Pen Blue')
        ->and($pen->fresh()->sku)->toBe('PEN-01');

    // SKU of another product: rejected, nothing changed
    $this->putJson(route('product.update', $pen->id), ['name' => 'Pen Blue', 'sku' => 'nb-01', 'price' => '11', 'stock_qty' => 5])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['sku' => 'This SKU already exists.']);
    expect($pen->fresh()->sku)->toBe('PEN-01')
        ->and($book->fresh()->sku)->toBe('NB-01');
});

test('order form lists same-name products with their SKU and stock', function () {
    createProduct('Pen', '10', 5, 'PEN-BLUE-01');
    createProduct('Pen', '12', 0, 'PEN-BLUE-02');

    $this->get(route('order.create'))
        ->assertOk()
        ->assertSee('Pen (PEN-BLUE-01) - Stock: 5')
        ->assertSee('Pen (PEN-BLUE-02) - Stock: 0');
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
    $this->postJson(route('product.store'), ['name' => 'Bad', 'sku' => 'BAD-01', 'price' => -1, 'stock_qty' => -5])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['price', 'stock_qty']);

    expect(Product::count())->toBe(0);
});
