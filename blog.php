<?php
require_once 'db.php';

$stmt = $pdo->query("SELECT * FROM news WHERE is_published = 1 ORDER BY created_at DESC");
$newsList = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Новости и акции — Anna Flowers</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Montserrat:wght@600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #BE185D; --primary-hover: #9D174D; --primary-light: #FDF2F8; --text-dark: #111827; --text-body: #4B5563; --text-light: #6B7280; --white: #FFFFFF; --bg-light: #FAFAFA; --border: #E5E7EB; --radius: 12px; --shadow-md: 0 4px 6px -1px rgba(0,0,0,0.1); }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; color: var(--text-body); background: var(--bg-light); line-height: 1.6; }
        .container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }
        
        .header { background: var(--white); border-bottom: 1px solid var(--border); padding: 20px 0; }
        .header-inner { display: flex; align-items: center; justify-content: space-between; }
        .logo { font-family: 'Montserrat', sans-serif; font-size: 1.6rem; font-weight: 800; color: var(--primary); text-decoration: none; }
        .logo span { color: var(--text-dark); }
        .nav { display: flex; gap: 28px; }
        .nav-link { text-decoration: none; color: var(--text-dark); font-weight: 500; transition: color 0.2s; }
        .nav-link:hover, .nav-link.active { color: var(--primary); }

        .page-header { text-align: center; padding: 60px 0 40px; }
        .page-header h1 { font-family: 'Montserrat', sans-serif; font-size: 2.5rem; color: var(--text-dark); margin-bottom: 16px; }
        .page-header p { color: var(--text-light); font-size: 1.1rem; max-width: 600px; margin: 0 auto; }

        .news-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 28px; padding-bottom: 80px; }
        
        .news-card { background: var(--white); border-radius: var(--radius); box-shadow: var(--shadow-md); overflow: hidden; transition: transform 0.3s; display: flex; flex-direction: column; }
        .news-card:hover { transform: translateY(-4px); }
        .news-card-body { padding: 24px; flex: 1; display: flex; flex-direction: column; }
        .news-date { font-size: 0.85rem; color: var(--primary); font-weight: 600; margin-bottom: 8px; }
        .news-title { font-family: 'Montserrat', sans-serif; font-size: 1.25rem; color: var(--text-dark); margin-bottom: 12px; line-height: 1.3; }
        .news-excerpt { color: var(--text-light); font-size: 0.95rem; margin-bottom: 20px; flex: 1; }
        .news-link { color: var(--primary); text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 4px; }
        .news-link:hover { color: var(--primary-hover); }

        .empty-news { text-align: center; padding: 60px 20px; color: var(--text-light); grid-column: 1 / -1; }
        
        @media (max-width: 768px) {
            .header-inner { flex-direction: column; gap: 16px; }
            .page-header h1 { font-size: 2rem; }
            .news-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="container header-inner">
            <a href="index.html" class="logo">Anna <span>Flowers</span></a>
            <nav class="nav">
                <a href="index.html#catalog" class="nav-link">Каталог</a>
                <a href="index.html#reviews" class="nav-link">Отзывы</a>
                <a href="blog.php" class="nav-link active">Новости</a>
                <a href="index.html#contact" class="nav-link">Контакты</a>
            </nav>
        </div>
    </header>

    <main class="container">
        <div class="page-header">
            <h1>Новости и акции</h1>
            <p>Будьте в курсе свежих поставок, сезонных предложений и специальных акций нашего салона.</p>
        </div>

        <div class="news-grid">
            <?php if (empty($newsList)): ?>
                <div class="empty-news">
                    <h3>Пока нет опубликованных новостей</h3>
                    <p>Загляните к нам позже, мы обязательно поделимся чем-то интересным! 🌸</p>
                </div>
            <?php else: ?>
                <?php foreach ($newsList as $news): ?>
                    <article class="news-card">
                        <div class="news-card-body">
                            <div class="news-date"><?= date('d.m.Y', strtotime($news['created_at'])) ?></div>
                            <h2 class="news-title"><?= htmlspecialchars($news['title']) ?></h2>
                            <p class="news-excerpt">
                                <?= htmlspecialchars(mb_substr(strip_tags($news['content']), 0, 150)) ?>...
                            </p>
                            <a href="#" class="news-link" onclick="alert('<?= addslashes(htmlspecialchars(strip_tags($news['content']))) ?>'); return false;">Читать далее →</a>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>