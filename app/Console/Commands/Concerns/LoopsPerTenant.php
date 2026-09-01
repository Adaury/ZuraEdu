<?php

namespace App\Console\Commands\Concerns;

use App\Models\Tenant;

/**
 * Un comando de consola nunca pasa por el middleware ResolveTenant, así que
 * `app()->bound('tenant')` es siempre false ahí — el scope automático de
 * BelongsToTenant (y por extensión App\Helpers\Setting, ConfigInstitucional::get/set,
 * etc.) queda inactivo y mezcla datos de todos los tenants en una sola corrida.
 *
 * Este trait reproduce, por cada tenant activo/en prueba, el mismo binding que
 * ResolveTenant hace en HTTP (ver app/Http/Middleware/ResolveTenant.php).
 */
trait LoopsPerTenant
{
    protected function forEachTenant(callable $callback): void
    {
        $tenants = Tenant::whereIn('estado', ['activo', 'prueba'])->get();

        if ($tenants->isEmpty()) {
            $this->warn('No hay tenants activos/en prueba.');
            return;
        }

        foreach ($tenants as $tenant) {
            app()->instance('tenant', $tenant);
            app()->instance(Tenant::class, $tenant);
            config([
                'tenant.id'               => $tenant->id,
                'tenant.nombre'           => $tenant->nombre_institucion,
                'tenant.color_primario'   => $tenant->color_primario,
                'tenant.color_secundario' => $tenant->color_secundario,
            ]);

            try {
                $callback($tenant);
            } catch (\Throwable $e) {
                $this->error("[{$tenant->nombre_institucion}] Error: " . $e->getMessage());
                report($e);
            } finally {
                app()->forgetInstance('tenant');
                app()->forgetInstance(Tenant::class);
            }
        }
    }
}
