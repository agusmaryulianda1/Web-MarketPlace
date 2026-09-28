<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('address_id')->nullable()->constrained('addresses')->nullOnDelete();
            $table->decimal('total_amount', 15, 2);
            $table->string('payment_method');
            $table->string('payment_status')->index();
            $table->string('order_status')->index();
            $table->text('shipping_address');
            $table->timestamps();
        });

        DB::statement('ALTER TABLE orders ADD CONSTRAINT orders_total_amount_check CHECK (total_amount >= 0)');
        DB::statement("ALTER TABLE orders ADD CONSTRAINT orders_payment_method_check CHECK (payment_method IN ('bank_transfer', 'cod'))");
        DB::statement("ALTER TABLE orders ADD CONSTRAINT orders_payment_status_check CHECK (payment_status IN ('pending', 'paid', 'failed'))");
        DB::statement("ALTER TABLE orders ADD CONSTRAINT orders_order_status_check CHECK (order_status IN ('pending', 'processing', 'shipped', 'completed', 'cancelled'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};