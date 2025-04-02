<?php
// 開啟 session，如果需要可以檢查用戶登入狀態
// session_start();

// 資料庫設定
$host = '127.0.0.1';
$username = 'hhkone_foodiemap';
$password = 'foodiemap';
$dbname = 'hhkone_foodiemap'; // 資料庫名稱

try {
    // 使用 PDO 建立資料庫連線
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 從資料庫中讀取地點資料
    $sql = "SELECT id, name, description, lat, lng, category FROM places";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $places = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    echo "資料庫連接錯誤: " . $e->getMessage();
    exit();
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>聯大美食地圖 2.0</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet-geosearch@3.0.0/dist/geosearch.css" />
    <style>
    body {
    margin: 0;
    height: 100vh; /* 確保頁面高度占滿視窗 */
    font-family: Arial, sans-serif;
    display: flex;
}

#map {
    flex: 1;
    height: 100%;
}

/* 滾動條樣式 */
.sidebar::-webkit-scrollbar {
    width: 8px;
}

.sidebar::-webkit-scrollbar-thumb {
    background: #ccc;
    border-radius: 5px;
}

        /* 自訂縮放控制的樣式 */
        .leaflet-control-zoom {
            position: absolute;
            top: 20px;
            right: 20px; /* 設定在右側 */
            z-index: 1000; /* 讓縮放控制顯示在最上層 */
        }

        /* 調整縮放按鈕的大小 */
        .leaflet-control-zoom a {
            background-color: rgba(255, 255, 255, 0.8);
            border-radius: 5px;
            padding: 5px;
            font-size: 20px;
            transition: background-color 0.3s ease;
        }

        .leaflet-control-zoom a:hover {
            background-color: rgba(0, 0, 0, 0.1);
        }

.sidebar::-webkit-scrollbar-thumb:hover {
    background: #999;
}

.sidebar {
    width: 350px;
    background: #f9f9f9;
    box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
    overflow-y: auto; /* 只允許縱向滾動 */
    position: fixed; /* 使 sidebar 固定在螢幕左側 */
    height: 100vh; /* 讓 sidebar 高度占滿整個視窗 */
    z-index: 1000; /* 確保 sidebar 在最上層 */
}

.sidebar h2 {
    margin: 0;
    padding: 1rem;
    background: #4CAF50;
    color: white;
    font-size: 1.5rem;
    text-align: center;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

.place-list {
    padding: 1rem;
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

    .description {
        white-space: pre-line;  /* 保留換行符號並處理多行文本 */
    }

.place-item {
    display: flex;
    align-items: center;
    justify-content: start;
    background-color: #ffffff;
    margin-bottom: 10px; /* 增加項目之間的間距 */
    padding: 8px 10px; /* 調整內邊距，讓框框變窄 */
    border-radius: 10px; /* 添加圓角 */
    border: 1px solid #ddd; /* 添加邊框 */
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1); /* 添加陰影提升層次感 */
    width: 90%; /* 控制框框寬度（使其變窄） */
    max-width: 250px; /* 限制最大寬度 */
    margin-left: auto;
    margin-right: auto;
    cursor: pointer; /* 讓整個框框可點擊 */
    transition: all 0.2s ease-in-out; /* 增加鼠標懸停動畫 */
    height: auto; /* 自動調整高度以適應文字內容 */
    overflow: hidden; /* 隱藏超出部分 */
    text-overflow: ellipsis; /* 添加省略號效果 */
    white-space: normal; /* 單行顯示文字 */
}

.place-item:hover {
    transform: scale(1.03); /* 鼠標懸停時稍微放大 */
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2); /* 增強懸停效果 */
}

.category-group {
    margin-bottom: 20px;
}

.category-name {
    font-size: 18px;
    color: #333;
    margin-bottom: 10px;
    font-weight: bold;
    cursor: pointer; /* 讓標題具有可點擊樣式 */
    padding: 5px;
    background-color: #f1f1f1;
    border-radius: 5px;
    transition: background-color 0.3s;
}

.place-thumbnail img {
    width: 40px; /* 圖標寬度稍微縮小 */
    height: 25px; /* 圖標高度稍微縮小 */
    object-fit: contain; /* 保持圖標比例 */
    border-radius: 8px; /* 圖片圓角 */
    background-color: #ffffff; /* 添加背景顏色 */
    padding: 5px; /* 內邊距 */
    margin-right: 10px; /* 與文本區域保持距離 */
}

.place-info {
    flex: 1; /* 讓內容區域自動調整大小 */
    overflow: hidden; /* 隱藏超出部分 */
    text-overflow: ellipsis; /* 添加省略號效果 */
    white-space: nowrap; /* 單行顯示文字 */
}

