<?php

namespace Beike\Admin\Http\Controllers;

use Beike\Models\Coupon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    public function index(Request $request)
    {
        $query = Coupon::query()->orderByDesc('id');

        if ($request->filled('code')) {
            $query->where('code', 'like', '%' . $request->code . '%');
        }

        $coupons = $query->paginate(20);

        if ($request->expectsJson()) {
            return json_success(trans('common.success'), ['coupons' => $coupons]);
        }

        return view('admin::pages.coupons.index', ['coupons' => $coupons]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'code'  => 'required|unique:coupons,code',
            'type'  => 'required|in:fixed,percent',
            'value' => 'required|numeric|min:0',
        ]);

        $data           = $request->only(['code', 'type', 'value', 'min_order', 'usage_limit', 'usage_limit_per_user', 'customer_id', 'starts_at', 'expires_at', 'active']);
        $data['active'] = $request->boolean('active', true);
        $data['code']   = strtoupper(trim($data['code']));

        $coupon = Coupon::create($data);

        return json_success(trans('common.created_success'), $coupon);
    }

    public function update(Request $request, Coupon $coupon): JsonResponse
    {
        $request->validate([
            'code'  => 'required|unique:coupons,code,' . $coupon->id,
            'type'  => 'required|in:fixed,percent',
            'value' => 'required|numeric|min:0',
        ]);

        $data           = $request->only(['code', 'type', 'value', 'min_order', 'usage_limit', 'usage_limit_per_user', 'customer_id', 'starts_at', 'expires_at', 'active']);
        $data['active'] = $request->boolean('active', true);
        $data['code']   = strtoupper(trim($data['code']));

        $coupon->update($data);

        return json_success(trans('common.updated_success'), $coupon->fresh());
    }

    public function destroy(Coupon $coupon): JsonResponse
    {
        if ($coupon->usages()->exists()) {
            return json_fail(trans('coupon.delete_has_usages'));
        }

        $coupon->delete();

        return json_success(trans('common.deleted_success'));
    }
}
