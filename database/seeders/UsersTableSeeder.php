<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UsersTableSeeder extends Seeder
{
    public function run()
    {
        DB::table('users')->insert([
            [
                'iduserId'  => 1,
                'username'  => 'admin',
                'email'     => 'admin@sig.com',
                'password'  => Hash::make('12345'),
                'role'      => 'admin',
                'created_at'=> now(),
                'updated_at'=> now(),
            ],
            [
                'iduserId'  => 2,
                'username'  => 'user',
                'email'     => 'user@sig.com',
                'password'  => Hash::make('12345'),
                'role'      => 'user',
                'created_at'=> now(),
                'updated_at'=> now(),
            ]
        ]);
    }
}