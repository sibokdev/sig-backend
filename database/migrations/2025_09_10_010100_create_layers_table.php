<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
class CreateLayersTable extends Migration
{
    public function up(){ Schema::create('layers', function (Blueprint $table) { $table->id('idlayers'); $table->string('name',120)->nullable(); $table->json('geojson')->nullable(); $table->string('kmlfileLocation',300)->nullable(); $table->unsignedBigInteger('states_idstates')->nullable(); $table->unsignedBigInteger('municipality_idmunicipality')->nullable(); $table->unsignedBigInteger('section_idsection')->nullable(); $table->timestamps(); $table->foreign('states_idstates')->references('idstates')->on('states')->onDelete('set null')->onUpdate('cascade'); $table->foreign('municipality_idmunicipality')->references('idmunicipality')->on('municipality')->onDelete('set null')->onUpdate('cascade'); $table->foreign('section_idsection')->references('idsection')->on('section')->onDelete('set null')->onUpdate('cascade'); }); }
    public function down(){ Schema::dropIfExists('layers'); }
}
