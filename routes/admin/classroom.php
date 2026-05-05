<?php

use App\Http\Controllers\Admin\ClaseVirtualController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas Admin — Classroom / Aulas Virtuales
|--------------------------------------------------------------------------
*/

Route::prefix('classroom')->name('classroom.')->group(function () {
    Route::get('/',                              [ClaseVirtualController::class, 'index'])->name('index');
    Route::get('/crear',                         [ClaseVirtualController::class, 'create'])->name('create');
    Route::post('/',                             [ClaseVirtualController::class, 'store'])->name('store');
    Route::get('/{claseVirtual}',                [ClaseVirtualController::class, 'show'])->name('show');
    Route::get('/{claseVirtual}/editar',         [ClaseVirtualController::class, 'edit'])->name('edit');
    Route::put('/{claseVirtual}',                [ClaseVirtualController::class, 'update'])->name('update');
    Route::delete('/{claseVirtual}',             [ClaseVirtualController::class, 'destroy'])->name('destroy');
    Route::patch('/{claseVirtual}/toggle-activo',[ClaseVirtualController::class, 'toggleActivo'])->name('toggle-activo');
});
