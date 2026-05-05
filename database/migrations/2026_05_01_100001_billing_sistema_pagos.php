<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Stripe customer ID en tenants ──────────────────────────────
        if (!Schema::hasColumn('tenants', 'stripe_customer_id')) {
            Schema::table('tenants', function (Blueprint $table) {
                $table->string('stripe_customer_id', 100)->nullable()->after('plan_id');
            });
        }

        // ── 2. Campos Stripe en subscriptions + estado pendiente ──────────
        Schema::table('subscriptions', function (Blueprint $table) {
            if (!Schema::hasColumn('subscriptions', 'stripe_session_id')) {
                $table->string('stripe_session_id', 200)->nullable()->after('referencia_pago');
            }
            if (!Schema::hasColumn('subscriptions', 'stripe_payment_intent')) {
                $table->string('stripe_payment_intent', 200)->nullable()->after('stripe_session_id');
            }
        });

        // Añadir 'pendiente' al ENUM de subscriptions.estado
        DB::statement("ALTER TABLE subscriptions MODIFY COLUMN estado
            ENUM('prueba','pendiente','activa','vencida','cancelada','suspendida')
            NOT NULL DEFAULT 'prueba'");

        // ── 3. Actualizar planes: pro→basico, premium→pro ─────────────────
        if (!DB::table('plans')->where('slug', 'basico')->exists()) {
            DB::table('plans')->where('slug', 'pro')->update([
                'nombre'             => 'Básico',
                'slug'               => 'basico',
                'precio_mensual'     => 29,
                'precio_anual'       => 290,
                'limite_estudiantes' => 300,
                'limite_docentes'    => 15,
                'limite_usuarios'    => 40,
                'descripcion'        => 'Para centros en crecimiento con herramientas avanzadas',
                'caracteristicas'    => json_encode([
                    'Hasta 300 estudiantes',
                    '15 docentes',
                    'Todo en Free +',
                    'Módulo de Horarios',
                    'ZuraClass LMS',
                    'Disciplina y Tutorías',
                    'Soporte prioritario',
                ]),
                'es_popular'         => true,
                'orden'              => 2,
                'updated_at'         => now(),
            ]);
        }

        if (!DB::table('plans')->where('slug', 'pro')->where('precio_mensual', 59)->exists()) {
            DB::table('plans')->where('slug', 'premium')->update([
                'nombre'             => 'Pro',
                'slug'               => 'pro',
                'precio_mensual'     => 59,
                'precio_anual'       => 590,
                'limite_estudiantes' => 9999,
                'limite_docentes'    => 9999,
                'limite_usuarios'    => 9999,
                'descripcion'        => 'Para grandes instituciones con control total',
                'caracteristicas'    => json_encode([
                    'Estudiantes ilimitados',
                    'Docentes ilimitados',
                    'Todo en Básico +',
                    'Módulo de Pagos y Nómina',
                    'WhatsApp integrado',
                    'Admisiones digitales',
                    'Soporte 24/7 dedicado',
                ]),
                'es_popular'         => false,
                'orden'              => 3,
                'updated_at'         => now(),
            ]);
        }

        // Expandir ENUM antes de renombrar datos (si aún tiene valores viejos)
        $enumCol = DB::select("SHOW COLUMNS FROM tenants LIKE 'plan'")[0]->Type ?? '';
        if (str_contains($enumCol, 'premium') || !str_contains($enumCol, 'basico')) {
            DB::statement("ALTER TABLE tenants MODIFY COLUMN plan ENUM('free','pro','basico','premium') NOT NULL DEFAULT 'free'");
            DB::table('tenants')->where('plan', 'pro')->update(['plan' => 'basico']);
            DB::table('tenants')->where('plan', 'premium')->update(['plan' => 'pro']);
            DB::statement("ALTER TABLE tenants MODIFY COLUMN plan ENUM('free','basico','pro') NOT NULL DEFAULT 'free'");
        }
    }

    public function down(): void
    {
        Schema::table('tenants', fn(Blueprint $t) => $t->dropColumn('stripe_customer_id'));
        Schema::table('subscriptions', function (Blueprint $t) {
            $t->dropColumn(['stripe_session_id', 'stripe_payment_intent']);
        });

        DB::statement("ALTER TABLE subscriptions MODIFY COLUMN estado
            ENUM('prueba','activa','vencida','cancelada','suspendida')
            NOT NULL DEFAULT 'prueba'");

        // Revertir nombres de planes
        DB::table('plans')->where('slug', 'basico')->update(['slug' => 'pro', 'nombre' => 'Pro']);
        DB::table('plans')->where('slug', 'pro')->update(['slug' => 'premium', 'nombre' => 'Premium']);
        DB::table('tenants')->where('plan', 'basico')->update(['plan' => 'pro']);
        DB::table('tenants')->where('plan', 'pro')->update(['plan' => 'premium']);
    }
};
