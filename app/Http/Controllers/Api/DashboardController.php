<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GeoState;
use App\Models\GeoMunicipality;
use App\Models\LayerAssignment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    // ── Semaforo helpers ──────────────────────────────────────────────────────

    private function getThresholds(): array
    {
        $user            = auth()->user();
        $nationalAdminId = $user->role === 'national_admin'
            ? $user->iduserId
            : ($user->national_admin_id ?? null);

        $config = null;
        if ($nationalAdminId) {
            $config = User::find($nationalAdminId)?->semaforo_config;
        }

        return $config ?? ['yellow' => 51, 'green' => 85];
    }

    private function calcSemaforo(int $captured, int $goal, array $thresholds): string
    {
        if ($goal === 0) return 'grey';
        $pct = ($captured / $goal) * 100;
        if ($pct >= $thresholds['green'])  return 'green';
        if ($pct >= $thresholds['yellow']) return 'yellow';
        return 'red';
    }

    // ── Section goal lookup ───────────────────────────────────────────────────

    /**
     * Build a map of cve_seccion → goal from all layers accessible to the
     * national_admin in the current user's chain.
     */
    private function buildSectionGoalMap(): array
    {
        $user            = auth()->user();
        $nationalAdminId = $user->role === 'national_admin'
            ? $user->iduserId
            : ($user->national_admin_id ?? null);

        if (!$nationalAdminId) return [];

        $map         = [];
        $assignments = LayerAssignment::where('user_id', $nationalAdminId)
            ->with('layer')
            ->get();

        foreach ($assignments as $assignment) {
            $layer = $assignment->layer;
            if (!$layer || !$layer->geojson) continue;

            foreach ($layer->geojson['features'] ?? [] as $feature) {
                $props      = $feature['properties'] ?? [];
                $cveSeccion = (string) ($props['cve_seccion'] ?? $props['SECCION'] ?? '');
                if ($cveSeccion === '' || isset($map[$cveSeccion])) continue;

                if (isset($props['goal']))  { $map[$cveSeccion] = (int) $props['goal'];  continue; }
                if (isset($props['meta']))  { $map[$cveSeccion] = (int) $props['meta'];   continue; }
                if ($layer->goal !== null)  { $map[$cveSeccion] = (int) $layer->goal; }
            }
        }

        return $map;
    }

    private function getSectionGoal(string $cveSeccion, array $goalMap): int
    {
        return $goalMap[(string) $cveSeccion] ?? 0;
    }

    // ── stats ─────────────────────────────────────────────────────────────────

    public function stats()
    {
        $user       = auth()->user();
        $role       = $user->role ?? 'seccion_admin';
        $thresholds = $this->getThresholds();
        $goalMap    = $this->buildSectionGoalMap();

        // ── field_workforce ───────────────────────────────────────────────
        if ($role === 'field_workforce') {
            $goal     = $this->getSectionGoal($user->cve_seccion ?? '', $goalMap);
            $captured = (int) DB::table('table_data')->where('captured_by', $user->iduserId)->count();
            $pct      = $goal > 0 ? round($captured / $goal * 100, 1) : 0;

            return response()->json([
                'scope'          => 'seccion',
                'total_goal'     => $goal,
                'total_captured' => $captured,
                'semaforo'       => $this->calcSemaforo($captured, $goal, $thresholds),
                'breakdown'      => [[
                    'label'    => 'Mi sección',
                    'sublabel' => $user->cve_seccion ?? 'Sin sección',
                    'goal'     => $goal,
                    'captured' => $captured,
                    'pct'      => $pct,
                    'semaforo' => $this->calcSemaforo($captured, $goal, $thresholds),
                ]],
                'thresholds'     => $thresholds,
            ]);
        }

        // ── seccion_admin ─────────────────────────────────────────────────
        if ($role === 'seccion_admin') {
            $cveSeccion  = $user->cve_seccion ?? '';
            $sectionGoal = $this->getSectionGoal($cveSeccion, $goalMap);

            // Direct field_workforce subordinates share the same cve_seccion
            $subordinates = User::where('role', 'field_workforce')
                ->where('national_admin_id', $user->national_admin_id)
                ->where('cve_seccion', $cveSeccion)
                ->get();

            $breakdown     = [];
            $totalCaptured = 0;

            foreach ($subordinates as $sub) {
                $subCaptured    = (int) DB::table('table_data')->where('captured_by', $sub->iduserId)->count();
                $subGoal        = $this->getSectionGoal($sub->cve_seccion ?? '', $goalMap);
                $totalCaptured += $subCaptured;
                $breakdown[]    = [
                    'label'    => $sub->username,
                    'sublabel' => 'Sección: ' . ($sub->cve_seccion ?? '—'),
                    'goal'     => $subGoal,
                    'captured' => $subCaptured,
                    'pct'      => $subGoal > 0 ? round($subCaptured / $subGoal * 100, 1) : 0,
                    'semaforo' => $this->calcSemaforo($subCaptured, $subGoal, $thresholds),
                ];
            }

            // Seccion_admin's own captures (direct captures, not through field_workforce)
            $myCaptures = (int) DB::table('table_data')->where('captured_by', $user->iduserId)->count();
            if ($myCaptures > 0) {
                $totalCaptured += $myCaptures;
                array_unshift($breakdown, [
                    'label'    => $user->username . ' (yo)',
                    'sublabel' => 'Capturas directas',
                    'goal'     => $sectionGoal,
                    'captured' => $myCaptures,
                    'pct'      => $sectionGoal > 0 ? round($myCaptures / $sectionGoal * 100, 1) : 0,
                    'semaforo' => $this->calcSemaforo($myCaptures, $sectionGoal, $thresholds),
                ]);
            }

            return response()->json([
                'scope'          => 'seccion',
                'total_goal'     => $sectionGoal,
                'total_captured' => $totalCaptured,
                'semaforo'       => $this->calcSemaforo($totalCaptured, $sectionGoal, $thresholds),
                'breakdown'      => $breakdown,
                'thresholds'     => $thresholds,
            ]);
        }

        // ── For municipal_admin, estatal_admin, national_admin, admin:
        //    Use table_configs.goal for existing campaigns + add semaforo ──────

        $rows = DB::table('table_configs')
            ->join('users', 'table_configs.user_in_charge', '=', 'users.iduserId')
            ->whereNotNull('table_configs.goal')
            ->whereNotNull('table_configs.user_in_charge')
            ->select(
                'table_configs.id   as config_id',
                'table_configs.goal',
                'table_configs.table_name',
                'users.iduserId     as user_id',
                'users.username',
                'users.cve_ent',
                'users.cve_mun',
                'users.cve_seccion'
            )
            ->get();

        $counts = DB::table('table_data')
            ->select('config_id', DB::raw('COUNT(*) as captured'))
            ->groupBy('config_id')
            ->pluck('captured', 'config_id');

        $rows = $rows->map(function ($r) use ($counts) {
            $r->captured = (int) ($counts[$r->config_id] ?? 0);
            $r->pct      = $r->goal > 0 ? round($r->captured / $r->goal * 100, 1) : 0;
            return $r;
        });

        // ── municipal_admin ───────────────────────────────────────────────
        if ($role === 'municipal_admin' && $user->cve_mun) {
            $filtered  = $rows->where('cve_mun', $user->cve_mun);
            $breakdown = $filtered->map(fn($r) => [
                'label'    => $r->table_name,
                'sublabel' => 'Capturista: ' . $r->username,
                'goal'     => (int) $r->goal,
                'captured' => $r->captured,
                'pct'      => $r->pct,
                'semaforo' => $this->calcSemaforo($r->captured, (int) $r->goal, $thresholds),
            ])->values()->all();

            return response()->json([
                'scope'          => 'municipal',
                'total_goal'     => (int) $filtered->sum('goal'),
                'total_captured' => (int) $filtered->sum('captured'),
                'semaforo'       => $this->calcSemaforo(
                    (int) $filtered->sum('captured'),
                    (int) $filtered->sum('goal'),
                    $thresholds
                ),
                'breakdown'      => $breakdown,
                'thresholds'     => $thresholds,
            ]);
        }

        // ── estatal_admin ─────────────────────────────────────────────────
        if ($role === 'estatal_admin' && $user->cve_ent) {
            $filtered = $rows->where('cve_ent', $user->cve_ent);
            $munNames = GeoMunicipality::where('cve_ent', $user->cve_ent)
                ->pluck('nombre', 'cve_mun');

            $breakdown = $filtered->groupBy('cve_mun')
                ->map(function ($items, $cve_mun) use ($munNames, $thresholds) {
                    $name     = $munNames[$cve_mun] ?? null;
                    $goal     = (int) $items->sum('goal');
                    $captured = (int) $items->sum('captured');
                    return [
                        'label'    => $name ?? ('Municipio ' . ($cve_mun ?: '—')),
                        'sublabel' => $name ? 'Cve. municipio: ' . $cve_mun : 'Sin clave municipal',
                        'cve_mun'  => $cve_mun,
                        'goal'     => $goal,
                        'captured' => $captured,
                        'pct'      => $goal > 0 ? round($captured / $goal * 100, 1) : 0,
                        'semaforo' => $this->calcSemaforo($captured, $goal, $thresholds),
                    ];
                })->values()->all();

            return response()->json([
                'scope'          => 'estatal',
                'total_goal'     => (int) $filtered->sum('goal'),
                'total_captured' => (int) $filtered->sum('captured'),
                'semaforo'       => $this->calcSemaforo(
                    (int) $filtered->sum('captured'),
                    (int) $filtered->sum('goal'),
                    $thresholds
                ),
                'breakdown'      => $breakdown,
                'thresholds'     => $thresholds,
            ]);
        }

        // ── national_admin / admin ────────────────────────────────────────
        $stateNames = GeoState::pluck('nombre', 'cve_ent');

        // national_admin only sees their subtree
        if ($role === 'national_admin') {
            $rows = $rows->filter(function ($r) use ($user) {
                return DB::table('users')
                    ->where('iduserId', $r->user_id)
                    ->where('national_admin_id', $user->iduserId)
                    ->exists();
            });
        }

        $breakdown = $rows->groupBy('cve_ent')
            ->map(function ($items, $cve_ent) use ($stateNames, $thresholds) {
                $name     = $stateNames[$cve_ent] ?? null;
                $goal     = (int) $items->sum('goal');
                $captured = (int) $items->sum('captured');
                return [
                    'label'    => $name ?? ($cve_ent !== '' ? 'Estado ' . $cve_ent : 'Sin asignación geográfica'),
                    'sublabel' => $name ? 'Cve. estado: ' . $cve_ent : 'Configs sin estado asignado',
                    'cve_ent'  => $cve_ent,
                    'goal'     => $goal,
                    'captured' => $captured,
                    'pct'      => $goal > 0 ? round($captured / $goal * 100, 1) : 0,
                    'semaforo' => $this->calcSemaforo($captured, $goal, $thresholds),
                ];
            })->values()->all();

        return response()->json([
            'scope'          => 'national',
            'total_goal'     => (int) $rows->sum('goal'),
            'total_captured' => (int) $rows->sum('captured'),
            'semaforo'       => $this->calcSemaforo(
                (int) $rows->sum('captured'),
                (int) $rows->sum('goal'),
                $thresholds
            ),
            'breakdown'      => $breakdown,
            'thresholds'     => $thresholds,
        ]);
    }

    /**
     * PUT /dashboard/semaforo-config
     * national_admin only — update their traffic light thresholds.
     */
    public function updateSemaforoConfig(Request $request)
    {
        $user = auth()->user();

        $data = $request->validate([
            'yellow' => 'required|integer|min:1|max:99',
            'green'  => 'required|integer|min:1|max:100',
        ]);

        if ($data['green'] <= $data['yellow']) {
            return response()->json(['error' => 'green threshold must be greater than yellow'], 422);
        }

        $user->semaforo_config = $data;
        $user->save();

        return response()->json(['ok' => true, 'semaforo_config' => $data]);
    }

    // ── rankings ──────────────────────────────────────────────────────────────

    public function rankings()
    {
        $user = auth()->user();
        $role = $user->role ?? 'seccion_admin';

        $scopeFilter = function ($q) use ($user, $role) {
            if ($role === 'estatal_admin' && $user->cve_ent) {
                $q->where('users.cve_ent', $user->cve_ent);
            } elseif ($role === 'municipal_admin' && $user->cve_mun) {
                $q->where('users.cve_mun', $user->cve_mun);
            } elseif (in_array($role, ['seccion_admin', 'field_workforce'])) {
                $q->where('table_data.captured_by', $user->iduserId);
            }
        };

        $topUsers = DB::table('table_data')
            ->join('users', 'table_data.captured_by', '=', 'users.iduserId')
            ->when(true, $scopeFilter)
            ->select('users.username', DB::raw('COUNT(*) as total'))
            ->groupBy('users.iduserId', 'users.username')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        $topSecciones = DB::table('table_data')
            ->join('users', 'table_data.captured_by', '=', 'users.iduserId')
            ->when(true, $scopeFilter)
            ->select('users.username', 'users.cve_ent', 'users.cve_mun', 'users.cve_seccion', DB::raw('COUNT(*) as total'))
            ->groupBy('users.iduserId', 'users.username', 'users.cve_ent', 'users.cve_mun', 'users.cve_seccion')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        $topMunicipios = DB::table('table_data')
            ->join('users', 'table_data.captured_by', '=', 'users.iduserId')
            ->whereNotNull('users.cve_mun')
            ->where('users.cve_mun', '!=', '')
            ->when($role === 'estatal_admin' && $user->cve_ent, fn($q) => $q->where('users.cve_ent', $user->cve_ent))
            ->select('users.cve_ent', 'users.cve_mun', DB::raw('COUNT(*) as total'))
            ->groupBy('users.cve_ent', 'users.cve_mun')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        $munKeys  = $topMunicipios->map(fn($r) => $r->cve_mun)->unique()->values();
        $munNames = GeoMunicipality::whereIn('cve_mun', $munKeys)->pluck('nombre', 'cve_mun');

        $topMunicipios = $topMunicipios->map(fn($r) => [
            'label'   => $munNames[$r->cve_mun] ?? ('Municipio ' . $r->cve_mun),
            'cve_ent' => $r->cve_ent,
            'cve_mun' => $r->cve_mun,
            'total'   => (int) $r->total,
        ]);

        $topEstados = DB::table('table_data')
            ->join('users', 'table_data.captured_by', '=', 'users.iduserId')
            ->whereNotNull('users.cve_ent')
            ->where('users.cve_ent', '!=', '')
            ->select('users.cve_ent', DB::raw('COUNT(*) as total'))
            ->groupBy('users.cve_ent')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        $stateNames = GeoState::pluck('nombre', 'cve_ent');
        $topEstados = $topEstados->map(fn($r) => [
            'label'   => $stateNames[$r->cve_ent] ?? ('Estado ' . $r->cve_ent),
            'cve_ent' => $r->cve_ent,
            'total'   => (int) $r->total,
        ]);

        $baseQ = DB::table('table_data')
            ->join('users', 'table_data.captured_by', '=', 'users.iduserId');

        if ($role === 'estatal_admin' && $user->cve_ent)       $baseQ->where('users.cve_ent', $user->cve_ent);
        if ($role === 'municipal_admin' && $user->cve_mun)     $baseQ->where('users.cve_mun', $user->cve_mun);
        if (in_array($role, ['seccion_admin', 'field_workforce'])) $baseQ->where('table_data.captured_by', $user->iduserId);

        $totalRecords = (clone $baseQ)->count();
        $withGps      = (clone $baseQ)->whereNotNull('table_data.lat')->count();
        $today        = (clone $baseQ)->whereDate('table_data.created_at', now()->toDateString())->count();
        $thisWeek     = (clone $baseQ)->whereBetween('table_data.created_at', [now()->startOfWeek(), now()->endOfWeek()])->count();
        $activeUsers  = (clone $baseQ)->distinct()->count('table_data.captured_by');

        return response()->json([
            'top_users'      => $topUsers,
            'top_secciones'  => $topSecciones,
            'top_municipios' => $topMunicipios,
            'top_estados'    => $topEstados,
            'activity'       => [
                'total'        => $totalRecords,
                'today'        => $today,
                'this_week'    => $thisWeek,
                'with_gps'     => $withGps,
                'without_gps'  => $totalRecords - $withGps,
                'active_users' => $activeUsers,
                'gps_pct'      => $totalRecords > 0 ? round($withGps / $totalRecords * 100, 1) : 0,
            ],
        ]);
    }
}
