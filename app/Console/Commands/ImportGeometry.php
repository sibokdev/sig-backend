<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Import INEGI Marco Geoestadístico shapefiles (pre-converted to GeoJSON with ogr2ogr).
 *
 * Usage:
 *   php artisan gis:import-geometry states        storage/app/geo/states.geojson
 *   php artisan gis:import-geometry municipalities storage/app/geo/municipalities.geojson
 *   php artisan gis:import-geometry sections      storage/app/geo/sections.geojson
 *
 * Expected GeoJSON property keys (INEGI standard):
 *   States:        CVE_ENT, NOMGEO
 *   Municipalities: CVE_ENT, CVE_MUN, NOMGEO
 *   Sections:      CVE_ENT, CVE_MUN, SECCION (or CVE_SECCION)
 */
class ImportGeometry extends Command
{
    protected $signature   = 'gis:import-geometry {level : states|municipalities|sections} {file : path to GeoJSON} {--batch=50 : Features per DB upsert batch}';
    protected $description = 'Import INEGI Marco Geoestadístico GeoJSON into geo_* tables (streams large files to avoid OOM)';

    // Max polygon vertices kept after simplification
    private const MAX_SIMPLIFIED_VERTICES = 150;

    public function handle(): int
    {
        $level     = $this->argument('level');
        $file      = $this->argument('file');
        $batchSize = max(1, (int) $this->option('batch'));

        if (!in_array($level, ['states', 'municipalities', 'sections'])) {
            $this->error("level must be: states | municipalities | sections");
            return self::FAILURE;
        }

        if (!file_exists($file)) {
            $this->error("File not found: {$file}");
            return self::FAILURE;
        }

        $sizeMb = round(filesize($file) / 1_048_576, 1);
        $this->info("Streaming {$file} ({$sizeMb} MB) as {$level} — batch size: {$batchSize}…");

        $total  = 0;
        $errors = 0;
        $batch  = [];

        foreach ($this->streamFeatures($file) as $feature) {
            try {
                $row = $this->mapFeature($level, $feature);
                if ($row) $batch[] = $row;
            } catch (\Throwable $e) {
                $errors++;
                if ($errors <= 10) $this->warn("  Skipped feature: " . $e->getMessage());
            }

            if (count($batch) >= $batchSize) {
                $this->upsert($level, $batch);
                $total += count($batch);
                $batch  = [];
                gc_collect_cycles();
                $this->line("  Processed: {$total}");
            }
        }

        // Flush remainder
        if (!empty($batch)) {
            $this->upsert($level, $batch);
            $total += count($batch);
        }

        $this->info("Done. Imported: {$total}   Skipped: {$errors}");
        return self::SUCCESS;
    }

    /**
     * Stream features one at a time from a GeoJSON file without loading it fully.
     *
     * Handles two common GeoJSON layouts:
     *   1. One feature per line (geopandas default output)
     *   2. Compact/pretty-printed single-object (standard spec)
     *
     * Strategy for layout 2: scan char-by-char to find each { "type":"Feature"... }
     * object by tracking brace depth — zero allocation per feature until we JSON-decode it.
     */
    private function streamFeatures(string $file): \Generator
    {
        $fh = fopen($file, 'r');
        if (!$fh) throw new \RuntimeException("Cannot open {$file}");

        // Peek at first 4 KB to detect layout
        $peek = fread($fh, 4096);
        rewind($fh);

        // Layout 1: features are separated by newlines (geopandas writes one per line)
        // Detect by checking if a line by itself looks like a complete Feature object
        if ($this->isNewlineDelimited($peek)) {
            yield from $this->streamNewlineDelimited($fh);
        } else {
            yield from $this->streamBraceDepth($fh);
        }

        fclose($fh);
    }

    private function isNewlineDelimited(string $peek): bool
    {
        // Signal 1: closing brace then newline then opening brace (compact newline-delimited)
        if (preg_match('/\}\s*\n\s*\{/', $peek)) return true;

        // Signal 2: "features" array with one feature per line starting at column 0
        // (geopandas writes: "features": [\n{ "type": "Feature"...)
        if (preg_match('/^(\s*)\{\s*"type"\s*:\s*"Feature"/m', $peek)) return true;

        return false;
    }

