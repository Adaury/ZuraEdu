<?php

use App\Http\Controllers\Admin\KpiController;

// ── Dashboard KPIs — Director / Admin ────────────────────────────────────────
Route::prefix('kpis')->name('kpis.')
    ->middleware('can:acceso-direccion')
    ->group(function () {
        Route::get('/',     [KpiController::class, 'index'])->name('index');
        Route::get('/data', [KpiController::class, 'data'])->name('data');
    });
