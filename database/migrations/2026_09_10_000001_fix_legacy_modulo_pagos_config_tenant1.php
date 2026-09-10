<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Fase 0 del roadmap (unificación TenantFeature/ConfigInstitucional::
 * moduloActivo): el seed de la era mono-tenant
 * (2026_03_26_000008_create_config_institucional_table) insertó
 * `modulo_pagos_activo = '0'` sin tenant_id; la migración de
 * multi-tenant (2026_04_29_100003_add_tenant_id_to_core_tables) adjudicó
 * esa fila a tenant_id=1 por el default de la columna. Esa fila nunca fue
 * una decisión real de ningún centro -- era inerte porque moduloActivo()
 * era puramente cosmético (no bloqueaba rutas). Ahora que
 * CheckTenantFeature la usa como gate real (AND con TenantFeature), esa
 * fila bloquearía Pagos para el tenant 1 sin que nadie lo haya elegido.
 * Se borra para que vuelva al nuevo default (activo, opt-out) -- no se
 * toca la migración original, se corrige el dato con una nueva.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('config_institucional')
            ->where('tenant_id', 1)
            ->where('clave', 'modulo_pagos_activo')
            ->where('valor', '0')
            ->delete();
    }

    public function down(): void
    {
        // Intencionalmente sin revertir: restaurar el valor legado
        // reintroduciría el bloqueo que esta migración corrige.
    }
};
