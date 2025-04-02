<?php
// 開啟 session 用來檢查用戶是否已登入
session_start();

// 檢查用戶是否已登入
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");  // 如果未登入，跳轉回登入頁面
    exit();
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>後台管理</title>
</head>
<body>
    <h2>歡迎來到後台管理頁面</h2>
    <p>這是您登入後能夠訪問的頁面。</p>
    <a href="logout.php">登出</a>
</body>
</html>
