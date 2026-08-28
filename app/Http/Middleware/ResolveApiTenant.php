<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;

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

        // Sin caché: cachear el modelo Eloquent rompe con serializable_classes=false
        // (default desde Laravel 13); find() por PK ya es una consulta indexada barata.
        $tenant = Tenant::find($tenantId);

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
