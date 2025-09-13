<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
class CreateSectionTable extends Migration
{
    public function up(){ Schema::create('section', function (Blueprint $table) { $table->id('idsection'); $table->string('name',50)->nullable(); $table->unsignedBigInteger('municipality_idmunicipality'); $table->timestamps(); $table->foreign('municipality_idmunicipality')->references('idmunicipality')->on('municipality')->onDelete('restrict')->onUpdate('cascade'); }); }
    public function down(){ Schema::dropIfExists('section'); }
}
