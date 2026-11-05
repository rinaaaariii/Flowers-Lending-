<?php
require_once 'auth.php';
checkAccess(['admin']); // Только админу

$message = '';
$messageType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'create') {
        $login = trim($_POST['login'] ?? '');
        $password = $_POST['password'] ?? '';
        $fullName = trim($_POST['full_name'] ?? '');
        $role = $_POST['role'] ?? 'guest';
        
        if (strlen($login) < 3 || strlen($password) < 6 || empty($fullName)) {
            $message = 'Ошибка: логин (мин. 3 символа), пароль (мин. 6 символов) и ФИО обязательны';
            $messageType = 'error';
        } else {
            try {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (login, password, full_name, role) VALUES (?, ?, ?, ?)");
                $stmt->execute([$login, $hash, $fullName, $role]);
                $message = "Пользователь $login создан";
            } catch (PDOException $e) {
                $message = 'Пользователь с таким логином уже существует';
                $messageType = 'error';
            }
        }
    }
    
    if ($action === 'toggle_active') {
        $userId = (int)($_POST['user_id'] ?? 0);
        if ($userId > 0 && $userId !== $currentUser['id']) {
            $stmt = $pdo->prepare("UPDATE users SET is_active = NOT is_active WHERE id = ?");
            $stmt->execute([$userId]);
            $message = 'Статус пользователя изменён';
        }
    }
    
    if ($action === 'change_password') {
        $userId = (int)($_POST['user_id'] ?? 0);
        $newPassword = $_POST['new_password'] ?? '';
        
        if ($userId > 0 && strlen($newPassword) >= 6) {
            $hash = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hash, $userId]);
            $message = 'Пароль изменён';
        } else {
            $message = 'Пароль должен быть не короче 6 символов';
            $messageType = 'error';
        }
    }
}

$users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Пользователи — Админка Anna Flowers</title>
    <link rel="stylesheet" href="style-admin.css">
</head>
<body>
    <header class="admin-header">
        <a href="admin.php" class="admin-logo">🌸 Anna Flowers</a>
        
        <nav class="admin-nav">
            <a href="admin.php">Заявки</a>
            <a href="news.php">Новости</a>
            <a href="users.php" class="active">Пользователи</a>
        </nav>
        
        <div class="admin-user">
            <span class="admin-user-name"><?= htmlspecialchars($currentUser['full_name']) ?></span>
            <span class="admin-user-role role-<?= $currentUser['role'] ?>">
                <?= $currentUser['role'] === 'admin' ? 'Админ' : ($currentUser['role'] === 'editor' ? 'Редактор' : 'Менеджер') ?>
            </span>
            <a href="logout.php" class="btn btn-sm btn-outline">Выйти</a>
        </div>
    </header>

    <div class="admin-container">
        <div class="page-header">
            <h1 class="page-title">Пользователи системы</h1>
            <button type="button" class="btn btn-primary" onclick="openCreateModal()">+ Добавить пользователя</button>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Логин</th>
                        <th>ФИО</th>
                        <th>Роль</th>
                        <th>Статус</th>
                        <th>Последний вход</th>
                        <th>Создан</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($user['login']) ?></strong></td>
                            <td><?= htmlspecialchars($user['full_name']) ?></td>
                            <td>
                                <span class="admin-user-role role-<?= $user['role'] ?>">
                                    <?php
                                    if ($user['role'] === 'admin') {
                                        echo 'Администратор';
                                    } elseif ($user['role'] === 'editor') {
                                        echo 'Редактор';
                                    } else {
                                        echo 'Менеджер';
                                    }
                                    ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($user['is_active']): ?>
                                    <span class="badge badge-completed">Активен</span>
                                <?php else: ?>
                                    <span class="badge badge-cancelled">Заблокирован</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= $user['last_login'] 
                                    ? date('d.m.Y H:i', strtotime($user['last_login'])) 
                                    : '<span style="color: var(--text-light);">никогда</span>' ?>
                            </td>
                            <td><?= date('d.m.Y', strtotime($user['created_at'])) ?></td>
                            <td>
                                <div class="actions">
                                    <button type="button" class="btn btn-sm btn-outline"
                                            onclick="openPasswordModal(<?= $user['id'] ?>)">
                                        Пароль
                                    </button>
                                    
                                    <?php if ($user['id'] !== $currentUser['id']): ?>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="action" value="toggle_active">
                                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                            <button type="submit" class="btn btn-sm <?= $user['is_active'] ? 'btn-danger' : 'btn-success' ?>">
                                                <?= $user['is_active'] ? 'Заблокировать' : 'Разблокировать' ?>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Модальное окно создания пользователя -->
    <div class="modal-backdrop" id="createModal">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title">Новый пользователь</h3>
                <button type="button" class="modal-close" onclick="closeCreateModal()">×</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="create">
                
                <div class="modal-body">
                    <div class="form-group">
                        <label for="newLogin">Логин *</label>
                        <input type="text" id="newLogin" name="login" required minlength="3">
                    </div>
                    
                    <div class="form-group">
                        <label for="newPassword">Пароль *</label>
                        <input type="password" id="newPassword" name="password" required minlength="6">
                        <small style="color: var(--text-light);">Минимум 6 символов</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="newFullName">ФИО *</label>
                        <input type="text" id="newFullName" name="full_name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="newRole">Роль *</label>
                        <select id="newRole" name="role" required>
                            <option value="guest">Менеджер</option>
                            <option value="editor">Редактор</option>
                            <option value="admin">Администратор</option>
                        </select>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeCreateModal()">Отмена</button>
                    <button type="submit" class="btn btn-primary">Создать</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Модальное окно смены пароля -->
    <div class="modal-backdrop" id="passwordModal">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title">Сменить пароль</h3>
                <button type="button" class="modal-close" onclick="closePasswordModal()">×</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="change_password">
                <input type="hidden" name="user_id" id="pwdUserId">
                
                <div class="modal-body">
                    <div class="form-group">
                        <label for="newPwd">Новый пароль *</label>
                        <input type="password" id="newPwd" name="new_password" required minlength="6">
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closePasswordModal()">Отмена</button>
                    <button type="submit" class="btn btn-primary">Сменить</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openCreateModal() {
            document.getElementById('createModal').classList.add('active');
        }
        function closeCreateModal() {
            document.getElementById('createModal').classList.remove('active');
        }
        function openPasswordModal(userId) {
            document.getElementById('pwdUserId').value = userId;
            document.getElementById('passwordModal').classList.add('active');
        }
        function closePasswordModal() {
            document.getElementById('passwordModal').classList.remove('active');
        }
        
        document.querySelectorAll('.modal-backdrop').forEach(modal => {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) modal.classList.remove('active');
            });
        });
        
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal-backdrop.active').forEach(m => m.classList.remove('active'));
            }
        });
    </script>
</body>
</html>