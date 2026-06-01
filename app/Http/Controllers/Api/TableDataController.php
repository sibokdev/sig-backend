<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\TableConfig;
use App\Models\TableData;
use League\Csv\Reader;

class TableDataController extends Controller
{
    /**
     * Return ALL records across ALL configs matching optional geo filters.
     * Used by the dashboard map when a geo filter is active.
     * GET /table/data/all?cve_ent=29&cve_mun=089
     */
    public function getAllData(Request $request)
    {
        $user = auth()->user();
        $role = $user->role ?? 'seccion_admin';

        $query = TableData::query()
            ->leftJoin('users', 'table_data.captured_by', '=', 'users.iduserId')
            ->select(
                'table_data.*',
                'users.username     as captured_by_name',
                'users.cve_ent      as user_cve_ent',
                'users.cve_mun      as user_cve_mun'
            );

        // Mandatory role scope
        if ($role === 'national_admin') {
            $configIds = TableConfig::where('national_admin_id', $user->iduserId)->pluck('id');
            $query->whereIn('table_data.config_id', $configIds);
        } elseif ($role === 'estatal_admin' && $user->cve_ent) {
            $query->where('users.cve_ent', $user->cve_ent);
        } elseif ($role === 'municipal_admin' && $user->cve_mun) {
            $query->where('users.cve_mun', $user->cve_mun);
        } elseif (in_array($role, ['seccion_admin', 'field_workforce'])) {
            $query->where('table_data.captured_by', $user->iduserId);
        }

        // Optional geo filters (available to all roles that pass the mandatory scope above)
        if ($request->filled('cve_ent')) {
            $query->where('users.cve_ent', $request->cve_ent);
        }
        if ($request->filled('cve_mun')) {
            $query->where('users.cve_mun', $request->cve_mun);
        }

        // Only return records that have GPS coordinates (for map rendering)
        $query->whereNotNull('table_data.lat')->whereNotNull('table_data.lng');

        $records = $query->get();

        return response()->json(
            $records->map(fn($r) => [
                '_id'          => $r->id,
                '_config_id'   => $r->config_id,
                '_captured_by' => $r->captured_by_name,
                '_cve_ent'     => $r->user_cve_ent,
                '_cve_mun'     => $r->user_cve_mun,
                '_lat'         => $r->lat,
                '_lng'         => $r->lng,
            ])
        );
    }

    /**
     * Create (POST) or update (PUT) a table config.
     */
    public function saveTableConfig(Request $request, $configId = null)
    {
        $data = [
            'table_name'        => $request->input('tableName', 'Tabla sin nombre'),
            'config'            => $request->input('config', []),
            'user_in_charge'    => $request->input('user_in_charge'),
            'goal'              => $request->input('goal'),
            'national_admin_id' => $request->input('national_admin_id'),
        ];

        if ($configId) {
            $config = TableConfig::findOrFail($configId);
            $config->update($data);
        } else {
            $config = TableConfig::create($data);
        }

        return response()->json(['ok' => true, 'config' => $config->load('nationalAdmin')]);
    }

    /**
     * Delete a table config (and its records via cascade).
     */
    public function deleteTableConfig($configId)
    {
        TableConfig::findOrFail($configId)->delete();
        return response()->json(['ok' => true]);
    }

    /**
     * Return configs with role-based scoping.
     * - admin: all configs; optional ?national_admin_id filter
     * - national_admin: only configs linked to their account
     * - seccion_admin / field_workforce: only configs they are in charge of
     */
    public function listConfigs(Request $request)
    {
        $user = auth()->user();
        $q = TableConfig::with('nationalAdmin:iduserId,username')
            ->orderBy('national_admin_id')
            ->orderBy('id');

        if ($user->role === 'national_admin') {
            $q->where('national_admin_id', $user->iduserId);
        } elseif (in_array($user->role, ['seccion_admin', 'field_workforce'])) {
            $q->where('user_in_charge', $user->iduserId);
        } elseif ($user->role === 'admin' && $request->filled('national_admin_id')) {
            $q->where('national_admin_id', $request->national_admin_id);
        }

        return response()->json($q->get());
    }

    /**
     * Return a single config by its ID.
     */
    public function getTableConfig($configId = 1)
    {
        $list = TableConfig::where('id', $configId)->get();
        return response()->json($list);
    }

    /**
     * Return all data rows for a config with captured_by username.
     * Geo-scoped automatically based on authenticated user's role.
     */
    public function getData(Request $request, TableConfig $config)
    {
        $user  = auth()->user();
        $query = TableData::where('config_id', $config->id)
            ->leftJoin('users', 'table_data.captured_by', '=', 'users.iduserId')
            ->select('table_data.*', 'users.username as captured_by_name',
                     'users.cve_ent as user_cve_ent', 'users.cve_mun as user_cve_mun');

        $role = $user->role ?? 'seccion_admin';

        // Role-based mandatory geo scope
        if ($role === 'national_admin') {
            $configIds = TableConfig::where('national_admin_id', $user->iduserId)->pluck('id');
            $query->whereIn('table_data.config_id', $configIds);
        } elseif ($role === 'estatal_admin' && $user->cve_ent) {
            $cve_ent = $user->cve_ent;
            $query->where(function ($q) use ($cve_ent) {
                $q->where('users.cve_ent', $cve_ent)
                  ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(table_data.data, '$.cve_ent')) = ?", [$cve_ent]);
            });
        } elseif ($role === 'municipal_admin' && $user->cve_mun) {
            $query->where('users.cve_mun', $user->cve_mun);
        } elseif (in_array($role, ['seccion_admin', 'field_workforce'])) {
            $query->where('table_data.captured_by', $user->iduserId);
        }

