<?php
// 開啟 session 用來檢查用戶是否已登入
session_start();

// 檢查用戶是否已登入
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// 資料庫設定
$host = '127.0.0.1';
$username = 'hhkone_foodiemap';
$password = 'foodiemap';
$dbname = 'hhkone_foodiemap'; // 資料庫名稱

try {
    // 使用 PDO 建立資料庫連線
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    
    // 設定 PDO 錯誤模式
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
} catch (PDOException $e) {
    // 若連線錯誤，顯示錯誤訊息並結束
    echo "資料庫連接錯誤: " . $e->getMessage();
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = htmlspecialchars($_POST['name']);
    $lat = filter_var($_POST['lat'], FILTER_VALIDATE_FLOAT);
    $lng = filter_var($_POST['lng'], FILTER_VALIDATE_FLOAT);
    $description = htmlspecialchars($_POST['description']);
    $category = htmlspecialchars($_POST['category']); // 新增景點類型

    if ($lat !== false && $lng !== false) {
        try {
            // 插入資料時包含 category 欄位
            $stmt = $conn->prepare("INSERT INTO places (name, lat, lng, description, category) VALUES (:name, :lat, :lng, :description, :category)");
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':lat', $lat);
            $stmt->bindParam(':lng', $lng);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':category', $category);
            $stmt->execute();

            // 取得剛剛插入資料的自動生成 id
            $lastId = $conn->lastInsertId();

            // 回饋用戶並跳轉
            $_SESSION['success_message'] = "地點新增成功！流水號 ID 為：{$lastId}";
            header("Location: admin.php");
            exit();
        } catch (PDOException $e) {
            $_SESSION['error_message'] = '新增地點時發生錯誤: ' . $e->getMessage();
        }
    } else {
        $_SESSION['error_message'] = '請輸入有效的經緯度！';
    }
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>新增地點 - 後台管理系統</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <link rel="icon" href="public/icon.png" type="image/png">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }

        .sidebar {
            height: 100%;
            width: 250px;
            position: fixed;
            top: 0;
            left: 0;
            background-color: #333;
            padding-top: 20px;
            color: white;
        }

        .sidebar a {
            padding: 10px 15px;
            text-decoration: none;
            font-size: 18px;
            color: white;
            display: block;
        }

        .sidebar a:hover {
            background-color: #575757;
        }

        .content {
            margin-left: 250px;
            padding: 20px;
        }

        h2 {
            font-size: 28px;
            color: #333;
        }

        form {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            max-width: 500px;
            margin: auto;
        }

        input[type="text"], textarea, select, input[type="submit"] {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
            border: 1px solid #ddd;
            font-size: 16px;
        }

        input[type="submit"] {
            background-color: #4CAF50;
            color: white;
            cursor: pointer;
        }

        input[type="submit"]:hover {
            background-color: #45a049;
        }

        #map {
            height: 400px;
            width: 100%;
            margin-top: 20px;
        }
    </style>
</head>
<body>

    <!-- 側邊欄 -->
    <div class="sidebar">
        <h2>後台管理</h2>
        <a href="admin.php">查看地點</a>
        <a href="add_place.php">新增地點</a>
        <a href="logout.php">登出</a>
    </div>

    <!-- 主要內容區 -->
    <div class="content">
        <h2>新增地點</h2>

        <!-- 地址輸入表單 -->
        <form id="address-form" method="POST" action="add_place.php">
            <label for="name">地點名稱:</label>
            <input type="text" id="name" name="name" placeholder="輸入地點名稱" required><br>

            <label for="description">詳細資訊:</label>
            <textarea id="description" name="description" placeholder="輸入詳細資訊" required></textarea><br>

            <label for="lat">經度:</label>
            <input type="text" id="lat" name="lat" placeholder="經度" required><br>

            <label for="lng">緯度:</label>
            <input type="text" id="lng" name="lng" placeholder="緯度" required><br>

            <label for="category">景點類型:</label>
            <select id="category" name="category" required>
                <option value="high">夜生活</option>
                <option value="cafe">咖啡廳、甜點、冰品</option>
                <option value="hotpot">火鍋</option>
                <option value="together">合菜</option>
                <option value="stew">滷味</option>
                <option value="bbq">烤肉</option>
                <option value="snack">宵夜</option>
                <option value="foreign">異國料理</option>
                <option value="dinner">晚餐</option>
                <option value="breakfast">早午餐</option>
            </select><br>

            <input type="submit" value="新增地點">
        </form>

        <!-- 顯示地圖 -->
        <div id="map"></div>
        <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
        <script>
            var map = L.map('map').setView([24.5458273, 120.80979], 17);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
            var marker;
            map.on('click', function(e) {
                document.getElementById('lat').value = e.latlng.lat;
                document.getElementById('lng').value = e.latlng.lng;
                if (marker) { map.removeLayer(marker); }
                marker = L.marker([e.latlng.lat, e.latlng.lng]).addTo(map);
            });
        </script>
    </div>
</body>
</html>
