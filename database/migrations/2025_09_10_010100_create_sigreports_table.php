<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
class CreateSigreportsTable extends Migration
{
    public function up(){ Schema::create('sigreports', function (Blueprint $table) { $table->id('idsigreports'); $table->string('reportname',150)->nullable(); $table->string('filedir',500)->nullable(); $table->dateTime('datecreated')->nullable(); $table->timestamps(); }); }
    public function down(){ Schema::dropIfExists('sigreports'); }
}
