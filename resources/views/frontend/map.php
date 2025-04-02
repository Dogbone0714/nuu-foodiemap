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
            background: rgba(255, 255, 255, 0.95);
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            z-index: 1000;
            min-width: 250px;
            max-height: 80vh;
            overflow-y: auto;
            transition: transform 0.3s ease;
        }
        .category-filter h3 {
            margin: 0 0 15px 0;
            color: #333;
            font-size: 18px;
            border-bottom: 2px solid #4CAF50;
            padding-bottom: 10px;
        }
        .category-checkbox {
            margin: 10px 0;
            display: flex;
            align-items: center;
            padding: 8px;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        .category-checkbox:hover {
            background-color: #f5f5f5;
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
        }
        .mobile-toggle i {
            transition: transform 0.3s;
        }
        .mobile-toggle.active i {
            transform: rotate(180deg);
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
        }
        .mobile-overlay.active {
            display: block;
        }

        /* 移動端樣式 */
        @media (max-width: 768px) {
            .mobile-toggle {
                display: block;
            }
            .category-filter {
                position: fixed;
                top: 0;
                right: -100%;
                width: 80%;
                max-width: 300px;
                height: 100vh;
                margin: 0;
                border-radius: 0;
                transition: right 0.3s ease;
            }
            .category-filter.active {
                right: 0;
            }
            .mobile-overlay.active {
                display: block;
            }
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
            <input type="text" id="categorySearch" placeholder="搜尋類別...">
            <i class="fas fa-search"></i>
        </div>
        <h3>景點類別</h3>
        <div id="categoryList">
            <!-- 類別選項將由 JavaScript 動態生成 -->
        </div>
        <div class="no-results">沒有找到相關類別</div>
    </div>
    <div class="loading">
        <i class="fas fa-spinner"></i>
    </div>

    <script>
        var map = L.map('map', {
            zoomControl: false,
            tap: true
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
            mobileToggle.classList.toggle('active');
            categoryFilter.classList.toggle('active');
            mobileOverlay.classList.toggle('active');
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
            categoryList.innerHTML = '';
            
            if (categories.length === 0) {
                document.querySelector('.no-results').style.display = 'block';
                return;
            }
            
            document.querySelector('.no-results').style.display = 'none';
            categories.forEach(category => {
                const div = document.createElement('div');
                div.className = 'category-checkbox';
                div.innerHTML = `
                    <input type="checkbox" id="category${category.id}" value="${category.id}">
                    <label for="category${category.id}">
                        <i class="fas ${category.icon || 'fa-map-marker-alt'} category-icon"></i>
                        ${category.name}
                    </label>
                `;
                categoryList.appendChild(div);

                // 添加事件監聽器
                document.getElementById(`category${category.id}`).addEventListener('change', function(e) {
                    if (e.target.checked) {
                        selectedCategories.add(category.id);
                    } else {
                        selectedCategories.delete(category.id);
                    }
                    updateMarkers();
                });
            });
        }

        // 搜尋類別
        document.getElementById('categorySearch').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const filteredCategories = allCategories.filter(category => 
                category.name.toLowerCase().includes(searchTerm)
            );
            renderCategories(filteredCategories);
        });

        // 獲取所有地點
        function fetchPlaces() {
            toggleLoading(true);
            return fetch('/api/places')
                .then(response => response.json())
                .then(data => {
                    toggleLoading(false);
                    return data;
                })
                .catch(error => {
                    toggleLoading(false);
                    console.error('Error fetching places:', error);
                    return [];
                });
        }

        // 更新地圖標記
        function updateMarkers() {
            // 清除所有現有標記
            markers.forEach(marker => map.removeLayer(marker));
            markers = [];

            fetchPlaces().then(places => {
                places.forEach(place => {
                    // 如果沒有選擇任何類別，或地點的類別在選擇的類別中
                    if (selectedCategories.size === 0 || selectedCategories.has(place.category_id)) {
                        const marker = L.marker([place.lat, place.lng])
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
                        markers.push(marker);
                    }
                });
            });
        }

        // 初始載入所有標記
        updateMarkers();

        // 防止移動端雙擊縮放
        map.doubleClickZoom.disable();
    </script>
</body>
</html>
