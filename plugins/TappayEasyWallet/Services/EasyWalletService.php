<?php

namespace Plugin\TappayEasyWallet\Services;

use Beike\Shop\Services\PaymentService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EasyWalletService extends PaymentService
{
    private string $partnerKey;
    private string $merchantId;
    private bool   $sandboxMode;
    private string $apiEndpoint;

    public function __construct($order)
    {
        parent::__construct($order);
        $setting           = plugin_setting('tappay_easy_wallet');
        $this->partnerKey  = $setting['partner_key'] ?? '';
        $this->merchantId  = $setting['merchant_id'] ?? '';
        $this->sandboxMode = (bool) ($setting['sandbox_mode'] ?? true);
        $this->apiEndpoint = $this->sandboxMode
            ? 'https://sandbox.tappaysdk.com/tpc/payment/pay-by-prime'
            : 'https://prod.tappaysdk.com/tpc/payment/pay-by-prime';
    }

    /**
     * 建立悠遊付付款，回傳 TapPay 的 payment_url
     */
    public function createPayment(string $prime): array
    {
        $order  = $this->order;
        $amount = (int) round($order->total);

        $payload = [
            'prime'       => $prime,
            'partner_key' => $this->partnerKey,
            'merchant_id' => $this->merchantId,
            'details'     => 'Order ' . $order->number,
            'amount'      => $amount,
            'currency'    => 'TWD',
            'cardholder'  => [
                'phone_number' => $order->shipping_telephone ?? '',
                'name'         => trim(($order->shipping_firstname ?? '') . ' ' . ($order->shipping_lastname ?? '')),
                'email'        => $order->customer->email ?? '',
            ],
            'result_url'  => [
                'frontend_redirect_url' => route('shop.tappay_easy_wallet.return', ['order_number' => $order->number]),
                'backend_notify_url'    => route('shop.tappay_easy_wallet.notify'),
                'go_back_url'           => url('/'),
            ],
            'order_number' => $order->number,
        ];

        Log::info('TapPay Easy Wallet request: ' . json_encode($payload));

        $response = Http::withHeaders([
            'x-api-key'    => $this->partnerKey,
            'Content-Type' => 'application/json',
        ])->post($this->apiEndpoint, $payload);

        $result = $response->json();
        Log::info('TapPay Easy Wallet response: ' . json_encode($result));

        return $result;
    }
}
