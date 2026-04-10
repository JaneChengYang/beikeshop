<?php

use Illuminate\Support\Facades\Route;
use Plugin\TappayLinePay\Controllers\LinePayController;

Route::post('/tappay-linepay/pay',    [LinePayController::class, 'pay'])->name('tappay_line_pay.pay');
Route::get('/tappay-linepay/return',  [LinePayController::class, 'return'])->name('tappay_line_pay.return');
Route::post('/tappay-linepay/notify', [LinePayController::class, 'notify'])->name('tappay_line_pay.notify')->withoutMiddleware(['web']);
