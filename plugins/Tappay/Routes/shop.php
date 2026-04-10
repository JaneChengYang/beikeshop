<?php

use Illuminate\Support\Facades\Route;
use Plugin\Tappay\Controllers\TapPayController;

Route::post('/tappay/capture',   [TapPayController::class, 'capture'])->name('tappay_capture');
Route::get('/tappay/3ds-return', [TapPayController::class, 'return3ds'])->name('tappay_3ds_return');
Route::post('/tappay/notify',    [TapPayController::class, 'notify'])->name('tappay_notify')->withoutMiddleware(['web']);
