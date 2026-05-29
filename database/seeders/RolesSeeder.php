<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'ver-dashboard',
            'gestionar-usuarios',
            'gestionar-school-years',
            'gestionar-grupos',
            'gestionar-docentes',
            'gestionar-estudiantes',
            'gestionar-matriculas',
            'gestionar-asignaturas',
            'gestionar-asignaciones',
            'gestionar-periodos',
            'ingresar-calificaciones',
            'ver-calificaciones',
            'ingresar-asistencia',
            'ver-asistencia',
            'gestionar-indicadores',
            'ver-boletines',
            'imprimir-boletines',
            'ver-estadisticas',
            'gestionar-configuracion',
            'supervisar-registros',
            'ver-reportes-institucionales',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Administrador — all permissions
        $administrador = Role::firstOrCreate(['name' => 'Administrador', 'guard_name' => 'web']);
        $administrador->syncPermissions($permissions);

        // Director — all except gestionar-usuarios and gestionar-configuracion
        $director = Role::firstOrCreate(['name' => 'Director', 'guard_name' => 'web']);
        $director->syncPermissions(
            array_values(array_filter($permissions, fn ($p) => !in_array($p, [
                'gestionar-usuarios',
                'gestionar-configuracion',
            ])))
        );

        // Coordinador Académico
        $coordinador = Role::firstOrCreate(['name' => 'Coordinador Académico', 'guard_name' => 'web']);
        $coordinador->syncPermissions([
            'ver-dashboard',
            'gestionar-grupos',
            'gestionar-docentes',
            'gestionar-estudiantes',
            'gestionar-matriculas',
            'gestionar-asignaturas',
            'gestionar-asignaciones',
            'gestionar-periodos',
            'ingresar-calificaciones',
            'ver-calificaciones',
            'ingresar-asistencia',
            'ver-asistencia',
            'gestionar-indicadores',
            'ver-boletines',
            'imprimir-boletines',
            'ver-estadisticas',
            'supervisar-registros',
            'ver-reportes-institucionales',
        ]);

        // Docente
        $docente = Role::firstOrCreate(['name' => 'Docente', 'guard_name' => 'web']);
        $docente->syncPermissions([
            'ver-dashboard',
            'ingresar-calificaciones',
            'ver-calificaciones',
            'ingresar-asistencia',
            'ver-asistencia',
            'ver-boletines',
        ]);

        // Secretaría
        $secretaria = Role::firstOrCreate(['name' => 'Secretaría', 'guard_name' => 'web']);
        $secretaria->syncPermissions([
            'ver-dashboard',
            'gestionar-estudiantes',
            'gestionar-matriculas',
            'ver-calificaciones',
            'ver-asistencia',
            'ver-boletines',
        ]);

        // Personal Administrativo (Registrador Académico) — supervisión de registros
        $personalAdm = Role::firstOrCreate(['name' => 'Personal Administrativo', 'guard_name' => 'web']);
        $personalAdm->syncPermissions([
            'ver-dashboard',
            'ver-calificaciones',
            'ver-asistencia',
            'ver-boletines',
            'imprimir-boletines',
            'ver-estadisticas',
            'supervisar-registros',
            'ver-reportes-institucionales',
        ]);

        // Estudiante
        $estudiante = Role::firstOrCreate(['name' => 'Estudiante', 'guard_name' => 'web']);
        $estudiante->syncPermissions([
            'ver-dashboard',
            'ver-calificaciones',
            'ver-asistencia',
            'ver-boletines',
        ]);

        // Representante (padre/tutor con login propio)
        $representante = Role::firstOrCreate(['name' => 'Representante', 'guard_name' => 'web']);
        $representante->syncPermissions([
            'ver-calificaciones',
            'ver-asistencia',
            'ver-boletines',
        ]);

        // Encargado de Área — supervisión y consulta de su área
        $encargadoArea = Role::firstOrCreate(['name' => 'Encargado de Área', 'guard_name' => 'web']);
        $encargadoArea->syncPermissions([
            'ver-dashboard',
            'gestionar-docentes',
            'gestionar-grupos',
            'gestionar-asignaturas',
            'ver-calificaciones',
            'ver-asistencia',
            'ver-boletines',
            'ver-estadisticas',
            'ver-reportes-institucionales',
        ]);

        // Coordinador Primer Ciclo — mismo alcance que Coordinador Académico
        $coordPC = Role::firstOrCreate(['name' => 'Coordinador Primer Ciclo', 'guard_name' => 'web']);
        $coordPC->syncPermissions([
            'ver-dashboard',
            'gestionar-grupos',
            'gestionar-docentes',
            'gestionar-estudiantes',
            'gestionar-matriculas',
            'gestionar-asignaturas',
            'gestionar-asignaciones',
            'gestionar-periodos',
            'ingresar-calificaciones',
            'ver-calificaciones',
            'ingresar-asistencia',
            'ver-asistencia',
            'gestionar-indicadores',
            'ver-boletines',
            'imprimir-boletines',
            'ver-estadisticas',
            'supervisar-registros',
            'ver-reportes-institucionales',
        ]);

        // Coordinador Segundo Ciclo — mismo alcance que Coordinador Primer Ciclo
        $coordSC = Role::firstOrCreate(['name' => 'Coordinador Segundo Ciclo', 'guard_name' => 'web']);
        $coordSC->syncPermissions([
            'ver-dashboard',
            'gestionar-grupos',
            'gestionar-docentes',
            'gestionar-estudiantes',
            'gestionar-matriculas',
            'gestionar-asignaturas',
            'gestionar-asignaciones',
            'gestionar-periodos',
            'ingresar-calificaciones',
            'ver-calificaciones',
            'ingresar-asistencia',
            'ver-asistencia',
            'gestionar-indicadores',
            'ver-boletines',
            'imprimir-boletines',
            'ver-estadisticas',
            'supervisar-registros',
            'ver-reportes-institucionales',
        ]);

        // Secretaria Docente — igual que Secretaría
        $secretariaDocente = Role::firstOrCreate(['name' => 'Secretaria Docente', 'guard_name' => 'web']);
        $secretariaDocente->syncPermissions([
            'ver-dashboard',
            'gestionar-estudiantes',
            'gestionar-matriculas',
            'ver-calificaciones',
            'ver-asistencia',
            'ver-boletines',
        ]);

        // Registrador Académico — gestión completa del departamento de registro
        $registrador = Role::firstOrCreate(['name' => 'Registrador Académico', 'guard_name' => 'web']);
        $registrador->syncPermissions([
            'ver-dashboard',
            'gestionar-estudiantes',
            'gestionar-matriculas',
            'ver-calificaciones',
            'ver-asistencia',
            'ver-boletines',
            'imprimir-boletines',
            'supervisar-registros',
            'ver-reportes-institucionales',
        ]);
    }
}