.place-info h3, 
.place-info p {
    overflow: hidden;
    text-overflow: ellipsis; /* 超出部分顯示省略號 */
    white-space: normal; /* 單行顯示 */
    margin: 0; /* 去掉多餘的外邊距 */
    padding: 0; /* 去掉多餘的內邊距 */
    word-wrap: break-word; /* 長字換行 */
}

/* 滾動條樣式 */
.sidebar::-webkit-scrollbar {
    width: 8px;
}

.sidebar::-webkit-scrollbar-thumb {
    background: #ccc;
    border-radius: 5px;
}

.sidebar::-webkit-scrollbar-thumb:hover {
    background: #999;
}

.leaflet-bar {
            background-color: rgba(255, 255, 255, 0.8);
            border-radius: 5px;
        }

        .leaflet-control-geosearch input {
            height: 30px;
            font-size: 16px;
        }

        .leaflet-control-geosearch .geosearch-icon {
            background-color: rgba(0, 0, 0, 0.1);
            border-radius: 50%;
            width: 36px;
            height: 36px;
            cursor: pointer;
        }
    </style>
    <link rel="icon" href="public/icon.png" type="image/png">

</head>
<body>
    <div id="sidebar" class="sidebar">
    <h2>地點清單</h2>
    <div class="place-list">
        <?php foreach ($places as $place): ?>
        
            <?php 
                // 根據類別選擇圖標的 URL
                $iconUrl = '';
                switch ($place['category']) {
                    case 'high':
                        $iconUrl = 'https://cdn-icons-png.flaticon.com/512/763/763072.png';
                        break;
                    case 'cafe':
                        $iconUrl = 'https://cdn-icons-png.flaticon.com/512/924/924514.png';
                        break;
                    case 'hotpot':
                        $iconUrl = 'https://cdn-icons-png.flaticon.com/512/8339/8339330.png';
                        break;
                    case 'together':
                        $iconUrl = 'https://cdn-icons-png.flaticon.com/512/16923/16923989.png';
                        break;
                    case 'stew':
                        $iconUrl = 'https://cdn-icons-png.flaticon.com/512/4727/4727284.png';
                        break;
                    case 'bbq':
                        $iconUrl = 'https://cdn-icons-png.flaticon.com/512/2946/2946598.png';
                        break;
                    case 'snack':
                        $iconUrl = 'https://cdn-icons-png.flaticon.com/512/2553/2553691.png';
                        break;
                    case 'foreign':
                        $iconUrl = 'https://cdn-icons-png.flaticon.com/512/4624/4624250.png';
                        break;
                    case 'dinner':
                        $iconUrl = 'https://cdn-icons-png.flaticon.com/512/3274/3274099.png';
                        break;
                    case 'breakfast':
                        $iconUrl = 'https://cdn-icons-png.flaticon.com/512/3480/3480823.png';
                        break;
                    default:
                        $iconUrl = 'https://cdn-icons-png.flaticon.com/512/1006/1006771.png'; // 預設圖標
                        break;
                }
            ?>
            <div class="place-item" data-id="<?php echo $place['id']; ?>" data-lat="<?php echo $place['lat']; ?>" data-lng="<?php echo $place['lng']; ?>">
                <div class="place-thumbnail">
                    <img src="<?php echo $iconUrl; ?>" alt="<?php echo $place['category']; ?> icon">
                </div>
                <div class="place-info">
                    <h3><?php echo $place['name']; ?></h3>
                    <p><?php echo $place['description']; ?></p>
                </div>
            </div>
            <?php // 顯示類型和地點列表
        foreach ($groupedPlaces as $category => $typePlaces) {
            echo "<div class='category-group'>";
            echo "<h3 class='category-name' onclick='toggleCategory(\"" . htmlspecialchars($category) . "\")'>" . htmlspecialchars($category) . "</h3>"; // 顯示分類名稱
            echo "<ul class='place-list' id='category-" . htmlspecialchars($category) . "' style='display:none;'>"; // 預設隱藏
            foreach ($typePlaces as $place) {
                echo "<li class='place-item'>
                        <a href='#' class='place-link' data-lat='" . htmlspecialchars($place['lat']) . "' data-lng='" . htmlspecialchars($place['lng']) . "'>
                            <div class='place-thumbnail'>
                                <img src='" . getIconUrl($place['category']) . "' alt='" . htmlspecialchars($place['category']) . "'>
                            </div>
                            <div class='place-info'>
                                <h3>" . htmlspecialchars($place['name']) . "</h3>
                                <p>" . htmlspecialchars($place['description']) . "</p>
                            </div>
                        </a>
                      </li>";
            }
            echo "</ul>";
            echo "</div>";
        } ?>
        <?php endforeach; ?>
    </div>
