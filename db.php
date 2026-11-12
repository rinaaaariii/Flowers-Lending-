<?php
require_once __DIR__ . '/config.php';

try {
    $dsn = "sqlite:" . DB_PATH;
    
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];
    
    $pdo = new PDO($dsn, null, null, $options);
    
    // Включаем поддержку внешних ключей в SQLite
    $pdo->exec("PRAGMA foreign_keys = ON");
    
} catch (PDOException $e) {
    die('Ошибка подключения к БД: ' . $e->getMessage());
}