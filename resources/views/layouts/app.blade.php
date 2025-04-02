<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#4CAF50">
    <meta name="description" content="NUU FoodieMap - 國立聯合大學美食地圖">
    <title>NUU FoodieMap - @yield('title')</title>
    
    <!-- PWA -->
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <link rel="apple-touch-icon" href="{{ asset('icons/icon-192x192.png') }}">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black">
    <meta name="apple-mobile-web-app-title" content="NUU FoodieMap">
    
    <!-- CSS -->
    <link rel="stylesheet" href="{{ asset('css/responsive.css') }}">
    @stack('styles')
    
    <!-- JavaScript -->
    <script src="{{ asset('js/app.js') }}" defer></script>
    @stack('scripts')

    <!-- PWA Service Worker -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js')
                    .then(registration => {
                        console.log('ServiceWorker 註冊成功:', registration.scope);
                    })
                    .catch(error => {
                        console.log('ServiceWorker 註冊失敗:', error);
                    });
            });
        }
    </script>
</head>
<body>
    <!-- 導航欄 -->
    <nav class="navbar">
        <div class="container">
            <button class="menu-toggle" id="menuToggle">
                <span></span>
                <span></span>
                <span></span>
            </button>
            <a href="/" class="logo">NUU FoodieMap</a>
            <div class="nav-links">
                <a href="/">首頁</a>
                <a href="/places">美食地圖</a>
                <a href="/reviews">評論</a>
                @auth
                    <a href="/profile">個人資料</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="btn btn-secondary">登出</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="btn btn-primary">登入</a>
                    <a href="{{ route('register') }}" class="btn btn-secondary">註冊</a>
                @endauth
            </div>
        </div>
    </nav>

    <!-- 側邊欄 -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-content">
            <div class="search-box">
                <input type="text" class="form-control" placeholder="搜尋餐廳...">
            </div>
            <div class="filter-section">
                <h3>篩選條件</h3>
                <div class="form-group">
                    <label>價格範圍</label>
                    <select class="form-control">
                        <option>全部</option>
                        <option>$</option>
                        <option>$$</option>
                        <option>$$$</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>評分</label>
                    <select class="form-control">
                        <option>全部</option>
                        <option>4星以上</option>
                        <option>3星以上</option>
                        <option>2星以上</option>
                    </select>
                </div>
            </div>
        </div>
    </aside>

    <!-- 主要內容區域 -->
    <main class="main-content">
        <div class="container">
            @yield('content')
        </div>
    </main>

    <!-- 頁尾 -->
    <footer class="footer">
        <div class="container">
            <p>&copy; {{ date('Y') }} NUU FoodieMap. All rights reserved.</p>
        </div>
    </footer>

    <!-- 移動端選單切換腳本 -->
    <script>
        document.getElementById('menuToggle').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('active');
        });

        // 點擊側邊欄外部時關閉選單
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const menuToggle = document.getElementById('menuToggle');
            
            if (!sidebar.contains(event.target) && !menuToggle.contains(event.target)) {
                sidebar.classList.remove('active');
            }
        });
    </script>
</body>
</html> 