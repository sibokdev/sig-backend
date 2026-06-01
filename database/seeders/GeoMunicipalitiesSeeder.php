<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\GeoMunicipality;

class GeoMunicipalitiesSeeder extends Seeder
{
    public function run(): void
    {
        $dataDir = database_path('data');
        $files   = glob($dataDir . '/municipalities_*.json') ?: [];
        sort($files);

        foreach ($files as $file) {
            $entries = json_decode(file_get_contents($file), true) ?? [];

            foreach (array_chunk($entries, 200) as $chunk) {
                foreach ($chunk as $row) {
                    GeoMunicipality::updateOrInsert(
                        ['cve_ent' => $row['cve_ent'], 'cve_mun' => $row['cve_mun']],
                        ['nombre' => $row['nombre'], 'updated_at' => now(), 'created_at' => now()]
                    );
                }
            }
        }
    }
}
