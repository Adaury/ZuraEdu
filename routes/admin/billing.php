<?php

use App\Http\Controllers\Admin\BillingController;

Route::prefix('billing')->name('billing.')->group(function () {
    Route::get('/',              [BillingController::class, 'index'])->name('index');
    Route::post('/checkout',     [BillingController::class, 'checkout'])->name('checkout');
    Route::get('/success',       [BillingController::class, 'success'])->name('success');
    Route::get('/cancel',        [BillingController::class, 'cancel'])->name('cancel');
    Route::post('/transferencia',[BillingController::class, 'transferencia'])->name('transferencia');
});
