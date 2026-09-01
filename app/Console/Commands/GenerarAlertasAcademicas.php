<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\LoopsPerTenant;
use App\Services\AcademicAlertService;
use Illuminate\Console\Command;

class GenerarAlertasAcademicas extends Command
{
    use LoopsPerTenant;

    protected $signature   = 'alertas:academicas {--year= : ID del año escolar}';
    protected $description = 'Evalúa el rendimiento académico y genera alertas de baja académica';

    public function handle(AcademicAlertService $service): int
    {
        $this->info('Evaluando rendimiento académico...');

        $yearId = $this->option('year') ? (int) $this->option('year') : null;
        $totalGeneradas = $totalOmitidas = 0;

        $this->forEachTenant(function ($tenant) use ($service, $yearId, &$totalGeneradas, &$totalOmitidas) {
            $result = $service->evaluarTodos($yearId);

            if (isset($result['error'])) {
                $this->line("  [{$tenant->nombre_institucion}] {$result['error']}");
                return;
            }

            $totalGeneradas += $result['generadas'];
            $totalOmitidas  += $result['omitidas'];
        });

        $this->info("Alertas generadas: {$totalGeneradas}");
        $this->info("Omitidas (ya existían): {$totalOmitidas}");

        return 0;
    }
}
