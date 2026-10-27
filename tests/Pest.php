<?php

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

// every feature test gets a fresh in-memory SQLite database (see phpunit.xml)
pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

// small helper so tests read like the requirement
function createProduct(string $name, $price, int $stock): Product
{
    return Product::create([
        'name' => $name,
        'sku' => Str::upper(Str::slug($name)),
        'price' => $price,
        'stock_qty' => $stock,
    ]);
}
