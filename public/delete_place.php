<?php
// 開啟 session 用來檢查用戶是否已登入
session_start();

// 檢查用戶是否已登入
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");  // 如果未登入，跳轉回登入頁面
    exit();
}

// 檢查是否有傳遞地點 ID，並且是有效的數字
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $place_id = $_GET['id'];

    // 設定資料庫連線（假設你的資料庫設置已經完成）
    $host = 'localhost';
    $username = 'hhkone_foodiemap';
    $password = 'foodiemap';
    $dbname = 'hhkone_foodiemap'; // 資料庫名稱

    // 創建資料庫連線
    $conn = new mysqli($host, $username, $password, $dbname);

    // 檢查連線是否成功
    if ($conn->connect_error) {
        die("資料庫連線失敗: " . $conn->connect_error);
    }

    // 刪除地點的 SQL 查詢
    $sql = "DELETE FROM places WHERE id = $place_id";

    if ($conn->query($sql) === TRUE) {
        // 刪除成功，跳轉回管理頁面並顯示成功訊息
        header("Location: admin.php?success=1");
    } else {
        // 刪除失敗，跳轉回管理頁面並顯示錯誤訊息
        echo "錯誤: " . $conn->error;
    }

    // 關閉資料庫連線
    $conn->close();
} else {
    // 如果沒有有效的地點 ID，跳轉回管理頁面
    header("Location: admin.php");
}
?>
