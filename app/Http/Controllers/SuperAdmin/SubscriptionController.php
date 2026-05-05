<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\TenantFeature;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SubscriptionController extends Controller
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

    /** Registrar pago y renovar/activar suscripción */
    public function store(Request $request, Tenant $tenant)
    {
        $data = $request->validate([
            'plan'            => 'required|in:free,basico,pro',
            'ciclo'           => 'required|in:mensual,anual',
            'monto_pagado'    => 'required|numeric|min:0',
            'meses'           => 'required|integer|min:1|max:24',
            'metodo_pago'     => 'nullable|string|max:50',
            'referencia_pago' => 'nullable|string|max:100',
        ]);

        DB::transaction(function () use ($data, $tenant) {
            $plan   = Plan::where('slug', $data['plan'])->first();
            $meses  = (int) $data['meses'];

            // Inicio: continúa desde la suscripción activa o desde hoy
            $suscActiva = $tenant->subscriptionActiva();
            $inicio = ($suscActiva && $suscActiva->fecha_fin->isFuture())
                ? $suscActiva->fecha_fin->addDay()->toDateString()
                : now()->toDateString();

            $fin = Carbon::parse($inicio)->addMonths($meses)->toDateString();

            // Cerrar suscripciones anteriores
            $tenant->subscriptions()
                ->whereIn('estado', ['prueba', 'activa'])
                ->update(['estado' => 'vencida']);

            Subscription::create([
                'tenant_id'       => $tenant->id,
                'plan_id'         => $plan?->id,
                'estado'          => 'activa',
                'fecha_inicio'    => $inicio,
                'fecha_fin'       => $fin,
                'monto_pagado'    => $data['monto_pagado'],
                'moneda'          => 'USD',
                'ciclo'           => $data['ciclo'],
                'metodo_pago'     => $data['metodo_pago'] ?? null,
                'referencia_pago' => $data['referencia_pago'] ?? null,
            ]);

            $limits = $this->limitesPorPlan($data['plan']);
            $tenant->update([
                'plan'             => $data['plan'],
                'plan_id'          => $plan?->id,
                'estado'           => 'activo',
                'fecha_vencimiento' => $fin,
                'max_estudiantes'  => $limits['estudiantes'],
                'max_docentes'     => $limits['docentes'],
                'max_usuarios'     => $limits['usuarios'],
            ]);

            $this->activarFeaturesPlan($tenant, $data['plan']);
            Cache::forget("tenant_host_{$tenant->dominio}");
        });

        $vence = $tenant->fresh()->fecha_vencimiento->format('d/m/Y');
        return back()->with('success', "Pago registrado correctamente. Acceso activo hasta {$vence}.");
    }

    /** Toggle individual de un módulo/feature */
    public function toggleFeature(Request $request, Tenant $tenant)
    {
        $feature = $request->validate(['feature' => 'required|string|max:50'])['feature'];

        $tf = TenantFeature::firstOrCreate(
            ['tenant_id' => $tenant->id, 'feature' => $feature],
            ['activo' => false]
        );
        $tf->activo = !$tf->activo;
        $tf->save();

        Cache::forget("tenant_{$tenant->id}_feature_{$feature}");

        return back()->with('success',
            "Módulo «{$feature}» " . ($tf->activo ? 'activado' : 'desactivado') . '.'
        );
    }

    private function limitesPorPlan(string $plan): array
    {
        return match($plan) {
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
