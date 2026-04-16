<?php

namespace Plugin\EcpayInvoice\Controllers\Shop;

use Beike\Repositories\OrderRepo;
use Beike\Shop\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Plugin\EcpayInvoice\Models\LoveCode;
use Plugin\EcpayInvoice\Models\OrderInvoice;
use Plugin\EcpayInvoice\Services\EcpayInvoiceService;

class InvoiceController extends Controller
{
    /**
     * 把發票選擇暫存到 Session（送出訂單前呼叫）
     */
    public function saveSession(Request $request): JsonResponse
    {
        $request->validate([
            'carrier_type' => 'required|in:personal,mobile,love,company',
        ]);

        session(['ecpay_invoice' => [
            'carrier_type'   => $request->input('carrier_type'),
            'carrier_number' => $request->input('carrier_number'),
            'tax_id'         => $request->input('tax_id'),
            'company_title'  => $request->input('company_title'),
            'love_code'      => $request->input('love_code'),
        ]]);

        return json_success('OK');
    }

    /**
     * 結帳時儲存發票資料（訂單建立後由 JS 呼叫，作為備援）
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'order_number'   => 'required|string',
            'carrier_type'   => 'required|in:personal,mobile,love,company',
            'carrier_number' => 'required_if:carrier_type,mobile',
            'tax_id'         => 'required_if:carrier_type,company|nullable|digits:8',
            'company_title'  => 'nullable|string|max:100',
            'love_code'      => 'required_if:carrier_type,love|nullable|string',
        ]);

        $customer = current_customer();
        $order    = OrderRepo::getOrderByNumber($request->input('order_number'), $customer);

        OrderInvoice::updateOrCreate(
            ['order_id' => $order->id],
            [
                'carrier_type'   => $request->input('carrier_type'),
                'carrier_number' => $request->input('carrier_number'),
                'tax_id'         => $request->input('tax_id'),
                'company_title'  => $request->input('company_title'),
                'love_code'      => $request->input('love_code'),
                'status'         => 'pending',
            ]
        );

        return json_success('OK');
    }

    /**
     * 即時驗證手機條碼（AJAX）
     */
    public function verifyCarrier(Request $request): JsonResponse
    {
        $carrier = $request->input('carrier');

        if (! preg_match('/^\/[0-9A-Z+\-.]{7}$/', $carrier)) {
            return json_fail(__('EcpayInvoice::common.carrier_format_error'));
        }

        $valid = (new EcpayInvoiceService())->verifyMobileCarrier($carrier);

        if ($valid) {
            return json_success(__('EcpayInvoice::common.carrier_valid'));
        }

        return json_fail(__('EcpayInvoice::common.carrier_invalid'));
    }

    /**
     * 驗證捐贈碼是否存在
     */
    public function verifyLoveCode(Request $request): JsonResponse
    {
        $loveCode = trim($request->input('love_code', ''));

        if (empty($loveCode)) {
            return json_fail(__('EcpayInvoice::common.love_code_required'));
        }

        $exists = LoveCode::where('love_code', $loveCode)->exists();

        if ($exists) {
            return json_success(__('EcpayInvoice::common.love_code_valid'));
        }

        return json_fail(__('EcpayInvoice::common.love_code_invalid'));
    }

    /**
     * 搜尋捐贈碼（AJAX，對比本地資料表）
     */
    public function loveCodes(Request $request): JsonResponse
    {
        $keyword = trim($request->input('keyword', ''));

        $results = LoveCode::query()
            ->when($keyword, function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                  ->orWhere('short_name', 'like', "%{$keyword}%")
                  ->orWhere('love_code', 'like', "%{$keyword}%");
            })
            ->orderBy('love_code')
            ->limit(20)
            ->get(['id', 'name', 'short_name', 'love_code', 'city']);

        return json_success('OK', $results);
    }
}
