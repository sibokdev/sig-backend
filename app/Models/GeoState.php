<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GeoState extends Model
{
    protected $table = 'geo_states';

    protected $fillable = ['cve_ent', 'nombre', 'geometry', 'geom_simplified', 'area_km2'];

    protected $casts = [
        'geometry'         => 'array',
        'geom_simplified'  => 'array',
        'area_km2'         => 'float',
    ];

    public function municipalities()
    {
        return $this->hasMany(GeoMunicipality::class, 'cve_ent', 'cve_ent');
    }

    public function indicatorValues()
    {
        return $this->hasMany(IndicatorValue::class, 'cve_ent', 'cve_ent');
    }
}
