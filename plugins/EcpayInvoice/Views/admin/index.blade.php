@extends('admin::layouts.master')

@section('title', __('EcpayInvoice::common.menu_title'))

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">{{ __('EcpayInvoice::common.menu_title') }}</h5>
    </div>
    <div class="card-body">

        {{-- 搜尋列 --}}
        <form method="GET" class="row g-2 mb-3">
            <div class="col-auto">
                <input type="text" name="keyword" class="form-control form-control-sm"
                       placeholder="{{ __('EcpayInvoice::common.search_placeholder') }}"
                       value="{{ request('keyword') }}">
            </div>
            <div class="col-auto">
                <select name="status" class="form-select form-select-sm">
                    <option value="">{{ __('common.all') }}</option>
                    @foreach (['pending', 'issued', 'failed', 'void'] as $s)
                        <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>
                            {{ __('EcpayInvoice::common.status_' . $s) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-primary">{{ __('common.search') }}</button>
            </div>
        </form>

        {{-- 列表 --}}
        <div class="table-responsive">
            <table class="table table-hover table-sm align-middle">
                <thead>
                    <tr>
                        <th>{{ __('order.number') }}</th>
                        <th>{{ __('EcpayInvoice::common.invoice_number') }}</th>
                        <th>{{ __('EcpayInvoice::common.carrier_type') }}</th>
                        <th>{{ __('common.status') }}</th>
                        <th>{{ __('EcpayInvoice::common.issued_at') }}</th>
                        <th>{{ __('EcpayInvoice::common.failed_reason') }}</th>
                        <th>{{ __('common.action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($invoices as $invoice)
                        <tr>
                            <td>
                                @if ($invoice->order)
                                    <a href="{{ admin_route('orders.show', $invoice->order_id) }}" target="_blank">
                                        {{ $invoice->order->number }}
                                    </a>
                                @else
                                    #{{ $invoice->order_id }}
                                @endif
                            </td>
                            <td>{{ $invoice->invoice_number ?: '—' }}</td>
                            <td>{{ $invoice->carrier_type_label }}</td>
                            <td>
                                <span class="badge bg-{{ match($invoice->status) {
                                    'issued'  => 'success',
                                    'pending' => 'warning',
                                    'failed'  => 'danger',
                                    'void'    => 'secondary',
                                    default   => 'light',
                                } }}">
                                    {{ $invoice->status_label }}
                                </span>
                            </td>
                            <td>{{ $invoice->issued_at?->format('Y-m-d H:i') ?? '—' }}</td>
                            <td class="text-danger small" style="max-width:200px">
                                <span title="{{ $invoice->failed_reason }}"
                                      style="display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                                    {{ $invoice->failed_reason }}
                                </span>
                            </td>
                            <td class="text-nowrap">
                                @if (in_array($invoice->status, ['pending', 'failed', 'void']))
                                    <button class="btn btn-xs btn-outline-primary btn-invoice-action"
                                            data-url="{{ admin_route('ecpay_invoice.issue', $invoice) }}"
                                            data-confirm="{{ __('EcpayInvoice::common.confirm_issue') }}">
                                        {{ __('EcpayInvoice::common.btn_issue') }}
                                    </button>
                                @endif
                                @if ($invoice->status === 'issued')
                                    <button class="btn btn-xs btn-outline-danger btn-invoice-action"
                                            data-url="{{ admin_route('ecpay_invoice.void', $invoice) }}"
                                            data-confirm="{{ __('EcpayInvoice::common.confirm_void') }}">
                                        {{ __('EcpayInvoice::common.btn_void') }}
                                    </button>
                                    <button class="btn btn-xs btn-outline-warning btn-allowance"
                                            data-url="{{ admin_route('ecpay_invoice.allowance', $invoice) }}">
                                        {{ __('EcpayInvoice::common.btn_allowance') }}
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">{{ __('common.no_data') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $invoices->withQueryString()->links('admin::vendor/pagination/bootstrap-4') }}
    </div>
</div>

{{-- 折讓 Modal --}}
<div class="modal fade" id="allowance-modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('EcpayInvoice::common.btn_allowance') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">{{ __('EcpayInvoice::common.allowance_desc') }}</label>
                    <input type="text" id="allowance-desc" class="form-control" maxlength="100">
                </div>
                <div class="mb-3">
                    <label class="form-label">{{ __('EcpayInvoice::common.allowance_amount') }}</label>
                    <input type="number" id="allowance-amount" class="form-control" min="1">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                <button type="button" class="btn btn-primary" id="btn-allowance-confirm">{{ __('common.confirm') }}</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('add-scripts')
<script>
$(function () {
    // 補開 / 作廢
    $(document).on('click', '.btn-invoice-action', function () {
        const url     = $(this).data('url');
        const confirm = $(this).data('confirm');
        if (!window.confirm(confirm)) return;

        $http.post(url).then((res) => {
            layer.msg(res.message || '{{ __('common.success') }}');
            setTimeout(() => location.reload(), 1000);
        }).catch((err) => {
            layer.msg(err.message || '{{ __('common.error') }}');
        });
    });

    // 折讓
    let allowanceUrl = '';
    $(document).on('click', '.btn-allowance', function () {
        allowanceUrl = $(this).data('url');
        $('#allowance-desc').val('');
        $('#allowance-amount').val('');
        $('#allowance-modal').modal('show');
    });

    $('#btn-allowance-confirm').click(function () {
        const desc   = $('#allowance-desc').val().trim();
        const amount = parseInt($('#allowance-amount').val());
        if (!desc || !amount || amount < 1) {
            layer.msg('{{ __('EcpayInvoice::common.allowance_input_error') }}');
            return;
        }
        $http.post(allowanceUrl, { desc, amount }).then((res) => {
            $('#allowance-modal').modal('hide');
            layer.msg(res.message || '{{ __('common.success') }}');
        }).catch((err) => {
            layer.msg(err.message || '{{ __('common.error') }}');
        });
    });
});
</script>
@endpush
