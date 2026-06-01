<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use App\Models\GeoState;

/**
 * Pull INEGI indicator values for all states and store them in indicator_values.
 *
 * Usage:
 *   php artisan gis:import-indicators 1002000001 --source=BISE
 *   php artisan gis:import-indicators 1002000001 --source=BISE --state=21
 *   php artisan gis:import-indicators 444612 --source=BIE
 *
 * INEGI returns one Series per geographic area when area=00 is requested.
 * Each Series has OBSERVATIONS[] with TIME_PERIOD and OBS_VALUE.
 * We store the most-recent observation per area into indicator_values.
 */
class ImportIndicators extends Command
{
    protected $signature   = 'gis:import-indicators {clave : Indicator clave (e.g. 1002000001)} {--source=BISE} {--state= : Import only a single state (cve_ent, e.g. 21)}';
    protected $description = 'Fetch INEGI API indicator values for all states and store in indicator_values';

    private string $token;
    private string $base = 'https://www.inegi.org.mx/app/api/indicadores/desarrolladores/jsonxml';

    public function handle(): int
    {
        $this->token  = env('INEGI_INDICADORES_TOKEN', '');
        $clave        = $this->argument('clave');
        $source       = strtoupper($this->option('source'));
        $filterState  = $this->option('state');

        if (!$this->token) {
            $this->error('INEGI_INDICADORES_TOKEN not set in .env');
            return self::FAILURE;
        }

        $this->info("Fetching indicator {$clave} ({$source}) from INEGI…");

        // area=00 returns all Series (one per geographic area that has data)
        $url = "{$this->base}/INDICATOR/{$clave}/es/00/false/{$source}/2.0/{$this->token}?type=json";

        try {
            $res = Http::withoutVerifying()->timeout(120)->get($url);
        } catch (\Exception $e) {
            $this->error("HTTP error: " . $e->getMessage());
            return self::FAILURE;
        }

        if ($res->failed()) {
            $body = $res->body();
            $this->error("INEGI returned HTTP {$res->status()}: {$body}");
            return self::FAILURE;
        }

        $data       = $res->json();
        $allSeries  = $data['Series'] ?? [];

        if (empty($allSeries)) {
            $this->warn("No Series returned — check clave and source.");
            return self::FAILURE;
        }

        $this->info("  Series returned: " . count($allSeries));

        // Get valid state codes for validation
        $validStates = GeoState::pluck('cve_ent')->toArray();

        $rows    = [];
        $skipped = 0;

        foreach ($allSeries as $series) {
            $observations = $series['OBSERVATIONS'] ?? [];
            if (empty($observations)) continue;

            // INEGI area code for state-level is zero-padded 2-digit (e.g. "01"…"32")
            $area = trim($series['AREA'] ?? '');
            if (strlen($area) !== 2) {
                // Skip national aggregate (area="00") and non-state areas
                $skipped++;
                continue;
            }

            if ($filterState && $area !== str_pad($filterState, 2, '0', STR_PAD_LEFT)) {
                continue;
            }

            if (!empty($validStates) && !in_array($area, $validStates)) {
                $skipped++;
                continue;
            }

            // Sort observations by TIME_PERIOD descending, take all
            usort($observations, fn($a, $b) => strcmp($b['TIME_PERIOD'], $a['TIME_PERIOD']));

            foreach ($observations as $obs) {
                $rawValue = $obs['OBS_VALUE'] ?? null;
                if ($rawValue === null || $rawValue === '') continue;

                $period = $obs['TIME_PERIOD'] ?? '';
                $year   = (int) substr($period, 0, 4);
                if ($year < 1900 || $year > 2100) continue;

                $rows[] = [
                    'indicador_clave' => $clave,
                    'year'            => $year,
                    'period'          => $period,           // '' if missing (NOT NULL column)
                    'cve_ent'         => $area,
                    'cve_mun'         => '',                // '' = state-level (NOT NULL)
                    'cve_seccion'     => '',                // '' = no section   (NOT NULL)
                    'value'           => (float) $rawValue,
                    'source'          => $source,
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ];
            }
        }

        if (empty($rows)) {
            $this->warn("No valid observations found. Skipped series: {$skipped}");
            return self::FAILURE;
        }

        // Upsert in chunks of 200
        foreach (array_chunk($rows, 200) as $chunk) {
            DB::table('indicator_values')->upsert(
                $chunk,
                ['indicador_clave', 'year', 'period', 'cve_ent', 'cve_mun', 'cve_seccion'],
                ['value', 'source', 'updated_at']
            );
        }

        $total = DB::table('indicator_values')->where('indicador_clave', $clave)->count();
        $this->info("Stored: " . count($rows) . " observations — Total in DB for {$clave}: {$total}   Skipped: {$skipped}");

        return self::SUCCESS;
    }
}
