<?php

use App\Http\Controllers\Admin\TicketController;

// ── Tickets de Soporte Interno ────────────────────────────────────────────
Route::prefix('soporte')->name('soporte.')->group(function () {
    Route::get('/',                             [TicketController::class, 'index'])->name('index');
    Route::get('/crear',                        [TicketController::class, 'create'])->name('create');
    Route::post('/',                            [TicketController::class, 'store'])->name('store');
    Route::get('/lista/excel',                  [TicketController::class, 'listaExcel'])->name('lista-excel');
    Route::get('/lista/pdf',                    [TicketController::class, 'listaPdf'])->name('lista-pdf');
    Route::get('/{soporte}',                    [TicketController::class, 'show'])->name('show');
    Route::post('/{soporte}/responder',         [TicketController::class, 'responder'])->name('responder');
    Route::patch('/{soporte}/estado',           [TicketController::class, 'cambiarEstado'])->name('estado');
    Route::patch('/{soporte}/asignar',          [TicketController::class, 'asignar'])->name('asignar');
});
