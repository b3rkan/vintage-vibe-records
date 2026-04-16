<?php
session_start();
require_once 'db_baglan.php';

$slug = isset($_GET['slug']) ? trim($_GET['slug']) : 'hakkimizda';

if ($slug === '' || !preg_match('/^[a-z0-9-]+$/', $slug)) {
    http_response_code(400);
    die('Gecersiz sayfa istegi.');
}

$stmt = $db->prepare('SELECT title, content FROM site_pages WHERE slug = ? AND is_published = 1 LIMIT 1');
$stmt->execute([$slug]);
$page = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$page) {
    http_response_code(404);
    $page = [
        'title' => 'Sayfa Bulunamadi',
        'content' => 'Istediginiz icerik bulunamadi veya yayin disi.',
    ];
}
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo htmlspecialchars($page['title']); ?> | Vintage Vibe Records">
    <title><?php echo htmlspecialchars($page['title']); ?> | Vintage Vibe Records</title>
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
</head>

<body class="vvr-theme">
    <div class="vvr-topbar">
        <div class="vvr-container">
            <div class="topbar-left"><span class="topbar-text">Vintage Vibe Records</span></div>
            <div class="topbar-right"><a href="index.php" class="topbar-link">Ana Sayfaya Don</a></div>
        </div>
    </div>

    <header class="vvr-header">
        <div class="vvr-container">
            <div class="header-wrapper">
                <div class="header-logo"><a href="index.php" class="logo-link"><span class="logo-text">Vintage Vibe Records</span></a></div>
                <div class="header-search">
                    <form action="index.php" method="GET" class="search-form">
                        <input type="text" name="arama" class="search-input" placeholder="Sanatci, Album...">
                        <button type="submit" class="search-btn">Ara</button>
                    </form>
                </div>
                <div class="header-icons">
                    <a href="login.php" class="header-icon" title="Hesabim">H</a>
                    <a href="favoriler.php" class="header-icon" title="Favorilerim">F</a>
                    <a href="sepet.php" class="header-icon" title="Sepetim">S</a>
                </div>
            </div>
        </div>
    </header>

    <main class="vvr-main">
        <div class="vvr-container" style="padding-top: 34px; padding-bottom: 34px;">
            <h1 style="margin-bottom: 20px;"><?php echo htmlspecialchars($page['title']); ?></h1>
            <article style="background: var(--surface-light); border: 1px solid var(--border-color); border-radius: 10px; padding: 24px; color: var(--text-main); line-height: 1.8; white-space: pre-wrap;">
                <?php echo nl2br(htmlspecialchars($page['content'])); ?>
            </article>
        </div>
    </main>

    <footer class="vvr-footer">
        <div class="vvr-container">
            <div class="footer-content">
                <div class="footer-section">
                    <h4>Kurumsal</h4>
                    <ul>
                        <li><a href="page.php?slug=hakkimizda">Hakkimizda</a></li>
                        <li><a href="page.php?slug=sss">S.S.S.</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>Yardim</h4>
                    <ul>
                        <li><a href="page.php?slug=gumruk-sozlesmesi">Gumruk Sozlesmesi</a></li>
                        <li><a href="page.php?slug=teslimat-iade">Teslimat ve Iade</a></li>
                        <li><a href="page.php?slug=gizlilik-politikasi">Gizlilik Politikasi</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2026 Vintage Vibe Records</p>
            </div>
        </div>
    </footer>
</body>

</html>