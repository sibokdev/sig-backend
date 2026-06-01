<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('table_configs', function (Blueprint $table) {
            $table->unsignedBigInteger('national_admin_id')->nullable()->after('user_in_charge');
            $table->foreign('national_admin_id')
                  ->references('iduserId')->on('users')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('table_configs', function (Blueprint $table) {
            $table->dropForeign(['national_admin_id']);
            $table->dropColumn('national_admin_id');
        });
    }
};
