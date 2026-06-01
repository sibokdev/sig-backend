<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\GeoState;

class GeoStatesSeeder extends Seeder
{
    public function run(): void
    {
        $states = [
            ['cve_ent' => '01', 'nombre' => 'Aguascalientes'],
            ['cve_ent' => '02', 'nombre' => 'Baja California'],
            ['cve_ent' => '03', 'nombre' => 'Baja California Sur'],
            ['cve_ent' => '04', 'nombre' => 'Campeche'],
            ['cve_ent' => '05', 'nombre' => 'Coahuila de Zaragoza'],
            ['cve_ent' => '06', 'nombre' => 'Colima'],
            ['cve_ent' => '07', 'nombre' => 'Chiapas'],
            ['cve_ent' => '08', 'nombre' => 'Chihuahua'],
            ['cve_ent' => '09', 'nombre' => 'Ciudad de México'],
            ['cve_ent' => '10', 'nombre' => 'Durango'],
            ['cve_ent' => '11', 'nombre' => 'Guanajuato'],
            ['cve_ent' => '12', 'nombre' => 'Guerrero'],
            ['cve_ent' => '13', 'nombre' => 'Hidalgo'],
            ['cve_ent' => '14', 'nombre' => 'Jalisco'],
            ['cve_ent' => '15', 'nombre' => 'México'],
            ['cve_ent' => '16', 'nombre' => 'Michoacán de Ocampo'],
            ['cve_ent' => '17', 'nombre' => 'Morelos'],
            ['cve_ent' => '18', 'nombre' => 'Nayarit'],
            ['cve_ent' => '19', 'nombre' => 'Nuevo León'],
            ['cve_ent' => '20', 'nombre' => 'Oaxaca'],
            ['cve_ent' => '21', 'nombre' => 'Puebla'],
            ['cve_ent' => '22', 'nombre' => 'Querétaro'],
            ['cve_ent' => '23', 'nombre' => 'Quintana Roo'],
            ['cve_ent' => '24', 'nombre' => 'San Luis Potosí'],
            ['cve_ent' => '25', 'nombre' => 'Sinaloa'],
            ['cve_ent' => '26', 'nombre' => 'Sonora'],
            ['cve_ent' => '27', 'nombre' => 'Tabasco'],
            ['cve_ent' => '28', 'nombre' => 'Tamaulipas'],
            ['cve_ent' => '29', 'nombre' => 'Tlaxcala'],
            ['cve_ent' => '30', 'nombre' => 'Veracruz de Ignacio de la Llave'],
            ['cve_ent' => '31', 'nombre' => 'Yucatán'],
            ['cve_ent' => '32', 'nombre' => 'Zacatecas'],
        ];

        foreach ($states as $state) {
            GeoState::updateOrInsert(
                ['cve_ent' => $state['cve_ent']],
                ['nombre' => $state['nombre'], 'updated_at' => now(), 'created_at' => now()]
            );
        }
    }
}
