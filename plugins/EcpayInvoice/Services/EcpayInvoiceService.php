<?php

namespace Plugin\EcpayInvoice\Services;

use Beike\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Plugin\EcpayInvoice\Models\OrderInvoice;
use Plugin\EcpayInvoice\Models\OrderInvoiceAllowance;

class EcpayInvoiceService
{
    private string $merchantId;
    private string $hashKey;
    private string $hashIV;
    private bool   $sandbox;

    private const ISSUE_URL_PROD    = 'https://einvoice.ecpay.com.tw/B2CInvoice/Issue';
    private const ISSUE_URL_SANDBOX = 'https://einvoice-stage.ecpay.com.tw/B2CInvoice/Issue';
    private const VOID_URL_PROD     = 'https://einvoice.ecpay.com.tw/B2CInvoice/Invalid';
    private const VOID_URL_SANDBOX  = 'https://einvoice-stage.ecpay.com.tw/B2CInvoice/Invalid';
    private const ALLOWANCE_URL_PROD    = 'https://einvoice.ecpay.com.tw/B2CInvoice/Allowance';
    private const ALLOWANCE_URL_SANDBOX = 'https://einvoice-stage.ecpay.com.tw/B2CInvoice/Allowance';
    private const VERIFY_CARRIER_URL_PROD    = 'https://einvoice.ecpay.com.tw/B2CInvoice/CheckBarcode';
    private const VERIFY_CARRIER_URL_SANDBOX = 'https://einvoice-stage.ecpay.com.tw/B2CInvoice/CheckBarcode';

    public function __construct()
    {
        $this->merchantId = plugin_setting('ecpay_invoice.merchant_id', '');
        $this->hashKey    = plugin_setting('ecpay_invoice.hash_key', '');
        $this->hashIV     = plugin_setting('ecpay_invoice.hash_iv', '');
        $this->sandbox    = (bool) plugin_setting('ecpay_invoice.sandbox_mode', true);
    }

