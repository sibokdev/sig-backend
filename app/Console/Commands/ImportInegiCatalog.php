<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class ImportInegiCatalog extends Command
{
    protected $signature   = 'inegi:import-catalog {--source=all : BISE, BIE, or all}';
    protected $description = 'Import all INEGI indicator claves from the official CL_INDICATOR catalog into indicadores_catalog';

    private string $token;
    private string $base = 'https://www.inegi.org.mx/app/api/indicadores/desarrolladores/jsonxml';

    public function handle(): int
    {
        $this->token = env('INEGI_INDICADORES_TOKEN', '');
        if (!$this->token) {
            $this->error('INEGI_INDICADORES_TOKEN not set in .env');
            return self::FAILURE;
        }

        $source = strtoupper($this->option('source'));
        $sources = match($source) {
            'BISE' => ['BISE'],
            'BIE'  => ['BIE'],
            default => ['BISE', 'BIE'],
        };

        $totalImported = 0;

        foreach ($sources as $src) {
            $this->info("Fetching topics for {$src}...");
            $topicMap = $this->fetchTopicMap($src);
            $this->line("  Topics loaded: " . count($topicMap));

            $this->info("Fetching indicator catalog for {$src}...");
            $indicators = $this->fetchIndicators($src, $topicMap);

            if (empty($indicators)) {
                $this->warn("  No indicators returned for {$src} — check token or API availability.");
                continue;
            }

            $this->info("  Upserting " . count($indicators) . " indicators...");
            $this->upsertBatch($indicators);
            $totalImported += count($indicators);
            $this->line("  Done: {$src}");
        }

        $total = DB::table('indicadores_catalog')->count();
        $this->info("Import complete. Imported/updated: {$totalImported} — Total in catalog: {$total}");

        return self::SUCCESS;
    }

    // ── Fetch topic map: topicCode → category name ───────────────────────────
    private function fetchTopicMap(string $source): array
    {
        $url = "{$this->base}/CL_TOPIC/null/es/{$source}/2.0/{$this->token}?type=json";

        try {
            $res = Http::withoutVerifying()->timeout(60)->get($url);
            if ($res->failed()) return [];
            $data = $res->json();
        } catch (\Exception $e) {
            $this->warn("  Could not fetch topics: " . $e->getMessage());
            return [];
        }

        $map = [];
        $codes = $this->extractCodes($data);
        foreach ($codes as $code) {
            $key  = trim($code['value'] ?? $code['id'] ?? '');
            $desc = trim($code['description'] ?? $code['name'] ?? '');
            if ($key !== '' && $desc !== '') {
                $map[$key] = $desc;
            }
        }
        return $map;
    }

    // ── Fetch all indicators and map to categories ───────────────────────────
    private function fetchIndicators(string $source, array $topicMap): array
    {
        $url = "{$this->base}/CL_INDICATOR/null/es/{$source}/2.0/{$this->token}?type=json";

        try {
            $res = Http::withoutVerifying()->timeout(120)->get($url);
            if ($res->failed()) {
                $this->warn("  HTTP {$res->status()} from CL_INDICATOR/{$source}");
                return [];
            }
            $data = $res->json();
        } catch (\Exception $e) {
            $this->warn("  Request failed: " . $e->getMessage());
            return [];
        }

        $codes = $this->extractCodes($data);
        $rows  = [];

        foreach ($codes as $code) {
            $clave  = trim($code['value'] ?? $code['id'] ?? '');
            $nombre = trim($code['description'] ?? $code['name'] ?? '');
            if ($clave === '' || $nombre === '') continue;

            // Derive category from topicMap using first 4 digits of clave
            $prefix   = substr($clave, 0, 4);
            $categoria = $topicMap[$prefix]
                ?? $topicMap[substr($clave, 0, 3)]
                ?? $topicMap[substr($clave, 0, 2)]
                ?? $source; // fallback to source name

            $rows[] = [
                'clave'       => $clave,
                'nombre'      => $nombre,
                'categoria'   => $categoria,
                'unidad'      => '',
                'fuente'      => $source,
                'descripcion' => '',
                'verificado'  => true,
            ];
        }

        return $rows;
    }

    // ── Upsert in chunks to avoid packet-size limits ─────────────────────────
    private function upsertBatch(array $rows): void
    {
        foreach (array_chunk($rows, 200) as $chunk) {
            DB::table('indicadores_catalog')->upsert(
                $chunk,
                ['clave'],                            // unique key
                ['nombre', 'categoria', 'unidad', 'fuente', 'descripcion', 'verificado']
            );
        }
    }

    // ── Parse INEGI's varied response shapes ─────────────────────────────────
    // INEGI may return:  { "CodeLists": { "CodeList": { "Code": [...] } } }
    //                 or { "CodeList": [ { "Code": [...] } ] }
    //                 or a flat array of { "value":"...", "description":"..." }
    private function extractCodes(array $data): array
    {
        // Flat array shortcut
        if (isset($data[0]['value']) || isset($data[0]['id'])) {
            return $data;
        }

        // Navigate common nesting patterns
        $node = $data['CodeLists']['CodeList'] ?? $data['CodeList'] ?? null;

        if ($node === null) return [];

        // CodeList might be a list of lists
        if (isset($node[0])) {
            $codes = [];
            foreach ($node as $list) {
                $inner = $list['Code'] ?? [];
                foreach ($inner as $c) $codes[] = $c;
            }
            return $codes;
        }

        // Single CodeList
        $codes = $node['Code'] ?? [];
        if (isset($codes['@value'])) {
            // Single item (not an array of items)
            return [$codes];
        }
        return $codes;
    }
}
