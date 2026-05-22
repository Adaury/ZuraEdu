<?php

use App\Http\Controllers\Admin\GaleriaController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas — Galería Institucional
|--------------------------------------------------------------------------
| Prefijo : admin/galeria   (heredado del grupo admin en web.php)
| Nombre  : admin.galeria.*
*/

Route::prefix('galeria')->name('galeria.')->group(function () {

    Route::get('/dashboard',                                 [GaleriaController::class, 'dashboard'])->name('dashboard');
    Route::get('/',                                         [GaleriaController::class, 'index'])->name('index');
    Route::get('/crear',                                    [GaleriaController::class, 'create'])->name('create');
    Route::post('/',                                        [GaleriaController::class, 'store'])->name('store');
    Route::get('/{galeria}',                                [GaleriaController::class, 'show'])->name('show');
    Route::get('/{galeria}/editar',                         [GaleriaController::class, 'edit'])->name('edit');
    Route::put('/{galeria}',                                [GaleriaController::class, 'update'])->name('update');
    Route::delete('/{galeria}',                             [GaleriaController::class, 'destroy'])->name('destroy');
    Route::post('/{galeria}/fotos',                         [GaleriaController::class, 'subirFotos'])->name('fotos.subir');
    Route::delete('/{galeria}/fotos/{foto}',                [GaleriaController::class, 'eliminarFoto'])->name('fotos.eliminar');
});
