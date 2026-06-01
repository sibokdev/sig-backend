<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\GisLayerService;
use App\Models\GeoState;
use App\Models\GeoMunicipality;
use App\Models\IndicatorValue;
use Illuminate\Http\Request;

class GisLayerController extends Controller
{
    public function __construct(private GisLayerService $service) {}

    /**
     * Build and return a GeoJSON FeatureCollection with indicator values.
     *
     * GET /api/gis/layers
     *   ?level=national|state|municipality|section
     *   &indicator=1002000001
     *   &cve_ent=21         (required for state/municipality/section)
     *   &cve_mun=132        (required for section)
     *   &year=2020          (optional, defaults to latest)
     *   &viz=choropleth|solid
     *   &scale=sequential-warm|blue-red|green-yellow-red|blue-green
     *   &color=%233b82f6    (solid color hex, URL-encoded)
     */
    public function build(Request $request)
    {
        $level     = $request->query('level', 'national');
        $indicator = $request->query('indicator');
        $cveEnt    = $request->query('cve_ent');
        $cveMun    = $request->query('cve_mun');
        $year      = $request->query('year') ? (int) $request->query('year') : null;
        $viz       = $request->query('viz', 'choropleth');
        $scale     = $request->query('scale', 'sequential-warm');
        $color     = $request->query('color', '#3b82f6');

        // Validation
        if (!$indicator) {
            return response()->json(['error' => 'indicator param required'], 422);
        }
        if (!in_array($level, ['national', 'state', 'municipality', 'section'])) {
            return response()->json(['error' => 'level must be: national|state|municipality|section'], 422);
        }
        if (in_array($level, ['state', 'municipality', 'section']) && !$cveEnt) {
            return response()->json(['error' => "cve_ent required for level={$level}"], 422);
        }
        if ($level === 'section' && !$cveMun) {
            return response()->json(['error' => 'cve_mun required for level=section'], 422);
        }

        // Pad codes to correct length
        if ($cveEnt) $cveEnt = str_pad($cveEnt, 2, '0', STR_PAD_LEFT);
        if ($cveMun) $cveMun = str_pad($cveMun, 3, '0', STR_PAD_LEFT);

        try {
            $geojson = $this->service->build(
                $level, $indicator, $cveEnt, $cveMun, $year, $viz, $scale, $color
            );
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }

        return response()->json($geojson);
    }

    /**
     * List all states (minimal — for dropdowns and drill-down).
     * GET /api/gis/geo/states
     */
    public function geoStates()
    {
        $states = GeoState::select('cve_ent', 'nombre', 'area_km2')->orderBy('nombre')->get();
        return response()->json($states);
    }

    /**
     * List municipalities for a given state (minimal — for dropdowns).
     * GET /api/gis/geo/municipalities?cve_ent=21
     */
    public function geoMunicipalities(Request $request)
    {
        $cveEnt = $request->query('cve_ent');
        if (!$cveEnt) return response()->json(['error' => 'cve_ent required'], 422);

        $cveEnt = str_pad($cveEnt, 2, '0', STR_PAD_LEFT);
        $munis  = GeoMunicipality::where('cve_ent', $cveEnt)
                    ->select('cve_ent', 'cve_mun', 'nombre')
                    ->orderBy('nombre')
                    ->get();
        return response()->json($munis);
    }

    /**
     * Return available years for an indicator (for the year selector).
     * GET /api/gis/years?indicator=1002000001&cve_ent=21
     */
    public function availableYears(Request $request)
    {
        $indicator = $request->query('indicator');
        $cveEnt    = $request->query('cve_ent');
        if (!$indicator) return response()->json(['error' => 'indicator required'], 422);

        $years = $this->service->availableYears($indicator, $cveEnt);
        return response()->json($years);
    }

    /**
     * Trigger indicator import from INEGI API and store in indicator_values.
     * POST /api/gis/import-indicators?clave=1002000001&source=BISE
     */
    public function importIndicators(Request $request)
    {
        $clave  = $request->query('clave');
        $source = strtoupper($request->query('source', 'BISE'));

        if (!$clave) return response()->json(['error' => 'clave required'], 422);

        $before   = IndicatorValue::where('indicador_clave', $clave)->count();
        $exitCode = \Illuminate\Support\Facades\Artisan::call('gis:import-indicators', [
            'clave'    => $clave,
            '--source' => $source,
        ]);

        if ($exitCode !== 0) {
            return response()->json(['error' => 'Import failed — check server logs. The indicator clave may not exist or have no data for area=00.'], 500);
        }

        $after = IndicatorValue::where('indicador_clave', $clave)->count();
        return response()->json([
            'message' => 'Indicadores importados',
            'antes'   => $before,
            'despues' => $after,
            'nuevos'  => max(0, $after - $before),
        ]);
    }
}
