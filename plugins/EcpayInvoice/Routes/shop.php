<?php

use Illuminate\Support\Facades\Route;
use Plugin\EcpayInvoice\Controllers\Shop\InvoiceController;

Route::post('/ecpay-invoice/verify-carrier',  [InvoiceController::class, 'verifyCarrier'])->name('ecpay_invoice.verify_carrier');
Route::get('/ecpay-invoice/love-codes',       [InvoiceController::class, 'loveCodes'])->name('ecpay_invoice.love_codes');
Route::post('/ecpay-invoice/verify-love-code', [InvoiceController::class, 'verifyLoveCode'])->name('ecpay_invoice.verify_love_code');
Route::post('/ecpay-invoice/store',           [InvoiceController::class, 'store'])->name('ecpay_invoice.store')->middleware(['checkout_auth']);
Route::post('/ecpay-invoice/session',         [InvoiceController::class, 'saveSession'])->name('ecpay_invoice.save_session');
