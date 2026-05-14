<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Enterprise optimization: índices compuestos para las tablas creadas
 * en la Fase Enterprise (Mayo 2026) — mejora las queries más frecuentes.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── mensaje_destinatarios ─────────────────────────────────────────
        // Optimiza el conteo de no-leídos del sidebar (frecuencia muy alta)
        if (Schema::hasTable('mensaje_destinatarios')) {
            Schema::table('mensaje_destinatarios', function (Blueprint $table) {
                // Índice compuesto para el badge de mensajes no leídos
                if (!$this->indexExists('mensaje_destinatarios', 'mdest_unread_idx')) {
                    $table->index(
                        ['destinatario_id', 'leido_at', 'eliminado'],
                        'mdest_unread_idx'
                    );
                }
            });
        }

        // ── solicitudes_docente ───────────────────────────────────────────
        // Optimiza lista por docente + estado (portal y admin)
        if (Schema::hasTable('solicitudes_docente')) {
            Schema::table('solicitudes_docente', function (Blueprint $table) {
                if (!$this->indexExists('solicitudes_docente', 'sol_doc_tenant_estado_idx')) {
                    $table->index(['tenant_id', 'estado'], 'sol_doc_tenant_estado_idx');
                }
                if (!$this->indexExists('solicitudes_docente', 'sol_doc_created_idx')) {
                    $table->index('created_at', 'sol_doc_created_idx');
                }
            });
        }

        // ── mensajes ─────────────────────────────────────────────────────
        // Optimiza lista de enviados (remitente + tenant + timestamp)
        if (Schema::hasTable('mensajes')) {
            Schema::table('mensajes', function (Blueprint $table) {
                if (!$this->indexExists('mensajes', 'mensajes_remitente_created_idx')) {
                    $table->index(['remitente_id', 'created_at'], 'mensajes_remitente_created_idx');
                }
                if (Schema::hasColumn('mensajes', 'tenant_id') &&
                    !$this->indexExists('mensajes', 'mensajes_tenant_tipo_idx')) {
                    $table->index(['tenant_id', 'tipo'], 'mensajes_tenant_tipo_idx');
                }
            });
        }

        // ── solicitudes_representante ─────────────────────────────────────
        if (Schema::hasTable('solicitudes_representante')) {
            Schema::table('solicitudes_representante', function (Blueprint $table) {
                if (!$this->indexExists('solicitudes_representante', 'sol_rep_tenant_estado_idx')) {
                    $table->index(['tenant_id', 'estado'], 'sol_rep_tenant_estado_idx');
                }
            });
        }

        // ── solicitudes_estudiante ────────────────────────────────────────
        if (Schema::hasTable('solicitudes_estudiante')) {
            Schema::table('solicitudes_estudiante', function (Blueprint $table) {
                if (!$this->indexExists('solicitudes_estudiante', 'sol_est_tenant_estado_idx')) {
                    $table->index(['tenant_id', 'estado'], 'sol_est_tenant_estado_idx');
                }
            });
        }

        // ── calificaciones_academicas ─────────────────────────────────────
        // Optimiza la grilla de calificaciones por asignación (query más pesada del sistema)
        if (Schema::hasTable('calificaciones_academicas')) {
            Schema::table('calificaciones_academicas', function (Blueprint $table) {
                if (!$this->indexExists('calificaciones_academicas', 'cal_acad_asig_pub_idx')) {
                    $table->index(['asignacion_id', 'publicado'], 'cal_acad_asig_pub_idx');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('mensaje_destinatarios')) {
            Schema::table('mensaje_destinatarios', function (Blueprint $table) {
                $table->dropIndex('mdest_unread_idx');
            });
        }
        if (Schema::hasTable('solicitudes_docente')) {
            Schema::table('solicitudes_docente', function (Blueprint $table) {
                $table->dropIndex('sol_doc_tenant_estado_idx');
                $table->dropIndex('sol_doc_created_idx');
            });
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        try {
            $sm = \DB::getDoctrineSchemaManager();
            $indexes = $sm->listTableIndexes($table);
            return isset($indexes[$indexName]);
        } catch (\Throwable) {
            return false;
        }
    }
};
