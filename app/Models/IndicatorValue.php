<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IndicatorValue extends Model
{
    protected $table = 'indicator_values';

    protected $fillable = [
        'indicador_clave', 'year', 'period',
        'cve_ent', 'cve_mun', 'cve_seccion',
        'value', 'source',
    ];

    protected $casts = [
        'year'  => 'integer',
        'value' => 'float',
    ];

    public function state()
    {
        return $this->belongsTo(GeoState::class, 'cve_ent', 'cve_ent');
    }

    public function indicator()
    {
        return $this->belongsTo(IndicadoresCatalog::class, 'indicador_clave', 'clave');
    }

    /** Scope: latest year per area for a given indicator */
    public function scopeLatestYear($query, string $clave)
    {
        return $query->where('indicador_clave', $clave)
                     ->orderByDesc('year')
                     ->orderByDesc('period');
    }
}
