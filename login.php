<?php
require_once 'db.php';
session_start();

// Если уже авторизован — перенаправляем по роли
if (isset($_SESSION['user_id'])) {
    header('Location: ' . ($_SESSION['user_role'] === 'editor' ? 'news.php' : 'admin.php'));
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($login) || empty($password)) {
        $error = 'Введите логин и пароль';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE login = ? AND is_active = 1");
        $stmt->execute([$login]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_login'] = $user['login'];
            $_SESSION['user_role'] = $user['role'];
            
            $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $stmt->execute([$user['id']]);
            
            // Перенаправляем в зависимости от роли
            if ($user['role'] === 'editor') {
                header('Location: news.php');
            } else {
                header('Location: admin.php');
            }
            exit;
        } else {
            $error = 'Неверный логин или пароль';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход в админку — Anna Flowers</title>
    <link rel="stylesheet" href="style-admin.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-box">
            <div class="login-logo">🌸 Anna Flowers</div>
            <h1>Вход в админку</h1>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <form method="POST" action="login.php" autocomplete="off">
                <div class="form-group">
                    <label for="login">Логин</label>
                    <input type="text" id="login" name="login" required autofocus
                           value="<?= htmlspecialchars($_POST['login'] ?? '') ?>">
                </div>
                
                <div class="form-group">
                    <label for="password">Пароль</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">Войти</button>
            </form>
            
            <div class="login-footer">
                <a href="index.html">← Вернуться на сайт</a>
            </div>
        </div>
    </div>
</body>
</html>