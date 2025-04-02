<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>聯大美食地圖</title>
    
    <!-- 外部資源引入 -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>

    <style>
        /* 基礎樣式 */
        :root {
            --primary-color: #4CAF50;
            --primary-hover: #45a049;
            --text-color: #333;
            --text-light: #666;
            --bg-light: #f8f8f8;
            --border-color: #e0e0e0;
            --shadow: 0 2px 15px rgba(0,0,0,0.1);
            --transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        body {
            margin: 0;
            padding: 0;
            font-family: 'Microsoft JhengHei', sans-serif;
            touch-action: none;
        }

        /* 地圖容器 */
        #map {
            height: 100vh;
            width: 100%;
            position: relative;
        }

        /* 篩選面板 */
        .category-filter {
            position: absolute;
            top: 20px;
            right: 20px;
            background: rgba(255, 255, 255, 0.98);
            padding: 20px;
            border-radius: 10px;
            box-shadow: var(--shadow);
            z-index: 1000;
            min-width: 250px;
            max-height: 80vh;
            overflow-y: auto;
            transform: translate3d(0, 0, 0);
            will-change: transform;
            transition: var(--transition);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }

        /* 搜尋框 */
        .search-box {
            margin-bottom: 15px;
            position: relative;
        }

        .search-box input {
            width: 100%;
            padding: 10px 35px 10px 15px;
            border: 2px solid var(--border-color);
            border-radius: 5px;
            font-size: 14px;
            transition: border-color var(--transition);
        }

        .search-box input:focus {
            outline: none;
            border-color: var(--primary-color);
        }

        .search-box i {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
        }

        /* 篩選選項 */
        .filter-options {
            margin-bottom: 15px;
            padding: 10px;
            background: var(--bg-light);
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
            color: var(--text-light);
            font-size: 14px;
        }

        .filter-group select {
            width: 100%;
            padding: 8px;
            border: 2px solid var(--border-color);
            border-radius: 5px;
            font-size: 14px;
            color: var(--text-color);
            background-color: white;
            transition: border-color var(--transition);
        }

        .filter-group select:focus {
            outline: none;
            border-color: var(--primary-color);
        }

        /* 類別列表 */
        .category-checkbox {
            margin: 10px 0;
            display: flex;
            align-items: center;
            padding: 8px;
            border-radius: 5px;
            transform: translate3d(0, 0, 0);
            will-change: transform;
            transition: var(--transition);
            opacity: 1;
        }

        .category-checkbox:hover {
            background-color: var(--bg-light);
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
            color: var(--text-color);
        }

        .category-checkbox label::before {
            content: '';
            display: inline-block;
            width: 18px;
            height: 18px;
            margin-right: 10px;
            border: 2px solid var(--primary-color);
            border-radius: 3px;
            transition: var(--transition);
        }

        .category-checkbox input[type="checkbox"]:checked + label::before {
            background-color: var(--primary-color);
            content: '✓';
            color: white;
            text-align: center;
            line-height: 18px;
            font-size: 12px;
            transform: scale3d(1.1, 1.1, 1);
        }

        .category-checkbox input[type="checkbox"]:checked + label {
            color: var(--primary-color);
            font-weight: 500;
        }

        /* 標記彈窗 */
        .marker-popup {
            padding: 10px;
        }

        .marker-popup h3 {
            margin: 0 0 10px 0;
            color: var(--text-color);
            font-size: 16px;
        }

        .marker-popup p {
            margin: 5px 0;
            color: var(--text-light);
            font-size: 14px;
        }

        .marker-popup .category-tag {
            display: inline-block;
            background-color: #e8f5e9;
            color: var(--primary-color);
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 12px;
            margin-top: 5px;
        }

        /* 載入動畫 */
        .loading {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(255, 255, 255, 0.9);
            padding: 20px;
            border-radius: 10px;
            box-shadow: var(--shadow);
            display: none;
            z-index: 1001;
        }

        .loading i {
            color: var(--primary-color);
            font-size: 24px;
            animation: spin 1s linear infinite;
        }

        /* 移動端樣式 */
        @media (max-width: 768px) {
            .category-filter {
                display: none;
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
                transition: var(--transition);
                box-shadow: -2px 0 15px rgba(0,0,0,0.1);
                overflow-y: auto;
                -webkit-overflow-scrolling: touch;
                overscroll-behavior: contain;
                z-index: 1000;
            }

            .category-filter.active {
                display: block;
                transform: translate3d(0, 0, 0);
            }

            .mobile-toggle {
                display: block;
                position: fixed;
                top: 20px;
                right: 20px;
                background: var(--primary-color);
                color: white;
                border: none;
                border-radius: 50%;
                width: 40px;
                height: 40px;
                font-size: 20px;
                cursor: pointer;
                z-index: 1002;
                box-shadow: var(--shadow);
                transform: translate3d(0, 0, 0);
                will-change: transform;
                transition: var(--transition);
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
                transition: opacity var(--transition);
                backdrop-filter: blur(3px);
                -webkit-backdrop-filter: blur(3px);
            }

            .mobile-overlay.active {
                display: block;
                opacity: 1;
            }
        }

        /* 動畫效果 */
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        /* 滾動條樣式 */
        .category-filter::-webkit-scrollbar {
            width: 6px;
        }

        .category-filter::-webkit-scrollbar-track {
            background: var(--bg-light);
            border-radius: 3px;
        }

        .category-filter::-webkit-scrollbar-thumb {
            background: var(--primary-color);
            border-radius: 3px;
        }

        .category-filter::-webkit-scrollbar-thumb:hover {
            background: var(--primary-hover);
        }
    </style>
