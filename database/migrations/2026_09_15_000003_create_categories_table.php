<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('status')->index();
            $table->timestamps();
        });

        DB::statement("ALTER TABLE categories ADD CONSTRAINT categories_status_check CHECK (status IN ('active', 'inactive'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};