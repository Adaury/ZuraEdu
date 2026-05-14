<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
$pdo = DB::connection()->getPdo();

$tables = [
    'periodos','school_years','faltas_disciplinarias',
    'pre_matriculas','solicitudes_representante','solicitudes_docente',
    'mensajes','mensaje_destinatarios',
    'horario_detalle','horario_activo',
    'entregas_classroom','materiales_clase',
];

foreach ($tables as $t) {
    try {
        $rows = $pdo->query("SHOW INDEX FROM `{$t}`")->fetchAll(PDO::FETCH_ASSOC);
        $byKey = [];
        foreach ($rows as $r) {
            $byKey[$r['Key_name']][] = $r['Column_name'];
        }
        $parts = [];
        foreach ($byKey as $name => $cols) {
            $parts[] = $name . '(' . implode(',', $cols) . ')';
        }
        echo $t . ":\n  " . implode("\n  ", $parts) . "\n\n";
    } catch (Exception $e) {
        echo $t . ": ERROR - " . $e->getMessage() . "\n\n";
    }
}