        // Optional geo filters for admin / national_admin (from query string)
        if (in_array($role, ['admin', 'national_admin'])) {
            if ($request->filled('cve_ent')) {
                $query->where('users.cve_ent', $request->cve_ent);
            }
            if ($request->filled('cve_mun')) {
                $query->where('users.cve_mun', $request->cve_mun);
            }
        }

        $records = $query->get();

        return response()->json(
            $records->map(fn($r) => array_merge(
                [
                    '_id'           => $r->id,
                    '_captured_by'  => $r->captured_by_name,
                    '_cve_ent'      => $r->user_cve_ent,
                    '_cve_mun'      => $r->user_cve_mun,
                    '_lat'          => $r->lat,
                    '_lng'          => $r->lng,
                ],
                (array) $r->data
            ))
        );
    }

    /**
     * Insert a single data row.
     */
    public function store(Request $request, TableConfig $config)
    {
        $validated = $request->validate([
            'data' => 'required|array',
            'lat'  => 'nullable|numeric',
            'lng'  => 'nullable|numeric',
        ]);

        $record = TableData::create([
            'config_id'   => $config->id,
            'data'        => $validated['data'],
            'captured_by' => auth()->id(),
            'lat'         => $validated['lat'] ?? null,
            'lng'         => $validated['lng'] ?? null,
        ]);

        return response()->json([
            'message' => 'Registro guardado',
            'record'  => array_merge(['_id' => $record->id], (array) $record->data),
        ]);
    }

    /**
     * Upload a media file (photo / video) for a table config.
     * Returns the public URL.
     */
    public function uploadMedia(Request $request, TableConfig $config)
    {
        $request->validate([
            'file' => 'required|file|mimes:jpg,jpeg,png,gif,webp,mp4,mov,avi,webm|max:51200',
        ]);

        $file  = $request->file('file');
        $path  = $file->store("table-media/{$config->id}", 'public');
        $url   = Storage::disk('public')->url($path);

        return response()->json([
            'url'  => $url,
            'name' => $file->getClientOriginalName(),
        ]);
    }

    /**
     * Download a blank CSV template with the correct headers for this config.
     * GET /table/{config}/csv-template
     */
    public function csvTemplate(TableConfig $config)
    {
        $fields = collect($config->config)->pluck('field')->implode(',');
        $filename = 'plantilla_' . str_replace(' ', '_', strtolower($config->table_name)) . '.csv';

        return response($fields . "\n")
            ->header('Content-Type', 'text/csv; charset=UTF-8')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    /**
     * Bulk import via CSV.
     * Column headers must match the configured field names (order-independent).
     */
    public function uploadCsv(Request $request, TableConfig $config)
    {
        $request->validate(['file' => 'required|file|max:20480']);

        $expectedFields = collect($config->config)->pluck('field')->toArray();

        $path = $request->file('file')->getRealPath();

        // ── Auto-detect delimiter (comma vs semicolon) ────────────────────
        $firstLine = fgets(fopen($path, 'r'));
        $delimiter = substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';

        $csv = Reader::createFromPath($path, 'r');
        $csv->setDelimiter($delimiter);
        $csv->setHeaderOffset(0);

        // ── Strip BOM and trim whitespace from headers ────────────────────
        $rawHeaders = $csv->getHeader();
        $headers = array_map(function ($h) {
            // Remove UTF-8 BOM (\xEF\xBB\xBF) that Excel adds to first column
            return trim(preg_replace('/^\xEF\xBB\xBF/', '', $h));
        }, $rawHeaders);

        // ── Ignore internal _id / _meta columns ──────────────────────────
        $csvFields = array_values(array_filter($headers, fn($h) => $h !== '' && !str_starts_with($h, '_')));

        // ── Compare sorted field lists ────────────────────────────────────
        $sortedCsv      = $csvFields;
        $sortedExpected = $expectedFields;
        sort($sortedCsv);
        sort($sortedExpected);

        if ($sortedCsv !== $sortedExpected) {
            $missing = array_values(array_diff($sortedExpected, $sortedCsv));
            $extra   = array_values(array_diff($sortedCsv, $sortedExpected));
            return response()->json([
                'error'    => 'Las columnas del CSV no coinciden con la configuración de la tabla.',
                'expected' => $expectedFields,
                'received' => $csvFields,
                'missing'  => $missing,
                'extra'    => $extra,
            ], 422);
        }

        // ── Import records ────────────────────────────────────────────────
        $records = iterator_to_array($csv->getRecords($headers));
        $imported = 0;

        foreach ($records as $record) {
            // Keep only configured fields, strip internal keys, trim values
            $data = [];
            foreach ($expectedFields as $field) {
                $data[$field] = isset($record[$field]) ? trim((string) $record[$field]) : '';
            }
            // Skip completely empty rows
            if (implode('', array_values($data)) === '') continue;

            TableData::create(['config_id' => $config->id, 'data' => $data]);
            $imported++;
        }

        return response()->json([
            'message'       => 'CSV procesado correctamente',
            'total_records' => $imported,
            'skipped'       => count($records) - $imported,
        ]);
    }
}
