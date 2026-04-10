@php
    $setting     = plugin_setting('tappay_easy_wallet');
    $sandboxMode = (bool) ($setting['sandbox_mode'] ?? true);
    $serverType  = $sandboxMode ? 'sandbox' : 'production';
@endphp

<script src="https://js.tappaysdk.com/sdk/tpdirect/v5.18.0"></script>

<div class="mt-4" id="easywallet-form">
    <hr class="mb-4">
    <h5 class="checkout-title mb-3">{{ __('TappayEasyWallet::common.title') }}</h5>
    <div style="max-width: 500px;">
        <p class="text-muted mb-3">{{ __('TappayEasyWallet::common.description') }}</p>
        <div id="easywallet-error-msg" class="text-danger mb-3" style="display:none;"></div>
        <button type="button" id="easywallet-submit-btn" class="btn btn-info btn-lg px-4 text-white">
            {{ __('TappayEasyWallet::common.btn_pay') }}
        </button>
    </div>
</div>

<script>
    const easyWalletOrderNumber = @json($order->number ?? '');

    TPDirect.setupSDK(
        {{ $setting['app_id'] ?? 0 }},
        '{{ $setting['app_key'] ?? '' }}',
        '{{ $serverType }}'
    );

    document.getElementById('easywallet-submit-btn').addEventListener('click', function () {
        const errorEl = document.getElementById('easywallet-error-msg');
        errorEl.style.display = 'none';
        layer.load(2, {shade: [0.3, '#fff']});

        TPDirect.easyWallet.getPrime(function (error, result) {
            if (error) {
                layer.closeAll('loading');
                errorEl.textContent = error.message || '{{ __('TappayEasyWallet::common.prime_fail') }}';
                errorEl.style.display = 'block';
                return;
            }

            $http.post('/tappay-easywallet/pay', {
                order_number: easyWalletOrderNumber,
                prime:        result.prime,
            }).then(function (res) {
                layer.closeAll('loading');
                if (res.status === 'success' && res.data && res.data.payment_url) {
                    TPDirect.redirect(res.data.payment_url);
                } else {
                    errorEl.textContent = res.message || '{{ __('TappayEasyWallet::common.pay_fail') }}';
                    errorEl.style.display = 'block';
                }
            }).catch(function () {
                layer.closeAll('loading');
                errorEl.textContent = '{{ __('TappayEasyWallet::common.pay_fail') }}';
                errorEl.style.display = 'block';
            });
        });
    });
</script>
