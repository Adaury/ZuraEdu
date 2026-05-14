<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── matriculas ────────────────────────────────────────────────────
        // Cubre: where grupo_id=X AND estado='activa' — query más frecuente del sistema
        Schema::table('matriculas', function (Blueprint $table) {
            if (! $this->idx('matriculas', 'mat_grupo_estado_idx')) {
                $table->index(['grupo_id', 'estado'], 'mat_grupo_estado_idx');
            }
            // Extiende para el filtro por año escolar
            if (! $this->idx('matriculas', 'mat_grupo_sy_estado_idx')) {
                $table->index(['grupo_id', 'school_year_id', 'estado'], 'mat_grupo_sy_estado_idx');
            }
        });

        // ── asignaciones ──────────────────────────────────────────────────
        // Cubre: portal docente — where docente_id=X AND school_year_id=Y AND activo=1
        Schema::table('asignaciones', function (Blueprint $table) {
            if (! $this->idx('asignaciones', 'asig_docente_sy_activo_idx')) {
                $table->index(['docente_id', 'school_year_id', 'activo'], 'asig_docente_sy_activo_idx');
            }
        });

        // ── grupos ────────────────────────────────────────────────────────
        // Cubre: where school_year_id=X AND activo=1 (AsignaturaController, ReportesEjecutivos)
        Schema::table('grupos', function (Blueprint $table) {
            if (! $this->idx('grupos', 'grupos_sy_activo_idx')) {
                $table->index(['school_year_id', 'activo'], 'grupos_sy_activo_idx');
            }
        });

        // ── periodos ─────────────────────────────────────────────────────
        // Cubre: where school_year_id=X AND activo=true (cargado en cada request)
        Schema::table('periodos', function (Blueprint $table) {
            if (! $this->idx('periodos', 'periodos_sy_activo_idx')) {
                $table->index(['school_year_id', 'activo'], 'periodos_sy_activo_idx');
            }
        });

        // ── notificaciones ────────────────────────────────────────────────
        // Cubre: where user_id=X AND leida=0 ORDER BY created_at DESC LIMIT 10
        Schema::table('notificaciones', function (Blueprint $table) {
            if (! $this->idx('notificaciones', 'notif_user_leida_created_idx')) {
                $table->index(['user_id', 'leida', 'created_at'], 'notif_user_leida_created_idx');
            }
        });

        // ── faltas_disciplinarias ─────────────────────────────────────────
        // Cubre: dashboard ejecutivo — groupBy tipo filtrado por tenant
        Schema::table('faltas_disciplinarias', function (Blueprint $table) {
            if (! $this->idx('faltas_disciplinarias', 'faltas_tenant_tipo_idx')) {
                $table->index(['tenant_id', 'tipo'], 'faltas_tenant_tipo_idx');
            }
        });

        // ── pre_matriculas ────────────────────────────────────────────────
        // Cubre: dashboard — count by estado per tenant
        Schema::table('pre_matriculas', function (Blueprint $table) {
            if (! $this->idx('pre_matriculas', 'premat_tenant_estado_idx')) {
                $table->index(['tenant_id', 'estado'], 'premat_tenant_estado_idx');
            }
        });

        // ── entregas_classroom ────────────────────────────────────────────
        // Cubre: progresoEstudiantes — whereIn(matricula_id) + where(estado)
        Schema::table('entregas_classroom', function (Blueprint $table) {
            if (! $this->idx('entregas_classroom', 'entregas_mat_estado_idx')) {
                $table->index(['matricula_id', 'estado'], 'entregas_mat_estado_idx');
            }
            // Cubre: stats de clase — whereIn(material_id) + where(estado)
            if (! $this->idx('entregas_classroom', 'entregas_mat_id_estado_idx')) {
                $table->index(['material_id', 'estado'], 'entregas_mat_id_estado_idx');
            }
        });

        // ── materiales_clase ──────────────────────────────────────────────
        // Cubre: tareasPendientes — whereIn(clase_virtual_id) + tipo + publicado
        Schema::table('materiales_clase', function (Blueprint $table) {
            if (! $this->idx('materiales_clase', 'mat_clase_tipo_pub_idx')) {
                $table->index(['clase_virtual_id', 'tipo', 'publicado'], 'mat_clase_tipo_pub_idx');
            }
        });

        // ── calificaciones_academicas ─────────────────────────────────────
        // Extiende cal_acad_asig_pub_idx para incluir school_year_id (boletín + grilla)
        Schema::table('calificaciones_academicas', function (Blueprint $table) {
            if (! $this->idx('calificaciones_academicas', 'calac_asig_sy_pub_idx')) {
                $table->index(['asignacion_id', 'school_year_id', 'publicado'], 'calac_asig_sy_pub_idx');
            }
        });

        // ── pagos ─────────────────────────────────────────────────────────
        // Cubre: dashboard ejecutivo — SUM/COUNT por estado filtrado por tenant
        Schema::table('pagos', function (Blueprint $table) {
            if (! $this->idx('pagos', 'pagos_tenant_estado_idx')) {
                $table->index(['tenant_id', 'estado'], 'pagos_tenant_estado_idx');
            }
            // Cubre: recordatorio vencidos — where estado='pendiente' AND fecha_vencimiento < today
            if (! $this->idx('pagos', 'pagos_estado_venc_idx')) {
                $table->index(['estado', 'fecha_vencimiento'], 'pagos_estado_venc_idx');
            }
        });
    }

    public function down(): void
    {
        $drops = [
            'matriculas'               => ['mat_grupo_estado_idx', 'mat_grupo_sy_estado_idx'],
            'asignaciones'             => ['asig_docente_sy_activo_idx'],
            'grupos'                   => ['grupos_sy_activo_idx'],
            'periodos'                 => ['periodos_sy_activo_idx'],
            'notificaciones'           => ['notif_user_leida_created_idx'],
            'faltas_disciplinarias'    => ['faltas_tenant_tipo_idx'],
            'pre_matriculas'           => ['premat_tenant_estado_idx'],
            'entregas_classroom'       => ['entregas_mat_estado_idx', 'entregas_mat_id_estado_idx'],
            'materiales_clase'         => ['mat_clase_tipo_pub_idx'],
            'calificaciones_academicas'=> ['calac_asig_sy_pub_idx'],
            'pagos'                    => ['pagos_tenant_estado_idx', 'pagos_estado_venc_idx'],
        ];

        foreach ($drops as $table => $indexes) {
            if (Schema::hasTable($table)) {
                Schema::table($table, function (Blueprint $t) use ($indexes) {
                    foreach ($indexes as $idx) {
                        try { $t->dropIndex($idx); } catch (\Throwable) {}
                    }
                });
            }
        }
    }

    private function idx(string $table, string $name): bool
    {
        return count(DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$name])) > 0;
    }
};
