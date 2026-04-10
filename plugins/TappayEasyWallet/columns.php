<?php

return [
    [
        'name'        => 'partner_key',
        'label'       => 'Partner Key',
        'type'        => 'string',
        'required'    => true,
        'rules'       => 'required|min:10',
        'description' => 'TapPay Partner Key',
    ],
    [
        'name'        => 'merchant_id',
        'label'       => 'Merchant ID（悠遊付）',
        'type'        => 'string',
        'required'    => true,
        'rules'       => 'required|min:1',
        'description' => 'TapPay 後台悠遊付商戶 ID',
    ],
    [
        'name'        => 'app_id',
        'label'       => 'App ID',
        'type'        => 'string',
        'required'    => true,
        'rules'       => 'required',
        'description' => 'TapPay App ID（數字）',
    ],
    [
        'name'        => 'app_key',
        'label'       => 'App Key',
        'type'        => 'string',
        'required'    => true,
        'rules'       => 'required|min:10',
        'description' => 'TapPay App Key',
    ],
    [
        'name'        => 'sandbox_mode',
        'label'       => '測試模式',
        'type'        => 'select',
        'options'     => [
            ['value' => '1', 'label' => '開啟（Sandbox）'],
            ['value' => '0', 'label' => '關閉（正式環境）'],
        ],
        'required'    => true,
        'description' => '開啟時使用 TapPay Sandbox 環境',
    ],
];
