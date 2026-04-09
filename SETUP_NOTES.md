# BeikeShop 本地開發環境建置紀錄

## 環境資訊

| 項目 | 版本 |
|---|---|
| 專案版本 | v1.6.0.20 |
| XAMPP | 8.2.4 |
| PHP（實際使用）| Homebrew PHP 8.2.30 |
| MySQL | XAMPP 內建 |
| Composer | /opt/homebrew/bin/composer |
| Node.js | v18.20.8（nvm 切換） |
| npm | 10.8.2 |
| Laravel Mix | v6.0.49 |
| webpack | 5.74.0（指定版本） |
| webpack-cli | 4.10.0（指定版本） |
| 作業系統 | macOS（Apple Silicon） |

---

## 安裝步驟

### 1. 升級 XAMPP 到 8.2.4
- 原本裝的是 XAMPP 8.0.28（PHP 8.0，版本太舊）
- 重新下載安裝 XAMPP 8.2.4
- 安裝時勾選 **XAMPP Core Files**（必選）和 XAMPP Developer Files
- 安裝完成後啟動：**MySQL Database** 和 **Apache Web Server**（綠燈）

### 2. 建立資料庫
- 開啟 `http://localhost/phpmyadmin`
- 左側點 **新增**
- 資料庫名稱：`beike`
- 編碼：`utf8mb4_unicode_ci`
- 按建立

### 3. Clone 專案
```bash
git clone https://github.com/beikeshop/beikeshop.git
cd beikeshop
```

### 4. 設定 .env
```bash
cp .env.example .env
```

修改 `.env` 以下內容：
```
APP_URL=http://127.0.0.1:8000
APP_TIMEZONE=Asia/Taipei
TRUST_HOSTS_ENABLED=false
DB_DATABASE=beike
DB_USERNAME=root
DB_PASSWORD=
```

### 5. 安裝 PHP 依賴（composer install）

**問題一：系統預設 PHP 是 Homebrew 8.5.3，版本太新**
```bash
which php       # 顯示 /opt/homebrew/bin/php
php -v          # PHP 8.5.3 → 太新，部分套件不相容
```

改用 XAMPP 的 PHP 8.2：
```bash
/Applications/XAMPP/xamppfiles/bin/php -v  # PHP 8.2.4 ✓
```

**問題二：XAMPP PHP 缺少 ext-sodium 擴充套件**

套件 `lcobucci/jwt`、`w7corp/easywechat`、`tymon/jwt-auth` 都需要 sodium，
但 XAMPP 的 PHP 沒有內建這個擴充套件，需要忽略：

```bash
/Applications/XAMPP/xamppfiles/bin/php /opt/homebrew/bin/composer install --ignore-platform-req=ext-sodium
```

共安裝 **182 個套件**，包含：
- laravel/framework v10.38.0
- laravel/horizon, laravel/dusk, laravel/sail, laravel/socialite, laravel/tinker
- spatie/laravel-permission, spatie/laravel-ignition
- barryvdh/laravel-debugbar
- tymon/jwt-auth, lcobucci/jwt
- w7corp/easywechat
- phpoffice/phpspreadsheet
- guzzlehttp/guzzle
- 及其他依賴套件

### 6. 安裝前端依賴並編譯

**問題一：Node.js 版本太新（v22.17.0 與 Laravel Mix 6 不相容）**

需切換到 Node 18：
```bash
nvm install 18
nvm use 18
node -v  # v18.20.8 ✓
```

**問題二：webpack-cli 版本不相容**

Laravel Mix 6 需要 webpack-cli@4：
```bash
npm install webpack-cli@4 --save-dev --legacy-peer-deps
```

**問題三：webpack 版本不相容**

需要指定 webpack 5.74.0：
```bash
npm install webpack@5.74.0 --save-dev --legacy-peer-deps
```

**成功編譯：**
```bash
npm install && npm run prod
```

編譯產出的檔案：
```
/build/beike/admin/js/app.js          78.1 KiB
/build/beike/shop/default/js/app.js   73.7 KiB
build/beike/admin/css/app.css         52.6 KiB
build/beike/admin/css/bootstrap.css  299 KiB
build/beike/shop/default/css/app.css  67.8 KiB
install/css/app.css                    1.67 KiB
```

### 7. 安裝 Homebrew PHP 8.2（解決 intl 問題）

安裝精靈檢測系統環境時發現 `intl` 擴充套件缺失（XAMPP PHP 沒有內建 intl.so）。

```bash
brew install php@8.2
/opt/homebrew/opt/php@8.2/bin/php -m | grep intl  # 確認有 intl ✓
```

### 8. 啟動開發伺服器

```bash
cd /Users/jianchengyang/Documents/GitHub/beikeshop
/opt/homebrew/opt/php@8.2/bin/php artisan serve
```

開啟 `http://127.0.0.1:8000` 走安裝精靈。

### 9. 安裝精靈（瀏覽器）

**步驟 1 - 歡迎**：按「檢測系統環境」

**步驟 2 - 系統環境要求**：
- PHP 8.2.30 ✓
- BCMath, Ctype, cURL, DOM, Intl, Fileinfo, JSON, Mbstring, OpenSSL, PCRE, PDO, Tokenizer, XML, ZIP, GD 全部 ✓

**步驟 3 - 目錄權限**：
- .env, bootstrap/cache/, public/cache/, public/plugin/, storage/framework/, storage/logs/ 全部 755 ✓

**步驟 4 - 系統參數配置**：
- 資料庫類型：MySQL
- 資料庫主機：127.0.0.1
- 資料庫端口：3306
- 資料庫名：beike
- 資料庫帳號：root
- 資料庫密碼：（空白）
- 後台帳號：自訂 email
- 後台密碼：自訂密碼

**步驟 5 - 安裝完成**

---

## 後台設定

後台網址：`http://127.0.0.1:8000/admin`

### 語言設定
- 系統 → 語言管理 → 繁體中文（zh_hk）已安裝，排序設為 1
- 系統 → 系統設置 → 預設語言改為 `zh_hk`

### 貨幣設定
- 系統 → 貨幣管理 → 新增台幣
  - 名稱：台幣
  - 編碼：TWD
  - 左符號：NT$
  - 小數位數：0（台幣不需要小數）
  - 匯率值：1
  - 默認貨幣：開啟
  - 狀態：開啟

### 運送方式
- 插件 → 配送方式 → `flat_shipping`（固定運費）已啟用
- 設定：固定金額模式，可自訂金額
- 訂單狀態可在後台手動修改

---

## 待開發

### TapPay 支付插件
- 目標：建立 `plugins/TapPay/` 插件
- 參考結構：`plugins/Paypal/`
- 需要準備：
  - TapPay Partner Key
  - TapPay Merchant ID
  - 確認使用 Sandbox 或正式環境

---

## 每次開發前啟動指令

```bash
# 1. 確認 XAMPP 的 MySQL 是 Running 狀態

# 2. 切換 Node 版本（新開終端機需要重新切換）
nvm use 18

# 3. 啟動 Laravel 伺服器
cd /Users/jianchengyang/Documents/GitHub/beikeshop
/opt/homebrew/opt/php@8.2/bin/php artisan serve
```

前台：`http://127.0.0.1:8000`
後台：`http://127.0.0.1:8000/admin`
