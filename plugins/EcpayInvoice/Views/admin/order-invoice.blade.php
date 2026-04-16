@if ($invoice)
<tr>
    <td colspan="2">
        <div class="border rounded p-3 mt-2">
            <div class="fw-bold mb-2">{{ __('EcpayInvoice::common.invoice_title') }}</div>
            <table class="table table-sm table-borderless mb-0">
                <tr>
                    <td class="text-muted" style="width:120px">{{ __('common.status') }}</td>
                    <td>
                        <span class="badge bg-{{ match($invoice->status) {
                            'issued'  => 'success',
                            'pending' => 'warning',
                            'failed'  => 'danger',
                            'void'    => 'secondary',
                            default   => 'light',
                        } }}">{{ $invoice->status_label }}</span>
                    </td>
                </tr>
                <tr>
                    <td class="text-muted">{{ __('EcpayInvoice::common.carrier_type') }}</td>
                    <td>{{ $invoice->carrier_type_label }}</td>
                </tr>
                @if ($invoice->carrier_type === 'mobile' && $invoice->carrier_number)
                <tr>
                    <td class="text-muted">{{ __('EcpayInvoice::common.carrier_number') }}</td>
                    <td>{{ $invoice->carrier_number }}</td>
                </tr>
                @endif
                @if ($invoice->carrier_type === 'love' && $invoice->love_code)
                <tr>
                    <td class="text-muted">{{ __('EcpayInvoice::common.love_code') }}</td>
                    <td>{{ $invoice->love_code }}</td>
                </tr>
                @endif
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
                @if ($invoice->invoice_number)
                <tr>
                    <td class="text-muted">{{ __('EcpayInvoice::common.invoice_number') }}</td>
                    <td>{{ $invoice->invoice_number }}</td>
                </tr>
                @endif
                @if ($invoice->random_number)
                <tr>
                    <td class="text-muted">{{ __('EcpayInvoice::common.random_number') }}</td>
                    <td>{{ $invoice->random_number }}</td>
                </tr>
                @endif
                @if ($invoice->issued_at)
                <tr>
                    <td class="text-muted">{{ __('EcpayInvoice::common.issued_at') }}</td>
                    <td>{{ $invoice->issued_at->format('Y-m-d H:i') }}</td>
                </tr>
                @endif
                @if ($invoice->failed_reason)
                <tr>
                    <td class="text-muted">{{ __('EcpayInvoice::common.failed_reason') }}</td>
                    <td class="text-danger">{{ $invoice->failed_reason }}</td>
                </tr>
                @endif
            </table>

            {{-- 折讓歷史 --}}
            @if ($invoice->allowances && $invoice->allowances->count())
            <div class="mt-3">
                <div class="fw-bold mb-2 small">{{ __('EcpayInvoice::common.allowance_history') }}</div>
                <table class="table table-sm table-bordered mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('EcpayInvoice::common.allowance_number') }}</th>
                            <th>{{ __('EcpayInvoice::common.allowance_desc') }}</th>
                            <th>{{ __('EcpayInvoice::common.allowance_amount') }}</th>
                            <th>{{ __('common.status') }}</th>
                            <th>{{ __('common.created_at') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($invoice->allowances as $allowance)
                        <tr>
                            <td>{{ $allowance->allowance_number ?? '-' }}</td>
                            <td>{{ $allowance->desc }}</td>
                            <td>{{ $allowance->amount }}</td>
                            <td>
                                <span class="badge bg-{{ $allowance->status === 'void' ? 'secondary' : 'success' }}">
                                    {{ $allowance->status === 'void' ? __('EcpayInvoice::common.status_void') : __('EcpayInvoice::common.status_issued') }}
                                </span>
                            </td>
                            <td>{{ $allowance->created_at->format('Y-m-d H:i') }}</td>
                            <td>
                                @if ($allowance->status === 'issued')
                                <button class="btn btn-sm btn-outline-danger"
                                        onclick="invoiceAction('{{ admin_route('ecpay_invoice.void_allowance', $allowance) }}', '{{ __('EcpayInvoice::common.confirm_void_allowance') }}')">
                                    {{ __('EcpayInvoice::common.btn_void') }}
                                </button>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif

            <div class="mt-2 d-flex gap-2">
                @if (in_array($invoice->status, ['pending', 'failed', 'void']))
                    <button class="btn btn-sm btn-outline-primary"
                            onclick="openIssueModal(
                                '{{ admin_route('ecpay_invoice.issue', $invoice) }}',
                                '{{ $invoice->carrier_type }}',
                                '{{ $invoice->carrier_number }}',
                                '{{ $invoice->tax_id }}',
                                '{{ $invoice->company_title }}',
                                '{{ $invoice->love_code }}'
                            )">
                        {{ __('EcpayInvoice::common.btn_issue') }}
                    </button>
                @endif
                @if ($invoice->status === 'issued')
                    <button class="btn btn-sm btn-outline-danger"
                            onclick="invoiceAction('{{ admin_route('ecpay_invoice.void', $invoice) }}', '{{ __('EcpayInvoice::common.confirm_void') }}')">
                        {{ __('EcpayInvoice::common.btn_void') }}
                    </button>
                    <button class="btn btn-sm btn-outline-warning"
                            onclick="invoiceAllowance('{{ admin_route('ecpay_invoice.allowance', $invoice) }}')">
                        {{ __('EcpayInvoice::common.btn_allowance') }}
                    </button>
                @endif
            </div>
        </div>
    </td>
</tr>

{{-- 補開發票 Modal --}}
<div class="modal fade" id="invoice-issue-modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('EcpayInvoice::common.btn_issue') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">{{ __('EcpayInvoice::common.carrier_type') }}</label>
                    <select class="form-select" id="issue-carrier-type">
                        <option value="personal">{{ __('EcpayInvoice::common.carrier_personal') }}</option>
                        <option value="mobile">{{ __('EcpayInvoice::common.carrier_mobile') }}</option>
                        <option value="love">{{ __('EcpayInvoice::common.carrier_love') }}</option>
                        <option value="company">{{ __('EcpayInvoice::common.carrier_company') }}</option>
                    </select>
                </div>
                <div id="issue-mobile-wrap" class="mb-3 d-none">
                    <label class="form-label">{{ __('EcpayInvoice::common.carrier_number') }}</label>
                    <input type="text" class="form-control" id="issue-carrier-number" maxlength="8" placeholder="/XXXXXXX">
                </div>
                <div id="issue-love-wrap" class="mb-3 d-none">
                    <label class="form-label">{{ __('EcpayInvoice::common.love_code') }}</label>
                    <input type="text" class="form-control" id="issue-love-code">
                </div>
                <div id="issue-company-wrap" class="d-none">
                    <div class="mb-3">
                        <label class="form-label">{{ __('EcpayInvoice::common.tax_id') }}</label>
                        <input type="text" class="form-control" id="issue-tax-id" maxlength="8">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('EcpayInvoice::common.company_title') }}</label>
                        <input type="text" class="form-control" id="issue-company-title">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                <button type="button" class="btn btn-primary" onclick="issueConfirm()">{{ __('common.confirm') }}</button>
            </div>
        </div>
    </div>
