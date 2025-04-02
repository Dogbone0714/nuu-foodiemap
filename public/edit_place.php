<?php
// 開啟 session 用來檢查用戶是否已登入
session_start();

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

// 如果用戶沒有登入，跳轉至登入頁面
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// 處理編輯地點的表單提交
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_GET['id'];
    $name = $_POST['name'];
    $lat = $_POST['lat'];
    $lng = $_POST['lng'];
    $description = $_POST['description'];
    $category = $_POST['category'];

    try {
        // 更新資料庫中的地點資訊
        $sql = "UPDATE places SET name = :name, lat = :lat, lng = :lng, description = :description, category = :category WHERE id = :id";
        $stmt = $conn->prepare($sql);
        
        // 綁定參數
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':lat', $lat);
        $stmt->bindParam(':lng', $lng);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':category', $category);
        
        // 執行更新操作
        $stmt->execute();
        
        // 更新成功後跳轉到管理頁面
        header("Location: admin.php");
        exit();
    } catch (PDOException $e) {
        echo "更新地點時發生錯誤: " . $e->getMessage();
    }
}

// 根據 ID 取得要編輯的地點資料
if (isset($_GET['id'])) {
    $id = $_GET['id'];

    try {
        $stmt = $conn->prepare("SELECT * FROM places WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        // 如果找到資料，將其放入變數
        $place = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo "取得地點資料時發生錯誤: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>編輯地點</title>
    <link rel="icon" href="public/icon.png" type="image/png">
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <style>
        /* 頁面基本設定 */
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            display: flex;
        }

        footer {
            text-align: center;
            padding: 15px;
            background-color: #27ae60; /* 鮮明綠色 */
            color: white;
            position: fixed;
            bottom: 0;
            width: 100%;
        }

 /* 美化 select 元素 */
        select {
            background-color: #ffffff;
            color: #555;
            font-size: 16px;
            padding: 12px 15px;
            border-radius: 5px;
            border: 1px solid #ddd;
            transition: all 0.3s ease;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        select:hover {
            border-color: #5f6368;
            box-shadow: 0 0 5px rgba(0, 0, 0, 0.15);
        }

        select:focus {
            border-color: #4CAF50;
            outline: none;
            box-shadow: 0 0 5px rgba(76, 175, 80, 0.4);
        }

        /* 側邊欄 */
        .sidebar {
            height: 100%;
            width: 250px;
            position: fixed;
            top: 0;
            left: 0;
            background-color: #2c3e50;
            padding-top: 20px;
            color: white;
            padding-left: 20px;
        }

        .sidebar h2 {
            color: #ecf0f1;
            font-size: 24px;
            margin-bottom: 40px;
        }

        .description {
           white-space: pre-line; /* 保留換行符號並處理多行文本 */
            word-wrap: break-word; /* 避免長文字溢出 */
        }
        .sidebar a {
            padding: 10px 20px;
            text-decoration: none;
            font-size: 18px;
            color: #ecf0f1;
            display: block;
            margin-bottom: 10px;
            border-radius: 5px;
        }

        .sidebar a:hover {
            background-color: #34495e;
        }

        /* 主要內容區 */
        .content {
            margin-left: 250px;
            padding: 20px;
            width: calc(100% - 250px);
        }

        /* 表單樣式 */
        form {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            margin: auto;
        }

        input[type="text"], input[type="submit"], textarea {
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

        h2 {
            font-size: 28px;
            color: #333;
        }

        label {
            font-size: 16px;
            color: #555;
        }

        /* 地圖容器 */
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
        <a href="edit_place.php">編輯地點</a>
        <a href="logout.php">登出</a>
    </div>

    <!-- 主要內容區 -->
    <div class="content">
        <h2>編輯地點</h2>

        <!-- 編輯表單 -->
        <form method="POST" action="edit_place.php?id=<?php echo $place['id']; ?>">
            <label for="name">地點名稱:</label>
            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($place['name']); ?>" required><br>

            <label for="lat">經度:</label>
            <input type="text" id="lat" name="lat" value="<?php echo htmlspecialchars($place['lat']); ?>" required><br>

            <label for="lng">緯度:</label>
            <input type="text" id="lng" name="lng" value="<?php echo htmlspecialchars($place['lng']); ?>" required><br>

            <label for="description">詳細資訊:</label>
           <textarea id="description" name="description" rows="5" required><?php echo htmlspecialchars($place['description']); ?></textarea>

            <label for="category">景點類別:</label>
            <select id="category" name="category" required>
                <option value="high" <?php echo $place['category'] == '夜生活' ? 'selected' : ''; ?>>夜生活</option>
                <option value="cafe" <?php echo $place['category'] == '咖啡廳、甜點、冰品' ? 'selected' : ''; ?>>咖啡廳、甜點、冰品</option>
                <option value="hotpot" <?php echo $place['category'] == '火鍋' ? 'selected' : ''; ?>>火鍋</option>
                <option value="together" <?php echo $place['category'] == '合菜' ? 'selected' : ''; ?>>合菜</option>
                <option value="stew" <?php echo $place['category'] == '滷味' ? 'selected' : ''; ?>>滷味</option>
                <option value="bbq" <?php echo $place['category'] == '烤肉' ? 'selected' : ''; ?>>烤肉</option>
                <option value="snack" <?php echo $place['category'] == '宵夜' ? 'selected' : ''; ?>>宵夜</option>
                <option value="foreign" <?php echo $place['category'] == '異國料理' ? 'selected' : ''; ?>>異國料理</option>
                <option value="dinner" <?php echo $place['category'] == '晚餐' ? 'selected' : ''; ?>>晚餐</option>
                <option value="breakfast" <?php echo $place['category'] == '早午餐' ? 'selected' : ''; ?>>早午餐</option>
            </select><br>
            <input type="submit" value="更新地點">
        </form>

    </div>

    <footer>
        <p>&copy; 2025 聯大美食地圖 2.0. All rights reserved.</p>
    </footer>

</body>
</html>
