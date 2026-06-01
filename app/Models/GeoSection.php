<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GeoSection extends Model
{
    protected $table = 'geo_sections';

    protected $fillable = ['cve_ent', 'cve_mun', 'cve_seccion', 'geometry'];

    protected $casts = [
        'geometry' => 'array',
    ];

    public function municipality()
    {
        return $this->belongsTo(GeoMunicipality::class, 'cve_mun', 'cve_mun')
                    ->where('geo_municipalities.cve_ent', $this->cve_ent);
    }
}