    /** Layout 1: each line is a complete GeoJSON feature (or wrapper line to skip) */
    private function streamNewlineDelimited($fh): \Generator
    {
        while (!feof($fh)) {
            $line = trim(fgets($fh));
            if ($line === '' || $line === '[' || $line === ']') continue;
            $line = rtrim($line, ',');   // trailing commas in arrays
            $feature = json_decode($line, true);
            if (isset($feature['type']) && $feature['type'] === 'Feature') {
                yield $feature;
            }
        }
    }

    /**
     * Layout 2: standard GeoJSON — scan byte-by-byte tracking brace depth.
     * When depth returns to 1 after going deeper, we have one complete feature object.
     */
    private function streamBraceDepth($fh): \Generator
    {
        $depth   = 0;
        $inStr   = false;
        $escape  = false;
        $buf     = '';
        $capture = false;

        while (!feof($fh)) {
            $chunk = fread($fh, 65536); // 64 KB at a time
            $len   = strlen($chunk);

            for ($i = 0; $i < $len; $i++) {
                $ch = $chunk[$i];

                if ($escape) { $escape = false; if ($capture) $buf .= $ch; continue; }
                if ($ch === '\\' && $inStr) { $escape = true; if ($capture) $buf .= $ch; continue; }
                if ($ch === '"') { $inStr = !$inStr; if ($capture) $buf .= $ch; continue; }
                if ($inStr) { if ($capture) $buf .= $ch; continue; }

                if ($ch === '{') {
                    $depth++;
                    if ($depth === 2) { $capture = true; $buf = '{'; continue; }
                }
                if ($ch === '}') {
                    if ($capture) $buf .= $ch;
                    $depth--;
                    if ($depth === 1 && $capture) {
                        $feature = json_decode($buf, true);
                        if (isset($feature['type']) && $feature['type'] === 'Feature') {
                            yield $feature;
                        }
                        $buf     = '';
                        $capture = false;
                        continue;
                    }
                }
                if ($capture) $buf .= $ch;
            }
        }
    }

    // ── Map GeoJSON feature to DB row ─────────────────────────────────────────
    private function mapFeature(string $level, array $feature): ?array
    {
        $props    = $feature['properties'] ?? [];
        $geometry = $feature['geometry']   ?? null;

        if (!$geometry) throw new \RuntimeException("Feature has no geometry");

        $geometryJson    = json_encode($geometry);
        $simplifiedJson  = json_encode($this->simplifyGeometry($geometry));

        return match ($level) {
            'states' => [
                'cve_ent'          => $this->pad($props['CVE_ENT'] ?? $props['cve_ent'] ?? null, 2),
                'nombre'           => $props['NOMGEO'] ?? $props['NOM_ENT'] ?? $props['nombre'] ?? 'Sin nombre',
                'geometry'         => $geometryJson,
                'geom_simplified'  => $simplifiedJson,
                'updated_at'       => now(),
                'created_at'       => now(),
            ],
            'municipalities' => [
                'cve_ent'          => $this->pad($props['CVE_ENT'] ?? $props['cve_ent'] ?? null, 2),
                'cve_mun'          => $this->pad($props['CVE_MUN'] ?? $props['cve_mun'] ?? null, 3),
                'nombre'           => $props['NOMGEO'] ?? $props['NOM_MUN'] ?? $props['nombre'] ?? 'Sin nombre',
                'geometry'         => $geometryJson,
                'geom_simplified'  => $simplifiedJson,
                'updated_at'       => now(),
                'created_at'       => now(),
            ],
            'sections' => [
                'cve_ent'      => $this->pad($props['CVE_ENT'] ?? $props['cve_ent'] ?? null, 2),
                'cve_mun'      => $this->pad($props['CVE_MUN'] ?? $props['cve_mun'] ?? null, 3),
                'cve_seccion'  => $this->pad($props['SECCION'] ?? $props['CVE_SECCION'] ?? $props['cve_seccion'] ?? null, 4),
                'geometry'     => $geometryJson,
                'updated_at'   => now(),
                'created_at'   => now(),
            ],
            default => null,
        };
    }