    /**
     * 開立發票
     */
    public function issue(Order $order, OrderInvoice $invoice): void
    {
        $buyerName  = trim($order->shipping_firstname . ' ' . $order->shipping_lastname) ?: 'Customer';
        $buyerEmail = $order->email ?? '';

        $items = [];
        $seq   = 1;
        foreach ($order->orderProducts as $product) {
            $items[] = [
                'ItemSeq'     => $seq++,
                'ItemName'    => mb_substr($product->name, 0, 100),
                'ItemCount'   => (int) $product->quantity,
                'ItemWord'    => '個',
                'ItemPrice'   => (int) round($product->price),
                'ItemAmount'  => (int) round($product->price * $product->quantity),
                'ItemTaxType' => '1',
            ];
        }

        // 加入運費、折扣等附加項目，確保 SalesAmount = sum(ItemAmount)
        foreach ($order->orderTotals as $total) {
            if (in_array($total->code, ['sub_total', 'total', 'order_total'])) {
                continue;
            }
            $amount = (int) round($total->value);
            if ($amount === 0) {
                continue;
            }
            $items[] = [
                'ItemSeq'     => $seq++,
                'ItemName'    => mb_substr($total->title ?: $total->code, 0, 100),
                'ItemCount'   => 1,
                'ItemWord'    => '式',
                'ItemPrice'   => $amount,
                'ItemAmount'  => $amount,
                'ItemTaxType' => '1',
            ];
        }

        $salesAmount = array_sum(array_column($items, 'ItemAmount'));

        $reissueCount = (int) ($invoice->reissue_count ?? 0);
        $relateNumber = $reissueCount > 0
            ? $order->number . '-R' . $reissueCount
            : $order->number;

        $params = [
            'MerchantID'         => $this->merchantId,
            'RelateNumber'       => $relateNumber,
            'CustomerID'         => (string) ($order->customer_id ?? ''),
            'CustomerIdentifier' => '',
            'CustomerName'       => $buyerName,
            'CustomerAddr'       => '',
            'CustomerPhone'      => '',
            'CustomerEmail'      => $buyerEmail,
            'ClearanceMark'      => '',
            'Print'              => '0',
            'Donation'           => '0',
            'LoveCode'           => '',
            'CarrierType'        => '',
            'CarrierNum'         => '',
            'TaxType'            => '1',
            'SalesAmount'        => $salesAmount,
            'InvoiceRemark'      => '',
            'Items'              => $items,
            'InvType'            => '07',
            'vat'                => '1',
        ];

        match ($invoice->carrier_type) {
            'mobile' => $params = array_merge($params, [
                'CarrierType' => '3',
                'CarrierNum'  => $invoice->carrier_number,
            ]),
            'love' => $params = array_merge($params, [
                'Donation' => '1',
                'LoveCode' => $invoice->love_code,
            ]),
            'company' => $params = array_merge($params, [
                'CustomerIdentifier' => $invoice->tax_id,
                'CustomerName'       => $invoice->company_title ?: $buyerName,
                'Print'              => '1',
            ]),
            default => $params = array_merge($params, [
                'Print' => '1',
            ]),
        };

        $params['TimeStamp']     = now()->timestamp;
        $params['CheckMacValue'] = $this->buildCheckMacValue($params);

        $isReissue = ! empty($invoice->voided_at) || ! empty($invoice->invoice_number);
        $invoice->update([
            'issue_log'      => $params,
            'invoice_number' => null,
            'random_number'  => null,
            'failed_reason'  => null,
            'issued_at'      => null,
            'voided_at'      => null,
            'status'         => 'pending',
            'reissue_count'  => $isReissue ? $reissueCount + 1 : $reissueCount,
        ]);

        $url = $this->sandbox ? self::ISSUE_URL_SANDBOX : self::ISSUE_URL_PROD;

        try {
            $result = $this->callApi($url, $params);

            Log::info('EcpayInvoice issue result: ' . json_encode($result));
            $invoice->update(['response_log' => $result]);

            if (($result['RtnCode'] ?? '') == '1') {
                $invoice->update([
                    'status'         => 'issued',
                    'invoice_number' => $result['InvoiceNo']    ?? null,
                    'random_number'  => $result['RandomNumber'] ?? null,
                    'issued_at'      => now(),
                    'failed_reason'  => null,
                ]);
            } else {
                $invoice->update([
                    'status'        => 'failed',
                    'failed_reason' => $result['RtnMsg'] ?? ($result['TransMsg'] ?? 'Unknown error'),
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('EcpayInvoice issue exception: ' . $e->getMessage());
            $invoice->update(['status' => 'pending', 'failed_reason' => $e->getMessage()]);
        }
    }

    /**
     * 作廢發票
     */
    public function void(OrderInvoice $invoice): array
    {
        $params = [
            'MerchantID'  => $this->merchantId,
            'InvoiceNo'   => $invoice->invoice_number,
            'InvoiceDate' => $invoice->issued_at->format('Y-m-d'),
            'Reason'      => '作廢',
            'TimeStamp'   => now()->timestamp,
        ];
        $params['CheckMacValue'] = $this->buildCheckMacValue($params);

        $url    = $this->sandbox ? self::VOID_URL_SANDBOX : self::VOID_URL_PROD;
        $result = $this->callApi($url, $params);

        if (($result['RtnCode'] ?? '') == '1') {
            $invoice->update(['status' => 'void', 'voided_at' => now()]);
        }

        return $result;
    }

    /**
     * 折讓
     */
    public function allowance(OrderInvoice $invoice, string $desc, int $amount): array
    {
        $params = [
            'MerchantID'      => $this->merchantId,
            'InvoiceNo'       => $invoice->invoice_number,
            'InvoiceDate'     => $invoice->issued_at->format('Y-m-d'),
            'AllowanceNotify' => 'E',
            'NotifyMail'      => $invoice->order->email ?? '',
            'AllowanceAmount' => $amount,
            'Items'           => [
                [
                    'ItemSeq'     => 1,
                    'ItemName'    => mb_substr($desc, 0, 100),
                    'ItemCount'   => 1,
                    'ItemWord'    => '式',
                    'ItemPrice'   => $amount,
                    'ItemTaxType' => '1',
                    'ItemAmount'  => $amount,
                ],
            ],
            'TimeStamp'       => now()->timestamp,
        ];
        $params['CheckMacValue'] = $this->buildCheckMacValue($params);

        $url    = $this->sandbox ? self::ALLOWANCE_URL_SANDBOX : self::ALLOWANCE_URL_PROD;
        $result = $this->callApi($url, $params);

        if (($result['RtnCode'] ?? '') == '1') {
            OrderInvoiceAllowance::create([
                'order_invoice_id' => $invoice->id,
                'allowance_number' => $result['IA_Allow_No'] ?? null,
                'desc'             => $desc,
                'amount'           => $amount,
                'status'           => 'issued',
                'response_log'     => $result,
            ]);
        }

        return $result;
    }

    /**
     * 折讓作廢
     */
    public function voidAllowance(OrderInvoiceAllowance $allowance): array
    {
        $params = [
            'MerchantID'     => $this->merchantId,
            'InvoiceNo'      => $allowance->invoice->invoice_number,
            'AllowanceNo'    => $allowance->allowance_number,
            'Reason'         => '折讓作廢',
            'TimeStamp'      => now()->timestamp,
        ];
        $params['CheckMacValue'] = $this->buildCheckMacValue($params);

        $url    = $this->sandbox
            ? 'https://einvoice-stage.ecpay.com.tw/B2CInvoice/AllowanceInvalid'
            : 'https://einvoice.ecpay.com.tw/B2CInvoice/AllowanceInvalid';
        $result = $this->callApi($url, $params);

        if (($result['RtnCode'] ?? '') == '1') {
            $allowance->update(['status' => 'void', 'voided_at' => now(), 'response_log' => $result]);
        }

        return $result;
    }

    /**
     * 驗證手機條碼
     */
    public function verifyMobileCarrier(string $carrier): bool
    {
        $params = [
            'MerchantID' => $this->merchantId,
            'BarCode'    => $carrier,
            'TimeStamp'  => now()->timestamp,
        ];
        $params['CheckMacValue'] = $this->buildCheckMacValue($params);

        $url    = $this->sandbox ? self::VERIFY_CARRIER_URL_SANDBOX : self::VERIFY_CARRIER_URL_PROD;
        $result = $this->callApi($url, $params);

        return ($result['RtnCode'] ?? '') == '1' && ($result['IsExist'] ?? '') === 'Y';
    }

    /**
     * 呼叫綠界發票 API（新版：JSON body + AES-128-CBC 加密 Data）
     */
    private function callApi(string $url, array $params): array
    {
        $merchantId = $params['MerchantID'] ?? $this->merchantId;
        $timestamp  = $params['TimeStamp'] ?? now()->timestamp;

        $encryptedData = $this->encrypt(json_encode($params, JSON_UNESCAPED_UNICODE));

        $payload = [
            'MerchantID' => $merchantId,
            'RqHeader'   => [
                'Timestamp' => $timestamp,
            ],
            'Data' => $encryptedData,
        ];

        Log::info('EcpayInvoice payload: ' . json_encode($payload));

        $response = Http::asJson()->post($url, $payload);
        $body     = json_decode($response->body(), true);

        Log::info('EcpayInvoice raw response: ' . $response->body());

        if (empty($body['Data']) || ($body['TransCode'] ?? 0) != 1) {
            return [
                'RtnCode'   => '0',
                'RtnMsg'    => $body['TransMsg'] ?? 'API error',
                'TransCode' => $body['TransCode'] ?? null,
            ];
        }

        $decrypted = $this->decrypt($body['Data']);
        Log::info('EcpayInvoice decrypted: ' . $decrypted);

        return json_decode($decrypted, true) ?? [];
    }

    /**
     * AES-128-CBC 加密（先 urlencode 再加密，符合綠界規格）
     */
    public function encrypt(string $data): string
    {
        $encoded = urlencode($data);
        return openssl_encrypt($encoded, 'AES-128-CBC', $this->hashKey, 0, $this->hashIV);
    }

    /**
     * AES-128-CBC 解密（解密後再 urldecode，符合綠界規格）
     */
    public function decrypt(string $data): string
    {
        $decrypted = openssl_decrypt($data, 'AES-128-CBC', $this->hashKey, 0, $this->hashIV);
        return urldecode($decrypted);
    }

    /**
     * SHA256 CheckMacValue 簽章
     */
    public function buildCheckMacValue(array $params): string
    {
        unset($params['CheckMacValue'], $params['Items']);

        ksort($params);

        $str = 'HashKey=' . $this->hashKey . '&';
        foreach ($params as $key => $value) {
            if (is_array($value)) {
                continue;
            }
            $str .= $key . '=' . $value . '&';
        }
        $str .= 'HashIV=' . $this->hashIV;

        $str = urlencode($str);
        $str = strtolower($str);
        $str = str_replace(['%2d', '%5f', '%2e', '%21', '%2a', '%28', '%29'], ['-', '_', '.', '!', '*', '(', ')'], $str);

        return strtoupper(hash('sha256', $str));
    }
}
