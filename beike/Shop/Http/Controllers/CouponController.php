<?php

namespace Beike\Shop\Http\Controllers;

use Beike\Models\Coupon;
use Beike\Repositories\CartRepo;
use Beike\Shop\Services\CheckoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CouponController
{
    public function apply(Request $request): JsonResponse
    {
        $code = strtoupper(trim($request->input('code', '')));

        if (! $code) {
            return json_fail(trans('coupon.not_found'));
        }

        $coupon = Coupon::where('code', $code)->first();
        if (! $coupon) {
            return json_fail(trans('coupon.not_found'));
        }

        $customer     = current_customer();
        $cart         = CartRepo::createCart($customer);
        $checkoutData = (new CheckoutService)->checkoutData();
        $subtotal     = $checkoutData['carts']['amount'] ?? 0;
        $validation = $coupon->validate($customer?->id, $subtotal);

        if (! $validation['valid']) {
            return json_fail($validation['message']);
        }

        $cart->update(['coupon_code' => $code]);

        $discount = $coupon->calcDiscount($subtotal);

        return json_success(trans('coupon.applied'), [
            'code'            => $code,
            'discount'        => $discount,
            'discount_format' => currency_format(-$discount),
        ]);
    }

    public function remove(Request $request): JsonResponse
    {
        $customer = current_customer();
        $cart     = CartRepo::createCart($customer);
        $cart->update(['coupon_code' => null]);

        return json_success(trans('common.success'));
    }
}
