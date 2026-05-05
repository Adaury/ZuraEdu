<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\TenantFeature;
use App\Services\StripeService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class BillingController extends Controller
{
    private const FEATURES_POR_PLAN = [
        'free' => [
            'asistencia', 'calificaciones', 'boletines',
            'portal_padre', 'portal_estudiante', 'portal_docente',
            'comunicados', 'calendario', 'reportes',
        ],
        'basico' => [
            'asistencia', 'calificaciones', 'boletines',
            'portal_padre', 'portal_estudiante', 'portal_docente',
            'comunicados', 'calendario', 'reportes',
            'horarios', 'classroom', 'disciplina', 'tutorias', 'gamificacion',
        ],
        'pro' => [
            'asistencia', 'calificaciones', 'boletines',
            'portal_padre', 'portal_estudiante', 'portal_docente',
            'comunicados', 'calendario', 'reportes',
            'horarios', 'classroom', 'disciplina', 'tutorias', 'gamificacion',
            'seguimiento_social', 'competencias',
            'pagos', 'whatsapp', 'admisiones', 'nomina',
            'biblioteca', 'inventario', 'proyectos',
            'reconocimientos', 'evaluaciones_docentes',
            'transporte', 'salud', 'reuniones', 'cafeteria',
        ],
    ];

    public function __construct(private StripeService $stripe) {}

    // ── Vista principal de facturación ────────────────────────────────────

    public function index()
    {
        $tenant     = app('tenant');
        $planes     = Plan::where('activo', true)->orderBy('orden')->get();
        $suscActiva = $tenant->subscriptionActiva();
        $historial  = Subscription::where('tenant_id', $tenant->id)
                        ->with('plan')
                        ->latest('created_at')
                        ->take(10)
                        ->get();

        $stripeActivo = $this->stripe->estaConfigurado();

        return view('admin.billing.index', compact(
            'tenant', 'planes', 'suscActiva', 'historial', 'stripeActivo'
        ));
    }

    // ── Checkout con Stripe ───────────────────────────────────────────────

    public function checkout(Request $request)
    {
        $data = $request->validate([
            'plan_slug' => 'required|string|exists:plans,slug',
            'ciclo'     => 'required|in:mensual,anual',
        ]);

        $plan = Plan::bySlug($data['plan_slug']);

        if (! $plan->esPago()) {
            return back()->with('info', 'El plan Free no requiere pago.');
        }

        if (! $this->stripe->estaConfigurado()) {
            return back()->with('error', 'Los pagos con tarjeta no están configurados. Usa la opción de transferencia.');
        }

        try {
            $tenant  = app('tenant');
            $session = $this->stripe->crearCheckoutSession($tenant, $plan, $data['ciclo']);
            return redirect($session['url']);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    // ── Retorno exitoso de Stripe ─────────────────────────────────────────

    public function success(Request $request)
    {
        $sessionId = $request->query('session_id');

        if (! $sessionId) {
            return redirect()->route('admin.billing.index');
        }

        // Verificar que no fue ya procesado
        $yaExiste = Subscription::where('stripe_session_id', $sessionId)->exists();
        if ($yaExiste) {
            return redirect()->route('admin.billing.index')
                ->with('success', 'Tu suscripción ya fue activada correctamente.');
        }

        try {
            $session = $this->stripe->obtenerSession($sessionId);
        } catch (\RuntimeException $e) {
            return redirect()->route('admin.billing.index')
                ->with('error', 'No se pudo verificar el pago: ' . $e->getMessage());
        }

        if ($session['payment_status'] !== 'paid') {
            return redirect()->route('admin.billing.index')
                ->with('warning', 'El pago aún no fue confirmado por Stripe. Espera unos minutos.');
        }

        $this->activarSuscripcion(
            tenantId:        (int) $session['metadata']['tenant_id'],
            planSlug:        $session['metadata']['plan_slug'],
            ciclo:           $session['metadata']['ciclo'],
            meses:           (int) $session['metadata']['meses'],
            monto:           $session['amount_total'] / 100,
            stripeSessionId: $sessionId,
            metodoPago:      'stripe',
        );

        return redirect()->route('admin.billing.index')
            ->with('success', '¡Pago confirmado! Tu suscripción fue activada exitosamente.');
    }

    // ── Cancelación de Stripe ─────────────────────────────────────────────

    public function cancel()
    {
        return redirect()->route('admin.billing.index')
            ->with('warning', 'El pago fue cancelado. Puedes intentarlo de nuevo cuando quieras.');
    }

    // ── Pago por transferencia bancaria ───────────────────────────────────

    public function transferencia(Request $request)
    {
        $data = $request->validate([
            'plan_slug'  => 'required|string|exists:plans,slug',
            'ciclo'      => 'required|in:mensual,anual',
            'referencia' => 'required|string|max:150',
            'monto'      => 'required|numeric|min:1|max:9999',
        ]);

        $tenant = app('tenant');
        $plan   = Plan::bySlug($data['plan_slug']);
        $meses  = $data['ciclo'] === 'anual' ? 12 : 1;

        Subscription::create([
            'tenant_id'       => $tenant->id,
            'plan_id'         => $plan->id,
            'estado'          => 'pendiente',
            'fecha_inicio'    => now()->toDateString(),
            'fecha_fin'       => now()->addMonths($meses)->toDateString(),
            'monto_pagado'    => $data['monto'],
            'moneda'          => 'USD',
            'ciclo'           => $data['ciclo'],
            'metodo_pago'     => 'transferencia',
            'referencia_pago' => $data['referencia'],
        ]);

        return back()->with('success',
            'Transferencia registrada con referencia «' . $data['referencia'] . '». ' .
            'El equipo ZuraEdu verificará el pago en 24-48 horas y activará tu plan.'
        );
    }

    // ── Activar suscripción (reutilizado por Stripe y webhook) ───────────

    public function activarSuscripcion(
        int    $tenantId,
        string $planSlug,
        string $ciclo,
        int    $meses,
        float  $monto,
        string $stripeSessionId = '',
        string $metodoPago      = 'stripe',
    ): void {
        DB::transaction(function () use ($tenantId, $planSlug, $ciclo, $meses, $monto, $stripeSessionId, $metodoPago) {
            $tenant = Tenant::find($tenantId);
            $plan   = Plan::bySlug($planSlug);

            // Cerrar suscripciones previas activas
            Subscription::where('tenant_id', $tenantId)
                ->whereIn('estado', ['prueba', 'activa', 'pendiente'])
                ->update(['estado' => 'vencida']);

            // Inicio: continúa desde suscripción vigente (si existe) o desde hoy
            $suscVigente = Subscription::where('tenant_id', $tenantId)
                ->where('estado', 'vencida')
                ->where('fecha_fin', '>=', now()->toDateString())
                ->latest('fecha_fin')
                ->first();

            $inicio = $suscVigente
                ? Carbon::parse($suscVigente->fecha_fin)->addDay()->toDateString()
                : now()->toDateString();

            $fin = Carbon::parse($inicio)->addMonths($meses)->toDateString();

            Subscription::create([
                'tenant_id'           => $tenantId,
                'plan_id'             => $plan->id,
                'estado'              => 'activa',
                'fecha_inicio'        => $inicio,
                'fecha_fin'           => $fin,
                'monto_pagado'        => $monto,
                'moneda'              => 'USD',
                'ciclo'               => $ciclo,
                'metodo_pago'         => $metodoPago,
                'referencia_pago'     => $stripeSessionId,
                'stripe_session_id'   => $stripeSessionId ?: null,
            ]);

            $limites = $this->limitesPlan($planSlug);
            $tenant->update([
                'plan'             => $planSlug,
                'plan_id'          => $plan->id,
                'estado'           => 'activo',
                'fecha_vencimiento'=> $fin,
                'max_estudiantes'  => $limites['estudiantes'],
                'max_docentes'     => $limites['docentes'],
                'max_usuarios'     => $limites['usuarios'],
            ]);

            $this->activarFeaturesPlan($tenant, $planSlug);
            Cache::forget("tenant_host_{$tenant->dominio}");

            // Invalidar caché de features
            foreach (self::FEATURES_POR_PLAN['pro'] as $f) {
                Cache::forget("tenant_{$tenant->id}_feature_{$f}");
            }
        });
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private function limitesPlan(string $plan): array
    {
        return match ($plan) {
            'pro'    => ['estudiantes' => 9999, 'docentes' => 9999, 'usuarios' => 9999],
            'basico' => ['estudiantes' => 300,  'docentes' => 15,   'usuarios' => 40],
            default  => ['estudiantes' => 100,  'docentes' => 5,    'usuarios' => 15],
        };
    }

    private function activarFeaturesPlan(Tenant $tenant, string $plan): void
    {
        $features = self::FEATURES_POR_PLAN[$plan] ?? self::FEATURES_POR_PLAN['free'];

        TenantFeature::where('tenant_id', $tenant->id)->update(['activo' => false]);

        foreach ($features as $feature) {
            TenantFeature::updateOrCreate(
                ['tenant_id' => $tenant->id, 'feature' => $feature],
                ['activo' => true]
            );
        }
    }
}
