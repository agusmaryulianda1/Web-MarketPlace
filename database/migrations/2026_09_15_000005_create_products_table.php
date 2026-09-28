<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('vendors');
            $table->foreignId('category_id')->constrained('categories');
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('price', 15, 2);
            $table->integer('stock');
            $table->string('status')->index();
            $table->timestamps();
        });

        DB::statement('ALTER TABLE products ADD CONSTRAINT products_price_check CHECK (price >= 0)');
        DB::statement('ALTER TABLE products ADD CONSTRAINT products_stock_check CHECK (stock >= 0)');
        DB::statement("ALTER TABLE products ADD CONSTRAINT products_status_check CHECK (status IN ('active', 'inactive'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};