<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products');
            $table->foreignId('vendor_id')->constrained('vendors');
            $table->string('product_name');
            $table->decimal('price', 15, 2);
            $table->unsignedInteger('quantity');
            $table->decimal('subtotal', 15, 2);
            $table->timestamps();
        });

        DB::statement('ALTER TABLE order_items ADD CONSTRAINT order_items_price_check CHECK (price >= 0)');
        DB::statement('ALTER TABLE order_items ADD CONSTRAINT order_items_quantity_check CHECK (quantity > 0)');
        DB::statement('ALTER TABLE order_items ADD CONSTRAINT order_items_subtotal_check CHECK (subtotal >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};