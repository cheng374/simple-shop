# 日常選物所

依照需求製作的簡易購物網站範例：前端、PHP 後端 API、MySQL／MariaDB 資料庫分離。

## 功能

- 商品首頁、分類篩選、關鍵字搜尋
- 會員註冊、登入、登出
- 購物袋：加入商品、調整數量、移除商品
- 結帳建立訂單，並扣除商品庫存
- 商店後台：管理員登入、商品新增／編輯／刪除、庫存管理
- 商店後台：訂單查看、訂單狀態更新、營業統計
- `database/schema.sql` 提供資料庫結構
- 首次啟動會自動建立 `simple_shop` 資料庫、後台帳號與示範商品

## 啟動方式（XAMPP）

1. 將整個 `simple-shop` 資料夾放到 `C:\xampp\htdocs\simple-shop`。
2. 開啟 XAMPP Control Panel，啟動 **Apache**。
3. 開啟 http://localhost/simple-shop/ 。
4. 後台網址是 http://localhost/simple-shop/admin/ 。

XAMPP 的 PHP 已確認包含 `pdo_mysql`。網站會使用 XAMPP 的 MySQL／MariaDB，資料庫名稱是 `simple_shop`。

後台帳號請使用伺服器環境變數 `SHOP_ADMIN_EMAIL` 與 `SHOP_ADMIN_PASSWORD` 設定。為了安全，密碼不會放在公開 repository。你目前的 XAMPP 資料庫已經有後台管理員資料。

首次使用可在 phpMyAdmin 選擇「匯入」，匯入 `database/schema.sql`。也可以直接開啟網站，後端會在 XAMPP 的 root 帳號有建庫權限時自動建立資料庫與示範商品。

如果你的 MySQL root 有設定密碼，請修改 `backend/config.php` 的 `password`，或設定 `SHOP_DB_PASSWORD` 環境變數。

## 其他啟動方式

專案已附上 PHP 8.5 x64 執行檔與 SQLite 設定，可用來啟動前台展示；使用 XAMPP 時建議直接使用 Apache＋MySQL／MariaDB。

在本資料夾執行：

直接雙擊 `start-shop.bat`，或在 PowerShell 執行：

```powershell
Set-ExecutionPolicy -Scope Process Bypass
.\start-shop.ps1
```

再開啟 http://127.0.0.1:8000 。第一次註冊即可測試完整購物流程。

如果要確認環境：

```powershell
.\check-environment.ps1
```

## 專案結構

```text
router.php          PHP 內建伺服器路由
start-shop.ps1      一鍵啟動網站
check-environment.ps1 環境檢查
tools/php/          專案自帶 PHP 8.5 x64 與 SQLite 設定
backend/api.php     JSON API 與商業邏輯
backend/db.php      MySQL／MariaDB 初始化與資料庫連線
database/schema.sql 資料庫 schema（可直接匯入 phpMyAdmin）
admin/index.php     商店後台頁面
admin/app.js        後台互動與 API 呼叫
admin/styles.css    後台響應式樣式
public/index.html   前端頁面
public/app.js       前端互動與 API 呼叫
public/styles.css   響應式視覺樣式
```

