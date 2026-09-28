<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_vendor_groups', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('vendor_id')->constrained('vendors');
            $table->string('status')->index();
            $table->decimal('subtotal', 15, 2);
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();

            $table->unique(['order_id', 'vendor_id']);
            $table->index('order_id');
            $table->index('vendor_id');
        });

        DB::statement("ALTER TABLE order_vendor_groups ADD CONSTRAINT order_vendor_groups_status_check CHECK (status IN ('pending', 'processing', 'shipped', 'completed', 'cancelled'))");
        DB::statement('ALTER TABLE order_vendor_groups ADD CONSTRAINT order_vendor_groups_subtotal_check CHECK (subtotal >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('order_vendor_groups');
    }
};