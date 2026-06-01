<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class TestUsersSeeder extends Seeder
{
    public function run(): void
    {
        // Admin (top level, no geographic scope)
        $admin = User::updateOrCreate(
            ['email' => 'admin@sig.com'],
            [
                'username'   => 'admin',
                'password'   => Hash::make('Admin123!'),
                'role'       => 'admin',
                'cve_ent'    => null,
                'cve_mun'    => null,
                'cve_seccion'=> null,
            ]
        );

        // National admin
        $nacional = User::updateOrCreate(
            ['email' => 'nacional1@sig.com'],
            [
                'username'   => 'nacional1',
                'password'   => Hash::make('Admin123!'),
                'role'       => 'national_admin',
                'cve_ent'    => null,
                'cve_mun'    => null,
                'cve_seccion'=> null,
            ]
        );

        // Estatal admin — Tlaxcala (29)
        $estatal = User::updateOrCreate(
            ['email' => 'estatal1@sig.com'],
            [
                'username'         => 'estatal1',
                'password'         => Hash::make('Admin123!'),
                'role'             => 'estatal_admin',
                'cve_ent'          => '29',
                'cve_mun'          => null,
                'cve_seccion'      => null,
                'national_admin_id'=> $nacional->iduserId,
            ]
        );

        // Municipal admin — Tlaxcala, Apizaco (005)
        $municipal = User::updateOrCreate(
            ['email' => 'municipal1@sig.com'],
            [
                'username'         => 'municipal1',
                'password'         => Hash::make('Admin123!'),
                'role'             => 'municipal_admin',
                'cve_ent'          => '29',
                'cve_mun'          => '005',
                'cve_seccion'      => null,
                'national_admin_id'=> $nacional->iduserId,
            ]
        );

        // Seccion admin
        $seccion = User::updateOrCreate(
            ['email' => 'seccion1@sig.com'],
            [
                'username'         => 'seccion1',
                'password'         => Hash::make('Admin123!'),
                'role'             => 'seccion_admin',
                'cve_ent'          => '29',
                'cve_mun'          => '005',
                'cve_seccion'      => '0001',
                'national_admin_id'=> $nacional->iduserId,
            ]
        );

        // Field workforce
        User::updateOrCreate(
            ['email' => 'campo1@sig.com'],
            [
                'username'         => 'campo1',
                'password'         => Hash::make('Admin123!'),
                'role'             => 'field_workforce',
                'cve_ent'          => '29',
                'cve_mun'          => '005',
                'cve_seccion'      => '0001',
                'national_admin_id'=> $nacional->iduserId,
            ]
        );
    }
}
