<?php

use App\Http\Controllers\Admin\OdooController;

// ── Integración con Odoo (por centro educativo) — solo Administrador ──────────
Route::prefix('integraciones/odoo')->name('odoo.')->middleware('can:solo-administrador')->group(function () {
    Route::get('/',            [OdooController::class, 'index'])->name('index');
    Route::put('/',            [OdooController::class, 'guardar'])->name('guardar');
    Route::post('/probar',     [OdooController::class, 'probar'])->name('probar')->middleware('throttle:10,1');
    Route::post('/sincronizar', [OdooController::class, 'sincronizar'])->name('sincronizar')->middleware('throttle:6,1');
    Route::delete('/',         [OdooController::class, 'desconectar'])->name('desconectar');
});
