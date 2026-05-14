<?php

use App\Http\Controllers\Admin\ComunicacionesController;

Route::get('comunicaciones/no-leidos', [ComunicacionesController::class, 'apiNoLeidos'])
    ->name('comunicaciones.noLeidos');

Route::get('comunicaciones/{mensaje}/adjunto', [ComunicacionesController::class, 'descargarAdjunto'])
    ->name('comunicaciones.adjunto');

Route::resource('comunicaciones', ComunicacionesController::class)
    ->except(['edit', 'update']);
