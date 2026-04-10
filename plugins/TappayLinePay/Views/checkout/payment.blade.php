@php
    $setting     = plugin_setting('tappay_line_pay');
    $sandboxMode = (bool) ($setting['sandbox_mode'] ?? true);
    $serverType  = $sandboxMode ? 'sandbox' : 'production';
@endphp

<script src="https://js.tappaysdk.com/sdk/tpdirect/v5.18.0"></script>

<div class="mt-4" id="linepay-form">
    <hr class="mb-4">
    <h5 class="checkout-title mb-3">{{ __('TappayLinePay::common.title') }}</h5>
    <div style="max-width: 500px;">
        <p class="text-muted mb-3">{{ __('TappayLinePay::common.description') }}</p>
        <div id="linepay-error-msg" class="text-danger mb-3" style="display:none;"></div>
        <button type="button" id="linepay-submit-btn" class="btn btn-success btn-lg px-4">
            {{ __('TappayLinePay::common.btn_pay') }}
        </button>
    </div>
</div>

<script>
    const linePayOrderNumber = @json($order->number ?? '');

    TPDirect.setupSDK(
        {{ $setting['app_id'] ?? 0 }},
        '{{ $setting['app_key'] ?? '' }}',
        '{{ $serverType }}'
    );

    document.getElementById('linepay-submit-btn').addEventListener('click', function () {
        const errorEl = document.getElementById('linepay-error-msg');
        errorEl.style.display = 'none';
        layer.load(2, {shade: [0.3, '#fff']});

        TPDirect.linePay.getPrime(function (result) {
            if (result.status !== 0) {
                layer.closeAll('loading');
                errorEl.textContent = result.msg || '{{ __('TappayLinePay::common.prime_fail') }}';
                errorEl.style.display = 'block';
                return;
            }

            $http.post('/tappay-linepay/pay', {
                order_number: linePayOrderNumber,
                prime:        result.prime,
            }).then(function (res) {
                layer.closeAll('loading');
                if (res.status === 'success' && res.data && res.data.payment_url) {
                    TPDirect.redirect(res.data.payment_url);
                } else {
                    errorEl.textContent = res.message || '{{ __('TappayLinePay::common.pay_fail') }}';
                    errorEl.style.display = 'block';
                }
            }).catch(function () {
                layer.closeAll('loading');
                errorEl.textContent = '{{ __('TappayLinePay::common.pay_fail') }}';
                errorEl.style.display = 'block';
            });
        });
    });
</script>
