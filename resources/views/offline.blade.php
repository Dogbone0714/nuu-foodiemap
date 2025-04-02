<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>離線模式 - NUU FoodieMap</title>
    <link rel="stylesheet" href="{{ asset('css/responsive.css') }}">
    <style>
        .offline-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            text-align: center;
            padding: 20px;
        }
        .offline-icon {
            font-size: 64px;
            margin-bottom: 20px;
            color: var(--primary-color);
        }
        .offline-title {
            font-size: 24px;
            margin-bottom: 15px;
            color: var(--text-color);
        }
        .offline-message {
            font-size: 16px;
            color: #666;
            margin-bottom: 30px;
        }
        .retry-button {
            padding: 12px 24px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s ease;
        }
        .retry-button:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <div class="offline-container">
        <div class="offline-icon">📡</div>
        <h1 class="offline-title">您目前處於離線狀態</h1>
        <p class="offline-message">請檢查您的網路連線，並重新整理頁面。</p>
        <button class="retry-button" onclick="window.location.reload()">重新整理</button>
    </div>
</body>
</html> 