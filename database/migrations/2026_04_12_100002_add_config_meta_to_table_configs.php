<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('table_configs', function (Blueprint $table) {
            $table->unsignedBigInteger('user_in_charge')->nullable()->after('table_name');
            $table->integer('goal')->nullable()->after('user_in_charge');
            $table->foreign('user_in_charge')->references('iduserId')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('table_configs', function (Blueprint $table) {
            $table->dropForeign(['user_in_charge']);
            $table->dropColumn(['user_in_charge', 'goal']);
        });
    }
};
