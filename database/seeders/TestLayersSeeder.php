<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Layer;
use App\Models\GeoSection;

class TestLayersSeeder extends Seeder
{
    public function run(): void
    {
        // Two minimal section-level layers for Tlaxcala / Apizaco (29-005)
        // Features carry cve_ent/cve_mun/cve_seccion so syncSections logic is exercised on fresh seed
        $layers = [
            [
                'name' => 'Secciones Apizaco Norte',
                'geojson' => [
                    'type' => 'FeatureCollection',
                    'features' => [
                        [
                            'type' => 'Feature',
                            'properties' => ['cve_ent' => '29', 'cve_mun' => '005', 'cve_seccion' => '0001', 'nombre' => 'Sección 1', 'goal' => 100],
                            'geometry'   => ['type' => 'Point', 'coordinates' => [-98.14, 19.42]],
                        ],
                        [
                            'type' => 'Feature',
                            'properties' => ['cve_ent' => '29', 'cve_mun' => '005', 'cve_seccion' => '0002', 'nombre' => 'Sección 2', 'goal' => 80],
                            'geometry'   => ['type' => 'Point', 'coordinates' => [-98.13, 19.43]],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Secciones Apizaco Sur',
                'geojson' => [
                    'type' => 'FeatureCollection',
                    'features' => [
                        [
                            'type' => 'Feature',
                            'properties' => ['cve_ent' => '29', 'cve_mun' => '005', 'cve_seccion' => '0003', 'nombre' => 'Sección 3', 'goal' => 120],
                            'geometry'   => ['type' => 'Point', 'coordinates' => [-98.15, 19.41]],
                        ],
                    ],
                ],
            ],
        ];

        foreach ($layers as $data) {
            $max   = Layer::max('idlayers') ?? 0;
            Layer::updateOrCreate(
                ['name' => $data['name']],
                [
                    'idlayers' => $max + 1,
                    'geojson'  => $data['geojson'],
                ]
            );

            // Populate geo_sections from each feature
            foreach ($data['geojson']['features'] as $feature) {
                $p = $feature['properties'];
                GeoSection::updateOrInsert(
                    ['cve_ent' => $p['cve_ent'], 'cve_mun' => $p['cve_mun'], 'cve_seccion' => $p['cve_seccion']],
                    ['geometry' => json_encode($feature['geometry']), 'updated_at' => now(), 'created_at' => now()]
                );
            }
        }
    }
}
