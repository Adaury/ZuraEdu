<?php

use App\Http\Controllers\Admin\SolicitudesAdminController;
use App\Http\Controllers\Admin\SolicitudesEstudianteAdminController;

// ── Solicitudes de Representantes ─────────────────────────────────────────
Route::get('solicitudes',                              [SolicitudesAdminController::class, 'index'])->name('solicitudes.index');
Route::get('solicitudes/{solicitud}',                  [SolicitudesAdminController::class, 'show'])->name('solicitudes.show');
Route::post('solicitudes/{solicitud}/responder',       [SolicitudesAdminController::class, 'responder'])->name('solicitudes.responder');

// ── Solicitudes de Estudiantes ─────────────────────────────────────────────
Route::get('solicitudes-est',                          [SolicitudesEstudianteAdminController::class, 'index'])->name('solicitudes-est.index');
Route::get('solicitudes-est/{solicitud}',              [SolicitudesEstudianteAdminController::class, 'show'])->name('solicitudes-est.show');
Route::post('solicitudes-est/{solicitud}/responder',   [SolicitudesEstudianteAdminController::class, 'responder'])->name('solicitudes-est.responder');
