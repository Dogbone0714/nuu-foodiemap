<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class User {
    private $pdo;
    private $config;

    public function __construct() {
        $this->pdo = Database::getInstance()->getConnection();
        $this->config = require __DIR__ . '/../../config/config.php';
    }

    public function validateUser($username, $password) {
        // 使用環境變數中的管理員帳密
        $admin_username = $this->config['admin']['username'];
        $admin_password = $this->config['admin']['password'];

        if ($username === $admin_username && $password === $admin_password) {
            return true;
        }
        return false;
    }

    // 可以添加其他用戶相關的方法
    public function getCurrentUser() {
        if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
            return [
                'username' => $this->config['admin']['username'],
                'is_admin' => true
            ];
        }
        return null;
    }
}
