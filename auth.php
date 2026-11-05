<?php
require_once 'db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND is_active = 1");
$stmt->execute([$_SESSION['user_id']]);
$currentUser = $stmt->fetch();

if (!$currentUser) {
    session_destroy();
    header('Location: login.php?error=blocked');
    exit;
}

function checkAccess($allowedRoles) {
    global $currentUser;
    if (!in_array($currentUser['role'], $allowedRoles)) {
        http_response_code(403);
        die('<h1>403 Forbidden</h1><p>Доступ запрещён. У вас нет прав для просмотра этой страницы.</p><a href="admin.php">← Вернуться в админку</a>');
    }
}

function isAdmin() {
    global $currentUser;
    return $currentUser['role'] === 'admin';
}