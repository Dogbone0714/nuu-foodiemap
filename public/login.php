<?php
// 開啟 session 用來保存登入狀態
session_start();

// 登入處理
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 設定帳號與密碼
    $username = "dogbone0714";
    $password = "abc054015";

    // 獲取用戶輸入的帳號與密碼
    $input_username = $_POST['username'];
    $input_password = $_POST['password'];

    // 驗證帳號密碼是否正確
    if ($input_username == $username && $input_password == $password) {
        // 設置 session 變數
        $_SESSION['logged_in'] = true;
        header("Location: admin.php");  // 登入成功後跳轉到 admin.php
        exit();
    } else {
        $error = "帳號或密碼錯誤";
    }
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="public/icon.png" type="image/png">
    <title>登入後台</title>
    <style>
        /* 頁面整體設置 */
        body {
            font-family: 'Arial', sans-serif;
            background: linear-gradient(to right, #6a11cb, #2575fc); /* 背景漸變 */
            color: #fff;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .login-container {
            background: rgba(0, 0, 0, 0.5); /* 背景半透明 */
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }

        h2 {
            margin-bottom: 20px;
            font-size: 24px;
        }

        .error-message {
            color: #ff6b6b;
            margin-bottom: 10px;
        }

        input {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
            border: 1px solid #ddd;
            font-size: 16px;
        }

        input[type="submit"] {
            background-color: #2575fc;
            border: none;
            color: white;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        input[type="submit"]:hover {
            background-color: #6a11cb;
        }

        label {
            text-align: left;
            display: block;
            font-size: 14px;
            margin-bottom: 5px;
        }

        .footer {
            margin-top: 20px;
            font-size: 12px;
            color: #bbb;
        }
    </style>
</head>
<body>

    <div class="login-container">
        <h2>後台登入</h2>
        
        <?php if (isset($error)): ?>
            <p class="error-message"><?php echo $error; ?></p>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <label for="username">帳號:</label>
            <input type="text" name="username" id="username" required><br>

            <label for="password">密碼:</label>
            <input type="password" name="password" id="password" required><br>

            <input type="submit" value="登入">
        </form>

        <div class="footer">
            <p>&copy; 2025 美食地圖管理後台</p>
        </div>
    </div>

</body>
</html>
