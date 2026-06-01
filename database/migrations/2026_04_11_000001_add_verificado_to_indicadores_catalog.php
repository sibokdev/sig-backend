<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddVerificadoToIndicadoresCatalog extends Migration
{
    public function up()
    {
        Schema::table('indicadores_catalog', function (Blueprint $table) {
            $table->boolean('verificado')->default(false)->after('fuente');
        });
    }

    public function down()
    {
        Schema::table('indicadores_catalog', function (Blueprint $table) {
            $table->dropColumn('verificado');
        });
    }
}
