@php
    $setting     = plugin_setting('tappay_jko_pay');
    $sandboxMode = (bool) ($setting['sandbox_mode'] ?? true);
    $serverType  = $sandboxMode ? 'sandbox' : 'production';
@endphp

<script src="https://js.tappaysdk.com/sdk/tpdirect/v5.18.0"></script>

<div class="mt-4" id="jkopay-form">
    <hr class="mb-4">
    <h5 class="checkout-title mb-3">{{ __('TappayJkoPay::common.title') }}</h5>
    <div style="max-width: 500px;">
        <p class="text-muted mb-3">{{ __('TappayJkoPay::common.description') }}</p>
        <div id="jkopay-error-msg" class="text-danger mb-3" style="display:none;"></div>
        <button type="button" id="jkopay-submit-btn" class="btn btn-warning btn-lg px-4 text-white">
            {{ __('TappayJkoPay::common.btn_pay') }}
        </button>
    </div>
</div>

<script>
    const jkoPayOrderNumber = @json($order->number ?? '');

    TPDirect.setupSDK(
        {{ $setting['app_id'] ?? 0 }},
        '{{ $setting['app_key'] ?? '' }}',
        '{{ $serverType }}'
    );

    document.getElementById('jkopay-submit-btn').addEventListener('click', function () {
        const errorEl = document.getElementById('jkopay-error-msg');
        errorEl.style.display = 'none';
        layer.load(2, {shade: [0.3, '#fff']});

        TPDirect.jkoPay.getPrime(function (result) {
            if (result.status !== 0) {
                layer.closeAll('loading');
                errorEl.textContent = result.msg || '{{ __('TappayJkoPay::common.prime_fail') }}';
                errorEl.style.display = 'block';
                return;
            }

            $http.post('/tappay-jkopay/pay', {
                order_number: jkoPayOrderNumber,
                prime:        result.prime,
            }).then(function (res) {
                layer.closeAll('loading');
                if (res.status === 'success' && res.data && res.data.payment_url) {
                    TPDirect.redirect(res.data.payment_url);
                } else {
                    errorEl.textContent = res.message || '{{ __('TappayJkoPay::common.pay_fail') }}';
                    errorEl.style.display = 'block';
                }
            }).catch(function () {
                layer.closeAll('loading');
                errorEl.textContent = '{{ __('TappayJkoPay::common.pay_fail') }}';
                errorEl.style.display = 'block';
            });
        });
    });
</script>
