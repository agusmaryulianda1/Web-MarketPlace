<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users');
            $table->string('status')->index();
            $table->timestamps();
        });

        DB::statement("ALTER TABLE vendors ADD CONSTRAINT vendors_status_check CHECK (status IN ('active', 'inactive'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('vendors');
    }
};