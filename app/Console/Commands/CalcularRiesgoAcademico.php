<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\LoopsPerTenant;
use App\Services\AcademicRiskScoreService;
use Illuminate\Console\Command;

class CalcularRiesgoAcademico extends Command
{
    use LoopsPerTenant;

    protected $signature   = 'riesgo:calcular';
    protected $description = 'Recalcula el Academic Risk Score de todos los estudiantes activos, por cada centro';

    public function handle(AcademicRiskScoreService $service): int
    {
        $total = 0;

        $this->forEachTenant(function ($tenant) use ($service, &$total) {
            $n = $service->calcularTodos();   // año escolar actual del tenant
            $total += $n;
            $this->line("  [{$tenant->nombre_institucion}] {$n} estudiantes");
        });

        $this->info("Risk Score recalculado: {$total} estudiantes.");
        return self::SUCCESS;
    }
}
