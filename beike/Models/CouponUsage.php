<?php

namespace Beike\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CouponUsage extends Model
{
    protected $fillable = ['coupon_id', 'customer_id', 'order_id'];

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }
}
