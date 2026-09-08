<?php

use App\Http\Controllers\Admin\HomepageController;
use App\Http\Controllers\Admin\PaginaSeccionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas — Sitio Público (Branding + Constructor de Secciones)
|--------------------------------------------------------------------------
| Prefijo : admin/homepage, admin/secciones   (heredado del grupo admin en web.php)
| Nombre  : admin.homepage.*, admin.secciones.*
| Permiso : gestionar-configuracion
|
| homepage.* se movió aquí desde admin/academico.php (estaba mal ubicado) --
| los nombres de ruta NO cambiaron, nada externo se rompe. moverOrden ya no
| existe: el reordenamiento ahora lo maneja secciones.reordenar.
*/

Route::middleware('can:gestionar-configuracion')->group(function () {
    Route::get('homepage',  [HomepageController::class, 'edit'])->name('homepage.edit');
    Route::post('homepage', [HomepageController::class, 'update'])->name('homepage.update');
});

Route::prefix('secciones')->name('secciones.')->middleware('can:gestionar-configuracion')->group(function () {
    Route::get('/',                  [PaginaSeccionController::class, 'index'])->name('index');
    Route::post('/',                 [PaginaSeccionController::class, 'store'])->name('store');
    Route::post('/reordenar',        [PaginaSeccionController::class, 'reordenar'])->name('reordenar');
    Route::get('/{seccion}/editar',  [PaginaSeccionController::class, 'edit'])->name('edit');
    Route::put('/{seccion}',         [PaginaSeccionController::class, 'update'])->name('update');
    Route::patch('/{seccion}/activo',[PaginaSeccionController::class, 'toggleActivo'])->name('toggle');
    Route::delete('/{seccion}',      [PaginaSeccionController::class, 'destroy'])->name('destroy');
});
