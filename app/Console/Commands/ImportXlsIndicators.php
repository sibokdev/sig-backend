<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Import indicator values from an XLS/XLSX/CSV file.
 *
 * Usage:
 *   php artisan gis:import-xls storage/app/geo/poblacion.xlsx 1002000001
 *
 * Expected columns (flexible order, detected by header name):
 *   CVE_ENT  — 2-digit state code (or state name, mapped automatically)
 *   CVE_MUN  — 3-digit municipality code (optional)
 *   VALUE    — numeric value
 *   YEAR     — 4-digit year
 *   PERIOD   — optional (e.g. 2020/01)
 *
 * Requires: composer require phpoffice/phpspreadsheet
 */
class ImportXlsIndicators extends Command
{
    protected $signature   = 'gis:import-xls {file} {clave : Indicator clave} {--source=XLS}';
    protected $description = 'Import indicator values from XLS/XLSX/CSV into indicator_values';

    public function handle(): int
    {
        $file   = $this->argument('file');
        $clave  = $this->argument('clave');
        $source = strtoupper($this->option('source'));

        if (!file_exists($file)) {
            $this->error("File not found: {$file}");
            return self::FAILURE;
        }

        if (!class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class)) {
            $this->error("phpspreadsheet not installed. Run: composer require phpoffice/phpspreadsheet");
            return self::FAILURE;
        }

        $this->info("Loading {$file}…");

        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file);
        $sheet       = $spreadsheet->getActiveSheet();
        $rows        = $sheet->toArray(null, true, true, false);

        if (empty($rows)) {
            $this->error("Empty spreadsheet");
            return self::FAILURE;
        }

        // Detect column indexes from header row
        $header  = array_map('strtoupper', array_map('trim', $rows[0]));
        $colMap  = [];
        foreach ($header as $idx => $col) {
            foreach (['CVE_ENT','CVE_MUN','VALUE','YEAR','PERIOD'] as $key) {
                if (str_contains($col, $key)) $colMap[$key] = $idx;
            }
        }

        if (!isset($colMap['CVE_ENT'], $colMap['VALUE'], $colMap['YEAR'])) {
            $this->error("Could not find required columns CVE_ENT, VALUE, YEAR in header: " . implode(',', $header));
            return self::FAILURE;
        }

        $this->info("Header mapped: " . json_encode($colMap));

        $importRows = [];
        $errors     = 0;

        foreach (array_slice($rows, 1) as $row) {
            $cveEnt  = str_pad((string) intval($row[$colMap['CVE_ENT']] ?? 0), 2, '0', STR_PAD_LEFT);
            $cveMun  = isset($colMap['CVE_MUN']) ? str_pad((string) intval($row[$colMap['CVE_MUN']]), 3, '0', STR_PAD_LEFT) : null;
            $value   = $row[$colMap['VALUE']] ?? null;
            $year    = (int) ($row[$colMap['YEAR']] ?? 0);
            $period  = isset($colMap['PERIOD']) ? (string) ($row[$colMap['PERIOD']] ?? '') : (string) $year;

            if (!is_numeric($value) || $year < 1900 || $cveEnt === '00') { $errors++; continue; }

            $importRows[] = [
                'indicador_clave' => $clave,
                'year'            => $year,
                'period'          => $period,
                'cve_ent'         => $cveEnt,
                'cve_mun'         => ($cveMun === '000' ? '' : $cveMun),
                'cve_seccion'     => '',
                'value'           => (float) $value,
                'source'          => $source,
                'created_at'      => now(),
                'updated_at'      => now(),
            ];
        }

        foreach (array_chunk($importRows, 200) as $chunk) {
            DB::table('indicator_values')->upsert(
                $chunk,
                ['indicador_clave', 'year', 'period', 'cve_ent', 'cve_mun', 'cve_seccion'],
                ['value', 'source', 'updated_at']
            );
        }

        $this->info("Imported: " . count($importRows) . "   Skipped/errors: {$errors}");
        return self::SUCCESS;
    }
}
