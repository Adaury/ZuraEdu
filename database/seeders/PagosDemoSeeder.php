<?php

namespace Database\Seeders;

use App\Models\Matricula;
use App\Models\Pago;
use App\Models\SchoolYear;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class PagosDemoSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::first();
        if (! $tenant) { $this->command->error('No hay tenant.'); return; }
        app()->instance('tenant', $tenant);

        $schoolYear = SchoolYear::actual();
        if (! $schoolYear) { $this->command->error('No hay año escolar activo.'); return; }

        $admin = User::where('email', 'admin@sge.test')->first()
            ?? User::first();

        $this->command->info('💰 Generando pagos de prueba...');

        $matriculas = Matricula::withoutGlobalScope('tenant')
            ->where('school_year_id', $schoolYear->id)
            ->where('estado', 'activa')
            ->get();

        if ($matriculas->isEmpty()) {
            $this->command->warn('   ⚠ No hay matrículas activas. Ejecuta EstudiantesRealistasSeeder primero.');
            return;
        }

        $meses = [
            ['mes' => 'Agosto',     'vence' => Carbon::create(now()->year, 8,  5)],
            ['mes' => 'Septiembre', 'vence' => Carbon::create(now()->year, 9,  5)],
            ['mes' => 'Octubre',    'vence' => Carbon::create(now()->year, 10, 5)],
            ['mes' => 'Noviembre',  'vence' => Carbon::create(now()->year, 11, 5)],
            ['mes' => 'Diciembre',  'vence' => Carbon::create(now()->year, 12, 5)],
        ];

        $monto      = 2500.00;
        $metodos    = ['efectivo', 'transferencia', 'tarjeta'];
        $creados    = 0;

        foreach ($matriculas as $matricula) {
            // Matrícula inicial
            $yaExiste = Pago::withoutGlobalScope('tenant')
                ->where('matricula_id', $matricula->id)
                ->where('concepto', 'Matrícula ' . $schoolYear->nombre)
                ->exists();

            if (! $yaExiste) {
                Pago::create([
                    'matricula_id'    => $matricula->id,
                    'concepto'        => 'Matrícula ' . $schoolYear->nombre,
                    'monto'           => 1500.00,
                    'fecha_vencimiento'=> Carbon::create(now()->year, 8, 1)->toDateString(),
                    'fecha_pago'      => Carbon::create(now()->year, 7, rand(20, 31))->toDateString(),
                    'estado'          => 'pagado',
                    'metodo_pago'     => $metodos[array_rand($metodos)],
                    'referencia'      => 'REC-' . strtoupper(substr(md5($matricula->id . 'mat'), 0, 8)),
                    'registrado_por'  => $admin?->id,
                    'tenant_id'       => $tenant->id,
                ]);
                $creados++;
            }

            // Cuotas mensuales
            foreach ($meses as $i => $mesData) {
                $yaExiste = Pago::withoutGlobalScope('tenant')
                    ->where('matricula_id', $matricula->id)
                    ->where('concepto', 'Cuota ' . $mesData['mes'] . ' ' . now()->year)
                    ->exists();

                if ($yaExiste) continue;

                $hoy       = Carbon::today();
                $vencimiento = $mesData['vence'];

                // Distribución realista: 70% pagados, 15% pendientes, 15% vencidos
                $rand = rand(1, 100);

                if ($vencimiento->isPast()) {
                    // Mes pasado: 80% pagado, 20% vencido
                    if ($rand <= 80) {
                        $estado     = 'pagado';
                        $fechaPago  = $vencimiento->copy()->subDays(rand(0, 10))->toDateString();
                        $metodo     = $metodos[array_rand($metodos)];
                        $referencia = 'REC-' . strtoupper(substr(md5($matricula->id . $i), 0, 8));
                    } else {
                        $estado     = 'vencido';
                        $fechaPago  = null;
                        $metodo     = null;
                        $referencia = null;
                    }
                } else {
                    // Mes vigente o futuro: pendiente
                    $estado     = 'pendiente';
                    $fechaPago  = null;
                    $metodo     = null;
                    $referencia = null;
                }

                Pago::create([
                    'matricula_id'     => $matricula->id,
                    'concepto'         => 'Cuota ' . $mesData['mes'] . ' ' . now()->year,
                    'monto'            => $monto,
                    'fecha_vencimiento'=> $vencimiento->toDateString(),
                    'fecha_pago'       => $fechaPago,
                    'estado'           => $estado,
                    'metodo_pago'      => $metodo,
                    'referencia'       => $referencia,
                    'registrado_por'   => $admin?->id,
                    'tenant_id'        => $tenant->id,
                ]);
                $creados++;
            }
        }

        $pagados   = Pago::withoutGlobalScope('tenant')->where('estado', 'pagado')->count();
        $pendientes= Pago::withoutGlobalScope('tenant')->where('estado', 'pendiente')->count();
        $vencidos  = Pago::withoutGlobalScope('tenant')->where('estado', 'vencido')->count();

        $this->command->line("   ✓ {$creados} registros de pago creados.");
        $this->command->line("     → Pagados: {$pagados} | Pendientes: {$pendientes} | Vencidos: {$vencidos}");
    }
}
