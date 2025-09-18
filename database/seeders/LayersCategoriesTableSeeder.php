<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use DB;

class LayersCategoriesTableSeeder extends Seeder
{
    public function run()
    {
        $categories = [
            ['id'=>1,'name'=>'Politica'],
            ['id'=>2,'name'=>'Educacion'],
            ['id'=>3,'name'=>'Salud'],
            ['id'=>4,'name'=>'Infraestructura'],
            ['id'=>5,'name'=>'Ambiental'],
        ];
        DB::table('layers_categories')->insert($categories);
    }
}
