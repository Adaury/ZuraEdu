<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\LoopsPerTenant;
use App\Models\SchoolYear;
use App\Models\SigerdConfig;
use App\Models\User;
use App\Services\SigerdExportService;
use App\Services\WhatsAppService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SigerdValidar extends Command
{
    use LoopsPerTenant;

    protected $signature = 'sigerd:validar {--grupo_id= : ID del grupo (opcional, valida todos si se omite)}';
    protected $description = 'Valida los datos del centro para exportación SIGERD y notifica al registrador';

    private bool $hubErrores = false;

    public function handle(SigerdExportService $service): int
    {
        $this->forEachTenant(function ($tenant) use ($service) {
            $this->procesarTenant($tenant, $service);
        });

        return $this->hubErrores ? self::FAILURE : self::SUCCESS;
    }

    private function procesarTenant($tenant, SigerdExportService $service): void
    {
        $schoolYear = SchoolYear::actual();

        if (! $schoolYear) {
            return;
        }

        $config = SigerdConfig::first();
        if (! $config || empty($config->codigo_centro)) {
            $this->line("  [{$tenant->nombre_institucion}] SIGERD no configurado, omitido.");
            return;
        }

        $grupoId = $this->option('grupo_id') ? (int) $this->option('grupo_id') : null;

        $this->info("Validando datos SIGERD ({$tenant->nombre_institucion}) para año escolar: {$schoolYear->nombre}");

        // Validar nómina
        $resultNomina = $service->validarNomina($schoolYear, $grupoId);
        $this->line("  Nómina: {$resultNomina['total']} estudiantes — " .
            ($resultNomina['ok'] ? '✓ Sin errores' : count($resultNomina['errores']) . ' errores'));

        foreach ($resultNomina['errores'] as $err) {
            $this->warn("    #{$err['no']} {$err['nombre']}: {$err['descripcion']}");
        }

        // Validar calificaciones (si hay grupo específico)
        $resultCalif = null;
        if ($grupoId) {
            $resultCalif = $service->validarCalificaciones($schoolYear, $grupoId, null);
            $this->line("  Calificaciones: " .
                ($resultCalif['ok'] ? '✓ Sin errores' : count($resultCalif['errores']) . ' errores'));
        }

        // Notificar al registrador
        $this->notificarRegistrador($resultNomina, $resultCalif, $schoolYear->nombre);

        $hayErrores = ! $resultNomina['ok'] || ($resultCalif && ! $resultCalif['ok']);

        if ($hayErrores) {
            $this->warn('Validación completada con errores. Corrígelos antes de exportar a SIGERD.');
            $this->hubErrores = true;
            return;
        }

        $this->info('Validación completada. Datos listos para exportar a SIGERD.');
    }

    private function notificarRegistrador(array $nomina, ?array $calif, string $syNombre): void
    {
        // Buscar usuarios con rol de registro
        $registradores = User::role(['Registrador Académico', 'Encargado de Registro Académico'])
            ->whereNotNull('telefono')
            ->where('activo', true)
            ->get();

        if ($registradores->isEmpty()) {
            Log::info('SIGERD validar: sin registradores con teléfono para notificar.');
            return;
        }

        $totalErrores = count($nomina['errores']) + (isset($calif['errores']) ? count($calif['errores']) : 0);
        $totalEst     = $nomina['total'];

        $school  = \App\Helpers\Setting::get('system_name', 'El centro educativo');
        $estado  = $totalErrores === 0 ? '✅ LISTO' : "⚠️ {$totalErrores} ERROR(ES)";

        $msg = "📋 *{$school}* — Validación SIGERD\n\n"
             . "Año escolar: *{$syNombre}*\n"
             . "Estado: *{$estado}*\n"
             . "Estudiantes: {$totalEst}\n";

        if ($totalErrores > 0) {
            $msg .= "\nCorrecciones necesarias antes de exportar al portal SIGERD/MINERD.\n";
            $msg .= "Ir a: Integraciones → SIGERD";
        } else {
            $msg .= "\nDatos listos. Puedes exportar desde Integraciones → SIGERD.";
        }

        foreach ($registradores as $reg) {
            WhatsAppService::send($reg->telefono, $msg);
        }

        Log::info("SIGERD: validación notificada a {$registradores->count()} registrador(es).");
    }
}
