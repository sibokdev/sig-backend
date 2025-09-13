<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
class CreateUsersTable extends Migration
{
    public function up(){ Schema::create('users', function (Blueprint $table) { $table->id('iduserId'); $table->string('username',45)->nullable(); $table->string('pwd',150)->nullable(); $table->timestamps(); }); }
    public function down(){ Schema::dropIfExists('users'); }
}
