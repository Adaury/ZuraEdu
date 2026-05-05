<?php

use App\Models\Tenant;

if (! function_exists('tenant')) {
    /** Retorna el tenant actual o null */
    function tenant(): ?Tenant
    {
        return app()->bound('tenant') ? app('tenant') : null;
    }
}

if (! function_exists('tenant_id')) {
    /** Retorna el ID del tenant actual (1 como fallback) */
    function tenant_id(): int
    {
        return tenant()?->id ?? 1;
    }
}

if (! function_exists('tenant_can')) {
    /** Verifica si el tenant actual tiene un feature activo */
    function tenant_can(string $feature): bool
    {
        return tenant()?->can($feature) ?? true;
    }
}

if (! function_exists('tenant_config')) {
    /** Obtiene la configuración específica de un feature para el tenant actual */
    function tenant_config(string $feature): ?array
    {
        return tenant()?->featureConfig($feature);
    }
}
