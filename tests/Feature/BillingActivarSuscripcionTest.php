<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\BillingController;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\TenantFeature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * BillingController::activarSuscripcion / downgradarAFree — hallazgo
 * "cero tests en flujos de dinero" de la auditoría del proyecto (ver
 * [[project_auditoria_2026_09_01_system_settings]]). Cubre la continuidad
 * de fecha al renovar (debe sumar desde fecha_fin+1 de la suscripción
 * vigente, no desde hoy) y que el downgrade revierta límites/features.
 *
 * Se llama al controller directamente (no vía HTTP): este método lo
 * reutilizan tanto el webhook de Stripe como el flujo manual de admin, sin
 * su propia ruta con validación de Request.
 */
class BillingActivarSuscripcionTest extends TestCase
{
    use RefreshDatabase;

    private function tenant(array $overrides = []): Tenant
    {
        return Tenant::create(array_merge([
            'nombre_institucion' => 'Colegio de Prueba',
            'dominio'            => 'colegio' . random_int(10000, 99999),
            'estado'             => 'activo',
            'tipo'               => 'privado',
            'plan'               => 'free',
        ], $overrides));
    }

    public function test_activacion_sin_suscripcion_previa_empieza_hoy(): void
    {
        $tenant = $this->tenant();

        (app(BillingController::class))->activarSuscripcion(
            tenantId: $tenant->id, planSlug: 'pro', ciclo: 'mensual', meses: 1, monto: 49,
        );

        $sub = Subscription::where('tenant_id', $tenant->id)->where('estado', 'activa')->first();
        $this->assertNotNull($sub);
        $this->assertEquals(now()->toDateString(), $sub->fecha_inicio->toDateString());
        $this->assertEquals(now()->addMonths(1)->toDateString(), $sub->fecha_fin->toDateString());
    }

    public function test_renovar_con_suscripcion_vigente_continua_desde_fecha_fin_mas_un_dia(): void
    {
        $tenant = $this->tenant();

        Subscription::create([
            'tenant_id' => $tenant->id, 'plan_id' => Plan::bySlug('pro')->id, 'estado' => 'activa',
            'fecha_inicio' => now()->subDays(20)->toDateString(),
            'fecha_fin'    => now()->addDays(10)->toDateString(),
            'monto_pagado' => 49, 'moneda' => 'USD', 'ciclo' => 'mensual', 'metodo_pago' => 'stripe',
        ]);

        (app(BillingController::class))->activarSuscripcion(
            tenantId: $tenant->id, planSlug: 'pro', ciclo: 'mensual', meses: 1, monto: 49,
        );

        $nueva = Subscription::where('tenant_id', $tenant->id)->where('estado', 'activa')->first();
        $esperadoInicio = now()->addDays(10)->addDay()->toDateString();
        $this->assertEquals(
            $esperadoInicio,
            $nueva->fecha_inicio->toDateString(),
            'Debe continuar desde fecha_fin+1 de la suscripción vigente, no desde hoy.'
        );
        $this->assertEquals(
            now()->addDays(10)->addDay()->addMonths(1)->toDateString(),
            $nueva->fecha_fin->toDateString()
        );
    }

    public function test_renovar_con_suscripcion_ya_vencida_empieza_hoy_no_desde_la_fecha_vieja(): void
    {
        $tenant = $this->tenant();

        Subscription::create([
            'tenant_id' => $tenant->id, 'plan_id' => Plan::bySlug('pro')->id, 'estado' => 'activa',
            'fecha_inicio' => now()->subMonths(2)->toDateString(),
            'fecha_fin'    => now()->subDays(15)->toDateString(), // ya venció hace 15 días
            'monto_pagado' => 49, 'moneda' => 'USD', 'ciclo' => 'mensual', 'metodo_pago' => 'stripe',
        ]);

        (app(BillingController::class))->activarSuscripcion(
            tenantId: $tenant->id, planSlug: 'pro', ciclo: 'mensual', meses: 1, monto: 49,
        );

        $nueva = Subscription::where('tenant_id', $tenant->id)->where('estado', 'activa')->first();
        $this->assertEquals(
            now()->toDateString(),
            $nueva->fecha_inicio->toDateString(),
            'Una suscripción ya vencida no debe arrastrar su fecha vieja — reactivar empieza hoy.'
        );
    }

