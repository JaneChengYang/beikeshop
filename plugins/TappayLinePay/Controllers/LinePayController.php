<?php

namespace Plugin\TappayLinePay\Controllers;

use Beike\Repositories\OrderPaymentRepo;
use Beike\Repositories\OrderRepo;
use Beike\Services\StateMachineService;
use Beike\Shop\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Plugin\TappayLinePay\Services\LinePayService;

class LinePayController extends Controller
{
    /**
     * 前端送出訂單後，建立 LINE Pay 付款，回傳 payment_url
     */
    public function pay(Request $request): JsonResponse
    {
        try {
            $number   = $request->input('order_number');
            $prime    = $request->input('prime');
            $customer = current_customer();
            $order    = OrderRepo::getOrderByNumber($number, $customer);

            OrderPaymentRepo::createOrUpdatePayment($order->id, ['request' => $request->all()]);

            $result = (new LinePayService($order))->createPayment($prime);

            OrderPaymentRepo::createOrUpdatePayment($order->id, ['response' => $result]);

            if (isset($result['status']) && $result['status'] === 0 && isset($result['payment_url'])) {
                return json_success('OK', ['payment_url' => $result['payment_url']]);
            }

            return json_fail($result['msg'] ?? __('TappayLinePay::common.pay_fail'));

        } catch (\Exception $e) {
            Log::error('TapPay LINE Pay error: ' . $e->getMessage());
            return json_fail($e->getMessage());
        }
    }

    /**
     * 用戶付款後 TapPay 導回的前端頁面
     */
    public function return(Request $request): RedirectResponse
    {
        $number     = $request->input('order_number');
        $recTradeId = $request->input('rec_trade_id');
        $status     = $request->input('status');

        if ($number && $recTradeId && $status === '0') {
            try {
                $order = OrderRepo::getOrderByNumber($number);
                StateMachineService::getInstance($order)->setShipment()->changeStatus(StateMachineService::PAID);
            } catch (\Exception $e) {
                Log::error('TapPay LINE Pay return error: ' . $e->getMessage());
            }
        }

        return redirect(shop_route('checkout.success', ['order_number' => $number]));
    }

    /**
     * TapPay 伺服器端 Webhook 通知
     */
    public function notify(Request $request): JsonResponse
    {
        try {
            $data   = $request->all();
            $number = $data['order_number'] ?? null;
            $status = $data['status'] ?? null;

            Log::info('TapPay LINE Pay notify: ' . json_encode($data));

            if ($number && (int) $status === 0) {
                $order = OrderRepo::getOrderByNumber($number);
                StateMachineService::getInstance($order)->setShipment()->changeStatus(StateMachineService::PAID);
            }
        } catch (\Exception $e) {
            Log::error('TapPay LINE Pay notify error: ' . $e->getMessage());
        }

        return response()->json(['status' => 'ok']);
    }
}
