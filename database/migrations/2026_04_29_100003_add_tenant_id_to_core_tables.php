<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // Tablas core que deben aislarse por tenant
    private array $tables = [
        'users', 'school_years', 'grados', 'secciones', 'grupos',
        'docentes', 'estudiantes', 'matriculas',
        'asignaturas', 'asignaciones', 'areas',
        'periodos', 'config_calificaciones',
        'calificaciones', 'calificaciones_academicas',
        'calificacion_audits',
        'asistencias',
        'indicadores_aprendizaje', 'evaluaciones_indicadores',
        'resultados_aprendizaje',
        'competencias_especificas', 'indicadores_logro', 'evaluaciones_registro',
        'boletines_config', 'boletin_observaciones', 'promociones',
        'especialidades_tecnicas', 'malla_curricular',
        'calendario_academico', 'alertas_sistema', 'comunicados',
        'aulas', 'franjas_horarias', 'horarios', 'horario_detalles',
        'disponibilidad_docentes', 'suplencias',
        'config_institucional',
        'activity_logs',
        'clases_virtuales', 'materiales_clase', 'entregas_classroom',
        'comentarios_classroom', 'archivos_material', 'archivos_entrega',
        'zc_recursos', 'zc_quizzes', 'zc_preguntas', 'zc_opciones',
        'zc_intentos', 'zc_respuestas', 'zc_rubrics', 'zc_rubric_criterios',
        'zc_rubric_calificaciones',
        'planificaciones', 'plan_clases',
        'representantes', 'notificaciones',
        'pagos', 'mensajes',
        'observaciones', 'faltas_disciplinarias',
        'reconocimientos', 'tipos_reconocimiento',
        'tutorias', 'sesiones_tutorias',
        'seguimiento_social_casos', 'intervenciones_caso',
        'reuniones', 'acuerdos_reunion',
        'evaluaciones_docentes',
        'proyectos_escolares', 'fases_proyecto', 'integrantes_proyecto',
        'gamificacion_puntos', 'gamificacion_insignias',
        'nomina_empleados', 'pagos_nomina',
        'productos_cafeteria', 'ventas_cafeteria',
        'libros', 'prestamos_biblioteca',
        'articulos_inventario', 'movimientos_inventario',
        'rutas_transporte', 'paradas_ruta',
        'fichas_salud', 'incidentes_medicos',
        'encuestas', 'preguntas_encuesta', 'opciones_pregunta', 'respuestas_encuesta',
        'tickets_soporte', 'respuestas_ticket',
        'albumes', 'fotos_album',
        'eventos', 'inscripciones_evento',
        'avisos_emergencia',
        'becas', 'becas_estudiante',
        'puntos_estudiante', 'insignias_estudiante',
        'instrumentos_evaluacion', 'instrumento_criterios',
        'instrumentos_evaluacion_estudiantes',
        'pre_matriculas',
        'rendimiento_cache',
        'recursos_fisicos', 'reservas_recurso',
        'equipos', 'prestamos_equipos',
        'planificaciones_actividades', 'planificaciones_ra_items',
        'estudiantes_ruta',
        'kpis',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            if (! Schema::hasTable($table)) continue;
            if (Schema::hasColumn($table, 'tenant_id')) continue;

            Schema::table($table, function (Blueprint $t) use ($table) {
                $t->unsignedBigInteger('tenant_id')->default(1)->after('id');
                $t->index('tenant_id', "idx_{$table}_tenant");
            });
        }

        // Asegurarse de que todos los registros existentes queden en tenant 1
        foreach ($this->tables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'tenant_id')) {
                DB::table($table)->whereNull('tenant_id')->update(['tenant_id' => 1]);
            }
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            if (! Schema::hasTable($table)) continue;
            if (! Schema::hasColumn($table, 'tenant_id')) continue;

            Schema::table($table, function (Blueprint $t) use ($table) {
                $t->dropIndex("idx_{$table}_tenant");
                $t->dropColumn('tenant_id');
            });
        }
    }
};
