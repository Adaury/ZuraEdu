<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PDO;

/**
 * Reporta los índices reales (SHOW INDEX) de un conjunto de tablas de interés,
 * agrupados por nombre de índice. Herramienta de inspección manual, no valida
 * ni corrige nada.
 */
class CheckIndexesCommand extends Command
{
    protected $signature   = 'sge:check-indexes {--tabla=* : Tabla(s) específica(s), por defecto una lista fija de interés}';
    protected $description = 'Muestra los índices reales de las tablas indicadas (SHOW INDEX)';

    private const TABLAS_DEFECTO = [
        'periodos', 'school_years', 'faltas_disciplinarias',
        'pre_matriculas', 'solicitudes_representante', 'solicitudes_docente',
        'mensajes', 'mensaje_destinatarios',
        'horario_detalle', 'horario_activo',
        'entregas_classroom', 'materiales_clase',
    ];

    public function handle(): int
    {
        $tablas = $this->option('tabla') ?: self::TABLAS_DEFECTO;
        $pdo    = DB::connection()->getPdo();

        foreach ($tablas as $tabla) {
            try {
                $rows = $pdo->query("SHOW INDEX FROM `{$tabla}`")->fetchAll(PDO::FETCH_ASSOC);

                $porIndice = [];
                foreach ($rows as $row) {
                    $porIndice[$row['Key_name']][] = $row['Column_name'];
                }

                $partes = [];
                foreach ($porIndice as $nombre => $columnas) {
                    $partes[] = $nombre . '(' . implode(',', $columnas) . ')';
                }

                $this->info($tabla . ':');
                $this->line('  ' . implode("\n  ", $partes));
                $this->newLine();
            } catch (\Throwable $e) {
                $this->error($tabla . ': ' . $e->getMessage());
            }
        }

        return self::SUCCESS;
    }
}
