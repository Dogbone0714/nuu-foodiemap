<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>聯大美食地圖</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Microsoft JhengHei', sans-serif;
            touch-action: none;
        }
        #map {
            height: 100vh;
            width: 100%;
            position: relative;
        }
        .category-filter {
            position: absolute;
            top: 20px;
            right: 20px;
            background: rgba(255, 255, 255, 0.98);
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            z-index: 1000;
            min-width: 250px;
            max-height: 80vh;
            overflow-y: auto;
            transform: translate3d(0, 0, 0);
            will-change: transform;
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }
        .category-filter h3 {
            margin: 0 0 15px 0;
            color: #333;
            font-size: 18px;
            border-bottom: 2px solid #4CAF50;
            padding-bottom: 10px;
            position: relative;
            overflow: hidden;
        }
        .category-filter h3::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 100%;
            height: 2px;
            background: #4CAF50;
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 0.3s ease;
        }
        .category-filter.active h3::after {
            transform: scaleX(1);
        }
        .category-checkbox {
            margin: 10px 0;
            display: flex;
            align-items: center;
            padding: 8px;
            border-radius: 5px;
            transform: translate3d(0, 0, 0);
            will-change: transform;
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            opacity: 1;
        }
        .category-checkbox:hover {
            background-color: #f5f5f5;
            transform: translate3d(5px, 0, 0);
        }
        .category-checkbox input[type="checkbox"] {
            display: none;
        }
        .category-checkbox label {
            display: flex;
            align-items: center;
            cursor: pointer;
            user-select: none;
            font-size: 14px;
            color: #555;
        }
        .category-checkbox label::before {
            content: '';
            display: inline-block;
            width: 18px;
            height: 18px;
            margin-right: 10px;
            border: 2px solid #4CAF50;
            border-radius: 3px;
            transition: all 0.3s;
        }
        .category-checkbox input[type="checkbox"]:checked + label::before {
            background-color: #4CAF50;
            content: '✓';
            color: white;
            text-align: center;
            line-height: 18px;
            font-size: 12px;
            transform: scale3d(1.1, 1.1, 1);
        }
        .category-checkbox input[type="checkbox"]:checked + label {
            color: #4CAF50;
            font-weight: 500;
        }
        .category-icon {
            margin-right: 8px;
            color: #4CAF50;
        }
        .search-box {
            margin-bottom: 15px;
            position: relative;
        }
        .search-box input {
            width: 100%;
            padding: 10px 35px 10px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        .search-box input:focus {
            outline: none;
            border-color: #4CAF50;
        }
        .search-box i {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
        }
        .marker-popup {
            padding: 10px;
        }
        .marker-popup h3 {
            margin: 0 0 10px 0;
            color: #333;
            font-size: 16px;
        }
        .marker-popup p {
            margin: 5px 0;
            color: #666;
            font-size: 14px;
        }
        .marker-popup .category-tag {
            display: inline-block;
            background-color: #e8f5e9;
            color: #4CAF50;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 12px;
            margin-top: 5px;
        }
        .loading {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(255, 255, 255, 0.9);
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            display: none;
            z-index: 1001;
        }
        .loading i {
            color: #4CAF50;
            font-size: 24px;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .no-results {
            text-align: center;
            padding: 20px;
            color: #666;
            display: none;
        }
        .mobile-toggle {
            display: none;
            position: absolute;
            top: 20px;
            right: 20px;
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            font-size: 20px;
            cursor: pointer;
            z-index: 1002;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            transform: translate3d(0, 0, 0);
            will-change: transform;
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .mobile-toggle:hover {
            transform: scale3d(1.1, 1.1, 1);
        }
        .mobile-toggle.active {
            transform: rotate3d(0, 0, 1, 180deg);
        }
        .mobile-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 999;
            opacity: 0;
            transform: translate3d(0, 0, 0);
            will-change: opacity;
            transition: opacity 0.3s ease;
        }
        .mobile-overlay.active {
            display: block;
            opacity: 1;
        }

        /* 移動端樣式 */
        @media (max-width: 768px) {
            .category-filter {
                display: none; /* 默認隱藏清單 */
            }

            .category-filter.active {
                display: block; /* 只在選單打開時顯示 */
                position: fixed;
                top: 0;
                right: 0;
                width: 80%;
                max-width: 300px;
                height: 100vh;
                margin: 0;
                border-radius: 0;
                transform: translate3d(100%, 0, 0);
                will-change: transform;
                transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                box-shadow: -2px 0 15px rgba(0,0,0,0.1);
                overflow-y: auto;
                -webkit-overflow-scrolling: touch;
                overscroll-behavior: contain;
                z-index: 1000;
            }

            .category-filter.closing {
                transform: translate3d(100%, 0, 0);
            }

            /* 優化移動端按鈕樣式 */
            .mobile-toggle {
                display: block;
                position: fixed;
                top: 20px;
                right: 20px;
                background: #4CAF50;
                color: white;
                border: none;
                border-radius: 50%;
                width: 40px;
                height: 40px;
                font-size: 20px;
                cursor: pointer;
                z-index: 1002;
                box-shadow: 0 2px 5px rgba(0,0,0,0.2);
                transform: translate3d(0, 0, 0);
                will-change: transform;
                transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            }

            .mobile-toggle.active {
                transform: rotate3d(0, 0, 1, 180deg);
            }

            .mobile-toggle.closing {
                transform: rotate3d(0, 0, 1, 0deg);
            }

            /* 優化遮罩層樣式 */
            .mobile-overlay {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0,0,0,0.5);
                z-index: 999;
                opacity: 0;
                transform: translate3d(0, 0, 0);
                will-change: opacity;
                transition: opacity 0.3s ease;
                backdrop-filter: blur(3px);
                -webkit-backdrop-filter: blur(3px);
            }

            .mobile-overlay.active {
                display: block;
                opacity: 1;
            }

            .mobile-overlay.closing {
                opacity: 0;
            }

            /* 優化地圖容器樣式 */
            #map {
                height: 100vh;
                width: 100%;
                position: relative;
                z-index: 1;
            }

            /* 優化標記彈窗樣式 */
            .marker-popup {
                max-width: 90vw;
            }

            .leaflet-popup-content-wrapper {
                max-width: 90vw;
            }

            .leaflet-popup-content {
                margin: 10px;
            }

            .leaflet-popup-tip {
                display: none;
            }
        }

        /* 防止移動端雙擊縮放 */
        * {
            touch-action: manipulation;
        }

        /* 添加滾動條樣式 */
        .category-filter::-webkit-scrollbar {
            width: 6px;
        }
        .category-filter::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }
        .category-filter::-webkit-scrollbar-thumb {
            background: #4CAF50;
            border-radius: 3px;
        }
        .category-filter::-webkit-scrollbar-thumb:hover {
            background: #45a049;
        }

        /* 優化滾動性能 */
        .category-filter {
            -webkit-overflow-scrolling: touch;
            scroll-behavior: smooth;
        }

        /* 圖片加載相關樣式 */
        .lazy-image {
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .lazy-image.loaded {
            opacity: 1;
        }

        .image-placeholder {
            background: #f0f0f0;
            position: relative;
            overflow: hidden;
        }

        .image-placeholder::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: shimmer 1.5s infinite;
        }

        @keyframes shimmer {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }

        .image-error {
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8f8f8;
            color: #999;
            font-size: 14px;
        }

        .image-error i {
            margin-right: 5px;
        }

        /* 圖片加載進度條樣式 */
        .image-progress {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 2px;
            background: rgba(255, 255, 255, 0.2);
            overflow: hidden;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .image-progress-bar {
            position: absolute;
            top: 0;
            left: 0;
            height: 100%;
            width: 0;
            background: linear-gradient(90deg, #4CAF50, #45a049);
            transition: width 0.3s ease;
        }

        .image-progress.loading {
            opacity: 1;
        }

        .image-progress.complete {
            opacity: 0;
            transition: opacity 0.5s ease;
        }

        .image-container {
            position: relative;
            overflow: hidden;
        }

        /* 優化標記圖標樣式 */
        .custom-marker {
            position: relative;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .custom-marker img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }

        /* 添加加載動畫 */
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }

        .custom-marker.loading {
            animation: pulse 1s infinite;
        }

        .filter-options {
            margin-bottom: 15px;
            padding: 10px;
            background: #f8f8f8;
            border-radius: 5px;
        }

        .filter-group {
            margin-bottom: 10px;
        }

        .filter-group:last-child {
            margin-bottom: 0;
        }

        .filter-group label {
            display: block;
            margin-bottom: 5px;
            color: #666;
            font-size: 14px;
        }

        .filter-group select {
            width: 100%;
            padding: 8px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 14px;
            color: #333;
            background-color: white;
            transition: border-color 0.3s;
        }

        .filter-group select:focus {
            outline: none;
            border-color: #4CAF50;
        }

        .filter-group select option {
            padding: 8px;
        }

        /* 優化移動端篩選選項樣式 */
        @media (max-width: 768px) {
            .filter-options {
                margin: 10px 0;
                padding: 8px;
            }

            .filter-group select {
                padding: 10px;
                font-size: 16px;
            }
        }
    </style>
</head>
<body>
    <div id="map"></div>
    <button class="mobile-toggle" id="mobileToggle">
        <i class="fas fa-bars"></i>
    </button>
    <div class="mobile-overlay" id="mobileOverlay"></div>
    <div class="category-filter" id="categoryFilter">
        <div class="search-box">
            <input type="text" id="placeSearch" placeholder="搜尋地點...">
            <i class="fas fa-search"></i>
        </div>
        <div class="filter-options">
            <div class="filter-group">
                <label>距離</label>
                <select id="distanceFilter">
                    <option value="">全部</option>
                    <option value="1">1公里內</option>
                    <option value="3">3公里內</option>
                    <option value="5">5公里內</option>
                    <option value="10">10公里內</option>
                </select>
            </div>
            <div class="filter-group">
                <label>評分</label>
                <select id="ratingFilter">
                    <option value="">全部</option>
                    <option value="4">4星以上</option>
                    <option value="3">3星以上</option>
                    <option value="2">2星以上</option>
                    <option value="1">1星以上</option>
                </select>
            </div>
        </div>
        <h3>景點類別</h3>
        <div id="categoryList">
            <!-- 類別選項將由 JavaScript 動態生成 -->
        </div>
        <div class="no-results">沒有找到相關地點</div>
    </div>
    <div class="loading">
        <i class="fas fa-spinner"></i>
    </div>

    <script>
        // 安全防護代碼
        (function() {
            // 禁用右鍵選單
            document.addEventListener('contextmenu', function(e) {
                e.preventDefault();
                return false;
            });

            // 禁用開發者工具快捷鍵
            document.addEventListener('keydown', function(e) {
                // 禁用 F12
                if (e.keyCode === 123) {
                    e.preventDefault();
                    return false;
                }
                // 禁用 Ctrl+Shift+I
                if (e.ctrlKey && e.shiftKey && e.keyCode === 73) {
                    e.preventDefault();
                    return false;
                }
                // 禁用 Ctrl+Shift+J
                if (e.ctrlKey && e.shiftKey && e.keyCode === 74) {
                    e.preventDefault();
                    return false;
                }
                // 禁用 Ctrl+U
                if (e.ctrlKey && e.keyCode === 85) {
                    e.preventDefault();
                    return false;
                }
            });

            // 檢測開發者工具開啟
            let devtools = function() {};
            devtools.toString = function() {
                window.location.href = '/';
                return '';
            };

            // 定期檢查開發者工具
            setInterval(function() {
                console.log(devtools);
                console.clear();
            }, 1000);

            // 混淆關鍵變量
            const _0x4f2a = ['lat', 'lng', 'validateCoordinates', 'formatCoordinates', 'calculateDistance'];
            const _0x1b3c = {
                'lat': _0x4f2a[0],
                'lng': _0x4f2a[1],
                'validate': _0x4f2a[2],
                'format': _0x4f2a[3],
                'distance': _0x4f2a[4]
            };

            // 安全檢查函數
            function securityCheck() {
                // 檢查是否在 iframe 中
                if (window.self !== window.top) {
                    window.top.location = window.self.location;
                }

                // 檢查是否啟用了 JavaScript
                if (typeof window === 'undefined') {
                    window.location.href = '/';
                }

                // 檢查是否啟用了 cookies
                if (!navigator.cookieEnabled) {
                    window.location.href = '/';
                }
            }

            // 定期執行安全檢查
            setInterval(securityCheck, 5000);

            // 添加 CSP 頭
            const meta = document.createElement('meta');
            meta.httpEquiv = 'Content-Security-Policy';
            meta.content = "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://unpkg.com https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com; img-src 'self' data: https:; font-src 'self' https://cdnjs.cloudflare.com;";
            document.head.appendChild(meta);

            // 禁用拖放
            document.addEventListener('dragstart', function(e) {
                e.preventDefault();
                return false;
            });

            // 禁用選擇
            document.addEventListener('selectstart', function(e) {
                e.preventDefault();
                return false;
            });

            // 禁用複製
            document.addEventListener('copy', function(e) {
                e.preventDefault();
                return false;
            });

            // 添加錯誤處理
            window.onerror = function(msg, url, lineNo, columnNo, error) {
                console.log('Error: ' + msg + '\nURL: ' + url + '\nLine: ' + lineNo + '\nColumn: ' + columnNo + '\nError object: ' + JSON.stringify(error));
                return false;
            };

            // 添加警告處理
            window.onwarn = function(msg, url, lineNo, columnNo, error) {
                console.log('Warning: ' + msg + '\nURL: ' + url + '\nLine: ' + lineNo + '\nColumn: ' + columnNo + '\nError object: ' + JSON.stringify(error));
                return false;
            };
        })();

        // 優化坐標處理工具類
        class CoordinateUtils {
            static validateCoordinates(lat, lng) {
                return (
                    typeof lat === 'number' &&
                    typeof lng === 'number' &&
                    lat >= -90 &&
                    lat <= 90 &&
                    lng >= -180 &&
                    lng <= 180
                );
            }

            static formatCoordinates(lat, lng) {
                return {
                    lat: Number(lat.toFixed(6)),
                    lng: Number(lng.toFixed(6))
                };
            }

            static calculateDistance(lat1, lng1, lat2, lng2) {
                const R = 6371; // 地球半徑（公里）
                const dLat = this.toRad(lat2 - lat1);
                const dLng = this.toRad(lng2 - lng1);
                const a = 
                    Math.sin(dLat/2) * Math.sin(dLat/2) +
                    Math.cos(this.toRad(lat1)) * Math.cos(this.toRad(lat2)) * 
                    Math.sin(dLng/2) * Math.sin(dLng/2);
                const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
                return R * c;
            }

            static toRad(value) {
                return value * Math.PI / 180;
            }

            static parseCoordinates(lat, lng) {
                // 處理字符串格式的坐標
                if (typeof lat === 'string') {
                    lat = parseFloat(lat);
                }
                if (typeof lng === 'string') {
                    lng = parseFloat(lng);
                }
                return { lat, lng };
            }
        }

        // 優化地圖初始化
        var map = L.map('map', {
            zoomControl: false,
            tap: true,
            maxBounds: [
                [24.4458273, 120.78979], // 西南角
                [24.6458273, 120.82979]  // 東北角
            ],
            maxBoundsViscosity: 1.0
        }).setView([24.5458273, 120.80979], 13);
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
        
        // 將縮放控制移到右下角
        L.control.zoom({
            position: 'bottomright'
        }).addTo(map);
        
        var markers = [];
        var selectedCategories = new Set();
        var allCategories = [];

        // 移動端選單控制
        const mobileToggle = document.getElementById('mobileToggle');
        const categoryFilter = document.getElementById('categoryFilter');
        const mobileOverlay = document.getElementById('mobileOverlay');

        function toggleMobileMenu() {
            const isOpening = !categoryFilter.classList.contains('active');
            
            if (isOpening) {
                // 打開選單
                categoryFilter.style.display = 'block';
                requestAnimationFrame(() => {
                    categoryFilter.classList.add('active');
                    mobileOverlay.classList.add('active');
                    mobileToggle.classList.add('active');
                });
                
                // 重置動畫
                const checkboxes = document.querySelectorAll('.category-checkbox');
                checkboxes.forEach((checkbox, index) => {
                    checkbox.style.animation = 'none';
                    checkbox.offsetHeight; // 觸發重排
                    checkbox.style.animation = `slideIn 0.3s ease forwards ${index * 0.1}s`;
                });
            } else {
                // 關閉選單
                categoryFilter.classList.add('closing');
                mobileOverlay.classList.add('closing');
                mobileToggle.classList.add('closing');
                
                // 動畫結束後移除類別和隱藏元素
                setTimeout(() => {
                    categoryFilter.classList.remove('active', 'closing');
                    mobileOverlay.classList.remove('active', 'closing');
                    mobileToggle.classList.remove('active', 'closing');
                    categoryFilter.style.display = 'none';
                }, 300);
            }
        }

        mobileToggle.addEventListener('click', toggleMobileMenu);
        mobileOverlay.addEventListener('click', toggleMobileMenu);

        // 顯示/隱藏載入動畫
        function toggleLoading(show) {
            document.querySelector('.loading').style.display = show ? 'block' : 'none';
        }

        // 獲取所有類別
        fetch('/api/categories')
            .then(response => response.json())
            .then(categories => {
                allCategories = categories;
                renderCategories(categories);
            });

        // 渲染類別列表
        function renderCategories(categories) {
            const categoryList = document.getElementById('categoryList');
            const fragment = document.createDocumentFragment();
            
            if (categories.length === 0) {
                document.querySelector('.no-results').style.display = 'block';
                return;
            }
            
            document.querySelector('.no-results').style.display = 'none';
            
            categories.forEach((category, index) => {
                const div = document.createElement('div');
                div.className = 'category-checkbox';
                div.style.animationDelay = `${index * 0.1}s`;
                div.innerHTML = `
                    <input type="checkbox" id="category${category.id}" value="${category.id}">
                    <label for="category${category.id}">
                        <i class="fas ${category.icon || 'fa-map-marker-alt'} category-icon"></i>
                        ${category.name}
                    </label>
                `;
                fragment.appendChild(div);
            });
            
            categoryList.innerHTML = '';
            categoryList.appendChild(fragment);
        }

        // 添加篩選相關變量
        let allPlaces = [];
        let searchTimeout;
        let currentFilters = {
            search: '',
            distance: '',
            rating: '',
            categories: new Set()
        };

        // 優化獲取地點函數
        function fetchPlaces() {
            toggleLoading(true);
            return fetch('/api/places')
                .then(response => response.json())
                .then(data => {
                    toggleLoading(false);
                    allPlaces = data;
                    return filterPlaces(data);
                })
                .catch(error => {
                    toggleLoading(false);
                    console.error('Error fetching places:', error);
                    return [];
                });
        }

        // 添加篩選函數
        function filterPlaces(places) {
            return places.filter(place => {
                // 搜尋文字篩選
                if (currentFilters.search && 
                    !place.name.toLowerCase().includes(currentFilters.search.toLowerCase()) &&
                    !place.description.toLowerCase().includes(currentFilters.search.toLowerCase())) {
                    return false;
                }

                // 評分篩選
                if (currentFilters.rating && place.rating < parseFloat(currentFilters.rating)) {
                    return false;
                }

                // 距離篩選
                if (currentFilters.distance) {
                    const distance = CoordinateUtils.calculateDistance(
                        map.getCenter().lat,
                        map.getCenter().lng,
                        place.lat,
                        place.lng
                    );
                    if (distance > parseFloat(currentFilters.distance)) {
                        return false;
                    }
                }

                // 類別篩選
                if (currentFilters.categories.size > 0 && 
                    !currentFilters.categories.has(place.category_id)) {
                    return false;
                }

                return true;
            });
        }

        // 添加篩選事件監聽
        document.getElementById('placeSearch').addEventListener('input', function(e) {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                currentFilters.search = e.target.value;
                updateMarkers();
            }, 300);
        });

        document.getElementById('distanceFilter').addEventListener('change', function(e) {
            currentFilters.distance = e.target.value;
            updateMarkers();
        });

        document.getElementById('ratingFilter').addEventListener('change', function(e) {
            currentFilters.rating = e.target.value;
            updateMarkers();
        });

        // 優化類別選擇事件
        document.getElementById('categoryList').addEventListener('change', function(e) {
            if (e.target.type === 'checkbox') {
                if (e.target.checked) {
                    currentFilters.categories.add(e.target.value);
                } else {
                    currentFilters.categories.delete(e.target.value);
                }
                updateMarkers();
            }
        });

        // 優化地圖移動事件
        map.on('moveend', function() {
            if (currentFilters.distance) {
                updateMarkers();
            }
        });

        // 優化更新標記函數
        function updateMarkers() {
            requestAnimationFrame(() => {
                markers.forEach(marker => map.removeLayer(marker));
                markers = [];

                const filteredPlaces = filterPlaces(allPlaces);
                
                if (filteredPlaces.length === 0) {
                    document.querySelector('.no-results').style.display = 'block';
                } else {
                    document.querySelector('.no-results').style.display = 'none';
                }

                filteredPlaces.forEach(place => {
                    // 解析並驗證坐標
                    const coords = CoordinateUtils.parseCoordinates(place.lat, place.lng);
                    if (!CoordinateUtils.validateCoordinates(coords.lat, coords.lng)) {
                        console.error('Invalid coordinates for place:', place);
                        return;
                    }

                    // 格式化坐標
                    const formattedCoords = CoordinateUtils.formatCoordinates(coords.lat, coords.lng);

                    if (selectedCategories.size === 0 || selectedCategories.has(place.category_id)) {
                        const markerIcon = createMarkerIcon(place);
                        if (!markerIcon) return;

                        const marker = L.marker([formattedCoords.lat, formattedCoords.lng], {
                            icon: markerIcon
                        })
                        .addTo(map)
                        .bindPopup(`
                            <div class="marker-popup">
                                <h3>${place.name}</h3>
                                <p>${place.description}</p>
                                <span class="category-tag">
                                    <i class="fas fa-tag"></i> ${place.category_name}
                                </span>
                            </div>
                        `);

                        // 添加點擊事件
                        marker.on('click', function(e) {
                            // 使用原始坐標進行跳轉
                            map.setView([coords.lat, coords.lng], 15);
                            marker.openPopup();
                        });

                        // 觀察標記圖標的圖片加載
                        const markerImage = marker.getElement().querySelector('img');
                        if (markerImage) {
                            imageLoader.observe(markerImage);
                        }

                        markers.push(marker);
                    }
                });
            });
        }

        // 初始載入所有標記
        updateMarkers();

        // 優化地圖點擊事件
        map.on('click', function(e) {
            const coords = CoordinateUtils.formatCoordinates(e.latlng.lat, e.latlng.lng);
            console.log('Clicked coordinates:', coords);
        });

        // 優化地圖移動事件
        map.on('moveend', function() {
            const center = map.getCenter();
            const coords = CoordinateUtils.formatCoordinates(center.lat, center.lng);
            console.log('Map center:', coords);
        });

        // 防止移動端雙擊縮放
        map.doubleClickZoom.disable();

        // 添加坐標錯誤處理
        window.addEventListener('error', function(e) {
            if (e.message.includes('coordinates') || e.message.includes('latlng')) {
                console.error('Coordinate error:', e);
            }
        });

        // 防止選單內容滾動時觸發滑動關閉
        categoryFilter.addEventListener('scroll', () => {
            isSwiping = false;
        }, { passive: true });

        // 點擊遮罩層關閉選單
        mobileOverlay.addEventListener('click', () => {
            if (categoryFilter.classList.contains('active')) {
                toggleMobileMenu();
            }
        });

        // 防止選單內容點擊事件冒泡
        categoryFilter.addEventListener('click', e => {
            e.stopPropagation();
        });

        // 預加載關鍵圖片
        function preloadCriticalImages() {
            const criticalImages = [
                '/images/default-marker.png',
                '/images/logo.png'
            ];

            criticalImages.forEach(url => {
                imageLoader.preloadImage(url);
            });
        }

        // 頁面加載完成後預加載關鍵圖片
        document.addEventListener('DOMContentLoaded', preloadCriticalImages);

        // 添加圖片加載錯誤處理
        window.addEventListener('error', function(e) {
            if (e.target.tagName === 'IMG') {
                e.target.classList.remove('image-placeholder');
                e.target.classList.add('image-error');
                e.target.innerHTML = '<i class="fas fa-image"></i> 圖片加載失敗';
            }
        }, true);
    </script>
</body>
</html>
