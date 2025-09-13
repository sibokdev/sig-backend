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
        Schema::table('layers', function (Blueprint $table) {
            $table->foreign('id_category')
                    ->references('id') // The primary key column of the referenced table
                    ->on('layers_categories') // The table being referenced
                    ->onDelete('cascade'); // Optional: specify action on parent deletion (e.g., 'cascade', 'set null', 'restrict')
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('layers', function (Blueprint $table) {
            $table->('id_category')->nullable();
        });
    }
};
