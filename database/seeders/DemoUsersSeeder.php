<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Estudiante;
use App\Models\Docente;
use App\Models\Representante;

class DemoUsersSeeder extends Seeder
{
    public function run(): void
    {
        $this->command?->info('👤 Creando/actualizando usuarios demo...');

        // ── Docente demo ──────────────────────────────────────────────────
        $docenteUser = User::updateOrCreate(
            ['email' => 'docente@demo.com'],
            [
                'name'      => 'Demo',
                'apellidos' => 'Docente',
                'password'  => Hash::make('123456'),
                'activo'    => true,
            ]
        );
        $docenteUser->syncRoles(['Docente']);

        // Vincular a un docente existente o crear uno nuevo
        $docente = Docente::where('user_id', $docenteUser->id)->first()
            ?? Docente::whereNull('user_id')->first();

        if ($docente) {
            $docente->update(['user_id' => $docenteUser->id, 'email' => 'docente@demo.com']);
        } else {
            Docente::firstOrCreate(
                ['user_id' => $docenteUser->id],
                [
                    'nombres'   => 'Demo',
                    'apellidos' => 'Docente',
                    'email'     => 'docente@demo.com',
                    'cedula'    => '000-0000001-0',
                    'estado'    => 'activo',
                ]
            );
        }

        $this->command?->line('   ✓ docente@demo.com');

        // ── Estudiante demo ───────────────────────────────────────────────
        $estudianteUser = User::updateOrCreate(
            ['email' => 'estudiante@demo.com'],
            [
                'name'      => 'Demo',
                'apellidos' => 'Estudiante',
                'password'  => Hash::make('123456'),
                'activo'    => true,
            ]
        );
        $estudianteUser->syncRoles(['Estudiante']);

        // Vincular o crear perfil de estudiante
        $estudiante = Estudiante::where('user_id', $estudianteUser->id)->first()
            ?? Estudiante::whereNull('user_id')->first();

        if ($estudiante) {
            $estudiante->update(['user_id' => $estudianteUser->id]);
        } else {
            Estudiante::firstOrCreate(
                ['user_id' => $estudianteUser->id],
                [
                    'nombres'          => 'Demo',
                    'apellidos'        => 'Estudiante',
                    'email'            => 'estudiante@demo.com',
                    'cedula'           => '000-0000002-0',
                    'numero_matricula' => 'DEMO-001',
                    'fecha_nacimiento' => '2010-01-01',
                    'sexo'             => 'M',
                    'estado'           => 'activo',
                ]
            );
        }

        $this->command?->line('   ✓ estudiante@demo.com');

        // ── Representante demo ────────────────────────────────────────────
        $padreUser = User::updateOrCreate(
            ['email' => 'padre@demo.com'],
            [
                'name'      => 'Demo',
                'apellidos' => 'Representante',
                'password'  => Hash::make('123456'),
                'activo'    => true,
            ]
        );
        $padreUser->syncRoles(['Representante']);

        // Vincular o crear perfil de representante
        $rep = Representante::where('user_id', $padreUser->id)->first()
            ?? Representante::whereNull('user_id')->first();

        if ($rep) {
            $rep->update(['user_id' => $padreUser->id]);
        } else {
            Representante::firstOrCreate(
                ['user_id' => $padreUser->id],
                [
                    'nombres'   => 'Demo',
                    'apellidos' => 'Representante',
                    'email'     => 'padre@demo.com',
                    'cedula'    => '000-0000003-0',
                    'telefono'  => '8091234567',
                ]
            );
        }

        $this->command?->line('   ✓ padre@demo.com');

        $this->command?->info('✅ Usuarios demo listos.');
    }
}
