<div class="card mb-4">
    <div class="card-header">
        <h6 class="mb-0">{{ __('EcpayInvoice::common.invoice_title') }}</h6>
    </div>
    <div class="card-body">
        <table class="table table-sm table-borderless mb-0">
            <tr>
                <td class="text-muted" style="width:140px">{{ __('EcpayInvoice::common.carrier_type') }}</td>
                <td>{{ $invoice->carrier_type_label }}</td>
            </tr>
            {{-- 載具號碼 --}}
            @if ($invoice->carrier_type === 'mobile' && $invoice->carrier_number)
            <tr>
                <td class="text-muted">{{ __('EcpayInvoice::common.carrier_number') }}</td>
                <td>{{ $invoice->carrier_number }}</td>
            </tr>
            @endif
            {{-- 捐贈愛心碼 --}}
            @if ($invoice->carrier_type === 'love' && $invoice->love_code)
            <tr>
                <td class="text-muted">{{ __('EcpayInvoice::common.love_code') }}</td>
                <td>{{ $invoice->love_code }}</td>
            </tr>
            @endif
            {{-- 公司統編與抬頭 --}}
            @if ($invoice->carrier_type === 'company')
            @if ($invoice->tax_id)
            <tr>
                <td class="text-muted">{{ __('EcpayInvoice::common.tax_id') }}</td>
                <td>{{ $invoice->tax_id }}</td>
            </tr>
            @endif
            @if ($invoice->company_title)
            <tr>
                <td class="text-muted">{{ __('EcpayInvoice::common.company_title') }}</td>
                <td>{{ $invoice->company_title }}</td>
            </tr>
            @endif
            @endif
            {{-- 已開立才顯示發票號碼 --}}
            @if ($invoice->status === 'issued')
            <tr>
                <td class="text-muted">{{ __('EcpayInvoice::common.invoice_number') }}</td>
                <td>{{ $invoice->invoice_number }}</td>
            </tr>
            <tr>
                <td class="text-muted">{{ __('EcpayInvoice::common.random_number') }}</td>
                <td>{{ $invoice->random_number }}</td>
            </tr>
            <tr>
                <td class="text-muted">{{ __('EcpayInvoice::common.issued_at') }}</td>
                <td>{{ $invoice->issued_at?->format('Y-m-d H:i') }}</td>
            </tr>
            @endif
        </table>
    </div>
</div>
