<?php

use App\Http\Controllers\Admin\RegistroAcademicoController;

Route::prefix('registro-academico')->name('registro-academico.')->group(function () {
    Route::get('/', [RegistroAcademicoController::class, 'dashboard'])->name('dashboard');
});
