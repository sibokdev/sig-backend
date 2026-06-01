<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('national_admin_id')->nullable()->after('cve_mun');
            $table->char('cve_seccion', 4)->nullable()->after('national_admin_id');

            $table->foreign('national_admin_id')
                  ->references('iduserId')
                  ->on('users')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['national_admin_id']);
            $table->dropColumn(['national_admin_id', 'cve_seccion']);
        });
    }
};
