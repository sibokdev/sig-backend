<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call([
            \Database\Seeders\StatesTableSeeder::class,
            \Database\Seeders\TlaxcalaMunicipalitiesSeeder::class,
            \Database\Seeders\UsersTableSeeder::class,
        ]);
    }
}
