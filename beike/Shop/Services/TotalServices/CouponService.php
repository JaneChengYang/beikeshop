<?php

namespace Beike\Shop\Services\TotalServices;

use Beike\Models\Coupon;
use Beike\Shop\Services\CheckoutService;

class CouponService
{
    public static function getTotal(CheckoutService $checkout): ?array
    {
        $totalService = $checkout->totalService;
        $cart         = $totalService->getCurrentCart();
        $couponCode   = $cart->coupon_code ?? null;

        if (! $couponCode) {
            return null;
        }

        $coupon = Coupon::where('code', $couponCode)->first();
        if (! $coupon) {
            return null;
        }

        $customer   = current_customer();
        $customerId = $customer?->id;
        $subtotal   = $totalService->getSubTotal();

        $validation = $coupon->validate($customerId, $subtotal);
        if (! $validation['valid']) {
            return null;
        }

        $discount  = $coupon->calcDiscount($subtotal);
        $totalData = [
            'code'          => 'coupon',
            'title'         => trans('coupon.discount') . '（' . $couponCode . '）',
            'amount'        => -$discount,
            'amount_format' => currency_format(-$discount),
        ];

        $totalService->amount  += $totalData['amount'];
        $totalService->totals[] = $totalData;

        return $totalData;
    }
}
