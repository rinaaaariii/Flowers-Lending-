<?php
require_once 'auth.php';
checkAccess(['admin', 'editor']);

$message = '';
$messageType = 'success';

function generateSlug($text) {
    $text = mb_strtolower($text, 'UTF-8');
    $cyr = ['а','б','в','г','д','е','ё','ж','з','и','й','к','л','м','н','о','п','р','с','т','у','ф','х','ц','ч','ш','щ','ъ','ы','ь','э','ю','я',' '];
    $lat = ['a','b','v','g','d','e','e','zh','z','i','y','k','l','m','n','o','p','r','s','t','u','f','h','c','ch','sh','sch','','y','','e','yu','ya','-'];
    $text = str_replace($cyr, $lat, $text);
    $text = preg_replace('/[^a-z0-9\-]/', '', $text);
    $text = preg_replace('/\-+/', '-', $text);
    return trim($text, '-');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $title = trim($_POST['title']);
        $slug = trim($_POST['slug']) ?: generateSlug($title);
        $content = trim($_POST['content']);
        $is_published = isset($_POST['is_published']) ? 1 : 0;
        
        if (empty($title) || empty($content)) {
            $message = 'Заголовок и текст новости обязательны';
            $messageType = 'error';
        } else {
            if ($id > 0) {
                $stmt = $pdo->prepare("UPDATE news SET title=?, slug=?, content=?, is_published=? WHERE id=?");
                $stmt->execute([$title, $slug, $content, $is_published, $id]);
                $message = 'Новость обновлена';
            } else {
                $stmt = $pdo->prepare("INSERT INTO news (title, slug, content, author_id, is_published) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$title, $slug, $content, $currentUser['id'], $is_published]);
                $message = 'Новость создана';
            }
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $pdo->prepare("DELETE FROM news WHERE id = ?");
        $stmt->execute([$id]);
        $message = 'Новость удалена';
    }
}

$statusFilter = $_GET['status'] ?? 'all';
$sql = "SELECT n.*, u.full_name AS author_name FROM news n LEFT JOIN users u ON n.author_id = u.id WHERE 1=1";
$params = [];

if ($statusFilter === 'published') {
    $sql .= " AND n.is_published = 1";
} elseif ($statusFilter === 'draft') {
    $sql .= " AND n.is_published = 0";
}
$sql .= " ORDER BY n.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$newsList = $stmt->fetchAll();

$editNews = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM news WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $editNews = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Новости — Админка Anna Flowers</title>
    <link rel="stylesheet" href="style-admin.css">
</head>
<body>
    <header class="admin-header">
        <a href="admin.php" class="admin-logo">🌸 Anna Flowers</a>
        <nav class="admin-nav">
            <a href="admin.php">Заявки</a>
            <a href="news.php" class="active">Новости</a>
            <?php if (isAdmin()): ?><a href="users.php">Пользователи</a><?php endif; ?>
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
            <h1 class="page-title">Управление новостями</h1>
            <a href="news.php" class="btn btn-primary">+ Добавить новость</a>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <?php if ($editNews): ?>
            <div class="table-wrapper" style="padding: 24px; margin-bottom: 24px;">
                <h3 style="margin-bottom: 16px;">Редактирование новости</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" value="<?= $editNews['id'] ?>">
                    
                    <div class="form-group">
                        <label>Заголовок *</label>
                        <input type="text" name="title" value="<?= htmlspecialchars($editNews['title']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>URL-адрес (slug)</label>
                        <input type="text" name="slug" value="<?= htmlspecialchars($editNews['slug']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Текст новости *</label>
                        <textarea name="content" rows="8" required><?= htmlspecialchars($editNews['content']) ?></textarea>
                    </div>
                    <div class="form-group" style="display: flex; align-items: center; gap: 8px;">
                        <input type="checkbox" name="is_published" id="is_pub" value="1" <?= $editNews['is_published'] ? 'checked' : '' ?> style="width: auto; margin: 0;">
                        <label for="is_pub" style="margin: 0;">Опубликовать сразу</label>
                    </div>
                    <div style="display: flex; gap: 8px; margin-top: 16px;">
                        <button type="submit" class="btn btn-primary">Сохранить</button>
                        <a href="news.php" class="btn btn-outline">Отмена</a>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <div class="table-wrapper" style="padding: 24px; margin-bottom: 24px;">
                <h3 style="margin-bottom: 16px;">Добавить новость</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="save">
                    
                    <div class="form-group">
                        <label>Заголовок *</label>
                        <input type="text" name="title" required>
                    </div>
                    <div class="form-group">
                        <label>URL-адрес (slug)</label>
                        <input type="text" name="slug" placeholder="skidka-20-na-rozy">
                    </div>
                    <div class="form-group">
                        <label>Текст новости *</label>
                        <textarea name="content" rows="8" required></textarea>
                    </div>
                    <div class="form-group" style="display: flex; align-items: center; gap: 8px;">
                        <input type="checkbox" name="is_published" id="is_pub_new" value="1" style="width: auto; margin: 0;">
                        <label for="is_pub_new" style="margin: 0;">Опубликовать сразу</label>
                    </div>
                    <button type="submit" class="btn btn-primary">Создать</button>
                </form>
            </div>
        <?php endif; ?>

        <div class="table-wrapper">
            <form method="GET" class="table-filters">
                <select name="status">
                    <option value="all" <?= $statusFilter === 'all' ? 'selected' : '' ?>>Все</option>
                    <option value="published" <?= $statusFilter === 'published' ? 'selected' : '' ?>>Опубликованные</option>
                    <option value="draft" <?= $statusFilter === 'draft' ? 'selected' : '' ?>>Черновики</option>
                </select>
                <button type="submit" class="btn btn-sm btn-primary">Применить</button>
            </form>

            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Заголовок</th>
                        <th>Автор</th>
                        <th>Дата</th>
                        <th>Статус</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($newsList as $item): ?>
                        <tr>
                            <td><?= $item['id'] ?></td>
                            <td><strong><?= htmlspecialchars($item['title']) ?></strong></td>
                            <td><?= htmlspecialchars($item['author_name'] ?? 'Неизвестно') ?></td>
                            <td><?= date('d.m.Y H:i', strtotime($item['created_at'])) ?></td>
                            <td>
                                <span class="badge <?= $item['is_published'] ? 'badge-completed' : 'badge-cancelled' ?>">
                                    <?= $item['is_published'] ? 'Опубликовано' : 'Черновик' ?>
                                </span>
                            </td>
                            <td>
                                <div class="actions">
                                    <a href="news.php?edit=<?= $item['id'] ?>" class="btn btn-sm btn-outline">Изменить</a>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Удалить новость?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">Удалить</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($newsList)): ?>
                        <tr><td colspan="6" class="empty-state">Новостей пока нет</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>