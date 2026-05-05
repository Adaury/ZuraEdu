<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ── tenant_modules: módulos habilitados por tenant ────────────────
        Schema::create('tenant_modules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('module_id')->constrained('modules')->cascadeOnDelete();
            $table->boolean('activo')->default(true);
            $table->json('config')->nullable()->comment('Configuración específica del módulo para este tenant');
            $table->timestamps();

            $table->unique(['tenant_id', 'module_id'], 'tenant_modules_unique');
            $table->index(['tenant_id', 'activo']);
        });

        // ── Agregar plan_id y otros campos SaaS a tenants ─────────────────
        Schema::table('tenants', function (Blueprint $table) {
            $table->foreignId('plan_id')->nullable()->after('plan')
                  ->constrained('plans')->nullOnDelete();
            $table->boolean('is_demo_temporal')->default(false)->after('metadatos');
        });

        // ── Sincronizar plan_id con el enum plan existente ────────────────
        $planIds = DB::table('plans')->pluck('id', 'slug');
        DB::table('tenants')->get()->each(function ($tenant) use ($planIds) {
            $planSlug = $tenant->plan ?? 'free';
            $planId   = $planIds[$planSlug] ?? $planIds['free'];
            DB::table('tenants')->where('id', $tenant->id)->update(['plan_id' => $planId]);
        });

        // ── Poblar tenant_modules desde tenant_features existentes ─────────
        $moduleIds = DB::table('modules')->pluck('id', 'clave');
        DB::table('tenant_features')->get()->each(function ($tf) use ($moduleIds) {
            $moduleId = $moduleIds[$tf->feature] ?? null;
            if (! $moduleId) return;
            DB::table('tenant_modules')->insertOrIgnore([
                'tenant_id'  => $tf->tenant_id,
                'module_id'  => $moduleId,
                'activo'     => $tf->activo ?? true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        // ── Marcar tenants demo temporales usando metadatos existente ─────
        DB::table('tenants')->whereNotNull('metadatos')->get()->each(function ($t) {
            $meta = json_decode($t->metadatos, true);
            if (! empty($meta['is_demo_temporal'])) {
                DB::table('tenants')->where('id', $t->id)
                    ->update(['is_demo_temporal' => true]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropForeign(['plan_id']);
            $table->dropColumn(['plan_id', 'is_demo_temporal']);
        });
        Schema::dropIfExists('tenant_modules');
    }
};
