<?php
/**
 * API ДЛЯ ПРИЁМА ЗАЯВОК С САЙТА
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Метод не разрешён']);
    exit;
}

require_once 'db.php';

$name = trim($_POST['name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$message = trim($_POST['message'] ?? '');
$consent = isset($_POST['consent']) && $_POST['consent'] === 'on';

$errors = [];

if (strlen($name) < 2) {
    $errors[] = 'Имя должно содержать минимум 2 символа';
}

if (!preg_match('/^\+7 \(\d{3}\) \d{3}-\d{2}-\d{2}$/', $phone)) {
    $errors[] = 'Неверный формат телефона';
}

if (!$consent) {
    $errors[] = 'Необходимо согласие на обработку персональных данных';
}

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Ошибка валидации', 'errors' => $errors]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO orders (client_name, client_phone, comment, status) 
        VALUES (?, ?, ?, 'new')
    ");
    $stmt->execute([$name, $phone, $message ?: null]);
    
    $orderId = $pdo->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'message' => 'Заявка успешно отправлена! Мы свяжемся с вами в ближайшее время.',
        'order_id' => $orderId
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Ошибка сохранения заявки']);
}