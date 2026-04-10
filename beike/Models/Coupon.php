<?php

namespace Beike\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Coupon extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'code', 'type', 'value', 'min_order', 'usage_limit',
        'usage_limit_per_user', 'used_count', 'customer_id',
        'starts_at', 'expires_at', 'active',
    ];

    protected $casts = [
        'value'                 => 'float',
        'min_order'             => 'float',
        'active'                => 'boolean',
        'starts_at'             => 'datetime',
        'expires_at'            => 'datetime',
    ];

    public function usages(): HasMany
    {
        return $this->hasMany(CouponUsage::class);
    }

    /**
     * 驗證優惠碼是否可用，回傳 ['valid' => bool, 'message' => string]
     */
    public function validate(?int $customerId, float $orderAmount): array
    {
        if (! $this->active) {
            return ['valid' => false, 'message' => trans('coupon.inactive')];
        }

        if ($this->starts_at && now()->lt($this->starts_at)) {
            return ['valid' => false, 'message' => trans('coupon.not_started')];
        }

        if ($this->expires_at && now()->gt($this->expires_at)) {
            return ['valid' => false, 'message' => trans('coupon.expired')];
        }

        if ($this->usage_limit > 0 && $this->used_count >= $this->usage_limit) {
            return ['valid' => false, 'message' => trans('coupon.usage_limit_reached')];
        }

        if ($this->min_order > 0 && $orderAmount < $this->min_order) {
            return ['valid' => false, 'message' => trans('coupon.min_order', ['amount' => currency_format($this->min_order)])];
        }

        if ($this->customer_id && $this->customer_id !== $customerId) {
            return ['valid' => false, 'message' => trans('coupon.not_found')];
        }

        if ($customerId && $this->usage_limit_per_user > 0) {
            $usedByCustomer = $this->usages()->where('customer_id', $customerId)->count();
            if ($usedByCustomer >= $this->usage_limit_per_user) {
                return ['valid' => false, 'message' => trans('coupon.usage_limit_per_user_reached')];
            }
        }

        return ['valid' => true, 'message' => ''];
    }

    /**
     * 計算折扣金額
     */
    public function calcDiscount(float $subtotal): float
    {
        if ($this->type === 'percent') {
            $discount = $subtotal * $this->value / 100;
        } else {
            $discount = $this->value;
        }

        return min($discount, $subtotal);
    }
}
