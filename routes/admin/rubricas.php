<?php

use App\Http\Controllers\Admin\RubricaAdminController;

Route::prefix('rubricas')->name('rubricas.')->group(function () {
    Route::get('/',           [RubricaAdminController::class, 'index'])->name('index');
    Route::get('/{rubrica}',  [RubricaAdminController::class, 'show'])->name('show');
});