</div>

{{-- 折讓 Modal --}}
<div class="modal fade" id="invoice-allowance-modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('EcpayInvoice::common.btn_allowance') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">{{ __('EcpayInvoice::common.allowance_desc') }}</label>
                    <input type="text" id="invoice-allowance-desc" class="form-control" maxlength="100">
                </div>
                <div class="mb-3">
                    <label class="form-label">{{ __('EcpayInvoice::common.allowance_amount') }}</label>
                    <input type="number" id="invoice-allowance-amount" class="form-control" min="1">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                <button type="button" class="btn btn-primary" onclick="invoiceAllowanceConfirm()">{{ __('common.confirm') }}</button>
            </div>
        </div>
    </div>
</div>

<script>
var _invoiceIssueUrl = '';

function openIssueModal(url, carrierType, carrierNumber, taxId, companyTitle, loveCode) {
    _invoiceIssueUrl = url;
    document.getElementById('issue-carrier-type').value = carrierType || 'personal';
    document.getElementById('issue-carrier-number').value = carrierNumber || '';
    document.getElementById('issue-tax-id').value = taxId || '';
    document.getElementById('issue-company-title').value = companyTitle || '';
    document.getElementById('issue-love-code').value = loveCode || '';
    toggleIssueFields();
    new bootstrap.Modal(document.getElementById('invoice-issue-modal')).show();
}

