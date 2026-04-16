<?php

use Illuminate\Support\Facades\Route;
use Plugin\EcpayInvoice\Controllers\Admin\InvoiceController;

Route::prefix('ecpay-invoice')->group(function () {
    Route::get('/',                                          [InvoiceController::class, 'index'])->name('ecpay_invoice.index');
    Route::post('/{invoice}/issue',                          [InvoiceController::class, 'issue'])->name('ecpay_invoice.issue');
    Route::post('/{invoice}/void',                           [InvoiceController::class, 'void'])->name('ecpay_invoice.void');
    Route::post('/{invoice}/allowance',                      [InvoiceController::class, 'allowance'])->name('ecpay_invoice.allowance');
    Route::post('/allowance/{allowance}/void',               [InvoiceController::class, 'voidAllowance'])->name('ecpay_invoice.void_allowance');
});
