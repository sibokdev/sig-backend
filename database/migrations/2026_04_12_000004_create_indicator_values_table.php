<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIndicatorValuesTable extends Migration
{
    public function up()
    {
        Schema::create('indicator_values', function (Blueprint $table) {
            $table->id();
            $table->string('indicador_clave', 20);
            $table->smallInteger('year');
            $table->string('period', 20)->nullable();    // e.g. "2020/01" for quarterly
            $table->char('cve_ent', 2);
            $table->char('cve_mun', 3)->nullable();      // null = state-level
            $table->char('cve_seccion', 4)->nullable();  // null = municipality-level or above
            $table->decimal('value', 20, 4)->nullable();
            $table->enum('source', ['BISE', 'BIE', 'XLS', 'MANUAL'])->default('BISE');
            $table->timestamps();

            $table->unique(['indicador_clave', 'year', 'period', 'cve_ent', 'cve_mun', 'cve_seccion'],
                           'indicator_values_unique');
            $table->index(['indicador_clave', 'cve_ent']);
            $table->index(['indicador_clave', 'cve_ent', 'cve_mun']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('indicator_values');
    }
}
