<?php

namespace Plugin\EcpayInvoice;

use Beike\Models\Order;
use Illuminate\Support\Facades\Log;
use Plugin\EcpayInvoice\Models\OrderInvoice;
use Plugin\EcpayInvoice\Services\EcpayInvoiceService;

class Bootstrap
{
    public function boot(): void
    {
        $this->listenPaymentSuccess();
        $this->createInvoiceOnConfirm();
        $this->injectCheckoutForm();
        $this->injectAdminOrderDetail();
        $this->injectAdminMenu();
        $this->injectShopOrderInfo();
    }

    /**
     * 訂單 confirm 時從 session 建立 order_invoices 記錄
     */
    private function createInvoiceOnConfirm(): void
    {
        add_hook_action('service.checkout.confirm.after', function ($data) {
            try {
                $invoiceData = session('ecpay_invoice');
                if (empty($invoiceData)) {
                    return;
                }

                $order = $data['order'] ?? null;
                if (! $order) {
                    return;
                }

                OrderInvoice::updateOrCreate(
                    ['order_id' => $order->id],
                    [
                        'carrier_type'   => $invoiceData['carrier_type']   ?? 'personal',
                        'carrier_number' => $invoiceData['carrier_number'] ?? null,
                        'tax_id'         => $invoiceData['tax_id']         ?? null,
                        'company_title'  => $invoiceData['company_title']  ?? null,
                        'love_code'      => $invoiceData['love_code']      ?? null,
                        'status'         => 'pending',
                    ]
                );

                session()->forget('ecpay_invoice');
            } catch (\Throwable $e) {
                Log::error('EcpayInvoice createInvoiceOnConfirm: ' . $e->getMessage());
            }
        });
    }

    /**
     * 付款成功後自動開立發票
     */
    private function listenPaymentSuccess(): void
    {
        add_hook_filter('service.state_machine.change_status.after', function ($data) {
            try {
                $status = $data['status'] ?? null;
                $order  = $data['order']  ?? null;

                if ($status !== 'paid' || ! $order instanceof Order) {
                    return $data;
                }

                if (! plugin_setting('ecpay_invoice.enabled')) {
                    return $data;
                }

                $invoice = OrderInvoice::where('order_id', $order->id)->first();
                if (! $invoice) {
                    return $data;
                }

                if ($invoice->status !== 'pending') {
                    return $data;
                }

                (new EcpayInvoiceService())->issue($order, $invoice);

            } catch (\Throwable $e) {
                Log::error('EcpayInvoice boot error: ' . $e->getMessage());
            }

            return $data;
        });
    }

    /**
     * 在結帳頁注入發票表單
     */
    private function injectCheckoutForm(): void
    {
        add_hook_blade('checkout.bottom', function ($callback, $output, $data) {
            return $output . view('EcpayInvoice::checkout.invoice-form')->render();
        });
    }

    /**
     * 在後台訂單詳情頁注入發票區塊
     */
    private function injectAdminOrderDetail(): void
    {
        add_hook_blade('admin.order.form.base.updated_at.after', function ($callback, $output, $data) {
            $order   = $data['order'] ?? null;
            $invoice = $order ? OrderInvoice::where('order_id', $order->id)->first() : null;
            return $output . view('EcpayInvoice::admin.order-invoice', ['order' => $order, 'invoice' => $invoice])->render();
        }, 10);
    }

    /**
     * 在前台訂單詳情頁注入發票資訊
     */
    private function injectShopOrderInfo(): void
    {
        add_hook_blade('account.order_info.after', function ($callback, $output, $data) {
            $order   = $data['order'] ?? null;
            if (! $order) return $output;
            $invoice = OrderInvoice::where('order_id', $order->id)->first();
            if ($invoice) {
                return $output . view('EcpayInvoice::shop.order-invoice', ['invoice' => $invoice])->render();
            }
            return $output;
        });
    }

    /**
     * 在後台側邊欄注入「發票管理」選單
     */
    private function injectAdminMenu(): void
    {
        add_hook_filter('admin.components.sidebar.menus', function ($menus) {
            $menus[] = [
                'route'    => 'ecpay_invoice.index',
                'title'    => __('EcpayInvoice::common.menu_title'),
                'icon'     => 'bi bi-receipt',
                'prefixes' => ['ecpay-invoice'],
                'children' => [],
            ];
            return $menus;
        });
    }
}
