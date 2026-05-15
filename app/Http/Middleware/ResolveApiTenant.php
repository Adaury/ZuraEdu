<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ResolveApiTenant
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'No autenticado.'], 401);
        }

        $tenantId = $user->tenant_id ?? null;

        if (! $tenantId) {
            return response()->json(['message' => 'Tenant no resuelto para este usuario.'], 403);
        }

        $tenant = Cache::remember("tenant_{$tenantId}", 300, fn () => Tenant::find($tenantId));

        if (! $tenant) {
            return response()->json(['message' => 'Institución no encontrada.'], 404);
        }

        if (! $tenant->estaActivo()) {
            return response()->json(['message' => 'Tu institución está suspendida. Contacta a soporte.'], 403);
        }

        app()->instance('tenant', $tenant);
        app()->instance(Tenant::class, $tenant);

        return $next($request);
    }
}
