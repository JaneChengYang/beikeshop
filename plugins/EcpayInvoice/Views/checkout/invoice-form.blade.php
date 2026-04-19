@php
    $invoiceEnabled = (bool) plugin_setting('ecpay_invoice.enabled');
@endphp

<div class="checkout-black" id="invoice-form-wrap">
    <h5 class="checkout-title">{{ __('EcpayInvoice::common.invoice_title') }}</h5>

    @if (!$invoiceEnabled)
        <div class="alert alert-warning mb-0">{{ __('EcpayInvoice::common.invoice_disabled') }}</div>
    @endif

    <div class="invoice-types mb-3 {{ !$invoiceEnabled ? 'pe-none opacity-50' : '' }}">
        {{-- 型別選擇 --}}
        <div class="d-flex gap-3 flex-wrap mb-3">
            @foreach ([
                'personal' => __('EcpayInvoice::common.carrier_personal'),
                'mobile'   => __('EcpayInvoice::common.carrier_mobile'),
                'love'     => __('EcpayInvoice::common.carrier_love'),
                'company'  => __('EcpayInvoice::common.carrier_company'),
            ] as $type => $label)
                <div class="form-check">
                    <input class="form-check-input invoice-type-radio" type="radio"
                           name="invoice_carrier_type" id="invoice_type_{{ $type }}"
                           value="{{ $type }}" {{ $type === 'personal' ? 'checked' : '' }}
                           {{ !$invoiceEnabled ? 'disabled' : '' }}>
                    <label class="form-check-label" for="invoice_type_{{ $type }}">{{ $label }}</label>
                </div>
            @endforeach
        </div>

        {{-- 手機條碼 --}}
        <div id="invoice-mobile-wrap" class="d-none">
            <div class="input-group">
                <span class="input-group-text">/</span>
                <input type="text" class="form-control" id="invoice_carrier_number"
                       placeholder="XXXXXXX（7碼）" maxlength="8"
                       {{ !$invoiceEnabled ? 'disabled' : '' }}>
                <button class="btn btn-outline-secondary" type="button" id="btn-verify-carrier">
                    {{ __('EcpayInvoice::common.verify') }}
                </button>
            </div>
            <div id="carrier-verify-msg" class="small mt-1"></div>
        </div>

        {{-- 捐贈發票 --}}
        <div id="invoice-love-wrap" class="d-none">
            <div class="input-group mb-2">
                <input type="text" class="form-control" id="invoice_love_code"
                       placeholder="{{ __('EcpayInvoice::common.love_code_placeholder') }}"
                       {{ !$invoiceEnabled ? 'disabled' : '' }}>
                <button class="btn btn-outline-secondary" type="button" id="btn-search-love">
                    {{ __('EcpayInvoice::common.search') }}
                </button>
            </div>
            <div id="love-code-results" class="list-group mb-2" style="max-height:200px;overflow-y:auto;display:none;"></div>
            <div id="love-code-selected" class="text-success small"></div>
        </div>

        {{-- 公司發票 --}}
        <div id="invoice-company-wrap" class="d-none">
            <div class="mb-2">
                <input type="text" class="form-control" id="invoice_tax_id"
                       placeholder="{{ __('EcpayInvoice::common.tax_id_placeholder') }}"
                       maxlength="8" {{ !$invoiceEnabled ? 'disabled' : '' }}>
            </div>
            <div>
                <input type="text" class="form-control" id="invoice_company_title"
                       placeholder="{{ __('EcpayInvoice::common.company_title_placeholder') }}"
                       {{ !$invoiceEnabled ? 'disabled' : '' }}>
            </div>
        </div>
    </div>
</div>

