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

// 取得資料庫中所有地點的資料
try {
    $sql = "SELECT id, name, lat, lng, description, category FROM places";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $places = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 設定回傳的內容類型為 JSON
    header('Content-Type: application/json');

    // 將資料轉換為 JSON 格式並輸出
    echo json_encode($places);

} catch (PDOException $e) {
    // 若資料取得錯誤，返回錯誤訊息
    echo json_encode(["error" => "無法取得地點資料: " . $e->getMessage()]);
}
?>
