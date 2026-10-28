<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);                         // not unique: two products may share a name
            $table->string('sku', 50)->unique();                 // unique SKU, entered by the user
            $table->decimal('price', 12, 2)->unsigned();         // unsigned = cannot be negative
            $table->unsignedInteger('stock_qty')->default(0);    // unsigned = cannot be negative
            $table->foreignId('added_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
