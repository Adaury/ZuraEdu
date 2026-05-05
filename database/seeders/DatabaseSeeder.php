<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            // ── Base del sistema ──────────────────────────────────────────
            RolesSeeder::class,
            GradosSeeder::class,
            SeccionesSeeder::class,
            AreasSeeder::class,
            AsignaturasSeeder::class,
            SchoolYearSeeder::class,
            AdminSeeder::class,
            EspecialidadTecnicaSeeder::class,
            CurriculumMinerdSeeder::class,

            // ── Datos de prueba ───────────────────────────────────────────
            DocentesDemoSeeder::class,          // 8 docentes con usuarios
            EstudiantesRealistasSeeder::class,  // 18 grupos × 20 estudiantes + representantes
            AsignacionesDemoSeeder::class,      // asignaturas → docentes por grupo
            DatosDemoCompleteSeeder::class,     // calificaciones, asistencias, observaciones
            ComunicadosDemoSeeder::class,       // 8 comunicados institucionales
            PagosDemoSeeder::class,             // cuotas y pagos por estudiante
        ]);
    }
}
