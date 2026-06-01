<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Step 1: expand ENUM to include both old and new values so the UPDATE can run
        DB::statement("ALTER TABLE users MODIFY role ENUM(
            'admin','user','national_admin','estatal_admin','municipal_admin','seccion_admin'
        ) NOT NULL DEFAULT 'user'");

        // Step 2: map legacy 'user' role to 'seccion_admin'
        DB::statement("UPDATE users SET role = 'seccion_admin' WHERE role = 'user'");

        // Step 3: lock ENUM to only the new valid roles
        DB::statement("ALTER TABLE users MODIFY role ENUM(
            'admin','national_admin','estatal_admin','municipal_admin','seccion_admin'
        ) NOT NULL DEFAULT 'seccion_admin'");

        Schema::table('users', function (Blueprint $table) {
            $table->string('cve_ent', 6)->nullable()->after('role');
            $table->string('cve_mun', 9)->nullable()->after('cve_ent');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['cve_ent', 'cve_mun']);
        });

        DB::statement("ALTER TABLE users MODIFY role ENUM('admin','user') NOT NULL DEFAULT 'user'");
    }
};
