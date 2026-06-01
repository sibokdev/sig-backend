<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGeoSectionsTable extends Migration
{
    public function up()
    {
        Schema::create('geo_sections', function (Blueprint $table) {
            $table->id();
            $table->char('cve_ent', 2);
            $table->char('cve_mun', 3);
            $table->char('cve_seccion', 4);
            $table->json('geometry');
            $table->timestamps();

            $table->unique(['cve_ent', 'cve_mun', 'cve_seccion']);
            $table->index(['cve_ent', 'cve_mun']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('geo_sections');
    }
}
