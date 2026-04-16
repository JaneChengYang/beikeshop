<?php

return [
    [
        'name'        => 'merchant_id',
        'label'       => 'MerchantID（商店代號）',
        'type'        => 'string',
        'required'    => true,
        'rules'       => 'required|min:1',
        'description' => '綠界科技後台商店代號',
    ],
    [
        'name'        => 'hash_key',
        'label'       => 'HashKey',
        'type'        => 'string',
        'required'    => true,
        'rules'       => 'required|min:1',
        'description' => '綠界科技 HashKey',
    ],
    [
        'name'        => 'hash_iv',
        'label'       => 'HashIV',
        'type'        => 'string',
        'required'    => true,
        'rules'       => 'required|min:1',
        'description' => '綠界科技 HashIV',
    ],
    [
        'name'        => 'sandbox_mode',
        'label'       => '環境',
        'type'        => 'select',
        'options'     => [
            ['value' => '1', 'label' => '測試環境（Sandbox）'],
            ['value' => '0', 'label' => '正式環境'],
        ],
        'required'    => true,
        'description' => '開啟時使用綠界測試環境',
    ],
    [
        'name'        => 'enabled',
        'label'       => '開啟發票功能',
        'type'        => 'select',
        'options'     => [
            ['value' => '1', 'label' => '開啟'],
            ['value' => '0', 'label' => '關閉'],
        ],
        'required'    => true,
        'description' => '關閉後結帳頁發票選項將 disable',
    ],
];
