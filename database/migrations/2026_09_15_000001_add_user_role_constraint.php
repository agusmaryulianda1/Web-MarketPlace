<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'role')) {
                $table->string('role')->default('buyer')->index();
            }

            if (! Schema::hasColumn('users', 'phone')) {
                $table->string('phone')->nullable();
            }

            if (! Schema::hasColumn('users', 'avatar')) {
                $table->string('avatar')->nullable();
            }
        });

        DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role IN ('admin', 'vendor', 'buyer'))");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE users DROP CONSTRAINT users_role_check');
    }
};