<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Step 1: expand ENUM to include field_workforce alongside existing roles
        DB::statement("ALTER TABLE users MODIFY role ENUM(
            'admin','national_admin','estatal_admin','municipal_admin','seccion_admin','field_workforce'
        ) NOT NULL DEFAULT 'seccion_admin'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE users MODIFY role ENUM(
            'admin','national_admin','estatal_admin','municipal_admin','seccion_admin'
        ) NOT NULL DEFAULT 'seccion_admin'");
    }
};
