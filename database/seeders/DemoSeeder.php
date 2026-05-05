<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Docente;
use App\Models\Representante;
use App\Models\Estudiante;

class DemoSeeder extends Seeder
{
    /**
     * Usuarios demo del sistema.
     *
     * Credenciales:
     *   admin@demo.com       / 123456  → Administrador
     *   docente@demo.com     / 123456  → Docente
     *   padre@demo.com       / 123456  → Representante
     *   estudiante@demo.com  / 123456  → Estudiante
     */
    public function run(): void
    {
        // ── Admin Demo ────────────────────────────────────────────────────
        $this->crearUsuario(
            nombre:    'Admin Demo',
            apellidos: 'Demo',
            cedula:    '001-0000001-1',
            email:     'admin@demo.com',
            rol:       'Administrador',
        );

        // ── Docente Demo ──────────────────────────────────────────────────
        $docenteUser = $this->crearUsuario(
            nombre:    'Docente Demo',
            apellidos: 'Demo',
            cedula:    '001-0000002-2',
            email:     'docente@demo.com',
            rol:       'Docente',
        );

        Docente::firstOrCreate(
            ['user_id' => $docenteUser->id],
            [
                'nombres'          => 'Profesor',
                'apellidos'        => 'Demo',
                'especialidad'     => 'Matemáticas y Física',
                'titulo_academico' => 'Licenciado en Educación',
                'estado'           => 'activo',
            ]
        );

        // ── Padre / Representante Demo ────────────────────────────────────
        $padreUser = $this->crearUsuario(
            nombre:    'Padre Demo',
            apellidos: 'Demo',
            cedula:    '001-0000003-3',
            email:     'padre@demo.com',
            rol:       'Representante',
        );

        Representante::firstOrCreate(
            ['user_id' => $padreUser->id],
            [
                'cedula'    => '001-0000003-3',
                'nombres'   => 'Representante',
                'apellidos' => 'Demo',
                'telefono'  => '809-555-0001',
                'email'     => 'padre@demo.com',
                'ocupacion' => 'Empleado',
            ]
        );

        // ── Estudiante Demo ───────────────────────────────────────────────
        $estudianteUser = $this->crearUsuario(
            nombre:    'Estudiante Demo',
            apellidos: 'Demo',
            cedula:    '001-0000004-4',
            email:     'estudiante@demo.com',
            rol:       'Estudiante',
        );

        Estudiante::firstOrCreate(
            ['user_id' => $estudianteUser->id],
            [
                'numero_matricula' => 'DEMO-001',
                'cedula'           => '001-0000004-4',
                'nombres'          => 'Estudiante',
                'apellidos'        => 'Demo',
                'fecha_nacimiento' => '2005-01-01',
                'sexo'             => 'M',
                'estado'           => 'activo',
            ]
        );

        $this->command->info('');
        $this->command->info('✅ Usuarios demo creados correctamente:');
        $this->command->table(
            ['Email', 'Contraseña', 'Rol'],
            [
                ['admin@demo.com',      '123456', 'Administrador'],
                ['docente@demo.com',    '123456', 'Docente'],
                ['padre@demo.com',      '123456', 'Representante'],
                ['estudiante@demo.com', '123456', 'Estudiante'],
            ]
        );
        $this->command->info('');
        $this->command->warn('⚠  Recuerda: añadir DEMO_MODE_ENABLED=true en .env para activar el modo demo.');
    }

    private function crearUsuario(
        string $nombre,
        string $apellidos,
        string $cedula,
        string $email,
        string $rol,
    ): User {
        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name'                 => $nombre,
                'apellidos'            => $apellidos,
                'cedula'               => $cedula,
                'email'                => $email,
                'password'             => Hash::make('123456'),
                'activo'               => true,
                'pendiente_aprobacion' => false,
            ]
        );

        if (! $user->hasRole($rol)) {
            $user->assignRole($rol);
        }

        return $user;
    }
}
