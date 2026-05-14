<?php

namespace App\Jobs\Middleware;

use App\Models\Tenant;

/**
 * Job middleware que restaura el contexto del tenant en el worker.
 * Requiere que el job tenga una propiedad pública `$tenantId`.
 */
class ResolveTenantForJob
{
    public function handle(object $job, callable $next): void
    {
        if (isset($job->tenantId) && $job->tenantId) {
            $tenant = Tenant::find($job->tenantId);

            if ($tenant) {
                app()->instance('tenant', $tenant);
                app()->instance(Tenant::class, $tenant);
                config([
                    'tenant.id'     => $tenant->id,
                    'tenant.nombre' => $tenant->nombre_institucion,
                ]);
            }
        }

        $next($job);
    }
}
