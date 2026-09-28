<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE orders DROP CONSTRAINT orders_order_status_check');
        DB::statement("ALTER TABLE orders ADD CONSTRAINT orders_order_status_check CHECK (order_status IN ('pending', 'processing', 'shipped', 'completed', 'cancelled', 'partially_cancelled'))");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement("UPDATE orders SET order_status = 'cancelled' WHERE order_status = 'partially_cancelled'");
        DB::statement('ALTER TABLE orders DROP CONSTRAINT orders_order_status_check');
        DB::statement("ALTER TABLE orders ADD CONSTRAINT orders_order_status_check CHECK (order_status IN ('pending', 'processing', 'shipped', 'completed', 'cancelled'))");
    }
};