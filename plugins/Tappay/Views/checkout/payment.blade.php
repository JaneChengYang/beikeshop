@php
    $setting     = plugin_setting('tappay');
    $appId       = $setting['app_id'] ?? '';
    $appKey      = $setting['app_key'] ?? '';
    $sandboxMode = (bool) ($setting['sandbox_mode'] ?? true);
    $serverType  = $sandboxMode ? 'sandbox' : 'production';
@endphp

<script src="https://js.tappaysdk.com/sdk/tpdirect/v5.18.0"></script>

<div class="mt-4" id="tappay-form">
    <hr class="mb-4">
    <h5 class="checkout-title mb-3">{{ __('Tappay::common.title_info') }}</h5>

    <div style="max-width: 500px;">
        <div class="mb-3">
            <label class="form-label">{{ __('Tappay::common.cardholder_name') }}</label>
            <input type="text" id="tappay-cardholder-name" class="form-control"
                   placeholder="{{ __('Tappay::common.cardholder_name') }}">
        </div>

        <div class="mb-3">
            <label class="form-label">{{ __('Tappay::common.cardholder_phone') }}</label>
            <input type="text" id="tappay-cardholder-phone" class="form-control"
                   placeholder="0912345678">
        </div>

        <div class="mb-3">
            <label class="form-label">{{ __('Tappay::common.card_number') }}</label>
            <div id="card-number" class="form-control" style="height: 44px; padding-top: 10px;"></div>
        </div>

        <div class="row mb-3">
            <div class="col-6">
                <label class="form-label">{{ __('Tappay::common.expiry_date') }}</label>
                <div id="card-expiration-date" class="form-control" style="height: 44px; padding-top: 10px;"></div>
            </div>
            <div class="col-6">
                <label class="form-label">{{ __('Tappay::common.cvv') }}</label>
                <div id="card-ccv" class="form-control" style="height: 44px; padding-top: 10px;"></div>
            </div>
        </div>

        <div id="tappay-error-msg" class="text-danger mb-3" style="display:none;"></div>

        <button type="button" id="tappay-submit-btn" class="btn btn-primary btn-lg">
            {{ __('Tappay::common.btn_submit') }}
        </button>
    </div>
</div>

<script>
    const orderNumber = @json($order->number ?? '');
    const cardholderEmail = @json($order->customer->email ?? '');

    TPDirect.setupSDK(
        {{ $appId }},
        '{{ $appKey }}',
        '{{ $serverType }}'
    );

    TPDirect.card.setup({
        fields: {
            number: {
                element: '#card-number',
                placeholder: '**** **** **** ****'
            },
            expirationDate: {
                element: '#card-expiration-date',
                placeholder: 'MM / YY'
            },
            ccv: {
                element: '#card-ccv',
                placeholder: 'CVV'
            }
        },
        styles: {
            'input': {
                'color': '#333',
                'font-size': '16px',
            },
            ':focus': {
                'color': '#333'
            },
            '.valid': {
                'color': '#28a745'
            },
            '.invalid': {
                'color': '#dc3545'
            }
        }
    });

    document.getElementById('tappay-submit-btn').addEventListener('click', function () {
        const errorEl = document.getElementById('tappay-error-msg');
        errorEl.style.display = 'none';

        const cardholderName  = document.getElementById('tappay-cardholder-name').value.trim();
        const cardholderPhone = document.getElementById('tappay-cardholder-phone').value.trim();

        if (!cardholderName) {
            errorEl.textContent = '{{ __('Tappay::common.error_name') }}';
            errorEl.style.display = 'block';
            return;
        }

        const tappayStatus = TPDirect.card.getTappayFieldsStatus();
        if (!tappayStatus.canGetPrime) {
            errorEl.textContent = '{{ __('Tappay::common.error_card') }}';
            errorEl.style.display = 'block';
            return;
        }

        layer.load(2, {shade: [0.3, '#fff']});

        TPDirect.card.getPrime(function (result) {
            if (result.status !== 0) {
                layer.closeAll('loading');
                errorEl.textContent = result.msg;
                errorEl.style.display = 'block';
                return;
            }

            const token = $('meta[name="csrf-token"]').attr('content');

            $http.post('/tappay/capture', {
                order_number:     orderNumber,
                prime:            result.card.prime,
                cardholder_name:  cardholderName,
                cardholder_email: cardholderEmail,
                cardholder_phone: cardholderPhone,
            }).then(function (res) {
                layer.closeAll('loading');
                if (res.status === 'success') {
                    // 需要 3D 驗證，導向銀行驗證頁
                    if (res.data && res.data.payment_url) {
                        window.location.href = res.data.payment_url;
                        return;
                    }
                    // 不需要 3D 驗證，直接成功
                    location = "{{ shop_route('checkout.success', ['order_number' => $order->number]) }}";
                } else {
                    errorEl.textContent = res.message || '{{ __('Tappay::common.capture_fail') }}';
                    errorEl.style.display = 'block';
                }
            }).catch(function () {
                layer.closeAll('loading');
                errorEl.textContent = '{{ __('Tappay::common.capture_fail') }}';
                errorEl.style.display = 'block';
            });
        });
    });
</script>
