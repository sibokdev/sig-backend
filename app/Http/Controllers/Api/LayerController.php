<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Layer;
use App\Models\Config;
use App\Models\LayerAssignment;
use App\Models\User;
use App\Models\GeoSection;
use Illuminate\Support\Facades\Storage;

class LayerController extends Controller
{
    // ── Access control ────────────────────────────────────────────────────────

    /**
     * Returns the set of layer IDs the authenticated user may access.
     * null  → no restriction (admin)
     * []    → no layers (user without a national_admin parent)
     * [1,2] → specific layer IDs
     */
    private function accessibleLayerIds(): ?array
    {
        $user = auth()->user();
        if (!$user || $user->role === 'admin') return null;

        $nationalAdminId = $user->role === 'national_admin'
            ? $user->iduserId
            : $user->national_admin_id;

        if (!$nationalAdminId) return [];

        return LayerAssignment::where('user_id', $nationalAdminId)
            ->pluck('layer_id')
            ->toArray();
    }

    private function applyAccessScope($query): mixed
    {
        $ids = $this->accessibleLayerIds();
        if ($ids === null) return $query;
        if (empty($ids)) return $query->whereRaw('0 = 1');
        return $query->whereIn('idlayers', $ids);
    }

    // ── CRUD ──────────────────────────────────────────────────────────────────

    public function index()
    {
        return response()->json($this->applyAccessScope(Layer::query())->get());
    }

    public function store(Request $request)
    {
        $geojson = $request->input('geojson');
        $goal    = $request->input('goal');

        // If layer-level goal is provided, inject into every feature that lacks one
        if ($goal && is_array($geojson) && isset($geojson['features'])) {
            foreach ($geojson['features'] as &$feature) {
                if (empty($feature['properties']['goal']) && empty($feature['properties']['meta'])) {
                    $feature['properties']['goal'] = (int) $goal;
                }
            }
            unset($feature);
        }

        $max   = Layer::max('idlayers') ?? 0;
        $layer = Layer::create([
            'idlayers'                    => $max + 1,
            'name'                        => $request->input('name'),
            'goal'                        => $goal ? (int) $goal : null,
            'geojson'                     => $geojson,
            'kmlfileLocation'             => $request->input('kmlfileLocation'),
            'states_idstates'             => $request->input('states_idstates'),
            'municipality_idmunicipality' => $request->input('municipality_idmunicipality'),
            'section_idsection'           => $request->input('section_idsection'),
        ]);

        if (is_array($geojson)) {
            $this->syncSections($geojson);
        }

        return response()->json(['ok' => true, 'layer' => $layer], 201);
    }

    public function show($id)
    {
        return response()->json(Layer::findOrFail($id));
    }

    public function destroy($id)
    {
        $layer = Layer::findOrFail($id);
        if ($layer->kmlfileLocation && Storage::disk('private')->exists($layer->kmlfileLocation)) {
            Storage::disk('private')->delete($layer->kmlfileLocation);
        }
        $layer->delete();
        return response()->json(['ok' => true]);
    }

    // ── Section sync ─────────────────────────────────────────────────────────

    /**
     * When a layer with section-level features is saved, upsert cve_ent/cve_mun/cve_seccion
     * into geo_sections so the user-creation dropdown can find them.
     */
    private function syncSections(array $geojson): void
    {
        $features = $geojson['features'] ?? [];
        foreach ($features as $feature) {
            $props = $feature['properties'] ?? [];
            $cveEnt     = $props['cve_ent']     ?? $props['CVE_ENT']     ?? null;
            $cveMun     = $props['cve_mun']      ?? $props['CVE_MUN']     ?? null;
            $cveSeccion = $props['cve_seccion']  ?? $props['CVE_SECCION'] ?? null;

            if (!$cveEnt || !$cveMun || !$cveSeccion) continue;

            GeoSection::updateOrInsert(
                ['cve_ent' => (string) $cveEnt, 'cve_mun' => (string) $cveMun, 'cve_seccion' => (string) $cveSeccion],
                ['geometry' => json_encode($feature['geometry'] ?? null), 'updated_at' => now()]
            );
        }
    }

    // ── Minimal / filtered list helpers ──────────────────────────────────────

    public function getAllStatesLayers()
    {
        $q = $this->applyAccessScope(Layer::query());
        return response()->json($q->select('states_idstates')->distinct()->get());
    }

    public function getLayersByState($stateid)
    {
        $q = $this->applyAccessScope(Layer::query());
        return response()->json($q->where('states_idstates', $stateid)->get());
    }

    public function getLayersByMunicipality($stateid, $municipalityid)
    {
        $q = $this->applyAccessScope(Layer::query());
        return response()->json(
            $q->where('states_idstates', $stateid)
              ->where('municipality_idmunicipality', $municipalityid)
              ->get()
        );
    }

    public function getMinimalAllLayers()
    {
        $q = $this->applyAccessScope(Layer::query());
        return response()->json($q->select(['idlayers', 'name', 'goal'])->get());
    }

