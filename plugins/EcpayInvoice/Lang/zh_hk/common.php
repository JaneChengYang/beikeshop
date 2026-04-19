<?php

return [
    // 選單
    'menu_title' => '發票管理',

    // 發票型別
    'invoice_title'    => '發票資訊',
    'carrier_type'     => '發票型別',
    'carrier_personal' => '個人電子發票',
    'carrier_mobile'   => '手機條碼載具',
    'carrier_love'     => '捐贈發票',
    'carrier_company'  => '公司發票',

    // 狀態
    'status_pending' => '待開立',
    'status_issued'  => '已開立',
    'status_failed'  => '開立失敗',
    'status_void'    => '已作廢',

    // 欄位
    'invoice_number'  => '發票號碼',
    'random_number'   => '隨機碼',
    'issued_at'       => '開立時間',
    'failed_reason'   => '失敗原因',
    'carrier_number'  => '載具號碼',
    'love_code'       => '捐贈愛心碼',
    'tax_id'          => '統一編號',
    'company_title'   => '公司抬頭',

    // 表單
    'carrier_format_error'       => '手機條碼格式錯誤（/XXXXXXX，7碼大寫英數）',
    'carrier_valid'              => '條碼驗證成功',
    'carrier_invalid'            => '條碼不存在或無效',
    'tax_id_placeholder'         => '統一編號（8碼數字）',
    'tax_id_format_error'        => '統一編號格式錯誤，請輸入 8 碼數字',
    'tax_id_checksum_error'      => '統一編號檢查碼錯誤，請確認號碼是否正確',
    'company_title_required'     => '請輸入公司抬頭',
    'company_title_placeholder'  => '公司抬頭',
    'love_code_placeholder'      => '輸入愛心碼或搜尋機構名稱',
    'love_code_required'         => '請輸入捐贈愛心碼',
    'love_code_valid'            => '愛心碼驗證成功',
    'love_code_invalid'          => '愛心碼不存在或無效',
    'search'                     => '搜尋',
    'verify'                     => '驗證',
    'no_results'                 => '無符合結果',
    'invoice_disabled'           => '發票功能目前未開啟',

    // 後臺操作
    'btn_issue'          => '補開發票',
    'btn_void'           => '作廢',
    'btn_allowance'      => '折讓',
    'confirm_issue'      => '確定要手動補開此發票？',
    'confirm_void'       => '確定要作廢此發票？此操作不可復原。',
    'issue_success'      => '發票開立成功',
    'issue_fail'         => '發票開立失敗',
    'void_success'       => '作廢成功',
    'void_fail'          => '作廢失敗',
    'allowance_success'  => '折讓成功',
    'allowance_fail'     => '折讓失敗',
    'cannot_issue'       => '此發票狀態不允許補開',
    'cannot_void'        => '此發票狀態不允許作廢',
    'cannot_allowance'   => '此發票狀態不允許折讓',
    'allowance_desc'          => '折讓原因／品名',
    'allowance_amount'        => '折讓金額（元）',
    'allowance_input_error'   => '請填寫折讓原因及金額',
    'allowance_history'       => '折讓記錄',
    'allowance_number'        => '折讓單號',
    'confirm_void_allowance'  => '確定要作廢此折讓？此操作不可復原。',

    // 列表
    'search_placeholder' => '訂單編號 / 發票號碼',
];
