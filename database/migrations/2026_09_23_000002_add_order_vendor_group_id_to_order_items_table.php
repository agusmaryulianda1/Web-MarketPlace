<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table): void {
            $table->foreignId('order_vendor_group_id')->nullable()->after('vendor_id');
            $table->index('order_vendor_group_id');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table): void {
            $table->dropIndex(['order_vendor_group_id']);
            $table->dropColumn('order_vendor_group_id');
        });
    }
};