</head>
<body>
    <!-- 地圖容器 -->
    <div id="map"></div>

    <!-- 移動端控制按鈕 -->
    <button class="mobile-toggle" id="mobileToggle">
        <i class="fas fa-bars"></i>
    </button>

    <!-- 移動端遮罩層 -->
    <div class="mobile-overlay" id="mobileOverlay"></div>

    <!-- 篩選面板 -->
    <div class="category-filter" id="categoryFilter">
        <!-- 搜尋框 -->
        <div class="search-box">
            <input type="text" id="placeSearch" placeholder="搜尋地點...">
            <i class="fas fa-search"></i>
        </div>

        <!-- 篩選選項 -->
        <div class="filter-options">
            <!-- 距離篩選 -->
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

            <!-- 評分篩選 -->
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

        <!-- 類別列表 -->
        <h3>景點類別</h3>
        <div id="categoryList">
            <!-- 類別選項將由 JavaScript 動態生成 -->
        </div>

        <!-- 無結果提示 -->
        <div class="no-results">沒有找到相關地點</div>
    </div>

    <!-- 載入動畫 -->
    <div class="loading">
        <i class="fas fa-spinner"></i>
    </div>

    <script>
        // 安全防護代碼
        (function() {
            // 禁用右鍵選單
            document.addEventListener('contextmenu', e => e.preventDefault());

            // 禁用開發者工具快捷鍵
            document.addEventListener('keydown', e => {
                if (e.keyCode === 123 || // F12
                    (e.ctrlKey && e.shiftKey && e.keyCode === 73) || // Ctrl+Shift+I
                    (e.ctrlKey && e.shiftKey && e.keyCode === 74) || // Ctrl+Shift+J
                    (e.ctrlKey && e.keyCode === 85)) { // Ctrl+U
                    e.preventDefault();
                }
            });

            // 檢測開發者工具開啟
            const devtools = {
                toString: () => {
                    window.location.href = '/';
                    return '';
                }
            };

            // 定期檢查開發者工具
            setInterval(() => {
                console.log(devtools);
                console.clear();
            }, 1000);

            // 安全檢查函數
            const securityCheck = () => {
                if (window.self !== window.top || 
                    typeof window === 'undefined' || 
                    !navigator.cookieEnabled) {
                    window.location.href = '/';
                }
            };

            // 定期執行安全檢查
            setInterval(securityCheck, 5000);

            // 添加 CSP 頭
            const meta = document.createElement('meta');
            meta.httpEquiv = 'Content-Security-Policy';
            meta.content = "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://unpkg.com https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com; img-src 'self' data: https:; font-src 'self' https://cdnjs.cloudflare.com;";
            document.head.appendChild(meta);

            // 禁用拖放、選擇和複製
            ['dragstart', 'selectstart', 'copy'].forEach(event => {
                document.addEventListener(event, e => e.preventDefault());
            });

            // 錯誤處理
            window.onerror = (msg, url, lineNo, columnNo, error) => {
                console.log(`Error: ${msg}\nURL: ${url}\nLine: ${lineNo}\nColumn: ${columnNo}\nError object: ${JSON.stringify(error)}`);
                return false;
            };

            window.onwarn = (msg, url, lineNo, columnNo, error) => {
                console.log(`Warning: ${msg}\nURL: ${url}\nLine: ${lineNo}\nColumn: ${columnNo}\nError object: ${JSON.stringify(error)}`);
                return false;
            };
        })();

        // 坐標處理工具類
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
                return {
                    lat: typeof lat === 'string' ? parseFloat(lat) : lat,
                    lng: typeof lng === 'string' ? parseFloat(lng) : lng
                };
            }
        }

        // 地圖初始化
        const map = L.map('map', {
            zoomControl: false,
            tap: true,
            maxBounds: [
                [24.4458273, 120.78979], // 西南角
                [24.6458273, 120.82979]  // 東北角
            ],
            maxBoundsViscosity: 1.0
        }).setView([24.5458273, 120.80979], 13);

        // 添加地圖圖層
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
        L.control.zoom({ position: 'bottomright' }).addTo(map);

        // 全局變量
        let markers = [];
        let selectedCategories = new Set();
        let allCategories = [];
        let allPlaces = [];
        let searchTimeout;
        let currentFilters = {
            search: '',
            distance: '',
            rating: '',
            categories: new Set()
        };

        // DOM 元素
        const mobileToggle = document.getElementById('mobileToggle');
        const categoryFilter = document.getElementById('categoryFilter');
        const mobileOverlay = document.getElementById('mobileOverlay');

        // 移動端選單控制
        function toggleMobileMenu() {
            const isOpening = !categoryFilter.classList.contains('active');
            
            if (isOpening) {
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
                categoryFilter.classList.add('closing');
                mobileOverlay.classList.add('closing');
                mobileToggle.classList.add('closing');
                
                setTimeout(() => {
                    categoryFilter.classList.remove('active', 'closing');
                    mobileOverlay.classList.remove('active', 'closing');
                    mobileToggle.classList.remove('active', 'closing');
                    categoryFilter.style.display = 'none';
                }, 300);
            }
        }

        // 事件監聽器
        mobileToggle.addEventListener('click', toggleMobileMenu);
        mobileOverlay.addEventListener('click', toggleMobileMenu);
        categoryFilter.addEventListener('scroll', () => isSwiping = false, { passive: true });
        categoryFilter.addEventListener('click', e => e.stopPropagation());

        // 載入動畫控制
        function toggleLoading(show) {
            document.querySelector('.loading').style.display = show ? 'block' : 'none';
        }

        // 獲取類別列表
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

        // 獲取地點列表
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

        // 篩選地點
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

        // 篩選事件監聽
        document.getElementById('placeSearch').addEventListener('input', e => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                currentFilters.search = e.target.value;
                updateMarkers();
            }, 300);
        });

        document.getElementById('distanceFilter').addEventListener('change', e => {
            currentFilters.distance = e.target.value;
            updateMarkers();
        });

        document.getElementById('ratingFilter').addEventListener('change', e => {
            currentFilters.rating = e.target.value;
            updateMarkers();
        });

        document.getElementById('categoryList').addEventListener('change', e => {
            if (e.target.type === 'checkbox') {
                if (e.target.checked) {
                    currentFilters.categories.add(e.target.value);
                } else {
                    currentFilters.categories.delete(e.target.value);
                }
                updateMarkers();
            }
        });

        // 地圖事件
        map.on('moveend', () => {
            if (currentFilters.distance) {
                updateMarkers();
            }
        });

        map.on('click', e => {
            const coords = CoordinateUtils.formatCoordinates(e.latlng.lat, e.latlng.lng);
            console.log('Clicked coordinates:', coords);
        });

        // 更新地圖標記
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
                    const coords = CoordinateUtils.parseCoordinates(place.lat, place.lng);
                    if (!CoordinateUtils.validateCoordinates(coords.lat, coords.lng)) {
                        console.error('Invalid coordinates for place:', place);
                        return;
                    }

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

                        marker.on('click', () => {
                            map.setView([coords.lat, coords.lng], 15);
                            marker.openPopup();
                        });

                        const markerImage = marker.getElement().querySelector('img');
                        if (markerImage) {
                            imageLoader.observe(markerImage);
                        }

                        markers.push(marker);
                    }
                });
            });
        }

        // 初始化
        updateMarkers();
        map.doubleClickZoom.disable();

        // 錯誤處理
        window.addEventListener('error', e => {
            if (e.message.includes('coordinates') || e.message.includes('latlng')) {
                console.error('Coordinate error:', e);
            }
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

        // 圖片加載錯誤處理
        window.addEventListener('error', e => {
            if (e.target.tagName === 'IMG') {
                e.target.classList.remove('image-placeholder');
                e.target.classList.add('image-error');
                e.target.innerHTML = '<i class="fas fa-image"></i> 圖片加載失敗';
            }
        }, true);
    </script>
</body>
</html>
