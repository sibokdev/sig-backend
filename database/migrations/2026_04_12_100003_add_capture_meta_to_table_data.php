<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('table_data', function (Blueprint $table) {
            $table->unsignedBigInteger('captured_by')->nullable()->after('config_id');
            $table->decimal('lat', 10, 7)->nullable()->after('captured_by');
            $table->decimal('lng', 10, 7)->nullable()->after('lat');
            $table->foreign('captured_by')->references('iduserId')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('table_data', function (Blueprint $table) {
            $table->dropForeign(['captured_by']);
            $table->dropColumn(['captured_by', 'lat', 'lng']);
        });
    }
};
