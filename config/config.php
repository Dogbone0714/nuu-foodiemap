<?php

function loadEnv() {
    $envFile = __DIR__ . '/../.env';
    if (file_exists($envFile)) {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos($line, '#') === 0) continue;
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            if (!empty($key)) {
                // 移除引號
                $value = trim($value, '"');
                $value = trim($value, "'");
                putenv("$key=$value");
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }
    }
}

// 載入環境變數
loadEnv();

// 資料庫配置
return [
    'database' => [
        'host' => getenv('DB_HOST'),
        'name' => getenv('DB_NAME'),
        'user' => getenv('DB_USER'),
        'pass' => getenv('DB_PASS')
    ],
    'app' => [
        'name' => getenv('APP_NAME'),
        'env' => getenv('APP_ENV'),
        'debug' => getenv('APP_DEBUG'),
        'url' => getenv('APP_URL')
    ],
    'admin' => [
        'username' => getenv('ADMIN_USERNAME'),
        'password' => getenv('ADMIN_PASSWORD')
    ],
    'session' => [
        'lifetime' => getenv('SESSION_LIFETIME')
    ]
]; 