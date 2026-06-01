<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIndicadoresCatalogTable extends Migration
{
    public function up()
    {
        Schema::create('indicadores_catalog', function (Blueprint $table) {
            $table->id();
            $table->string('clave', 30)->unique();   // INEGI indicator ID e.g. "1002000001"
            $table->string('nombre', 200);            // Display name
            $table->string('categoria', 80);          // e.g. "Demografía"
            $table->string('unidad', 80)->nullable(); // e.g. "Personas", "Porcentaje"
            $table->string('fuente', 10)->default('BISE'); // BISE or BIE
            $table->text('descripcion')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('indicadores_catalog');
    }
}