function toggleIssueFields() {
    var type = document.getElementById('issue-carrier-type').value;
    document.getElementById('issue-mobile-wrap').classList.toggle('d-none', type !== 'mobile');
    document.getElementById('issue-love-wrap').classList.toggle('d-none', type !== 'love');
    document.getElementById('issue-company-wrap').classList.toggle('d-none', type !== 'company');
}

document.addEventListener('DOMContentLoaded', function () {
    var sel = document.getElementById('issue-carrier-type');
    if (sel) sel.addEventListener('change', toggleIssueFields);
});

function issueConfirm() {
    var type = document.getElementById('issue-carrier-type').value;
    var data = { carrier_type: type };
    if (type === 'mobile') data.carrier_number = document.getElementById('issue-carrier-number').value.trim();
    if (type === 'love')   data.love_code      = document.getElementById('issue-love-code').value.trim();
    if (type === 'company') {
        data.tax_id        = document.getElementById('issue-tax-id').value.trim();
        data.company_title = document.getElementById('issue-company-title').value.trim();
    }
    $http.post(_invoiceIssueUrl, data).then(function (res) {
        bootstrap.Modal.getInstance(document.getElementById('invoice-issue-modal')).hide();
        layer.msg(res.message || 'OK');
        setTimeout(function () { location.reload(); }, 800);
    }).catch(function (err) {
        var errMsg = (err && err.response && err.response.data && err.response.data.message)
            || (err && err.message) || 'Error';
        layer.msg(errMsg);
    });
}

var _invoiceAllowanceUrl = '';

function invoiceAction(url, msg) {
    if (!window.confirm(msg)) return;
    $http.post(url).then(function (res) {
        layer.msg(res.message || 'OK');
        setTimeout(function () { location.reload(); }, 800);
    }).catch(function (err) {
        var errMsg = (err && err.response && err.response.data && err.response.data.message)
            || (err && err.message)
            || 'Error';
        layer.msg(errMsg);
    });
}

function invoiceAllowance(url) {
    _invoiceAllowanceUrl = url;
    document.getElementById('invoice-allowance-desc').value = '';
    document.getElementById('invoice-allowance-amount').value = '';
    new bootstrap.Modal(document.getElementById('invoice-allowance-modal')).show();
}

function invoiceAllowanceConfirm() {
    var desc   = document.getElementById('invoice-allowance-desc').value.trim();
    var amount = parseInt(document.getElementById('invoice-allowance-amount').value);
    if (!desc || !amount) { layer.msg('請填寫折讓原因及金額'); return; }
    $http.post(_invoiceAllowanceUrl, { desc: desc, amount: amount }).then(function (res) {
        bootstrap.Modal.getInstance(document.getElementById('invoice-allowance-modal')).hide();
        layer.msg(res.message || 'OK');
        setTimeout(function () { location.reload(); }, 800);
    }).catch(function (err) {
        var errMsg = (err && err.response && err.response.data && err.response.data.message)
            || (err && err.message)
            || 'Error';
        layer.msg(errMsg);
    });
}
</script>
@endif
