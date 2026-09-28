<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_status_histories', function (Blueprint $table): void {
            $table->index('order_id');
            $table->index('order_vendor_group_id');
            $table->index('actor_user_id');
        });

        Schema::table('payment_status_histories', function (Blueprint $table): void {
            $table->index('order_id');
            $table->index('actor_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('order_status_histories', function (Blueprint $table): void {
            $table->dropIndex(['order_id']);
            $table->dropIndex(['order_vendor_group_id']);
            $table->dropIndex(['actor_user_id']);
        });

        Schema::table('payment_status_histories', function (Blueprint $table): void {
            $table->dropIndex(['order_id']);
            $table->dropIndex(['actor_user_id']);
        });
    }
};