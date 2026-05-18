<?php

use App\Http\Controllers\Admin\AcademicRiskController;
use Illuminate\Support\Facades\Route;

Route::prefix('riesgo')->name('riesgo.')->controller(AcademicRiskController::class)->group(function () {
    Route::get('/',                           'index')->name('index');
    Route::get('/{score}',                    'show')->name('show');
    Route::post('/calcular',                  'calcular')->name('calcular');
    Route::post('/estudiante/{id}/recalcular','recalcularUno')->name('recalcular-uno');
});
