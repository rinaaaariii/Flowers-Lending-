<?php
// Настройки SQLite (база данных хранится в файле database.sqlite)
define('DB_TYPE', 'sqlite');
define('DB_PATH', __DIR__ . '/database.sqlite');

date_default_timezone_set('Asia/Yakutsk');
error_reporting(E_ALL);
ini_set('display_errors', 1);