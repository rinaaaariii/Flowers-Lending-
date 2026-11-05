<?php
require_once 'auth.php';
checkAccess(['admin', 'guest']); // Редактору сюда нельзя

$message = '';
$messageType = 'success';

// Обработка действий
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $orderId = (int)($_POST['order_id'] ?? 0);
    
    if ($orderId > 0) {
        if ($action === 'update_status') {
            $newStatus = $_POST['status'] ?? '';
            $validStatuses = ['new', 'in_progress', 'completed', 'cancelled'];
            
            if (in_array($newStatus, $validStatuses)) {
                $stmt = $pdo->prepare("UPDATE orders SET status = ?, manager_id = ? WHERE id = ?");
                $stmt->execute([$newStatus, $currentUser['id'], $orderId]);
                $message = 'Статус заявки обновлён';
            }
        } elseif ($action === 'delete' && isAdmin()) {
            $stmt = $pdo->prepare("DELETE FROM orders WHERE id = ?");
            $stmt->execute([$orderId]);
            $message = 'Заявка удалена';
        }
    }
}

// Фильтры
$statusFilter = $_GET['status'] ?? 'all';
$searchFilter = trim($_GET['search'] ?? '');

// SQL-запрос
$sql = "SELECT o.*, u.full_name AS manager_name 
        FROM orders o 
        LEFT JOIN users u ON o.manager_id = u.id 
        WHERE 1=1";
$params = [];

if ($statusFilter !== 'all') {
    $sql .= " AND o.status = ?";
    $params[] = $statusFilter;
}

if ($searchFilter !== '') {
    $sql .= " AND (o.client_name LIKE ? OR o.client_phone LIKE ? OR o.comment LIKE ?)";
    $searchParam = "%$searchFilter%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

$sql .= " ORDER BY o.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

// Статистика
$stats = ['total' => 0, 'new' => 0, 'in_progress' => 0, 'completed' => 0, 'cancelled' => 0];
foreach ($orders as $order) {
    $stats['total']++;
    $stats[$order['status']]++;
}

$statusLabels = [
    'new' => 'Новая',
    'in_progress' => 'В работе',
    'completed' => 'Выполнена',
    'cancelled' => 'Отменена',
];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Заявки — Админка Anna Flowers</title>
    <link rel="stylesheet" href="style-admin.css">
</head>
<body>
    <header class="admin-header">
        <a href="admin.php" class="admin-logo">🌸 Anna Flowers</a>
        
        <nav class="admin-nav">
            <a href="admin.php" class="active">Заявки</a>
            <a href="news.php">Новости</a>
            <?php if (isAdmin()): ?>
                <a href="users.php">Пользователи</a>
            <?php endif; ?>
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
            <h1 class="page-title">Заявки с сайта</h1>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <!-- Статистика -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-card-label">Всего заявок</div>
                <div class="stat-card-value"><?= $stats['total'] ?></div>
            </div>
            <div class="stat-card new">
                <div class="stat-card-label">Новые</div>
                <div class="stat-card-value"><?= $stats['new'] ?></div>
            </div>
            <div class="stat-card progress">
                <div class="stat-card-label">В работе</div>
                <div class="stat-card-value"><?= $stats['in_progress'] ?></div>
            </div>
            <div class="stat-card completed">
                <div class="stat-card-label">Выполнено</div>
                <div class="stat-card-value"><?= $stats['completed'] ?></div>
            </div>
        </div>

        <!-- Таблица заявок -->
        <div class="table-wrapper">
            <form method="GET" class="table-filters">
                <input type="text" name="search" placeholder="🔍 Поиск..."
                       value="<?= htmlspecialchars($searchFilter) ?>">
                
                <select name="status">
                    <option value="all" <?= $statusFilter === 'all' ? 'selected' : '' ?>>Все статусы</option>
                    <option value="new" <?= $statusFilter === 'new' ? 'selected' : '' ?>>Новые</option>
                    <option value="in_progress" <?= $statusFilter === 'in_progress' ? 'selected' : '' ?>>В работе</option>
                    <option value="completed" <?= $statusFilter === 'completed' ? 'selected' : '' ?>>Выполнены</option>
                    <option value="cancelled" <?= $statusFilter === 'cancelled' ? 'selected' : '' ?>>Отменены</option>
                </select>
                
                <button type="submit" class="btn btn-sm btn-primary">Применить</button>
                <a href="admin.php" class="btn btn-sm btn-outline">Сбросить</a>
            </form>

            <?php if (empty($orders)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📭</div>
                    <p>Заявок не найдено</p>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Дата и время</th>
                            <th>Имя</th>
                            <th>Телефон</th>
                            <th>Комментарий</th>
                            <th>Статус</th>
                            <th>Менеджер</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td>
                                    <?= date('d.m.Y', strtotime($order['created_at'])) ?><br>
                                    <small style="color: var(--text-light);">
                                        <?= date('H:i', strtotime($order['created_at'])) ?>
                                    </small>
                                </td>
                                <td><strong><?= htmlspecialchars($order['client_name']) ?></strong></td>
                                <td>
                                    <a href="tel:<?= htmlspecialchars($order['client_phone']) ?>">
                                        <?= htmlspecialchars($order['client_phone']) ?>
                                    </a>
                                </td>
                                <td>
                                    <?php if ($order['comment']): ?>
                                        <?= htmlspecialchars(mb_substr($order['comment'], 0, 50)) ?>
                                        <?= mb_strlen($order['comment']) > 50 ? '...' : '' ?>
                                    <?php else: ?>
                                        <span style="color: var(--text-light);">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge badge-<?= $order['status'] ?>">
                                        <?= $statusLabels[$order['status']] ?>
                                    </span>
                                </td>
                                <td>
                                    <?= $order['manager_name'] 
                                        ? htmlspecialchars($order['manager_name']) 
                                        : '<span style="color: var(--text-light);">—</span>' ?>
                                </td>
                                <td>
                                    <div class="actions">
                                        <button type="button" 
                                                class="btn btn-sm btn-outline"
                                                onclick="openStatusModal(<?= $order['id'] ?>, '<?= $order['status'] ?>')">
                                            Изменить
                                        </button>
                                        
                                        <?php if (isAdmin()): ?>
                                            <form method="POST" style="display:inline;"
                                                  onsubmit="return confirm('Удалить заявку?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-danger">Удалить</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Модальное окно изменения статуса -->
    <div class="modal-backdrop" id="statusModal">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title">Изменить статус заявки</h3>
                <button type="button" class="modal-close" onclick="closeStatusModal()">×</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="order_id" id="modalOrderId">
                
                <div class="modal-body">
                    <div class="form-group">
                        <label for="modalStatus">Новый статус</label>
                        <select name="status" id="modalStatus" required>
                            <option value="new">Новая</option>
                            <option value="in_progress">В работе</option>
                            <option value="completed">Выполнена</option>
                            <option value="cancelled">Отменена</option>
                        </select>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeStatusModal()">Отмена</button>
                    <button type="submit" class="btn btn-primary">Сохранить</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openStatusModal(orderId, currentStatus) {
            document.getElementById('modalOrderId').value = orderId;
            document.getElementById('modalStatus').value = currentStatus;
            document.getElementById('statusModal').classList.add('active');
        }
        
        function closeStatusModal() {
            document.getElementById('statusModal').classList.remove('active');
        }
        
        document.getElementById('statusModal').addEventListener('click', (e) => {
            if (e.target.id === 'statusModal') closeStatusModal();
        });
        
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeStatusModal();
        });
    </script>
</body>
</html>