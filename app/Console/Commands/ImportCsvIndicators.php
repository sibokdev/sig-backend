<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Bulk-import indicator_values_XXXX_XXXX.csv produced by process_geodesico.py.
 *
 * Usage:
 *   php artisan gis:import-csv ../tools/output/indicators/indicator_values_2016_2026.csv
 *
 * Expected CSV columns (header row required):
 *   indicador_clave, year, period, cve_ent, cve_mun, cve_seccion, value, source
 *
 * Streams the file to avoid OOM on large CSVs (145k+ rows).
 * Upserts in batches of 500.
 */
class ImportCsvIndicators extends Command
{
    protected $signature   = 'gis:import-csv {file : Path to indicator_values CSV} {--batch=500 : Rows per DB upsert}';
    protected $description = 'Bulk-import indicator values from CSV into indicator_values table';

    public function handle(): int
    {
        $file      = $this->argument('file');
        $batchSize = max(1, (int) $this->option('batch'));

        if (!file_exists($file)) {
            $this->error("File not found: {$file}");
            return self::FAILURE;
        }

        $sizeMb = round(filesize($file) / 1_048_576, 1);
        $this->info("Importing {$file} ({$sizeMb} MB) — batch size: {$batchSize}…");

        $fh = fopen($file, 'r');
        if (!$fh) {
            $this->error("Cannot open: {$file}");
            return self::FAILURE;
        }

        // Read header and map column positions
        $header = fgetcsv($fh);
        $header = array_map('trim', $header);
        $idx    = array_flip($header);

        $required = ['indicador_clave', 'year', 'cve_ent', 'value'];
        foreach ($required as $col) {
            if (!isset($idx[$col])) {
                $this->error("Missing required column '{$col}'. Found: " . implode(', ', $header));
                fclose($fh);
                return self::FAILURE;
            }
        }

        $batch       = [];
        $total       = 0;
        $skipped     = 0;
        $now         = now();
        $claves      = [];
        $states      = [];

        while (($row = fgetcsv($fh)) !== false) {
            $clave  = trim($row[$idx['indicador_clave']] ?? '');
            $cveEnt = trim($row[$idx['cve_ent']]        ?? '');
            $value  = $row[$idx['value']] ?? '';
            $year   = (int) ($row[$idx['year']] ?? 0);

            // Validate required fields
            if ($clave === '' || $cveEnt === '' || !is_numeric($value) || $year < 1900) {
                $skipped++;
                continue;
            }

            // Normalize: cve_ent must be 2-digit, skip national (00)
            $cveEnt = str_pad($cveEnt, 2, '0', STR_PAD_LEFT);
            if ($cveEnt === '00') { $skipped++; continue; }

            $cveMun    = trim($row[$idx['cve_mun']]    ?? '');
            $cveSec    = trim($row[$idx['cve_seccion']] ?? '');
            $period    = trim($row[$idx['period']]      ?? (string) $year);
            $source    = trim($row[$idx['source']]      ?? 'XLS');

            // '' is the sentinel for "not applicable" (see migration 000005)
            $cveMun = ($cveMun === '' || $cveMun === '000') ? '' : str_pad($cveMun, 3, '0', STR_PAD_LEFT);
            $cveSec = $cveSec === '' ? '' : $cveSec;

            $batch[] = [
                'indicador_clave' => $clave,
                'year'            => $year,
                'period'          => $period,
                'cve_ent'         => $cveEnt,
                'cve_mun'         => $cveMun,
                'cve_seccion'     => $cveSec,
                'value'           => (float) $value,
                'source'          => $source,
                'created_at'      => $now,
                'updated_at'      => $now,
            ];

            $claves[$clave]  = true;
            $states[$cveEnt] = true;

            if (count($batch) >= $batchSize) {
                $this->upsertBatch($batch);
                $total += count($batch);
                $batch  = [];
                gc_collect_cycles();

                if ($total % 10000 === 0) {
                    $this->line("  Processed: {$total}");
                }
            }
        }

        // Flush remainder
        if (!empty($batch)) {
            $this->upsertBatch($batch);
            $total += count($batch);
        }

        fclose($fh);

        $dbCount = DB::table('indicator_values')->count();
        $this->info(sprintf(
            "Done.  Imported: %d rows   Skipped: %d   Unique indicators: %d   States: %d   Total in DB: %d",
            $total, $skipped, count($claves), count($states), $dbCount
        ));

        return self::SUCCESS;
    }

    private function upsertBatch(array $rows): void
    {
        DB::table('indicator_values')->upsert(
            $rows,
            ['indicador_clave', 'year', 'period', 'cve_ent', 'cve_mun', 'cve_seccion'],
            ['value', 'source', 'updated_at']
        );
    }
}
