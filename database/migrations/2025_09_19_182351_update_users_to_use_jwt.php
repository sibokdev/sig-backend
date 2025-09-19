<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Aseguramos que email sea único (JWT Auth trabaja muy bien con email)
            if (!Schema::hasColumn('users', 'email')) {
                $table->string('email', 100)->unique()->after('username');
            }

            // Cambiamos pwd a password si no existe ya
            if (Schema::hasColumn('users', 'pwd')) {
                $table->renameColumn('pwd', 'password');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'email')) {
                $table->dropColumn('email');
            }
            if (Schema::hasColumn('users', 'password')) {
                $table->renameColumn('password', 'pwd');
            }
        });
    }
};
