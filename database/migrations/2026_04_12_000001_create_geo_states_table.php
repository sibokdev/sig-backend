<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGeoStatesTable extends Migration
{
    public function up()
    {
        Schema::create('geo_states', function (Blueprint $table) {
            $table->id();
            $table->char('cve_ent', 2)->unique();
            $table->string('nombre', 120);
            $table->json('geometry');              // full GeoJSON polygon (MultiPolygon)
            $table->json('geom_simplified')->nullable(); // simplified for zoom-out
            $table->decimal('area_km2', 10, 2)->nullable();
            $table->timestamps();

            $table->index('cve_ent');
        });
    }

    public function down()
    {
        Schema::dropIfExists('geo_states');
    }
}
