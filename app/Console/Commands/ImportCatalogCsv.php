<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Bulk-import the indicadores_catalog.csv produced by process_geodesico.py.
 *
 * Usage:
 *   php artisan gis:import-catalog-csv ../tools/output/indicators/indicadores_catalog.csv
 *
 * Expected CSV columns (header row required):
 *   clave, nombre, categoria, unidad, fuente, descripcion, verificado
 */
class ImportCatalogCsv extends Command
{
    protected $signature   = 'gis:import-catalog-csv {file : Path to indicadores_catalog.csv}';
    protected $description = 'Bulk-import indicator catalog from CSV into indicadores_catalog table';

    public function handle(): int
    {
        $file = $this->argument('file');

        if (!file_exists($file)) {
            $this->error("File not found: {$file}");
            return self::FAILURE;
        }

        $fh = fopen($file, 'r');
        if (!$fh) {
            $this->error("Cannot open: {$file}");
            return self::FAILURE;
        }

        // Read header row and build column index map
        $header = fgetcsv($fh);
        $header = array_map('trim', $header);
        $idx    = array_flip($header);

        $required = ['clave', 'nombre'];
        foreach ($required as $col) {
            if (!isset($idx[$col])) {
                $this->error("Missing required column: {$col}");
                fclose($fh);
                return self::FAILURE;
            }
        }

        $batch   = [];
        $total   = 0;

        while (($row = fgetcsv($fh)) !== false) {
            $clave = trim($row[$idx['clave']] ?? '');
            if ($clave === '') continue;

            $batch[] = [
                'clave'       => $clave,
                'nombre'      => trim($row[$idx['nombre']]      ?? '')         ?: $clave,
                'categoria'   => trim($row[$idx['categoria']]   ?? ''),
                'unidad'      => trim($row[$idx['unidad']]      ?? ''),
                'fuente'      => trim($row[$idx['fuente']]      ?? 'XLS'),
                'descripcion' => trim($row[$idx['descripcion']] ?? ''),
                'verificado'  => (int) ($row[$idx['verificado']] ?? 1),
            ];

            if (count($batch) >= 200) {
                $this->flushCatalogBatch($batch);
                $total += count($batch);
                $batch  = [];
            }
        }

        // Flush remainder
        if (!empty($batch)) {
            $this->flushCatalogBatch($batch);
            $total += count($batch);
        }

        fclose($fh);

        $dbCount = DB::table('indicadores_catalog')->count();
        $this->info("Imported: {$total} entries   Total in DB: {$dbCount}");
        return self::SUCCESS;
    }

    /**
     * INSERT ... ON DUPLICATE KEY UPDATE without touching timestamps
     * (indicadores_catalog has no created_at/updated_at columns).
     */
    private function flushCatalogBatch(array $rows): void
    {
        if (empty($rows)) return;

        $cols        = ['clave','nombre','categoria','unidad','fuente','descripcion','verificado'];
        $placeholders = implode(', ', array_fill(0, count($cols), '?'));
        $rowPlaceholders = implode(', ', array_fill(0, count($rows), "({$placeholders})"));

        $updateCols = ['nombre','categoria','unidad','fuente','descripcion','verificado'];
        $updateSql  = implode(', ', array_map(fn($c) => "`{$c}` = VALUES(`{$c}`)", $updateCols));

        $sql = "INSERT INTO `indicadores_catalog` (`" . implode('`,`', $cols) . "`)
                VALUES {$rowPlaceholders}
                ON DUPLICATE KEY UPDATE {$updateSql}";

        $bindings = [];
        foreach ($rows as $row) {
            foreach ($cols as $col) {
                $bindings[] = $row[$col] ?? null;
            }
        }

        DB::statement($sql, $bindings);
    }
}
