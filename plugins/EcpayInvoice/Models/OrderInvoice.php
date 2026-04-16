<?php

namespace Plugin\EcpayInvoice\Models;

use Beike\Models\Order;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderInvoice extends Model
{
    protected $table = 'order_invoices';

    protected $fillable = [
        'order_id',
        'carrier_type',
        'carrier_number',
        'tax_id',
        'company_title',
        'love_code',
        'invoice_number',
        'random_number',
        'status',
        'failed_reason',
        'issued_at',
        'voided_at',
        'issue_log',
        'response_log',
        'reissue_count',
    ];

    protected $casts = [
        'issued_at'    => 'datetime',
        'voided_at'    => 'datetime',
        'issue_log'    => 'array',
        'response_log' => 'array',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function allowances()
    {
        return $this->hasMany(OrderInvoiceAllowance::class, 'order_invoice_id');
    }

    public function getCarrierTypeLabelAttribute(): string
    {
        return match ($this->carrier_type) {
            'personal' => __('EcpayInvoice::common.carrier_personal'),
            'mobile'   => __('EcpayInvoice::common.carrier_mobile'),
            'love'     => __('EcpayInvoice::common.carrier_love'),
            'company'  => __('EcpayInvoice::common.carrier_company'),
            default    => $this->carrier_type,
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending' => __('EcpayInvoice::common.status_pending'),
            'issued'  => __('EcpayInvoice::common.status_issued'),
            'failed'  => __('EcpayInvoice::common.status_failed'),
            'void'    => __('EcpayInvoice::common.status_void'),
            default   => $this->status,
        };
    }
}