    // ── Upsert by unique key ──────────────────────────────────────────────────
    private function upsert(string $level, array $rows): void
    {
        $table   = "geo_{$level}";
        $columns = match ($level) {
            'states'        => ['nombre', 'geometry', 'geom_simplified', 'updated_at'],
            'municipalities'=> ['nombre', 'geometry', 'geom_simplified', 'updated_at'],
            'sections'      => ['geometry', 'updated_at'],
        };
        $unique  = match ($level) {
            'states'         => ['cve_ent'],
            'municipalities' => ['cve_ent', 'cve_mun'],
            'sections'       => ['cve_ent', 'cve_mun', 'cve_seccion'],
        };
        DB::table($table)->upsert($rows, $unique, $columns);
    }

    // ── Zero-pad a code ───────────────────────────────────────────────────────
    private function pad($value, int $len): string
    {
        if ($value === null) throw new \RuntimeException("Missing code value (null)");
        return str_pad((string) intval($value), $len, '0', STR_PAD_LEFT);
    }

    // ── Douglas-Peucker geometry simplification ───────────────────────────────
    // Reduces polygon vertex count for the zoom-out (national) view
    private function simplifyGeometry(array $geometry): array
    {
        $type = $geometry['type'] ?? '';

        if ($type === 'Polygon') {
            return [
                'type'        => 'Polygon',
                'coordinates' => array_map([$this, 'simplifyRing'], $geometry['coordinates']),
            ];
        }

        if ($type === 'MultiPolygon') {
            return [
                'type'        => 'MultiPolygon',
                'coordinates' => array_map(function ($polygon) {
                    return array_map([$this, 'simplifyRing'], $polygon);
                }, $geometry['coordinates']),
            ];
        }

        return $geometry; // return unchanged for other types
    }

    private function simplifyRing(array $ring): array
    {
        if (count($ring) <= self::MAX_SIMPLIFIED_VERTICES) return $ring;
        $tolerance = $this->computeTolerance($ring);
        $simplified = $this->douglasPeucker($ring, $tolerance);
        // Ensure ring is closed
        if ($simplified[0] !== end($simplified)) {
            $simplified[] = $simplified[0];
        }
        return $simplified;
    }

    private function computeTolerance(array $ring): float
    {
        // Use ~1% of the bounding-box diagonal as tolerance
        $lats = array_column($ring, 1);
        $lons = array_column($ring, 0);
        $diag = sqrt(pow(max($lons) - min($lons), 2) + pow(max($lats) - min($lats), 2));
        return $diag * 0.005;
    }

    private function douglasPeucker(array $points, float $tolerance): array
    {
        if (count($points) < 3) return $points;

        $maxDist = 0;
        $maxIdx  = 0;
        $end     = count($points) - 1;

        for ($i = 1; $i < $end; $i++) {
            $d = $this->pointToSegmentDistance($points[$i], $points[0], $points[$end]);
            if ($d > $maxDist) { $maxDist = $d; $maxIdx = $i; }
        }

        if ($maxDist > $tolerance) {
            $left  = $this->douglasPeucker(array_slice($points, 0, $maxIdx + 1), $tolerance);
            $right = $this->douglasPeucker(array_slice($points, $maxIdx), $tolerance);
            return array_merge(array_slice($left, 0, -1), $right);
        }

        return [$points[0], $points[$end]];
    }

    private function pointToSegmentDistance(array $p, array $a, array $b): float
    {
        $dx = $b[0] - $a[0];
        $dy = $b[1] - $a[1];
        if ($dx == 0 && $dy == 0) {
            return sqrt(pow($p[0] - $a[0], 2) + pow($p[1] - $a[1], 2));
        }
        $t = (($p[0] - $a[0]) * $dx + ($p[1] - $a[1]) * $dy) / ($dx * $dx + $dy * $dy);
        $t = max(0, min(1, $t));
        return sqrt(pow($p[0] - ($a[0] + $t * $dx), 2) + pow($p[1] - ($a[1] + $t * $dy), 2));
    }
}