@push('add-scripts')
<script>
(function () {
    const invoiceEnabled = {{ $invoiceEnabled ? 'true' : 'false' }};

    // 切換顯示區塊
    function toggleInvoiceFields() {
        const type = $('input[name=invoice_carrier_type]:checked').val();
        $('#invoice-mobile-wrap').toggleClass('d-none', type !== 'mobile');
        $('#invoice-love-wrap').toggleClass('d-none', type !== 'love');
        $('#invoice-company-wrap').toggleClass('d-none', type !== 'company');
    }

    $(document).on('change', '.invoice-type-radio', toggleInvoiceFields);
    toggleInvoiceFields();

    // 驗證手機條碼
    $('#btn-verify-carrier').click(function () {
        const raw = $('#invoice_carrier_number').val().trim();
        const carrier = '/' + raw.replace(/^\//, '');
        $http.post('{{ shop_route('ecpay_invoice.verify_carrier') }}', { carrier })
            .then(() => {
                $('#carrier-verify-msg').removeClass('text-danger').addClass('text-success')
                    .text('{{ __('EcpayInvoice::common.carrier_valid') }}');
                $('#invoice_carrier_number').data('verified', true);
            })
            .catch((err) => {
                const errMsg = (err && err.response && err.response.data && err.response.data.message)
                    || '{{ __('EcpayInvoice::common.carrier_invalid') }}';
                $('#carrier-verify-msg').removeClass('text-success').addClass('text-danger').text(errMsg);
                $('#invoice_carrier_number').data('verified', false);
            });
    });

    // 搜尋捐贈碼
    function searchLoveCodes(keyword) {
        $http.get('{{ shop_route('ecpay_invoice.love_codes') }}', { params: { keyword } })
            .then((res) => {
                const $list = $('#love-code-results');
                $list.empty().show();
                if (!res.data || res.data.length === 0) {
                    $list.append('<div class="list-group-item text-muted">{{ __('EcpayInvoice::common.no_results') }}</div>');
                    return;
                }
                res.data.forEach((item) => {
                    $list.append(
                        `<button type="button" class="list-group-item list-group-item-action love-code-item"
                            data-code="${item.love_code}" data-name="${item.name}">
                            <strong>${item.love_code}</strong> ${item.name} <small class="text-muted">${item.city}</small>
                        </button>`
                    );
                });
            });
    }

    // 點搜尋按鈕
    $('#btn-search-love').click(function () {
        searchLoveCodes($('#invoice_love_code').val().trim());
    });

    // 輸入時即時搜尋
    let _loveSearchTimer = null;
    $('#invoice_love_code').on('input', function () {
        clearTimeout(_loveSearchTimer);
        _loveSearchTimer = setTimeout(() => searchLoveCodes($(this).val().trim()), 300);
    });

    // 點選輸入框時若清單未顯示則自動載入
    $('#invoice_love_code').on('focus', function () {
        if ($('#love-code-results').is(':hidden') || $('#love-code-results').children().length === 0) {
            searchLoveCodes($(this).val().trim());
        }
    });

    $(document).on('click', '.love-code-item', function () {
        const code = $(this).data('code');
        const name = $(this).data('name');
        $('#invoice_love_code').val(code);
        $('#love-code-selected').text(name);
        $('#love-code-results').hide();
    });

    // 在送出前把發票資料存到 server session（可靠，不依賴 confirm 後 timing）
    async function saveInvoiceToSession() {
        const type = $('input[name=invoice_carrier_type]:checked').val() || 'personal';
        const raw  = $('#invoice_carrier_number').val().trim();

        const invoiceData = {
            carrier_type:   type,
            carrier_number: type === 'mobile' ? '/' + raw.replace(/^\//, '') : null,
            tax_id:         type === 'company' ? $('#invoice_tax_id').val().trim() : null,
            company_title:  type === 'company' ? $('#invoice_company_title').val().trim() : null,
            love_code:      type === 'love'    ? $('#invoice_love_code').val().trim() : null,
        };

        try {
            await $http.post('{{ shop_route('ecpay_invoice.save_session') }}', invoiceData);
        } catch (e) {}
    }

    // 攔截送出按鈕：驗證 + 先存 session 再讓原有流程繼續
    const $submitBtn = $('#submit-checkout');

    $submitBtn.on('click.invoice', async function (e) {
        if (!invoiceEnabled) return;

        const type = $('input[name=invoice_carrier_type]:checked').val() || 'personal';

        // 手機條碼格式 + API 驗證
        if (type === 'mobile') {
            const raw = $('#invoice_carrier_number').val().trim();
            if (!/^[0-9A-Z+\-.]{7}$/.test(raw)) {
                layer.msg('{{ __('EcpayInvoice::common.carrier_format_error') }}');
                e.stopImmediatePropagation();
                return false;
            }
            // 呼叫 API 確認載具是否有效
            e.stopImmediatePropagation();
            const carrier = '/' + raw.replace(/^\//, '');
            try {
                await $http.post('{{ shop_route('ecpay_invoice.verify_carrier') }}', { carrier });
                $('#carrier-verify-msg').removeClass('text-danger').addClass('text-success')
                    .text('{{ __('EcpayInvoice::common.carrier_valid') }}');
            } catch (err) {
                const errMsg = (err && err.response && err.response.data && err.response.data.message)
                    || '{{ __('EcpayInvoice::common.carrier_invalid') }}';
                $('#carrier-verify-msg').removeClass('text-success').addClass('text-danger').text(errMsg);
                layer.msg(errMsg);
                return false;
            }
            await saveInvoiceToSession();
            $submitBtn.off('click.invoice').trigger('click');
            return;
        }

        // 公司發票驗證
        if (type === 'company') {
            const taxId       = $('#invoice_tax_id').val().trim();
            const companyTitle = $('#invoice_company_title').val().trim();

            // 格式：8碼數字
            if (!/^\d{8}$/.test(taxId)) {
                layer.msg('{{ __('EcpayInvoice::common.tax_id_format_error') }}');
                e.stopImmediatePropagation();
                return false;
            }

            // 統編檢查碼驗算（財政部演算法）
            const weights = [1, 2, 1, 2, 1, 2, 4, 1];
            const digits  = taxId.split('').map(Number);
            let sum = 0;
            for (let i = 0; i < 8; i++) {
                let p = digits[i] * weights[i];
                sum += p >= 10 ? Math.floor(p / 10) + (p % 10) : p;
            }
            const valid = sum % 10 === 0 || (digits[6] === 7 && (sum - 1) % 10 === 0);
            if (!valid) {
                layer.msg('{{ __('EcpayInvoice::common.tax_id_checksum_error') }}');
                e.stopImmediatePropagation();
                return false;
            }

            // 公司抬頭必填
            if (!companyTitle) {
                layer.msg('{{ __('EcpayInvoice::common.company_title_required') }}');
                e.stopImmediatePropagation();
                return false;
            }
        }

        // 捐贈碼驗證
        if (type === 'love') {
            const loveCode = $('#invoice_love_code').val().trim();
            if (!loveCode) {
                layer.msg('{{ __('EcpayInvoice::common.love_code_required') }}');
                e.stopImmediatePropagation();
                return false;
            }
            e.stopImmediatePropagation();
            try {
                await $http.post('{{ shop_route('ecpay_invoice.verify_love_code') }}', { love_code: loveCode });
            } catch (err) {
                const errMsg = (err && err.response && err.response.data && err.response.data.message)
                    || '{{ __('EcpayInvoice::common.love_code_invalid') }}';
                layer.msg(errMsg);
                return false;
            }
            await saveInvoiceToSession();
            $submitBtn.off('click.invoice').trigger('click');
            return;
        }

        // 先存 session，再讓原有 click 繼續
        await saveInvoiceToSession();
    });
})();
</script>
@endpush
