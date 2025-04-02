# NUU FoodieMap

NUU FoodieMap 是一個專為國立聯合大學學生設計的美食地圖應用程式，幫助學生們探索校園周邊的美食。

## 功能特點

- 🗺️ 互動式地圖顯示校園周邊美食位置
- 🔍 搜尋功能：可依餐廳名稱、類型、評分等條件搜尋
- ⭐ 評分系統：學生可以為餐廳評分和評論
- 👥 用戶系統：個人收藏、評論歷史等功能
- 📱 響應式設計：支援手機和電腦瀏覽

## 技術架構

- 後端框架：Laravel
- 前端框架：Vue.js
- 資料庫：MySQL
- 地圖服務：Google Maps API

## 安裝步驟

1. 克隆專案
```bash
git clone https://github.com/yourusername/nuu-foodiemap.git
cd nuu-foodiemap
```

2. 安裝依賴
```bash
composer install
npm install
```

3. 環境設定
```bash
cp .env.example .env
php artisan key:generate
```

4. 設定資料庫
- 在 `.env` 檔案中設定資料庫連線資訊
- 執行資料庫遷移
```bash
php artisan migrate
```

5. 啟動開發伺服器
```bash
php artisan serve
npm run dev
```

## 使用說明

1. 註冊/登入
   - 使用學校信箱註冊帳號
   - 登入後即可使用完整功能

2. 瀏覽地圖
   - 在地圖上查看所有餐廳位置
   - 點擊標記查看詳細資訊

3. 搜尋功能
   - 使用搜尋欄位尋找特定餐廳
   - 使用篩選器縮小搜尋範圍

4. 評分與評論
   - 點擊餐廳查看詳細資訊
   - 在評論區留下評分和意見

## 開發團隊

- 前端開發：康康
- 後端開發：康康
- UI/UX 設計：康康

## 授權條款

本專案採用 MIT 授權條款 - 詳見 [LICENSE](LICENSE) 檔案

## 聯絡方式

如有任何問題或建議，請透過以下方式聯絡我們：
- Email：0714@hhk.one
- GitHub Issues：[issues page]

## 致謝

感謝所有參與開發的同學們，以及提供建議的師長們。 