<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Optimización de índices (hallada con la BD sintética de carga, 2026-09-30).
 *
 * 1) Elimina 50 índices REDUNDANTES: son prefijo de otro índice (compuesto o único) de la
 *    misma tabla, o idénticos a otro. Un índice (tenant_id) es inútil si existe
 *    (tenant_id, ...): el compuesto sirve igual para `where tenant_id = ?` y para las claves
 *    foráneas. Cada índice de más encarece INSERT/UPDATE y ocupa memoria del buffer pool
 *    (en `calificaciones_academicas` había 5 solo para matricula_id/asignacion_id).
 * 2) Agrega un índice cubriente (tenant_id, fecha, estado) a `asistencias` para los KPIs del
 *    dashboard (conteo por estado del día): 0,093 s -> 0,022 s con 684k filas (Using index).
 *
 * Todo se comprueba con Schema::hasIndex(), así que es seguro si algún entorno ya no tiene
 * un índice. down() los recrea con las mismas columnas.
 */
return new class extends Migration
{
    /** [tabla, nombre del índice, columnas] */
    private const REDUNDANTES = [
        ['academic_risk_scores', 'academic_risk_scores_tenant_id_index', ['tenant_id']],
        ['activity_logs', 'idx_activity_logs_tenant', ['tenant_id']],
        ['asignaciones', 'asignaciones_docente_id_index', ['docente_id']],
        ['asignaciones', 'asignaciones_school_year_id_index', ['school_year_id']],
        ['asignaturas', 'idx_asignaturas_tenant', ['tenant_id']],
        // Queda cubierto por el índice (tenant_id, fecha, estado) que esta misma migración crea.
        ['asistencias', 'idx_asistencias_tenant', ['tenant_id']],
        ['calificaciones', 'calificaciones_matricula_id_index', ['matricula_id']],
        ['calificaciones', 'idx_cal_asignacion_id', ['asignacion_id']],
        ['calificaciones', 'idx_cal_mat_asi_per', ['matricula_id', 'asignacion_id', 'periodo_id']],
        ['calificaciones', 'idx_cal_periodo_id', ['periodo_id']],
        ['calificaciones_academicas', 'calificaciones_academicas_asignacion_id_index', ['asignacion_id']],
        ['calificaciones_academicas', 'calificaciones_academicas_matricula_id_index', ['matricula_id']],
        ['calificaciones_academicas', 'idx_calac_mat_id', ['matricula_id']],
        ['carnet_accesos', 'carnet_accesos_tenant_id_index', ['tenant_id']],
        ['carnet_identidades', 'carnet_identidades_tenant_id_index', ['tenant_id']],
        ['carnet_zonas', 'carnet_zonas_tenant_id_index', ['tenant_id']],
        ['concepto_pagos', 'concepto_pagos_tenant_id_index', ['tenant_id']],
        ['config_institucional', 'idx_config_institucional_tenant', ['tenant_id']],
        ['device_tokens', 'device_tokens_user_id_index', ['user_id']],
        ['docentes', 'idx_docentes_tenant', ['tenant_id']],
        ['estudiantes', 'idx_estudiantes_tenant', ['tenant_id']],
        ['faltas_disciplinarias', 'idx_faltas_disciplinarias_tenant', ['tenant_id']],
        ['grados', 'idx_grados_tenant', ['tenant_id']],
        ['grupos', 'idx_grupos_tenant', ['tenant_id']],
        ['inscripciones', 'inscripciones_tenant_id_index', ['tenant_id']],
        ['insignias_estudiante', 'insignias_estudiante_matricula_id_index', ['matricula_id']],
        ['matriculas', 'idx_matriculas_tenant', ['tenant_id']],
        ['matriculas', 'matriculas_estado_index', ['estado']],
        ['matriculas', 'matriculas_grupo_id_index', ['grupo_id']],
        ['mensaje_destinatarios', 'mensaje_destinatarios_destinatario_id_index', ['destinatario_id']],
        ['mensajes', 'idx_mensajes_tenant', ['tenant_id']],
        ['mensajes', 'mensajes_remitente_id_index', ['remitente_id']],
        ['notificaciones', 'notificaciones_user_id_leida_index', ['user_id', 'leida']],
        ['pagina_secciones', 'pagina_secciones_tenant_id_index', ['tenant_id']],
        ['pagos', 'idx_pagos_tenant', ['tenant_id']],
        ['plan_evaluacion_periodos', 'plan_evaluacion_periodos_tenant_id_index', ['tenant_id']],
        ['plantillas_comunicacion', 'plantillas_comunicacion_tenant_id_index', ['tenant_id']],
        ['pre_matriculas', 'idx_pre_matriculas_tenant', ['tenant_id']],
        ['publicaciones', 'publicaciones_tenant_id_index', ['tenant_id']],
        ['puntos_estudiante', 'puntos_estudiante_matricula_id_index', ['matricula_id']],
        ['school_years', 'idx_school_years_tenant', ['tenant_id']],
        ['secciones', 'idx_secciones_tenant', ['tenant_id']],
        ['solicitudes_docente', 'solicitudes_docente_tenant_id_index', ['tenant_id']],
        ['solicitudes_estudiante', 'solicitudes_estudiante_tenant_id_index', ['tenant_id']],
        ['solicitudes_representante', 'solicitudes_representante_tenant_id_index', ['tenant_id']],
        ['support_sessions', 'support_sessions_tenant_id_index', ['tenant_id']],
        ['system_settings', 'idx_system_settings_tenant', ['tenant_id']],
        ['tenant_chat_messages', 'tenant_chat_messages_tenant_id_index', ['tenant_id']],
        ['tenant_features', 'tenant_features_tenant_id_index', ['tenant_id']],
        ['ventas_cafeteria', 'idx_ventas_cafeteria_tenant', ['tenant_id']],
    ];

    private const COBERTURA_ASISTENCIAS = 'asistencias_tenant_fecha_estado_index';

    public function up(): void
    {
        // Primero el índice nuevo, para que nunca falte cobertura durante la migración.
        if (Schema::hasTable('asistencias') && ! Schema::hasIndex('asistencias', self::COBERTURA_ASISTENCIAS)) {
            Schema::table('asistencias', function (Blueprint $t) {
                $t->index(['tenant_id', 'fecha', 'estado'], self::COBERTURA_ASISTENCIAS);
            });
        }

        foreach (self::REDUNDANTES as [$tabla, $indice, $columnas]) {
            if (Schema::hasTable($tabla) && Schema::hasIndex($tabla, $indice)) {
                Schema::table($tabla, function (Blueprint $t) use ($indice) {
                    $t->dropIndex($indice);
                });
            }
        }
    }

    public function down(): void
    {
        foreach (self::REDUNDANTES as [$tabla, $indice, $columnas]) {
            if (Schema::hasTable($tabla) && ! Schema::hasIndex($tabla, $indice)) {
                Schema::table($tabla, function (Blueprint $t) use ($indice, $columnas) {
                    $t->index($columnas, $indice);
                });
            }
        }

        if (Schema::hasTable('asistencias') && Schema::hasIndex('asistencias', self::COBERTURA_ASISTENCIAS)) {
            Schema::table('asistencias', function (Blueprint $t) {
                $t->dropIndex(self::COBERTURA_ASISTENCIAS);
            });
        }
    }
};
