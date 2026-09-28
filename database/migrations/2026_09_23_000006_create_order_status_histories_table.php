<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_status_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('order_vendor_group_id')->nullable()->constrained('order_vendor_groups')->nullOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reason')->nullable();
            $table->string('source')->nullable();
            $table->timestamps();
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_status_histories');
    }
};