<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('geo_states', function (Blueprint $table) {
            $table->json('geometry')->nullable()->change();
        });

        Schema::table('geo_municipalities', function (Blueprint $table) {
            $table->json('geometry')->nullable()->change();
        });

        Schema::table('geo_sections', function (Blueprint $table) {
            $table->json('geometry')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('geo_states', function (Blueprint $table) {
            $table->json('geometry')->nullable(false)->change();
        });

        Schema::table('geo_municipalities', function (Blueprint $table) {
            $table->json('geometry')->nullable(false)->change();
        });

        Schema::table('geo_sections', function (Blueprint $table) {
            $table->json('geometry')->nullable(false)->change();
        });
    }
};
