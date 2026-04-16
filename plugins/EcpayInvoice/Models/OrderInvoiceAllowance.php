<?php

namespace Plugin\EcpayInvoice\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderInvoiceAllowance extends Model
{
    protected $table = 'order_invoice_allowances';

    protected $fillable = [
        'order_invoice_id',
        'allowance_number',
        'desc',
        'amount',
        'status',
        'voided_at',
        'response_log',
    ];

    protected $casts = [
        'voided_at'    => 'datetime',
        'response_log' => 'array',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(OrderInvoice::class, 'order_invoice_id');
    }
}
