<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Elimina el módulo "Scheduling" (namespace App\*\Scheduling): una
 * implementación paralela de horarios que quedó sin usar — sin ningún
 * enlace desde el sidebar/menú, tocada solo por 2 barridos mecánicos
 * (nunca desarrollo deliberado) desde que apareció en el commit inicial
 * del proyecto, y con 0 filas en sus 9 tablas. El módulo real y activamente
 * usado es App\Http\Controllers\Admin\HorarioController (tablas horarios/
 * horario_detalles), documentado en la auditoría del proyecto — ver
 * project_auditoria_2026_09_01_system_settings.md.
 *
 * Las migraciones originales que crearon estas tablas (2026_03_26_100001 a
 * 2026_03_26_100009) se dejan intactas como parte del historial — es la
 * práctica estándar en Laravel para deshacer una tabla ya migrada.
 */
return new class extends Migration
{
    private const TABLAS = [
        // Hijas primero (tienen FK hacia las demás)
        'sch_horario_detalles',
        'sch_disponibilidad_profesor',
        'sch_asignaciones',
        // Padres
        'sch_horarios',
        'sch_aulas',
        'sch_franjas',
        'sch_profesores',
        'sch_cursos',
        'sch_materias',
    ];

    public function up(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        foreach (self::TABLAS as $tabla) {
            Schema::dropIfExists($tabla);
        }
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    public function down(): void
    {
        // Irreversible a propósito: recrear el esquema original requeriría
        // duplicar las 9 migraciones de creación. Si hace falta deshacer,
        // restaurar desde backup o revertir manualmente ese rango de
        // migraciones antes de esta.
        throw new \RuntimeException(
            'No reversible: el módulo Scheduling fue eliminado deliberadamente. '
            . 'Restaurar desde backup si es necesario.'
        );
    }
};
