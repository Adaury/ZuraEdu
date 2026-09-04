<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Backup automático de ZuraEdu
    |--------------------------------------------------------------------------
    |
    | Controla el respaldo diario programado (base de datos + archivos
    | persistentes en storage/app/public). Ver docs/BACKUP_ZURAEDU.md.
    |
    */

    'enabled' => env('BACKUP_ENABLED', true),

    // Hora de ejecución diaria (formato H:i). El scheduler de Laravel usa
    // config('app.timezone') = UTC en este proyecto, no la hora local del
    // servidor — ver docs/BACKUP_ZURAEDU.md para la conversión.
    'hora' => env('BACKUP_HORA', '02:30'),

    // Días de retención: se conservan los backups de los últimos N días,
    // el resto se elimina automáticamente después de cada corrida exitosa.
    'retencion_dias' => (int) env('BACKUP_RETENCION_DIAS', 7),

    'incluir_archivos' => env('BACKUP_INCLUIR_ARCHIVOS', true),

    // Disco (config/filesystems.php) donde se guardan los .sql/.zip de backup.
    // Debe ser un disco PRIVADO (nunca 'public'): los backups contienen datos
    // sensibles de todos los tenants.
    'disco' => env('BACKUP_DISCO', 'local'),

    // Carpeta de origen a comprimir para el backup de archivos.
    'archivos_origen' => storage_path('app/public'),

    // Tamaño mínimo (bytes) para considerar un backup válido y no vacío/corrupto.
    'tamano_minimo_bytes' => 100,
];
