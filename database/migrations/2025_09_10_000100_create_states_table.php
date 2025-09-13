<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
class CreateStatesTable extends Migration
{
    public function up(){ Schema::create('states', function (Blueprint $table) { $table->id('idstates'); $table->string('name',150)->nullable(); $table->timestamps(); }); }
    public function down(){ Schema::dropIfExists('states'); }
}
