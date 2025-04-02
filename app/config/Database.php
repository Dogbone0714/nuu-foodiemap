<?php

namespace App\Config;

use PDO;
use PDOException;

class Database {
    private static $instance = null;
    private $connection;
    private $config;

    private function __construct() {
        $this->config = require __DIR__ . '/../../config/config.php';
        $this->connect();
    }

    private function connect() {
        try {
            $dsn = "mysql:host={$this->config['database']['host']};dbname={$this->config['database']['name']};charset=utf8";
            $this->connection = new PDO(
                $dsn,
                $this->config['database']['user'],
                $this->config['database']['pass'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
        } catch (PDOException $e) {
            die("資料庫連接錯誤: " . $e->getMessage());
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->connection;
    }

    // 防止複製實例
    private function __clone() {}
    private function __wakeup() {}
} 