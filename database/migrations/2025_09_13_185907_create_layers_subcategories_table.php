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
        Schema::create('layers_subcategories', function (Blueprint $table) {
            $table->id();
            $table->string('name')
            $table->timestamps();
            // Define the foreign key constraint
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
        Schema::dropIfExists('layers_subcategories');
    }
};
