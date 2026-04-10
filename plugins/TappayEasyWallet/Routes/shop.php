<?php

use Illuminate\Support\Facades\Route;
use Plugin\TappayEasyWallet\Controllers\EasyWalletController;

Route::post('/tappay-easywallet/pay',    [EasyWalletController::class, 'pay'])->name('tappay_easy_wallet.pay');
Route::get('/tappay-easywallet/return',  [EasyWalletController::class, 'return'])->name('tappay_easy_wallet.return');
Route::post('/tappay-easywallet/notify', [EasyWalletController::class, 'notify'])->name('tappay_easy_wallet.notify')->withoutMiddleware(['web']);
