<?php

namespace Plugin\Tappay\Controllers;

use Beike\Repositories\OrderPaymentRepo;
use Beike\Repositories\OrderRepo;
use Beike\Services\StateMachineService;
use Beike\Shop\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Plugin\Tappay\Services\TapPayService;

class TapPayController extends Controller
{
    /**
     * 接收前端 prime，向 TapPay 發起扣款
     */
    public function capture(Request $request): JsonResponse
    {
        try {
            $number    = $request->input('order_number');
            $prime     = $request->input('prime');
            $customer  = current_customer();
            $order     = OrderRepo::getOrderByNumber($number, $customer);

            $cardHolder = [
                'name'  => $request->input('cardholder_name', ''),
                'email' => $request->input('cardholder_email', $customer->email ?? ''),
                'phone' => $request->input('cardholder_phone', ''),
            ];

            OrderPaymentRepo::createOrUpdatePayment($order->id, ['request' => $request->all()]);

            $result = (new TapPayService($order))->capture($prime, $cardHolder);

            OrderPaymentRepo::createOrUpdatePayment($order->id, ['response' => $result]);

            if (isset($result['status']) && $result['status'] === 0) {
                // 需要 3D 驗證：銀行要求導向驗證頁
                if (!empty($result['payment_url'])) {
                    session(['tappay_3ds_order_number' => $number]);
                    return json_success('3DS required', ['payment_url' => $result['payment_url']]);
                }

                // 無需 3D 驗證：直接標記付款成功
                StateMachineService::getInstance($order)->setShipment()->changeStatus(StateMachineService::PAID);

                return json_success(__('TapPay::common.capture_success'));
            }

            $errorMsg = $result['msg'] ?? __('TapPay::common.capture_fail');

            return json_fail($errorMsg);

        } catch (\Exception $e) {
            Log::error('TapPay error: ' . $e->getMessage());

            return json_fail($e->getMessage());
        }
    }

    /**
     * 3D 驗證完成後 TapPay 導回的前端頁面（用戶瀏覽器）
     * 僅做頁面跳轉，實際付款狀態由 notify() 處理
     */
    public function return3ds(Request $request): RedirectResponse
    {
        // TapPay 導回時可能覆蓋 query string，從 session 取 order_number
        $number     = $request->input('order_number') ?: session()->pull('tappay_3ds_order_number');
        $recTradeId = $request->input('rec_trade_id');
        $status     = $request->input('status');

        // 有 rec_trade_id 且 status=0 代表付款成功
        if ($number && $recTradeId && $status === '0') {
            try {
                $order = OrderRepo::getOrderByNumber($number);
                StateMachineService::getInstance($order)->setShipment()->changeStatus(StateMachineService::PAID);
            } catch (\Exception $e) {
                Log::error('TapPay 3DS return mark paid error: ' . $e->getMessage());
            }
        }

        return redirect(shop_route('checkout.success', ['order_number' => $number]));
    }

    /**
     * TapPay 伺服器端 Webhook 通知（backend_notify_url）
     * 這是付款結果的可靠來源，不依賴用戶瀏覽器導回
     */
    public function notify(Request $request): JsonResponse
    {
        try {
            $data   = $request->all();
            $number = $data['order_number'] ?? null;
            $status = $data['status'] ?? null;

            Log::info('TapPay notify: ' . json_encode($data));

            if ($number && $status === 0) {
                $order = OrderRepo::getOrderByNumber($number);
                StateMachineService::getInstance($order)->setShipment()->changeStatus(StateMachineService::PAID);
            }
        } catch (\Exception $e) {
            Log::error('TapPay notify error: ' . $e->getMessage());
        }

        return response()->json(['status' => 'ok']);
    }
}
