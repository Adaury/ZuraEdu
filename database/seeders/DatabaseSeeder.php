<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesSeeder::class,
            GradosSeeder::class,
            SeccionesSeeder::class,
            AreasSeeder::class,          // Áreas normalizadas MINERD
            AsignaturasSeeder::class,
            SchoolYearSeeder::class,
            AdminSeeder::class,
            EspecialidadTecnicaSeeder::class,
            CurriculumMinerdSeeder::class, // CE e IL del currículo
        ]);
    }
}