</div>

    <!-- 顯示地圖 -->
    <div id="map"></div>

    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet-geosearch@3.0.0/dist/geosearch.umd.js"></script>
    <script>
        // 初始化地圖
        var map = L.map('map').setView([24.5458273, 120.80979], 17); // 設定為苗栗聯合大學的經緯度

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);

                // 添加縮放控制，並將其位置設置到右側
        L.control.zoom({
            position: 'topright' // 設置縮放控制顯示在右上角
        }).addTo(map);

     // 使用 leaflet-geosearch 插件建立搜尋功能
        const searchControl = new GeoSearch.GeoSearchControl({
            provider: new GeoSearch.OpenStreetMapProvider(),
            style: 'bar',
            showMarker: true,
            retainZoomLevel: false,
            animateZoom: true,
            position: 'topleft', // 將搜尋框設置在左上角
        });

        // 將搜尋框加入地圖
      //  map.addControl(searchControl);
        // 定義不同類型的圖標
        const icons = {
            high: L.icon({
                iconUrl: 'https://cdn-icons-png.flaticon.com/512/763/763072.png', // 餐廳圖標 
                iconSize: [40, 40],
                iconAnchor: [20, 40],
                popupAnchor: [0, -40]
            }),
            cafe: L.icon({
                iconUrl: 'https://cdn-icons-png.flaticon.com/512/924/924514.png', // 咖啡廳圖標
                iconSize: [40, 40],
                iconAnchor: [20, 40],
                popupAnchor: [0, -40]
            }),
            hotpot: L.icon({
                iconUrl: 'https://cdn-icons-png.flaticon.com/512/8339/8339330.png', // 景點圖標
                iconSize: [40, 40],
                iconAnchor: [20, 40],
                popupAnchor: [0, -40]
            }),
            together: L.icon({
                iconUrl: 'https://cdn-icons-png.flaticon.com/512/16923/16923989.png', // 購物圖標
                iconSize: [40, 40],
                iconAnchor: [20, 40],
                popupAnchor: [0, -40]
            }),
            stew: L.icon({
                iconUrl: 'https://cdn-icons-png.flaticon.com/512/4727/4727284.png', // 其他圖標
                iconSize: [40, 40],
                iconAnchor: [20, 40],
                popupAnchor: [0, -40]
            }),
            bbq: L.icon({
                iconUrl: 'https://cdn-icons-png.flaticon.com/512/2946/2946598.png', // 其他圖標
                iconSize: [40, 40],
                iconAnchor: [20, 40],
                popupAnchor: [0, -40]
            }),
            snack: L.icon({
                iconUrl: 'https://cdn-icons-png.flaticon.com/512/2553/2553691.png', // 其他圖標
                iconSize: [40, 40],
                iconAnchor: [20, 40],
                popupAnchor: [0, -40]
            }),
            foreign: L.icon({
                iconUrl: 'https://cdn-icons-png.flaticon.com/512/4624/4624250.png', // 其他圖標
                iconSize: [40, 40],
                iconAnchor: [20, 40],
                popupAnchor: [0, -40]
            }),
            dinner: L.icon({
                iconUrl: 'https://cdn-icons-png.flaticon.com/512/3274/3274099.png', // 其他圖標
                iconSize: [40, 40],
                iconAnchor: [20, 40],
                popupAnchor: [0, -40]
            }),
            breakfast: L.icon({
                iconUrl: 'https://cdn-icons-png.flaticon.com/512/3480/3480823.png', // 其他圖標
                iconSize: [40, 40],
                iconAnchor: [20, 40],
                popupAnchor: [0, -40]
            })
        };

        // 地點標記處理
        var places = <?php echo json_encode($places); ?>;
        let markers = [];

        places.forEach(function (place) {
            const icon = icons[place.category] || icons.other;
            const marker = L.marker([place.lat, place.lng], { icon: icon })
                .addTo(map)
                .bindPopup('<b>' + place.name + '</b><br>' + place.description);
            markers.push(marker);
        });
        </script>
    <script src="https://unpkg.com/leaflet-geosearch@3.0.0/dist/geosearch.umd.js"></script>
    <script>
        // 切換分類的顯示與隱藏
        function toggleCategory(category) {
            var categoryList = document.getElementById('category-' + category);
            if (categoryList.style.display === "none") {
                categoryList.style.display = "block"; // 顯示
            } else {
                categoryList.style.display = "none"; // 隱藏
            }
        }
    </script>
    <script>
        // 側邊欄互動功能
        document.querySelectorAll('.place-item').forEach((item) => {
        item.addEventListener('click', function () {
        const lat = this.getAttribute('data-lat');
        const lng = this.getAttribute('data-lng');
        const id = this.getAttribute('data-id');

        // 移動地圖到選中的位置
        map.setView([lat, lng], 17);

        // 彈出標記的彈窗
        if (markers[id]) {
            markers[id].openPopup();
        }

        // 高亮顯示選中的項目
        document.querySelectorAll('.place-item').forEach(el => el.classList.remove('active'));
        this.classList.add('active');
    });
});
    </script>
</body>
</html>