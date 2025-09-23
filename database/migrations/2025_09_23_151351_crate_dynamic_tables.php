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
        // migration for table_configs
        Schema::create('table_configs', function (Blueprint $table) {
            $table->id();
            $table->string('table_name');
            $table->json('config'); // guarda definición de columnas
            $table->timestamps();
        });

        // migration for table_data
        Schema::create('table_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('config_id')->constrained('table_configs')->onDelete('cascade');
            $table->json('data'); // guarda los registros
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('table_configs');
        Schema::dropIfExists('table_data');
    }
};
