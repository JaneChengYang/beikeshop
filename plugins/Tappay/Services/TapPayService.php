<?php

namespace Plugin\Tappay\Services;

use Beike\Shop\Services\PaymentService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TapPayService extends PaymentService
{
    private string $partnerKey;
    private string $merchantId;
    private bool   $sandboxMode;
    private string $apiEndpoint;

    public function __construct($order)
    {
        parent::__construct($order);
        $setting           = plugin_setting('tappay');
        $this->partnerKey  = $setting['partner_key'] ?? '';
        $this->merchantId  = $setting['merchant_id'] ?? '';
        $this->sandboxMode = (bool) ($setting['sandbox_mode'] ?? true);
        $this->apiEndpoint = $this->sandboxMode
            ? 'https://sandbox.tappaysdk.com/tpc/payment/pay-by-prime'
            : 'https://prod.tappaysdk.com/tpc/payment/pay-by-prime';
    }

    /**
     * 使用 prime 向 TapPay 發起扣款
     */
    public function capture(string $prime, array $cardHolder): array
    {
        $order  = $this->order;
        $amount = (int) round($order->total);

        $payload = [
            'prime'               => $prime,
            'partner_key'         => $this->partnerKey,
            'merchant_id'         => $this->merchantId,
            'details'             => 'Order ' . $order->number,
            'amount'              => $amount,
            'currency'            => 'TWD',
            'cardholder'          => [
                'phone_number' => $cardHolder['phone'] ?? '',
                'name'         => $cardHolder['name'] ?? '',
                'email'        => $cardHolder['email'] ?? ($order->customer->email ?? ''),
            ],
            'three_domain_secure' => true,
            'result_url'          => [
                'frontend_redirect_url' => route('shop.tappay_3ds_return', ['order_number' => $order->number]),
                'backend_notify_url'    => route('shop.tappay_notify'),
            ],
            'remember'            => false,
        ];

        Log::info('TapPay capture request: ' . json_encode($payload));

        $response = Http::withHeaders([
            'x-api-key'    => $this->partnerKey,
            'Content-Type' => 'application/json',
        ])->post($this->apiEndpoint, $payload);

        $result = $response->json();
        Log::info('TapPay capture response: ' . json_encode($result));

        return $result;
    }
}