    public function test_activar_cierra_las_suscripciones_previas_activas(): void
    {
        $tenant = $this->tenant();

        $vieja = Subscription::create([
            'tenant_id' => $tenant->id, 'plan_id' => Plan::bySlug('free')->id, 'estado' => 'prueba',
            'fecha_inicio' => now()->subDays(5)->toDateString(),
            'fecha_fin'    => now()->addDays(5)->toDateString(),
            'monto_pagado' => 0, 'moneda' => 'USD', 'ciclo' => 'mensual', 'metodo_pago' => 'manual',
        ]);

        (app(BillingController::class))->activarSuscripcion(
            tenantId: $tenant->id, planSlug: 'pro', ciclo: 'mensual', meses: 1, monto: 49,
        );

        $this->assertEquals('vencida', $vieja->fresh()->estado);
    }

    public function test_activar_actualiza_el_tenant_con_el_plan_y_limites_nuevos(): void
    {
        $tenant = $this->tenant(['plan' => 'free', 'max_estudiantes' => 100, 'max_docentes' => 5]);

        (app(BillingController::class))->activarSuscripcion(
            tenantId: $tenant->id, planSlug: 'pro', ciclo: 'anual', meses: 12, monto: 470,
        );

        $tenant->refresh();
        $this->assertEquals('pro', $tenant->plan);
        $this->assertEquals('activo', $tenant->estado);
        $this->assertEquals(9999, $tenant->max_estudiantes);
        $this->assertEquals(9999, $tenant->max_docentes);
        $this->assertEquals(now()->addMonths(12)->toDateString(), $tenant->fecha_vencimiento->toDateString());
    }

    public function test_activar_registra_los_datos_de_stripe(): void
    {
        $tenant = $this->tenant();

        (app(BillingController::class))->activarSuscripcion(
            tenantId: $tenant->id, planSlug: 'pro', ciclo: 'mensual', meses: 1, monto: 49,
            stripeSessionId: 'cs_test_123', stripePaymentIntent: 'pi_test_456', metodoPago: 'stripe',
        );

        $sub = Subscription::where('tenant_id', $tenant->id)->where('estado', 'activa')->first();
        $this->assertEquals('cs_test_123', $sub->stripe_session_id);
        $this->assertEquals('pi_test_456', $sub->stripe_payment_intent);
        $this->assertEquals('stripe', $sub->metodo_pago);
    }

    public function test_downgrade_revierte_a_plan_free_y_limites_free(): void
    {
        $tenant = $this->tenant(['plan' => 'pro', 'max_estudiantes' => 9999, 'max_docentes' => 9999]);
        Subscription::create([
            'tenant_id' => $tenant->id, 'plan_id' => Plan::bySlug('pro')->id, 'estado' => 'activa',
            'fecha_inicio' => now()->subDays(5)->toDateString(), 'fecha_fin' => now()->addDays(25)->toDateString(),
            'monto_pagado' => 49, 'moneda' => 'USD', 'ciclo' => 'mensual', 'metodo_pago' => 'stripe',
        ]);

        (app(BillingController::class))->downgradarAFree($tenant->id, 'disputa');

        $tenant->refresh();
        $this->assertEquals('free', $tenant->plan);
        $this->assertNull($tenant->fecha_vencimiento);
        $this->assertEquals(100, $tenant->max_estudiantes);
        $this->assertEquals(5, $tenant->max_docentes);
    }

    public function test_downgrade_cancela_la_suscripcion_activa(): void
    {
        $tenant = $this->tenant(['plan' => 'pro']);
        $sub = Subscription::create([
            'tenant_id' => $tenant->id, 'plan_id' => Plan::bySlug('pro')->id, 'estado' => 'activa',
            'fecha_inicio' => now()->subDays(5)->toDateString(), 'fecha_fin' => now()->addDays(25)->toDateString(),
            'monto_pagado' => 49, 'moneda' => 'USD', 'ciclo' => 'mensual', 'metodo_pago' => 'stripe',
        ]);

        (app(BillingController::class))->downgradarAFree($tenant->id);

        $this->assertEquals('cancelada', $sub->fresh()->estado);
    }

    public function test_downgrade_desactiva_features_que_no_son_de_free(): void
    {
        $tenant = $this->tenant(['plan' => 'pro']);
        TenantFeature::create(['tenant_id' => $tenant->id, 'feature' => 'horarios', 'activo' => true]);

        (app(BillingController::class))->downgradarAFree($tenant->id);

        $this->assertFalse(
            (bool) TenantFeature::where('tenant_id', $tenant->id)->where('feature', 'horarios')->value('activo'),
            'horarios no está en el plan free, debe quedar desactivado.'
        );
        $this->assertTrue(
            (bool) TenantFeature::where('tenant_id', $tenant->id)->where('feature', 'asistencia')->value('activo'),
            'asistencia sí está en free, debe estar activo.'
        );
    }
}
