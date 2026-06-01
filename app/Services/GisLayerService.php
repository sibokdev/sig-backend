<?php
namespace App\Services;

use App\Models\GeoState;
use App\Models\GeoMunicipality;
use App\Models\GeoSection;
use App\Models\IndicatorValue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class GisLayerService
{
    // Choropleth color scales (5-stop quantile)
    private const SCALES = [
        'sequential-warm'  => ['#ffffcc','#fed976','#feb24c','#f03b20','#bd0026'],
        'blue-red'         => ['#2166ac','#92c5de','#f7f7f7','#f4a582','#d6604d'],
        'green-yellow-red' => ['#1a9641','#a6d96a','#ffffbf','#fdae61','#d7191c'],
        'blue-green'       => ['#084081','#0868ac','#43a2ca','#7bccc4','#ccebc5'],
    ];

    /**
     * Build a GeoJSON FeatureCollection enriched with indicator values.
     *
     * @param string      $level      national|state|municipality|section
     * @param string      $indicator  indicator clave
     * @param string|null $cveEnt     required for state/municipality/section
     * @param string|null $cveMun     required for section
     * @param int|null    $year       defaults to latest available
     * @param string      $vizStyle   choropleth|solid
     * @param string      $colorScale key into SCALES
     * @param string      $solidColor hex color for solid style
     */
    public function build(
        string  $level,
        string  $indicator,
        ?string $cveEnt    = null,
        ?string $cveMun    = null,
        ?int    $year      = null,
        string  $vizStyle  = 'choropleth',
        string  $colorScale = 'sequential-warm',
        string  $solidColor = '#3b82f6'
    ): array {
        $cacheKey = "gis_{$level}_{$indicator}_{$cveEnt}_{$cveMun}_{$year}_{$vizStyle}_{$colorScale}";

        return Cache::remember($cacheKey, 3600, function () use (
            $level, $indicator, $cveEnt, $cveMun, $year, $vizStyle, $colorScale, $solidColor
        ) {
            return $this->_build($level, $indicator, $cveEnt, $cveMun, $year, $vizStyle, $colorScale, $solidColor);
        });
    }

    private function _build(
        string $level, string $indicator, ?string $cveEnt, ?string $cveMun,
        ?int $year, string $vizStyle, string $colorScale, string $solidColor
    ): array {
        // ── 1. Resolve year ───────────────────────────────────────────────────
        $resolvedYear = $year ?? $this->latestYear($indicator, $cveEnt, $cveMun);

        // ── 2. Load indicator values → keyed map ─────────────────────────────
        $valueMap = $this->loadValues($level, $indicator, $resolvedYear, $cveEnt, $cveMun);

        // ── 3. Load indicator metadata ────────────────────────────────────────
        $meta = DB::table('indicadores_catalog')->where('clave', $indicator)->first();

        // ── 4. Compute color scale ────────────────────────────────────────────
        $colors    = self::SCALES[$colorScale] ?? self::SCALES['sequential-warm'];
        $getColor  = $this->buildColorFn($valueMap, $colors);

        // ── 5. Load geometry + build features ────────────────────────────────
        $features = $this->buildFeatures($level, $cveEnt, $cveMun, $valueMap, $getColor, $vizStyle, $solidColor, $resolvedYear, $meta);

        // ── 6. Return FeatureCollection ───────────────────────────────────────
        return [
            'type'     => 'FeatureCollection',
            'meta'     => [
                'indicator' => $meta?->nombre ?? $indicator,
                'clave'     => $indicator,
                'unidad'    => $meta?->unidad ?? '',
                'categoria' => $meta?->categoria ?? '',
                'level'     => $level,
                'year'      => $resolvedYear,
                'cve_ent'   => $cveEnt,
                'cve_mun'   => $cveMun,
                'count'     => count($features),
            ],
            'features' => $features,
        ];
    }

    // ── Load indicator values keyed by geo code ───────────────────────────────
    private function loadValues(string $level, string $indicator, ?int $year, ?string $cveEnt, ?string $cveMun): array
    {
        $q = IndicatorValue::where('indicador_clave', $indicator);

        if ($year) $q->where('year', $year);

        // Scope values to the right granularity:
        //   national → state-level rows (cve_mun='')
        //   state    → municipality-level rows for the given state (cve_mun != '')
        //   municipality → section-level rows for the given municipality
        $q->when($level === 'national', fn($q) => $q->where('cve_mun', ''));
        $q->when($level === 'state',    fn($q) => $q->where('cve_ent', $cveEnt)->where('cve_mun', '!=', '')->where('cve_seccion', ''));
        $q->when($level === 'municipality', fn($q) => $q->where('cve_ent', $cveEnt)->where('cve_mun', $cveMun)->where('cve_seccion', '!=', ''));
        $q->when($level === 'section',      fn($q) => $q->where('cve_ent', $cveEnt)->where('cve_mun', $cveMun)->where('cve_seccion', '!=', ''));

        // Build map: for national → keyed by cve_ent; state → keyed by cve_mun
        $map = [];
        foreach ($q->get(['cve_ent', 'cve_mun', 'cve_seccion', 'value', 'period']) as $row) {
            $key = match ($level) {
                'national'     => $row->cve_ent,
                'state'        => $row->cve_mun,
                'municipality' => $row->cve_seccion ?? $row->cve_mun,
                'section'      => $row->cve_seccion,
                default        => $row->cve_ent,
            };
            if ($key !== null) $map[$key] = (float) $row->value;
        }
        return $map;
    }

    // ── Build features from geometry ──────────────────────────────────────────
    private function buildFeatures(
        string $level, ?string $cveEnt, ?string $cveMun,
        array $valueMap, callable $getColor,
        string $vizStyle, string $solidColor, ?int $year,
        $meta
    ): array {
        $features = [];

        $geoRows = match ($level) {
            'national'     => GeoState::select('cve_ent', 'nombre', 'geom_simplified as geometry')->get(),
            'state'        => GeoMunicipality::where('cve_ent', $cveEnt)->select('cve_ent', 'cve_mun', 'nombre', 'geom_simplified as geometry')->get(),
            'municipality' => GeoSection::where('cve_ent', $cveEnt)->where('cve_mun', $cveMun)->select('cve_ent', 'cve_mun', 'cve_seccion', 'geometry')->get(),
            'section'      => GeoSection::where('cve_ent', $cveEnt)->where('cve_mun', $cveMun)->select('cve_ent', 'cve_mun', 'cve_seccion', 'geometry')->get(),
            default        => collect(),
        };

        foreach ($geoRows as $row) {
            $geoKey = match ($level) {
                'national'     => $row->cve_ent,
                'state'        => $row->cve_mun,
                'municipality',
                'section'      => $row->cve_seccion,
                default        => $row->cve_ent,
            };

            $value = $valueMap[$geoKey] ?? null;
            $color = ($vizStyle === 'choropleth') ? $getColor($value) : $solidColor;

            // Decode geometry (stored as JSON string in DB)
            $geometry = is_array($row->geometry) ? $row->geometry : json_decode($row->geometry, true);

            $features[] = [
                'type'       => 'Feature',
                'geometry'   => $geometry,
                'properties' => [
                    'nombre'      => $row->nombre ?? "Sección {$row->cve_seccion}",
                    'cve_ent'     => $row->cve_ent,
                    'cve_mun'     => $row->cve_mun ?? null,
                    'cve_seccion' => $row->cve_seccion ?? null,
                    'Indicador'   => $meta?->nombre ?? '',
                    'Unidad'      => $meta?->unidad ?? '',
                    'Año'         => $year,
                    'Valor'       => $value,
                    // Rendering hints (used by Map.jsx styleFunction)
                    '_layerType'  => $vizStyle === 'choropleth' ? 'inegi_choropleth' : 'inegi_solid',
                    '_color'      => $color,
                    '_solidColor' => $solidColor,
                ],
            ];
        }

        return $features;
    }

    // ── Build quantile color function ─────────────────────────────────────────
    private function buildColorFn(array $valueMap, array $colors): callable
    {
        $values = array_values(array_filter($valueMap, fn($v) => $v !== null && is_numeric($v)));
        if (empty($values)) return fn($v) => '#cccccc';

        sort($values);
        $n = count($values);
        $quantiles = array_map(fn($p) => $values[(int) floor($p * ($n - 1))], [0, 0.25, 0.5, 0.75, 1.0]);

        return function ($value) use ($quantiles, $colors) {
            if ($value === null) return '#cccccc';
            for ($i = 0; $i < count($quantiles) - 1; $i++) {
                if ($value <= $quantiles[$i + 1]) return $colors[$i];
            }
            return $colors[count($colors) - 1];
        };
    }

    // ── Resolve latest year with available data ───────────────────────────────
    private function latestYear(string $indicator, ?string $cveEnt, ?string $cveMun): ?int
    {
        return IndicatorValue::where('indicador_clave', $indicator)
            ->when($cveEnt, fn($q) => $q->where('cve_ent', $cveEnt))
            ->when($cveMun, fn($q) => $q->where('cve_mun', $cveMun))
            ->max('year');
    }

    /** Return available years for a given indicator (for the year selector in the UI) */
    public function availableYears(string $indicator, ?string $cveEnt = null): array
    {
        return IndicatorValue::where('indicador_clave', $indicator)
            ->when($cveEnt, fn($q) => $q->where('cve_ent', $cveEnt))
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year')
            ->toArray();
    }

    /** Bust the cache for a specific indicator (called after import) */
    public function bustCache(string $indicator): void
    {
        // Laravel file cache doesn't support tag-based flush natively —
        // for now we let TTL expire (1h). With Redis, use Cache::tags([$indicator])->flush()
    }
}
