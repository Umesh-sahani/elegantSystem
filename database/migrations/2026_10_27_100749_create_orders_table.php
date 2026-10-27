<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // status: 1 = Active (new order), 2 = Completed, 3 = Cancelled
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_no', 30)->nullable()->unique();
            $table->string('name', 150);
            $table->string('party_name', 150);
            $table->decimal('total_amount', 14, 2)->unsigned()->default(0); // unsigned = cannot be negative
            $table->unsignedTinyInteger('status')->default(1);
            $table->foreignId('added_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
