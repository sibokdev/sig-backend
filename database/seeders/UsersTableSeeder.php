<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use DB;
use Illuminate\Support\Facades\Hash;

class UsersTableSeeder extends Seeder
{
    public function run()
    {
        DB::table('users')->insert([
            'iduserId' => 1,
            'username' => 'admin',
            'pwd' => Hash::make('ChangeMe123!'),
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }
}
