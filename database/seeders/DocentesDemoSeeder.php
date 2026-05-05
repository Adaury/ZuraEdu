<?php

namespace Database\Seeders;

use App\Models\Docente;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DocentesDemoSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::first();
        if (! $tenant) { $this->command->error('No hay tenant.'); return; }
        app()->instance('tenant', $tenant);

        $this->command->info('👩‍🏫 Creando docentes demo...');

        $docentes = [
            [
                'user'    => ['name' => 'Ana María', 'apellidos' => 'García Rosario',    'email' => 'ana.garcia@sge.test'],
                'docente' => ['nombres' => 'Ana María', 'apellidos' => 'García Rosario',    'cedula' => '001-1234567-1', 'especialidad' => 'Lengua Española',       'titulo_academico' => 'Licda. en Educación'],
            ],
            [
                'user'    => ['name' => 'Carlos',    'apellidos' => 'Rodríguez Marte',   'email' => 'carlos.rodriguez@sge.test'],
                'docente' => ['nombres' => 'Carlos',    'apellidos' => 'Rodríguez Marte',   'cedula' => '001-2345678-2', 'especialidad' => 'Matemáticas',           'titulo_academico' => 'Lic. en Matemáticas'],
            ],
            [
                'user'    => ['name' => 'Elena',     'apellidos' => 'Martínez Taveras',  'email' => 'elena.martinez@sge.test'],
                'docente' => ['nombres' => 'Elena',     'apellidos' => 'Martínez Taveras',  'cedula' => '001-3456789-3', 'especialidad' => 'Ciencias Naturales',    'titulo_academico' => 'Licda. en Biología'],
            ],
            [
                'user'    => ['name' => 'José Manuel','apellidos' => 'Pérez Bautista',   'email' => 'jose.perez@sge.test'],
                'docente' => ['nombres' => 'José Manuel','apellidos' => 'Pérez Bautista',   'cedula' => '001-4567890-4', 'especialidad' => 'Ciencias Sociales',     'titulo_academico' => 'Lic. en Historia'],
            ],
            [
                'user'    => ['name' => 'María',     'apellidos' => 'Sánchez Féliz',     'email' => 'maria.sanchez@sge.test'],
                'docente' => ['nombres' => 'María',     'apellidos' => 'Sánchez Féliz',     'cedula' => '001-5678901-5', 'especialidad' => 'Inglés',                'titulo_academico' => 'Licda. en Inglés'],
            ],
            [
                'user'    => ['name' => 'Roberto',   'apellidos' => 'Torres Núñez',      'email' => 'roberto.torres@sge.test'],
                'docente' => ['nombres' => 'Roberto',   'apellidos' => 'Torres Núñez',      'cedula' => '001-6789012-6', 'especialidad' => 'Educación Física',      'titulo_academico' => 'Lic. en Educación Física'],
            ],
            [
                'user'    => ['name' => 'Carmen',    'apellidos' => 'López De la Rosa',  'email' => 'carmen.lopez@sge.test'],
                'docente' => ['nombres' => 'Carmen',    'apellidos' => 'López De la Rosa',  'cedula' => '001-7890123-7', 'especialidad' => 'Educación Artística',   'titulo_academico' => 'Licda. en Artes'],
            ],
            [
                'user'    => ['name' => 'Miguel Ángel','apellidos' => 'González Suriel', 'email' => 'miguel.gonzalez@sge.test'],
                'docente' => ['nombres' => 'Miguel Ángel','apellidos' => 'González Suriel', 'cedula' => '001-8901234-8', 'especialidad' => 'Formación Integral / TIC','titulo_academico' => 'Lic. en Informática'],
            ],
        ];

        $creados = 0;

        foreach ($docentes as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['user']['email']],
                array_merge($data['user'], [
                    'password'             => Hash::make('Docente2030!'),
                    'activo'               => true,
                    'pendiente_aprobacion' => false,
                ])
            );

            if (! $user->hasRole('Docente')) {
                $user->assignRole('Docente');
            }

            Docente::firstOrCreate(
                ['user_id' => $user->id],
                array_merge($data['docente'], [
                    'estado'    => 'activo',
                    'telefono'  => '809-555-' . str_pad($creados + 10, 4, '0', STR_PAD_LEFT),
                    'tenant_id' => $tenant->id,
                ])
            );

            $creados++;
        }

        $this->command->line("   ✓ {$creados} docentes creados. Contraseña: Docente2030!");
    }
}
