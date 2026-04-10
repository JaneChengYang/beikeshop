# BeikeShop 部署到 Windows XAMPP 主機指南

## 前置需求

- Windows 主機（建議 Windows 10/11 或 Windows Server）
- 對外開放的固定 IP
- 管理員權限

---

## 步驟 1：安裝 XAMPP

1. 前往 [https://www.apachefriends.org](https://www.apachefriends.org) 下載 XAMPP
2. 選擇 PHP **8.1 或 8.2** 版本（Laravel 10 需要）
3. 安裝時勾選以下元件：
   - Apache
   - MySQL
   - PHP
   - phpMyAdmin（方便管理資料庫）
4. 安裝完成後開啟 XAMPP Control Panel，啟動 **Apache** 和 **MySQL**

---

## 步驟 2：安裝 Composer

1. 前往 [https://getcomposer.org/download](https://getcomposer.org/download) 下載 `Composer-Setup.exe`
2. 執行安裝，安裝過程中會自動偵測 PHP 路徑（通常是 `C:\xampp\php\php.exe`）
3. 安裝完成後開啟命令提示字元（CMD），確認安裝成功：

```bash
composer --version
```

---

## 步驟 3：複製程式碼

1. 將整個 `beikeshop` 專案資料夾複製到：

```
C:\xampp\htdocs\beikeshop
```

2. 確認 `C:\xampp\htdocs\beikeshop\public` 資料夾存在

> 可以用 USB、Git clone、或者 FTP 等方式傳輸檔案

---

## 步驟 4：建立資料庫

1. 開啟瀏覽器，前往 `http://localhost/phpmyadmin`
2. 點選「新增」，建立資料庫：
   - 資料庫名稱：`beikeshop`
   - 排序規則：`utf8mb4_unicode_ci`
3. 點「建立」

---

## 步驟 5：設定 .env

將 `beikeshop` 根目錄的 `.env.example` 複製一份並命名為 `.env`，修改以下設定：

```env
APP_NAME=BeikeShop
APP_ENV=production
APP_KEY=                        # 稍後用指令產生
APP_DEBUG=false
APP_URL=http://你的公司主機IP   # 例如 http://192.168.1.100

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=beikeshop
DB_USERNAME=root
DB_PASSWORD=                    # XAMPP 預設 root 密碼為空
```

---

## 步驟 6：安裝套件並初始化

開啟命令提示字元（CMD），切換到專案目錄：

```bash
cd C:\xampp\htdocs\beikeshop
```

依序執行以下指令：

```bash
# 安裝 PHP 套件
composer install --no-dev --optimize-autoloader

# 產生 APP_KEY
php artisan key:generate

# 執行資料庫 migration
php artisan migrate --seed

# 建立 storage 軟連結（讓上傳的圖片可以被存取）
php artisan storage:link

# 清除並快取設定
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 步驟 7：設定 Apache Virtual Host

這步驟讓 Apache 正確指向 Laravel 的 `public` 資料夾。

### 7-1. 開啟 httpd-vhosts.conf

用文字編輯器（記事本或 VS Code）開啟：

```
C:\xampp\apache\conf\extra\httpd-vhosts.conf
```

### 7-2. 在檔案最底部加入以下內容

```apache
<VirtualHost *:80>
    DocumentRoot "C:/xampp/htdocs/beikeshop/public"
    ServerName localhost

    <Directory "C:/xampp/htdocs/beikeshop/public">
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog "logs/beikeshop-error.log"
    CustomLog "logs/beikeshop-access.log" combined
</VirtualHost>
```

### 7-3. 確認 mod_rewrite 已啟用

開啟 `C:\xampp\apache\conf\httpd.conf`，確認以下這行沒有被註解掉（前面沒有 `#`）：

```apache
LoadModule rewrite_module modules/mod_rewrite.so
```

### 7-4. 重啟 Apache

在 XAMPP Control Panel 點 **Stop** 再點 **Start** 重啟 Apache。

---

## 步驟 8：設定資料夾權限

確認以下資料夾 Apache 可以寫入：

```
C:\xampp\htdocs\beikeshop\storage
C:\xampp\htdocs\beikeshop\bootstrap\cache
```

在 Windows 上右鍵資料夾 → 內容 → 安全性，確認 `Everyone` 或 `Users` 有寫入權限。

---

## 步驟 9：開放防火牆 Port 80

1. 開啟「Windows Defender 防火牆」→ 進階設定
2. 點選「輸入規則」→「新增規則」
3. 選「連接埠」→ TCP → 特定本機連接埠輸入 `80`
4. 選「允許連線」→ 全部勾選 → 命名為 `XAMPP HTTP`
5. 完成

---

## 步驟 10：確認可以連線

在瀏覽器輸入：

```
http://公司主機IP
```

應該可以看到 BeikeShop 首頁。

---

## 常見問題

### 頁面顯示 403 Forbidden
- 確認 `httpd-vhosts.conf` 的路徑正確
- 確認 `mod_rewrite` 已啟用
- 確認 `beikeshop/public` 資料夾內有 `index.php`

### 頁面顯示 500 Error
- 確認 `.env` 設定正確
- 確認執行過 `php artisan key:generate`
- 查看 `storage/logs/laravel.log` 的錯誤訊息

### 圖片或上傳檔案看不到
- 確認執行過 `php artisan storage:link`
- 確認 `storage` 資料夾有寫入權限

### 外部連不進來
- 確認防火牆 Port 80 已開放
- 確認路由器有做 Port Forwarding（如果主機在 NAT 後面）

---

## TapPay 相關注意事項

- Demo 環境（HTTP）：TapPay sandbox webhook 可能收不到，但付款流程仍可正常運作
- 正式環境：需要 HTTPS，webhook 才能正常接收
- 上正式環境前，記得將 4 個 TapPay 外掛的 `sandbox_mode` 在後台關閉
