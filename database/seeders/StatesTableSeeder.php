<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use DB;

class StatesTableSeeder extends Seeder
{
    public function run()
    {
        $states = [
            ['idstates'=>1,'name'=>'Aguascalientes'],
            ['idstates'=>2,'name'=>'Baja California'],
            ['idstates'=>3,'name'=>'Baja California Sur'],
            ['idstates'=>4,'name'=>'Campeche'],
            ['idstates'=>5,'name'=>'Coahuila'],
            ['idstates'=>6,'name'=>'Colima'],
            ['idstates'=>7,'name'=>'Chiapas'],
            ['idstates'=>8,'name'=>'Chihuahua'],
            ['idstates'=>9,'name'=>'Ciudad de México'],
            ['idstates'=>10,'name'=>'Durango'],
            ['idstates'=>11,'name'=>'Guanajuato'],
            ['idstates'=>12,'name'=>'Guerrero'],
            ['idstates'=>13,'name'=>'Hidalgo'],
            ['idstates'=>14,'name'=>'Jalisco'],
            ['idstates'=>15,'name'=>'México'],
            ['idstates'=>16,'name'=>'Michoacán'],
            ['idstates'=>17,'name'=>'Morelos'],
            ['idstates'=>18,'name'=>'Nayarit'],
            ['idstates'=>19,'name'=>'Nuevo León'],
            ['idstates'=>20,'name'=>'Oaxaca'],
            ['idstates'=>21,'name'=>'Puebla'],
            ['idstates'=>22,'name'=>'Querétaro'],
            ['idstates'=>23,'name'=>'Quintana Roo'],
            ['idstates'=>24,'name'=>'San Luis Potosí'],
            ['idstates'=>25,'name'=>'Sinaloa'],
            ['idstates'=>26,'name'=>'Sonora'],
            ['idstates'=>27,'name'=>'Tabasco'],
            ['idstates'=>28,'name'=>'Tamaulipas'],
            ['idstates'=>29,'name'=>'Tlaxcala'],
            ['idstates'=>30,'name'=>'Veracruz'],
            ['idstates'=>31,'name'=>'Yucatán'],
            ['idstates'=>32,'name'=>'Zacatecas'],
        ];
        DB::table('states')->insert($states);
    }
}
