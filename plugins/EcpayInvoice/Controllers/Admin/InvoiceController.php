<?php

namespace Plugin\EcpayInvoice\Controllers\Admin;

use Beike\Admin\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Plugin\EcpayInvoice\Models\OrderInvoice;
use Plugin\EcpayInvoice\Models\OrderInvoiceAllowance;
use Plugin\EcpayInvoice\Services\EcpayInvoiceService;

class InvoiceController extends Controller
{
    /**
     * 發票管理列表
     */
    public function index(Request $request)
    {
        $query = OrderInvoice::with('order')
            ->orderByDesc('id');

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }
        if ($keyword = $request->input('keyword')) {
            $query->where(function ($q) use ($keyword) {
                $q->where('invoice_number', 'like', "%{$keyword}%")
                  ->orWhereHas('order', fn ($q2) => $q2->where('number', 'like', "%{$keyword}%"));
            });
        }

        $invoices = $query->paginate(20)->withQueryString();

        return view('EcpayInvoice::admin.index', compact('invoices'));
    }

    /**
     * 手動補開發票（status = pending / failed）
     */
    public function issue(OrderInvoice $invoice, Request $request): JsonResponse
    {
        if (! in_array($invoice->status, ['pending', 'failed', 'void'])) {
            return json_fail(__('EcpayInvoice::common.cannot_issue'));
        }

        // 若 Modal 有帶新的載具資料，先更新
        if ($request->has('carrier_type')) {
            $invoice->update([
                'carrier_type'   => $request->input('carrier_type'),
                'carrier_number' => $request->input('carrier_number'),
                'tax_id'         => $request->input('tax_id'),
                'company_title'  => $request->input('company_title'),
                'love_code'      => $request->input('love_code'),
            ]);
            $invoice->refresh();
        }

        (new EcpayInvoiceService())->issue($invoice->order, $invoice);
        $invoice->refresh();

        if ($invoice->status === 'issued') {
            return json_success(__('EcpayInvoice::common.issue_success'));
        }

        return json_fail($invoice->failed_reason ?: __('EcpayInvoice::common.issue_fail'));
    }

    /**
     * 作廢發票
     */
    public function void(OrderInvoice $invoice): JsonResponse
    {
        if ($invoice->status !== 'issued') {
            return json_fail(__('EcpayInvoice::common.cannot_void'));
        }

        $result = (new EcpayInvoiceService())->void($invoice);

        if (($result['RtnCode'] ?? '') == 1) {
            return json_success(__('EcpayInvoice::common.void_success'));
        }

        return json_fail($result['RtnMsg'] ?? __('EcpayInvoice::common.void_fail'));
    }

    /**
     * 折讓
     */
    public function allowance(OrderInvoice $invoice, Request $request): JsonResponse
    {
        if ($invoice->status !== 'issued') {
            return json_fail(__('EcpayInvoice::common.cannot_allowance'));
        }

        $request->validate([
            'desc'   => 'required|string|max:100',
            'amount' => 'required|integer|min:1',
        ]);

        $result = (new EcpayInvoiceService())->allowance(
            $invoice,
            $request->input('desc'),
            (int) $request->input('amount')
        );

        if (($result['RtnCode'] ?? '') == 1) {
            return json_success(__('EcpayInvoice::common.allowance_success'));
        }

        return json_fail($result['RtnMsg'] ?? __('EcpayInvoice::common.allowance_fail'));
    }

    /**
     * 折讓作廢
     */
    public function voidAllowance(OrderInvoiceAllowance $allowance): JsonResponse
    {
        if ($allowance->status !== 'issued') {
            return json_fail(__('EcpayInvoice::common.cannot_void'));
        }

        $result = (new EcpayInvoiceService())->voidAllowance($allowance);

        if (($result['RtnCode'] ?? '') == '1') {
            return json_success(__('EcpayInvoice::common.void_success'));
        }

        return json_fail($result['RtnMsg'] ?? __('EcpayInvoice::common.void_fail'));
    }
}
