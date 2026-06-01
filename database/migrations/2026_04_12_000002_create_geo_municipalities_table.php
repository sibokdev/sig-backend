<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGeoMunicipalitiesTable extends Migration
{
    public function up()
    {
        Schema::create('geo_municipalities', function (Blueprint $table) {
            $table->id();
            $table->char('cve_ent', 2);
            $table->char('cve_mun', 3);
            $table->string('nombre', 120);
            $table->json('geometry');
            $table->json('geom_simplified')->nullable();
            $table->timestamps();

            $table->unique(['cve_ent', 'cve_mun']);
            $table->index('cve_ent');
        });
    }

    public function down()
    {
        Schema::dropIfExists('geo_municipalities');
    }
}