    public function getMinimalNational()
    {
        $q = $this->applyAccessScope(Layer::query());
        return response()->json(
            $q->select(['idlayers', 'name', 'states_idstates', 'goal'])
              ->whereNull('states_idstates')
              ->whereNull('municipality_idmunicipality')
              ->whereNull('section_idsection')
              ->get()
        );
    }

    public function getMinimalStates()
    {
        $q = $this->applyAccessScope(Layer::query());
        return response()->json(
            $q->select(['idlayers', 'name', 'states_idstates', 'goal'])
              ->whereNotNull('states_idstates')
              ->whereNull('municipality_idmunicipality')
              ->whereNull('section_idsection')
              ->get()
        );
    }

    public function getMinimalMunicipality()
    {
        $q = $this->applyAccessScope(Layer::query());
        return response()->json(
            $q->select(['idlayers', 'name', 'states_idstates', 'municipality_idmunicipality', 'goal'])
              ->whereNotNull('states_idstates')
              ->whereNotNull('municipality_idmunicipality')
              ->whereNull('section_idsection')
              ->get()
        );
    }

    public function getMinimalSections()
    {
        $q = $this->applyAccessScope(Layer::query());
        return response()->json(
            $q->select(['idlayers', 'name', 'states_idstates', 'municipality_idmunicipality', 'goal'])
              ->whereNotNull('states_idstates')
              ->whereNotNull('municipality_idmunicipality')
              ->whereNotNull('section_idsection')
              ->get()
        );
    }

    public function getMinimalStateById($stateid)
    {
        $q = $this->applyAccessScope(Layer::query());
        return response()->json(
            $q->select(['idlayers', 'name', 'states_idstates', 'goal'])
              ->where('states_idstates', $stateid)
              ->get()
        );
    }

    public function getMinimalMunicipalityById($stateid, $municipalityid)
    {
        $q = $this->applyAccessScope(Layer::query());
        return response()->json(
            $q->select(['idlayers', 'name', 'states_idstates', 'municipality_idmunicipality', 'goal'])
              ->where('states_idstates', $stateid)
              ->where('municipality_idmunicipality', $municipalityid)
              ->get()
        );
    }

    // ── Config helpers ────────────────────────────────────────────────────────

    public function downloadFile($id)
    {
        $layer = Layer::findOrFail($id);
        $path  = $layer->kmlfileLocation;
        if (!$path || !Storage::disk('private')->exists($path)) {
            return response()->json(['error' => 'file not found'], 404);
        }
        return Storage::disk('private')->download($path);
    }

    public function saveLayerConfig(Request $request, $id)
    {
        $layer  = Layer::findOrFail($id);
        $config = Config::create(['config' => json_encode($request->input('config'))]);
        $layer->id_config = $config->id;
        $layer->save();
        return response()->json(['ok' => true, 'config' => $config], 201);
    }

    public function updateLayerConfig(Request $request, $id)
    {
        $layer = Layer::findOrFail($id);

        if ($layer->id_config && $existing = Config::find($layer->id_config)) {
            $existing->config = json_encode($request->input('config'));
            $existing->save();
            return response()->json(['ok' => true, 'config' => $existing]);
        }

        $config = Config::create(['config' => json_encode($request->input('config'))]);
        $layer->id_config = $config->id;
        $layer->save();
        return response()->json(['ok' => true, 'config' => $config], 201);
    }

    public function getConfigById($id)
    {
        $layer = Layer::findOrFail($id);
        return response()->json(Config::findOrFail($layer->id_config));
    }

    // ── Layer assignment management (admin only) ──────────────────────────────

    /**
     * GET /layers/{id}/assignments
     * Returns all national_admins who have access to this layer.
     */
    public function getAssignments($id)
    {
        Layer::findOrFail($id);
        $assignments = LayerAssignment::where('layer_id', $id)
            ->with('user:iduserId,username,email,role')
            ->get()
            ->map(fn($a) => $a->user);

        return response()->json($assignments);
    }

    /**
     * POST /layers/{id}/assignments
     * Body: { user_id }
     */
    public function addAssignment(Request $request, $id)
    {
        Layer::findOrFail($id);

        $userId = $request->input('user_id');
        $user   = User::findOrFail($userId);

        if ($user->role !== 'national_admin') {
            return response()->json(['error' => 'Only national_admin users can be assigned layers'], 422);
        }

        $assignment = LayerAssignment::firstOrCreate([
            'layer_id' => $id,
            'user_id'  => $userId,
        ]);

        return response()->json(['ok' => true, 'assignment' => $assignment], 201);
    }

    /**
     * DELETE /layers/{id}/assignments/{userId}
     */
    public function removeAssignment($id, $userId)
    {
        $deleted = LayerAssignment::where('layer_id', $id)
            ->where('user_id', $userId)
            ->delete();

        if (!$deleted) {
            return response()->json(['error' => 'Assignment not found'], 404);
        }

        return response()->json(['ok' => true]);
    }
}
