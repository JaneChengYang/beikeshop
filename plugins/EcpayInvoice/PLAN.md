# EcpayInvoice Plugin 開發規劃

## 概述
透過綠界科技 API 實作台灣電子發票功能。
付款成功後自動開立，支援個人電子發票、手機條碼、捐贈發票、公司發票四種類型。

---

## 新增資料表

### `order_invoices`
| 欄位 | 類型 | 說明 |
|---|---|---|
| id | bigint | 主鍵 |
| order_id | bigint | 關聯訂單 |
| carrier_type | string | personal / mobile / love / company |
| carrier_number | string nullable | 手機條碼 /XXXXXXX |
| tax_id | string nullable | 統編（8碼） |
| company_title | string nullable | 公司抬頭 |
| love_code | string nullable | 捐贈愛心碼 |
| invoice_number | string nullable | 綠界回傳發票號碼 |
| random_number | string nullable | 綠界回傳隨機碼 |
| status | string | pending / issued / failed / void |
| failed_reason | string nullable | 失敗原因 |
| issued_at | timestamp nullable | 開立時間 |
| voided_at | timestamp nullable | 作廢時間 |
| issue_log | json nullable | 送出的 request |
| response_log | json nullable | 綠界回傳的 response |
| created_at / updated_at | timestamp | |

### `ecpay_love_codes`
| 欄位 | 類型 | 說明 |
|---|---|---|
| id | bigint | 主鍵 |
| name | string | 受捐贈機關全名 |
| short_name | string nullable | 簡稱 |
| love_code | string | 愛心碼 |
| tax_id | string | 統編 |
| city | string | 縣市 |

> 資料來源：g_受捐贈機關或團體捐贈碼清單.csv（1748 筆）

---

## Plugin 檔案結構

```
plugins/EcpayInvoice/
├── config.json
├── columns.php
├── Bootstrap.php
├── Models/
│   ├── OrderInvoice.php
│   └── LoveCode.php
├── Migrations/
│   ├── create_order_invoices_table.php
│   └── create_ecpay_love_codes_table.php
├── Seeders/
│   └── LoveCodeSeeder.php
├── Services/
│   └── EcpayInvoiceService.php
├── Controllers/
│   ├── Admin/InvoiceController.php
│   └── Shop/InvoiceController.php
├── Routes/
│   ├── admin.php
│   └── shop.php
├── Views/
│   ├── admin/
│   │   ├── index.blade.php       ← 獨立發票管理列表
│   │   └── show.blade.php        ← 訂單詳情頁發票區塊
│   └── checkout/
│       └── invoice-form.blade.php
└── Lang/zh_hk/common.php
```

---

## 後台設定（columns.php）

| 欄位 | 說明 |
|---|---|
| MerchantID | 綠界商店代號 |
| HashKey | 綠界 HashKey |
| HashIV | 綠界 HashIV |
| 環境 | sandbox / production |
| 開啟發票功能 | on / off |

---

## 結帳頁發票表單

```
預設：個人電子發票（不需輸入任何東西）
○ 手機條碼  → 輸入 /XXXXXXX，輸入後即時打綠界 API 驗證
○ 捐贈發票  → 輸入愛心碼 或 搜尋名稱（對比 ecpay_love_codes）
○ 公司發票  → 統編（8碼數字格式驗證）+ 公司抬頭
```

- 發票功能關閉時：顯示但 disable，提示「發票功能未開啟」
- 結帳送出時：建立 `order_invoices`，status = pending

---

## 開票流程

```
付款成功（StateMachine status = paid）
    ↓
Bootstrap 監聽 hook：service.state_machine.change_status.after
    ↓
發票功能是否開啟？
    ↓ 是
EcpayInvoiceService::issue($order)
    ↓
組裝 API 參數：
  - 買方名稱：shipping_firstname + shipping_lastname
  - 商品明細：每個訂單商品一行（品名 / 數量 / 單價）
  - SHA256 簽章
    ↓
打綠界發票 API
    ↓
成功 → status = issued，存 invoice_number / random_number / issued_at
失敗 → status = failed，存 failed_reason
連線異常 → status = pending（等待手動補開）
```

---

## EcpayInvoiceService 方法

| 方法 | 說明 |
|---|---|
| `issue($order)` | 開立發票 |
| `void($invoice)` | 作廢發票 |
| `allowance($invoice, $desc, $amount)` | 折讓（自由輸入描述＋金額） |
| `verifyMobileCarrier($carrier)` | 驗證手機條碼（AJAX 用） |
| `buildCheckMacValue($params)` | SHA256 簽章 |

---

## 後台功能

### 獨立「發票管理」選單
- 列表顯示：訂單編號、發票號碼、狀態、類型、開立時間、失敗原因
- 手動補開：status = pending / failed
- 作廢：status = issued
- 折讓：status = issued，自由輸入描述 + 金額

### 訂單詳情頁
- 發票狀態區塊
- 操作按鈕（補開 / 作廢 / 折讓）

### RMA 詳情頁
- 顯示此訂單發票狀態
- [作廢] / [折讓] 快捷按鈕

---

## 前台訂單頁

顯示欄位：
- 發票類型
- 發票號碼
- 隨機碼
- 開立時間

---

## 發票狀態說明

| status | 說明 | 後台可操作 |
|---|---|---|
| pending | 待開立（連線失敗 / 未觸發） | 手動補開 |
| issued | 已開立 | 作廢、折讓 |
| failed | 開立失敗（資料錯誤，綠界拒絕） | 修改後補開 |
| void | 已作廢 | — |

---

## 實作順序

1. Plugin 骨架 + config.json
2. Migration + Model（order_invoices / ecpay_love_codes）
3. LoveCode Seeder（匯入 CSV 1748 筆）
4. 後台設定（columns.php）
5. EcpayInvoiceService（issue / void / allowance / verify / sign）
6. Bootstrap hook 監聽付款成功
7. 結帳頁表單 + 手機條碼即時驗證 AJAX
8. 後台獨立發票管理列表 + 操作
9. 訂單詳情頁發票區塊
10. RMA 詳情頁發票捷徑
11. 前台訂單頁顯示發票資訊

---

## 確認事項

| 項目 | 決定 |
|---|---|
| SDK | 自己用 Http::post() 打 API |
| 商品明細 | 每個商品一行 |
| 買方名稱 | 收件人姓名 |
| 折讓方式 | 自由輸入描述 + 金額 |
| 發票關閉顯示 | disable + 提示文字 |
| 後台入口 | 獨立列表 + 訂單詳情頁兩處 |
| 統編驗證 | 只驗 8 碼數字格式，真實性交給綠界 |
| 手機條碼驗證 | 即時打綠界 API |
| 捐贈碼來源 | ecpay_love_codes 資料表（從 CSV 匯入） |
