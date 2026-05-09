<?php

use App\Http\Controllers\Admin\SolicitudesAdminController;

// ── Solicitudes de Representantes ─────────────────────────────────────────
Route::get('solicitudes',                              [SolicitudesAdminController::class, 'index'])->name('solicitudes.index');
Route::get('solicitudes/{solicitud}',                  [SolicitudesAdminController::class, 'show'])->name('solicitudes.show');
Route::post('solicitudes/{solicitud}/responder',       [SolicitudesAdminController::class, 'responder'])->name('solicitudes.responder');
