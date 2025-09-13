<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
class CreatePromotedTable extends Migration
{
    public function up(){ Schema::create('promoted', function (Blueprint $table) { $table->increments('id'); $table->string('nombre',100); $table->string('apellidop',50); $table->string('apellidom',50); $table->string('direccion',250); $table->string('sexo',20); $table->integer('edad'); $table->double('latitud'); $table->double('longitud'); $table->date('fechaRecepcion'); $table->longText('fotografiaBeneficiario'); $table->text('imagenFirma'); $table->text('fotografiaIneFront'); $table->text('fotografiaIneBack'); $table->string('claveIne',20); $table->string('seccion',10); $table->text('promotor'); $table->string('telefono',20); $table->string('tipoApoyo',20); $table->string('militante',4)->nullable(); $table->string('correo',50)->nullable(); $table->string('facebook',250)->nullable(); $table->string('twitter',250)->nullable(); $table->string('instagram',250)->nullable(); $table->string('cantidad',50); $table->string('unidadMedida',50); $table->text('fotografiaInicio'); $table->text('fotografiaDurante1'); $table->text('fotografiaDurante2'); $table->text('fotografiaDurante3'); $table->text('fotografiaDurante4'); $table->text('fotografiaDurante5'); $table->text('fotografiaFinal'); $table->timestamps(); }); }
    public function down(){ Schema::dropIfExists('promoted'); }
}
