<?php

use Illuminate\Support\Facades\Route;
use Plugin\TappayJkoPay\Controllers\JkoPayController;

Route::post('/tappay-jkopay/pay',    [JkoPayController::class, 'pay'])->name('tappay_jko_pay.pay');
Route::get('/tappay-jkopay/return',  [JkoPayController::class, 'return'])->name('tappay_jko_pay.return');
Route::post('/tappay-jkopay/notify', [JkoPayController::class, 'notify'])->name('tappay_jko_pay.notify')->withoutMiddleware(['web']);
