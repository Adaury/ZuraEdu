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
            // Dashboard y acceso general
            'ver-dashboard',
            // Usuarios y sistema
            'gestionar-usuarios',
            'gestionar-configuracion',
            // Año escolar y estructura académica
            'gestionar-school-years',
            'gestionar-grupos',
            'gestionar-asignaturas',
            'gestionar-asignaciones',
            'gestionar-periodos',
            'gestionar-indicadores',
            // Personas
            'gestionar-docentes',
            'gestionar-estudiantes',
            'gestionar-matriculas',
            // Calificaciones y asistencia
            'ingresar-calificaciones',
            'ver-calificaciones',
            'ingresar-asistencia',
            'ver-asistencia',
            // Boletines
            'ver-boletines',
            'imprimir-boletines',
            // Reportes y supervisión
            'ver-estadisticas',
            'ver-reportes-institucionales',
            'supervisar-registros',
            // Pagos y finanzas
            'ver-pagos',
            'gestionar-pagos',
            // Biblioteca
            'gestionar-biblioteca',
            // Servicios institucionales
            'ver-servicios',
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
        $registradorPerms = [
            'ver-dashboard',
            'gestionar-estudiantes',
            'gestionar-matriculas',
            'ver-calificaciones',
            'ver-asistencia',
            'ver-boletines',
            'imprimir-boletines',
            'supervisar-registros',
            'ver-reportes-institucionales',
        ];
        $registrador = Role::firstOrCreate(['name' => 'Registrador Académico', 'guard_name' => 'web']);
        $registrador->syncPermissions($registradorPerms);

        // Encargado de Registro Académico — alias del rol Registrador Académico
        $encargadoRegistro = Role::firstOrCreate(['name' => 'Encargado de Registro Académico', 'guard_name' => 'web']);
        $encargadoRegistro->syncPermissions($registradorPerms);

        // Caja / Finanzas — gestión de pagos y cobros
        $caja = Role::firstOrCreate(['name' => 'Caja / Finanzas', 'guard_name' => 'web']);
        $caja->syncPermissions([
            'ver-dashboard',
            'ver-pagos',
            'gestionar-pagos',
            'ver-reportes-institucionales',
            'gestionar-estudiantes', // solo lectura en práctica (ver quién debe)
        ]);

        // Biblioteca — gestión de préstamos e inventario
        $biblioteca = Role::firstOrCreate(['name' => 'Biblioteca', 'guard_name' => 'web']);
        $biblioteca->syncPermissions([
            'ver-dashboard',
            'gestionar-biblioteca',
            'ver-servicios',
        ]);

        // Recepción — atención al público y pre-matrículas
        $recepcion = Role::firstOrCreate(['name' => 'Recepción', 'guard_name' => 'web']);
        $recepcion->syncPermissions([
            'ver-dashboard',
            'gestionar-estudiantes',
            'gestionar-matriculas',
            'ver-servicios',
        ]);

        // Docente Académico — docente área académica (igual que Docente base)
        $docenteAcad = Role::firstOrCreate(['name' => 'Docente Académico', 'guard_name' => 'web']);
        $docenteAcad->syncPermissions([
            'ver-dashboard',
            'ingresar-calificaciones',
            'ver-calificaciones',
            'ingresar-asistencia',
            'ver-asistencia',
            'ver-boletines',
        ]);

        // Docente Técnico — docente área técnica/vocacional
        $docenteTec = Role::firstOrCreate(['name' => 'Docente Técnico', 'guard_name' => 'web']);
        $docenteTec->syncPermissions([
            'ver-dashboard',
            'ingresar-calificaciones',
            'ver-calificaciones',
            'ingresar-asistencia',
            'ver-asistencia',
            'ver-boletines',
        ]);

        // Docente Guía — docente con función de orientación/tutoría
        $docenteGuia = Role::firstOrCreate(['name' => 'Docente Guía', 'guard_name' => 'web']);
        $docenteGuia->syncPermissions([
            'ver-dashboard',
            'ingresar-calificaciones',
            'ver-calificaciones',
            'ingresar-asistencia',
            'ver-asistencia',
            'ver-boletines',
            'ver-estadisticas',
        ]);
    }
}
