<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenant
{
    // Rutas que nunca deben bloquearse por estado del tenant
    private const RUTAS_PUBLICAS = [
        'suspended',
        'login', 'login/*',
        'logout',
        'forgot-password',
        'reset-password/*',
        'password/*',
        '/',
        'onboarding', 'onboarding/*',
        'demo', 'demo/*',
        'verificar-matricula',
        'inscripcion', 'inscripcion/*',
        'portal/representante/*',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $host   = $request->getHost();
        $tenant = $this->resolve($host);

        if (! $tenant) {
            if ($this->isLocal($host)) {
                // SuperAdmin con tenant activo en sesión → usa ese contexto
                if (
                    auth()->check() &&
                    auth()->user()->hasRole('super_admin') &&
                    $saId = session('sa_tenant_id')
                ) {
                    $tenant = Tenant::find($saId);
                }

                if (! $tenant) {
                    $userTenantId = \Illuminate\Support\Facades\DB::table('users')
                        ->where('id', auth()->id())
                        ->value('tenant_id');

                    if ($userTenantId && $userTenantId != config('tenancy.fallback_tenant_id', 1)) {
                        $tenant = Tenant::find($userTenantId);
                    }
                }

                if (! $tenant) {
                    $tenant = Tenant::find(config('tenancy.fallback_tenant_id', 1));
                }
            }

            if (! $tenant) {
                abort(404, 'Institución no encontrada.');
            }
        }

        // Registrar en el contenedor ANTES de verificar estado
        app()->instance('tenant', $tenant);
        app()->instance(Tenant::class, $tenant);
        View::share('currentTenant', $tenant);
        View::share('saActiveTenant', session('sa_tenant_id') ? $tenant : null);
        config([
            'tenant.id'               => $tenant->id,
            'tenant.nombre'           => $tenant->nombre_institucion,
            'tenant.color_primario'   => $tenant->color_primario,
            'tenant.color_secundario' => $tenant->color_secundario,
        ]);

        // Verificar estado — super_admin en modo panel no bloqueado por estado
        if (
            ! $tenant->estaActivo() &&
            ! $request->is(self::RUTAS_PUBLICAS) &&
            ! (auth()->check() && auth()->user()->hasRole('super_admin'))
        ) {
            return redirect()->route('tenant.suspended');
        }

        return $next($request);
    }

    private function resolve(string $host): ?Tenant
    {
        $tenant = Tenant::where('dominio_personalizado', $host)->first();
        if ($tenant) return $tenant;

        $parts = explode('.', $host);
        if (count($parts) >= 3) {
            $tenant = Tenant::where('dominio', $parts[0])->first();
            if ($tenant) return $tenant;
        }

        return Tenant::where('dominio', $host)->first();
    }

    private function isLocal(string $host): bool
    {
        return in_array($host, ['localhost', '127.0.0.1', '::1'])
            || str_ends_with($host, '.test')
            || str_ends_with($host, '.local')
            || app()->environment('local', 'testing');
    }
}
