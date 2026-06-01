<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('layer_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('layer_id');
            $table->unsignedBigInteger('user_id'); // always a national_admin
            $table->timestamps();

            $table->unique(['layer_id', 'user_id']);

            $table->foreign('layer_id')
                  ->references('idlayers')
                  ->on('layers')
                  ->cascadeOnDelete();

            $table->foreign('user_id')
                  ->references('iduserId')
                  ->on('users')
                  ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('layer_assignments');
    }
};
