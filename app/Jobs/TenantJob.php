<?php

namespace App\Jobs;

use App\Jobs\Middleware\ResolveTenantForJob;
use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Base para jobs que necesitan contexto de tenant.
 * Captura el tenant_id al despachar y lo restaura en el worker.
 */
abstract class TenantJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tenantId = 0;

    public function __construct()
    {
        $tenant = app()->bound('tenant') ? app('tenant') : null;

        if ($tenant instanceof Tenant) {
            $this->tenantId = $tenant->id;
        }
    }

    public function middleware(): array
    {
        return [new ResolveTenantForJob()];
    }
}
