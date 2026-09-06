<?php

use App\Http\Controllers\Admin\PublicacionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas — Noticias y Publicaciones (portal público)
|--------------------------------------------------------------------------
| Prefijo : admin/publicaciones   (heredado del grupo admin en web.php)
| Nombre  : admin.publicaciones.*
| Permiso : gestionar-configuracion (mismo que Branding/Homepage)
*/

Route::prefix('publicaciones')->name('publicaciones.')->middleware('can:gestionar-configuracion')->group(function () {
    Route::get('/',              [PublicacionController::class, 'index'])->name('index');
    Route::get('/crear',         [PublicacionController::class, 'create'])->name('create');
    Route::post('/',             [PublicacionController::class, 'store'])->name('store');
    Route::get('/{publicacion}/editar', [PublicacionController::class, 'edit'])->name('edit');
    Route::put('/{publicacion}', [PublicacionController::class, 'update'])->name('update');
    Route::delete('/{publicacion}', [PublicacionController::class, 'destroy'])->name('destroy');
});
