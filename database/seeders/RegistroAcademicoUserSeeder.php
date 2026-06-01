<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RegistroAcademicoUserSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'registro@demo.com'],
            [
                'name'      => 'Encargado',
                'apellidos' => 'Registro',
                'password'  => Hash::make('Registroadmin'),
                'activo'    => true,
            ]
        );
        $user->syncRoles('Encargado de Registro Académico');
    }
}
