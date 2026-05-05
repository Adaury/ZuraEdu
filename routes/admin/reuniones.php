<?php

use App\Http\Controllers\Admin\ReunionController;
use Illuminate\Support\Facades\Route;

// ── Actas de Reuniones ────────────────────────────────────────────────────
Route::prefix('reuniones')->name('reuniones.')->group(function () {

    Route::get('/',               [ReunionController::class, 'index'])         ->name('index');
    Route::get('/crear',          [ReunionController::class, 'create'])        ->name('create');
    Route::post('/',              [ReunionController::class, 'store'])         ->name('store');
    Route::get('/{reunion}',      [ReunionController::class, 'show'])          ->name('show');
    Route::get('/{reunion}/editar',[ReunionController::class, 'edit'])         ->name('edit');
    Route::put('/{reunion}',      [ReunionController::class, 'update'])        ->name('update');
    Route::delete('/{reunion}',   [ReunionController::class, 'destroy'])       ->name('destroy');

    // Acuerdos
    Route::post('/{reunion}/acuerdos',              [ReunionController::class, 'addAcuerdo'])    ->name('acuerdos.store');
    Route::patch('/acuerdos/{acuerdo}/toggle',      [ReunionController::class, 'toggleCumplido'])->name('acuerdos.toggle');

    // PDF
    Route::get('/{reunion}/acta-pdf',               [ReunionController::class, 'actaPdf'])       ->name('acta_pdf');
});
