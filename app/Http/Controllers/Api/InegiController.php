<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

class InegiController extends Controller
{
    /**
     * Proxy DENUE search — keeps API key server-side.
     * GET /api/inegi/denue?keyword=farmacias&entidad=09&registro=1&max=500
     */
    public function denue(Request $request)
    {
        $token    = env('DENUE_API');
        $keyword  = $request->query('keyword', 'todos');
        $entidad  = $request->query('entidad', '00');
        $registro = $request->query('registro', 1);
        $max      = min((int) $request->query('max', 500), 1000);

        $url = "https://www.inegi.org.mx/app/api/denue/v1/consulta/BuscarEntidad/"
             . rawurlencode($keyword) . "/{$entidad}/{$registro}/{$max}/{$token}";

        try {
            $response = Http::withoutVerifying()->timeout(30)->get($url);
        } catch (\Exception $e) {
            return response()->json(['error' => 'No se pudo conectar con DENUE: ' . $e->getMessage()], 502);
        }

        if ($response->failed()) {
            return response()->json(['error' => 'DENUE error HTTP ' . $response->status(), 'raw' => $response->body()], 502);
        }

        return response()->json($response->json());
    }

    /**
     * Proxy INEGI Indicadores/BIE API — keeps token server-side.
     * GET /api/inegi/indicadores?indicador=1002000001&area=00&fuente=BISE
     */
    public function indicadores(Request $request)
    {
        $token      = env('INEGI_INDICADORES_TOKEN');
        $indicador  = $request->query('indicador');
        $area       = $request->query('area', '00');
        $fuente     = $request->query('fuente', 'BISE');

        if (!$indicador) {
            return response()->json(['error' => 'El parámetro indicador es requerido'], 422);
        }

        if (!$token) {
            return response()->json(['error' => 'INEGI_INDICADORES_TOKEN no configurado en .env'], 503);
        }

        $url = "https://www.inegi.org.mx/app/api/indicadores/desarrolladores/jsonxml/"
             . "INDICATOR/{$indicador}/es/{$area}/false/{$fuente}/2.0/{$token}?type=json";

        try {
            $response = Http::withoutVerifying()->timeout(30)->get($url);
        } catch (\Exception $e) {
            return response()->json(['error' => 'No se pudo conectar con INEGI: ' . $e->getMessage()], 502);
        }

        if ($response->failed()) {
            // Pass through the actual INEGI error body so the frontend can show it
            $body = $response->body();
            $decoded = json_decode($body, true);
            $msg = $decoded['message'] ?? $decoded['Message'] ?? $body;
            return response()->json([
                'error'  => 'INEGI devolvió un error (HTTP ' . $response->status() . '): ' . $msg,
                'status' => $response->status(),
                'raw'    => $body,
            ], 502);
        }

        return response()->json($response->json());
    }

    /**
     * Indicadores catalog stored in DB — searchable by name/category.
     * GET /api/inegi/catalogo?buscar=poblacion&categoria=Demografía
     */
    public function catalogo(Request $request)
    {
        $q = DB::table('indicadores_catalog');

        if ($buscar = $request->query('buscar')) {
            $q->where(function ($query) use ($buscar) {
                $query->where('nombre', 'like', "%{$buscar}%")
                      ->orWhere('descripcion', 'like', "%{$buscar}%")
                      ->orWhere('clave', 'like', "%{$buscar}%");
            });
        }

        if ($categoria = $request->query('categoria')) {
            $q->where('categoria', $categoria);
        }

        return response()->json($q->orderBy('categoria')->orderBy('nombre')->get());
    }

    /**
     * Trigger full catalog import from INEGI CL_INDICATOR.
     * POST /api/inegi/import-catalog?source=all|BISE|BIE
     */
    public function importCatalog(Request $request)
    {
        $source = strtoupper($request->query('source', 'all'));
        if (!in_array($source, ['ALL', 'BISE', 'BIE'])) {
            return response()->json(['error' => 'source must be BISE, BIE, or all'], 422);
        }

        $beforeCount = DB::table('indicadores_catalog')->count();

        $exitCode = Artisan::call('inegi:import-catalog', [
            '--source' => strtolower($source),
        ]);

        if ($exitCode !== 0) {
            return response()->json(['error' => 'Import failed — check server logs'], 500);
        }

        $afterCount = DB::table('indicadores_catalog')->count();

        return response()->json([
            'message'  => 'Catálogo actualizado',
            'antes'    => $beforeCount,
            'despues'  => $afterCount,
            'nuevos'   => max(0, $afterCount - $beforeCount),
        ]);
    }

    /**
     * Mexico state boundaries GeoJSON (proxied from public CDN).
     * Features have state_code (1-32) and state_name properties.
     * GET /api/inegi/boundaries/states
     */
    public function boundariesStates()
    {
        $url = 'https://raw.githubusercontent.com/strotgen/mexico-leaflet/master/states.geojson';
        try {
            $response = Http::withoutVerifying()->timeout(15)->get($url);
        } catch (\Exception $e) {
            return response()->json(['error' => 'No se pudo obtener límites: ' . $e->getMessage()], 502);
        }

        if ($response->failed()) {
            return response()->json(['error' => 'No se pudo obtener los límites estatales'], 502);
        }

        return response($response->body(), 200)
               ->header('Content-Type', 'application/json');
    }
}
