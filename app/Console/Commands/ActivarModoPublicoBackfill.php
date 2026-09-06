<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Models\TenantFeature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * Backfill de una sola vez (Fase 2 del portal público): el feature flag
 * "modo_publico" ya existía en SuperAdmin\TenantController::ALL_FEATURES
 * pero nunca se consultaba en ningún controlador. Ahora PublicSiteController
 * lo usa para decidir si /sitio muestra el contenido o "portal no
 * disponible". Como TenantFeature solo se crea al CREAR un tenant
 * (TenantController::store()), cualquier tenant ya existente antes de este
 * flag no tiene esa fila — y Tenant::can() trata "sin fila" como false, lo
 * que apagaría /sitio para todos los tenants actuales al desplegar esto.
 *
 * Este comando crea la fila activa=true SOLO para tenants que no tienen
 * ninguna fila de "modo_publico" todavía, preservando el comportamiento
 * actual (el sitio público sigue visible tal como está hoy). No toca los
 * tenants que ya tienen una fila explícita (activada o desactivada por un
 * SuperAdmin a propósito).
 */
class ActivarModoPublicoBackfill extends Command
{
    protected $signature   = 'tenant:activar-modo-publico {--dry-run : Solo mostrar qué se haría, sin guardar}';
    protected $description = 'Backfill: activa el feature modo_publico para tenants que aún no tienen esa fila';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $tenantsConFila = TenantFeature::where('feature', 'modo_publico')->pluck('tenant_id');
        $tenantsSinFila = Tenant::withTrashed()->whereNotIn('id', $tenantsConFila)->get();

        if ($tenantsSinFila->isEmpty()) {
            $this->info('Todos los tenants ya tienen una fila de modo_publico. Nada que hacer.');
            return 0;
        }

        $this->info("Tenants sin fila de modo_publico: {$tenantsSinFila->count()}");

        foreach ($tenantsSinFila as $tenant) {
            $this->line("  → tenant_id={$tenant->id}: {$tenant->nombre_institucion}");

            if (! $dryRun) {
                TenantFeature::create([
                    'tenant_id' => $tenant->id,
                    'feature'   => 'modo_publico',
                    'activo'    => true,
                ]);
                Cache::forget("tenant_{$tenant->id}_feature_modo_publico");
            }
        }

        $this->info($dryRun ? 'Dry-run: no se guardó nada.' : 'Listo.');
        return 0;
    }
}
