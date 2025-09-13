<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
class CreateMunicipalityTable extends Migration
{
    public function up(){ Schema::create('municipality', function (Blueprint $table) { $table->id('idmunicipality'); $table->string('name',500)->nullable(); $table->unsignedBigInteger('states_idstates'); $table->timestamps(); $table->foreign('states_idstates')->references('idstates')->on('states')->onDelete('restrict')->onUpdate('cascade'); }); }
    public function down(){ Schema::dropIfExists('municipality'); }
}
