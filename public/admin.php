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

// 取得資料庫中所有地點的資料，並根據新增時間排序
try {
    $sql = "SELECT * FROM places ";  // 按時間排序，從最新到最舊
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $places = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "無法取得地點資料: " . $e->getMessage();
    exit();
}
?>
<?php
// 取得資料庫中所有地點的資料
try {
    $sql = "SELECT * FROM places ORDER BY created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $places = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "無法取得地點資料: " . $e->getMessage();
    exit();
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>後台管理 - 查看地點</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <link rel="icon" href="public/icon.png" type="image/png">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f9f9f9;
            margin: 0;
            padding: 0;
            color: #333;
        }

        .sidebar {
            height: 100%;
            width: 250px;
            position: fixed;
            top: 0;
            left: 0;
            background-color: #1e2a3a; /* 深藍色 */
            padding-top: 20px;
            color: white;
        }

        .sidebar a {
            padding: 12px 15px;
            text-decoration: none;
            font-size: 18px;
            color: white;
            display: block;
        }

        .sidebar a:hover {
            background-color: #f39c12; /* 橙色 */
        }

        .content {
            margin-left: 250px;
            padding: 20px;
        }

        h2 {
            font-size: 28px;
            color: #333;
        }

        .table-container {
            background-color: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #f2f2f2;
        }

        tr:hover {
            background-color: #f1f1f1;
        }

        .btn-edit, .btn-delete {
    display: inline-block;
    text-decoration: none;
    padding: 5px 10px; /* 控制按鈕大小 */
    margin: 0 2px; /* 縮小按鈕間距 */
    border-radius: 5px;
    font-size: 14px; /* 確保按鈕文字大小一致 */
    color: white;
    text-align: center;
}

.btn-edit {
    background-color: #4CAF50; /* 綠色 */
    border: none;
}

.btn-edit:hover {
    background-color: #45a049;
}

.btn-delete {
    background-color: #f44336; /* 紅色 */
    border: none;
}

.btn-delete:hover {
    background-color: #d32f2f;
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

        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .scroll-container {
            height: 400px; /* 固定高度 */
            overflow-y: auto; /* 垂直滾動 */
            border: 1px solid #ccc;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        .location-card {
            border: 1px solid #ddd;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 4px;
        }
    </style>
</head>
<body>

    <!-- 側邊欄 -->
    <div class="sidebar">
        <h2>後台管理</h2>
        <a href="admin.php">查看地點</a>
        <a href="add_place.php">新增地點</a>
        <a href="logout.php" class="btn-logout">登出</a>
    </div>

    <!-- 主要內容區 -->
    <div class="content">
        <h2>地點列表</h2>

        <!-- 顯示地點資料表格 -->
        <div class="table-container">
             <div class="scroll-container">
                <table>
                    <thead>
                        <tr>
                            <th>地點名稱</th>
                            <th>經度</th>
                            <th>緯度</th>
                            <th>詳細資訊</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($places) > 0): ?>
                            <?php foreach ($places as $place): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($place['name']); ?></td>
                                    <td><?php echo htmlspecialchars($place['lng']); ?></td>
                                    <td><?php echo htmlspecialchars($place['lat']); ?></td>
                                    <td><?php echo htmlspecialchars($place['description']); ?></td>
                                    <td>
                                        <a href="edit_place.php?id=<?php echo $place['id']; ?>" class="btn-edit">編輯</a>
                                        <a href="delete_place.php?id=<?php echo $place['id']; ?>" class="btn-delete" onclick="return confirm('確定要刪除此地點嗎？')">刪除</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" style="text-align:center;">尚無資料</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            </div>
        </div>

    <!-- 版權聲明 -->
    <footer>
        <p>&copy; 2025 FoodieMap. All rights reserved.</p>
    </footer>

</body>
</html>
