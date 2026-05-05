<?php

use App\Http\Controllers\Admin\ExportacionMasivaController;

Route::get('exportacion-masiva',        [ExportacionMasivaController::class, 'index'])
    ->name('exportacion-masiva.index');

Route::post('exportacion-masiva/exportar', [ExportacionMasivaController::class, 'exportar'])
    ->name('exportacion-masiva.exportar');
