<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class VerificarPagosTenants extends Command
{
    protected $signature   = 'tenants:verificar-pagos {--dry-run : Mostrar cambios sin ejecutarlos}';
    protected $description = 'Suspender tenants con suscripción vencida y reactivar los que ya pagaron';

    public function handle(): int
    {
        $dry = $this->option('dry-run');

        $this->line('');
        $this->info('=== Verificación de pagos de tenants ===');

        $suspendidos  = $this->suspenderVencidos($dry);
        $reactivados  = $this->reactivarPagados($dry);

        $this->line('');
        $this->table(
            ['Acción', 'Cantidad'],
            [
                ['Suspendidos por vencimiento', $suspendidos],
                ['Reactivados por pago', $reactivados],
            ]
        );

        if ($dry) {
            $this->warn('[DRY RUN] No se realizaron cambios en la base de datos.');
        }

        return self::SUCCESS;
    }

    private function suspenderVencidos(bool $dry): int
    {
        // Tenants activos/prueba cuya fecha_vencimiento ya pasó y no tienen suscripción vigente
        $candidatos = Tenant::whereIn('estado', ['activo', 'prueba'])
            ->where('fecha_vencimiento', '<', now()->toDateString())
            ->whereDoesntHave('subscriptions', fn($q) => $q->activas())
            ->get();

        foreach ($candidatos as $tenant) {
            $this->warn("  SUSPENDER [{$tenant->id}] {$tenant->nombre_institucion}"
                . " — venció {$tenant->fecha_vencimiento->format('d/m/Y')}");

            if (! $dry) {
                $tenant->update(['estado' => 'suspendido']);
                Cache::forget("tenant_host_{$tenant->dominio}");
            }
        }

        return $candidatos->count();
    }

    private function reactivarPagados(bool $dry): int
    {
        // Tenants suspendidos que tienen una suscripción activa y válida
        $candidatos = Tenant::where('estado', 'suspendido')
            ->whereHas('subscriptions', fn($q) => $q->activas())
            ->get();

        foreach ($candidatos as $tenant) {
            $sub = $tenant->subscriptionActiva();
            $this->info("  REACTIVAR [{$tenant->id}] {$tenant->nombre_institucion}"
                . " — suscripción hasta {$sub?->fecha_fin?->format('d/m/Y')}");

            if (! $dry) {
                $tenant->update([
                    'estado'            => 'activo',
                    'fecha_vencimiento' => $sub?->fecha_fin?->toDateString(),
                ]);
                Cache::forget("tenant_host_{$tenant->dominio}");
            }
        }

        return $candidatos->count();
    }
}
