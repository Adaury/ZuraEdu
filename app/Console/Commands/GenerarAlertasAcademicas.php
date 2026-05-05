<?php

namespace App\Console\Commands;

use App\Services\AcademicAlertService;
use Illuminate\Console\Command;

class GenerarAlertasAcademicas extends Command
{
    protected $signature   = 'alertas:academicas {--year= : ID del año escolar}';
    protected $description = 'Evalúa el rendimiento académico y genera alertas de baja académica';

    public function handle(AcademicAlertService $service): int
    {
        $this->info('Evaluando rendimiento académico...');

        $yearId = $this->option('year') ? (int) $this->option('year') : null;
        $result = $service->evaluarTodos($yearId);

        if (isset($result['error'])) {
            $this->error($result['error']);
            return 1;
        }

        $this->info("Alertas generadas: {$result['generadas']}");
        $this->info("Omitidas (ya existían): {$result['omitidas']}");

        return 0;
    }
}
