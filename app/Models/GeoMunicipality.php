<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GeoMunicipality extends Model
{
    protected $table = 'geo_municipalities';

    protected $fillable = ['cve_ent', 'cve_mun', 'nombre', 'geometry', 'geom_simplified'];

    protected $casts = [
        'geometry'        => 'array',
        'geom_simplified' => 'array',
    ];

    public function state()
    {
        return $this->belongsTo(GeoState::class, 'cve_ent', 'cve_ent');
    }

    public function sections()
    {
        return $this->hasMany(GeoSection::class, 'cve_mun', 'cve_mun')
                    ->where('geo_sections.cve_ent', $this->cve_ent);
    }

    public function indicatorValues()
    {
        return $this->hasMany(IndicatorValue::class, 'cve_mun', 'cve_mun')
                    ->where('indicator_values.cve_ent', $this->cve_ent);
    }
}
