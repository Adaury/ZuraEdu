<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Estudiante;
use App\Models\Matricula;
use App\Models\SchoolYear;

/**
 * Crea registros demo de bajas y traslados para probar el módulo de Registro Académico.
 *
 * USO: php artisan db:seed --class=BajasTrasladosDemoSeeder
 */
class BajasTrasladosDemoSeeder extends Seeder
{
    public function run(): void
    {
        $schoolYear = SchoolYear::actual();

        if (! $schoolYear) {
            $this->command->error('No hay año escolar activo.');
            return;
        }

        // Tomar algunas matrículas activas para convertirlas en bajas/traslados
        $matriculas = Matricula::where('estado', 'activa')
            ->where('school_year_id', $schoolYear->id)
            ->with('estudiante')
            ->inRandomOrder()
            ->limit(6)
            ->get();

        if ($matriculas->count() < 2) {
            $this->command->warn('No hay suficientes matrículas activas. Ejecuta DatosDemoCompleteSeeder primero.');
            return;
        }

        $bajas = [
            [
                'estado'               => 'retirada',
                'fecha_baja'           => now()->subDays(45)->toDateString(),
                'motivo_baja'          => 'Mudanza familiar a otra provincia. La familia se trasladó a Santiago.',
                'institucion_traslado' => null,
            ],
            [
                'estado'               => 'retirada',
                'fecha_baja'           => now()->subDays(30)->toDateString(),
                'motivo_baja'          => 'Problemas económicos. La familia no puede cubrir los costos de transporte.',
                'institucion_traslado' => null,
            ],
            [
                'estado'               => 'retirada',
                'fecha_baja'           => now()->subDays(15)->toDateString(),
                'motivo_baja'          => 'Viaje al exterior. El estudiante emigró con su familia a España.',
                'institucion_traslado' => null,
            ],
            [
                'estado'               => 'transferida',
                'fecha_baja'           => now()->subDays(60)->toDateString(),
                'motivo_baja'          => 'Traslado por cambio de residencia familiar.',
                'institucion_traslado' => 'Liceo Secundario Prof. Juan Bosch, Santiago',
            ],
            [
                'estado'               => 'transferida',
                'fecha_baja'           => now()->subDays(20)->toDateString(),
                'motivo_baja'          => 'Traslado a institución más cercana al nuevo domicilio.',
                'institucion_traslado' => 'Centro Educativo Eugenio María de Hostos, Santo Domingo Norte',
            ],
            [
                'estado'               => 'transferida',
                'fecha_baja'           => now()->subDays(8)->toDateString(),
                'motivo_baja'          => 'Solicitud del representante por motivos personales.',
                'institucion_traslado' => 'Colegio San Juan Bosco, La Vega',
            ],
        ];

        $count = min($matriculas->count(), count($bajas));

        for ($i = 0; $i < $count; $i++) {
            $matricula = $matriculas[$i];
            $data      = $bajas[$i];

            $matricula->update($data);
            $matricula->estudiante->update(['estado' => 'inactivo']);
        }

        $this->command->info("✅ {$count} registros de bajas/traslados creados para el año {$schoolYear->nombre}.");

        $retiradas    = collect($bajas)->where('estado', 'retirada')->count();
        $transferidas = collect($bajas)->where('estado', 'transferida')->count();
        $this->command->info("   - Retiradas:    {$retiradas}");
        $this->command->info("   - Transferidas: {$transferidas}");
    }
}
