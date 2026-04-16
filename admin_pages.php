<?php
session_start();

if (!isset($_SESSION['admin_giris_yapti']) || $_SESSION['admin_giris_yapti'] !== true) {
    header('Location: login.php');
    exit;
}

require_once 'db_baglan.php';

$mesaj = '';
$hata = '';
$toastMesaj = '';
$toastTip = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $slug = trim($_POST['slug'] ?? '');
    $title = trim($_POST['title'] ?? '');
    $section = trim($_POST['section'] ?? 'kurumsal');
    $content = trim($_POST['content'] ?? '');
    $isPublished = isset($_POST['is_published']) ? 1 : 0;

    $allowedSections = ['kurumsal', 'yardim', 'musteri'];

    if ($slug === '' || !preg_match('/^[a-z0-9-]+$/', $slug)) {
        $hata = 'Slug sadece kucuk harf, rakam ve tire icerebilir.';
    } elseif ($title === '' || $content === '' || !in_array($section, $allowedSections, true)) {
        $hata = 'Tum alanlari dogru doldurun.';
    } else {
        try {
            if ($id > 0) {
                $stmt = $db->prepare('UPDATE site_pages SET slug = ?, title = ?, section = ?, content = ?, is_published = ? WHERE id = ?');
                $stmt->execute([$slug, $title, $section, $content, $isPublished, $id]);
                $mesaj = 'Sayfa guncellendi.';
                $toastMesaj = $mesaj;
            } else {
                $stmt = $db->prepare('INSERT INTO site_pages (slug, title, section, content, is_published) VALUES (?, ?, ?, ?, ?)');
                $stmt->execute([$slug, $title, $section, $content, $isPublished]);
                $mesaj = 'Yeni sayfa eklendi.';
                $toastMesaj = $mesaj;
            }
        } catch (Exception $e) {
            $hata = 'Kayit hatasi: ' . $e->getMessage();
            $toastMesaj = $hata;
            $toastTip = 'error';
        }
    }
}

$editPage = [
    'id' => 0,
    'slug' => '',
    'title' => '',
    'section' => 'kurumsal',
    'content' => '',
    'is_published' => 1,
];

if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    if ($editId > 0) {
        $stmt = $db->prepare('SELECT * FROM site_pages WHERE id = ?');
        $stmt->execute([$editId]);
        $found = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($found) {
            $editPage = $found;
        }
    }
}

$pagesStmt = $db->query('SELECT * FROM site_pages ORDER BY section ASC, title ASC');
$pages = $pagesStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sayfa Yonetimi | Vintage Vibe Records</title>
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    <style>
        .admin-pages-grid {
            display: grid;
            grid-template-columns: 1fr 1.2fr;
            gap: 24px;
            margin-top: 26px;
            margin-bottom: 40px;
        }

        .admin-pages-card {
            background: var(--surface-white);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.06);
        }

        .admin-pages-card h2 {
            margin-bottom: 16px;
            color: var(--text-main);
            font-size: 1.4em;
        }

        .admin-pages-table {
            overflow-x: auto;
        }

        .admin-pages-form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px 14px;
        }

        .admin-pages-form-grid .form-grup {
            margin-bottom: 0;
        }

        .admin-pages-form-grid .full {
            grid-column: 1 / -1;
        }

        .admin-toast {
            position: fixed;
            right: 18px;
            bottom: 18px;
            min-width: 280px;
            max-width: 420px;
            background: #10b981;
            color: #fff;
            border-radius: 10px;
            padding: 12px 14px;
            box-shadow: 0 10px 26px rgba(0, 0, 0, 0.25);
            z-index: 9999;
            animation: adminToastIn 0.25s ease-out;
        }

        .admin-toast.error {
            background: #ef4444;
        }

        @keyframes adminToastIn {
            from {
                transform: translateY(10px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        @media (max-width: 1024px) {
            .admin-pages-grid {
                grid-template-columns: 1fr;
            }

            .admin-pages-form-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body class="vvr-theme">
    <?php if ($toastMesaj !== ''): ?>
        <div id="admin-toast" class="admin-toast <?php echo $toastTip === 'error' ? 'error' : ''; ?>"><?php echo htmlspecialchars($toastMesaj); ?></div>
    <?php endif; ?>
    <div class="vvr-topbar">
        <div class="vvr-container">
            <div class="topbar-left"><span class="topbar-text">Vintage Vibe Records - İçerik Yönetimi</span></div>
            <div class="topbar-right"><a href="admin.php" class="topbar-link">← Admin Panele Dön</a></div>
        </div>
    </div>

    <header class="vvr-header">
        <div class="vvr-container">
            <div class="header-wrapper">
                <div class="header-logo">
                    <a href="admin_pages.php" class="logo-link"><span class="logo-text">Sayfa Yönetimi</span></a>
                </div>
                <div class="header-search">
                    <form action="admin_pages.php" method="GET" class="search-form">
                        <input type="text" class="search-input" placeholder="İçerik ara (yakında)">
                        <button type="submit" class="search-btn">🔎</button>
                    </form>
                </div>
                <div class="header-icons">
                    <a href="admin.php" class="header-icon" title="Ürün Yönetimi">📦</a>
                    <a href="index.php" class="header-icon" title="Vitrin">🏠</a>
                    <a href="cikis.php" class="header-icon" title="Çıkış">↩</a>
                </div>
            </div>
        </div>
    </header>

    <main class="vvr-main">
        <div class="vvr-container">
            <div class="admin-pages-grid">

                <?php if ($mesaj !== ''): ?>
                    <div class="basari-mesaji" style="margin-bottom: 14px; grid-column: 1 / -1;"><?php echo htmlspecialchars($mesaj); ?></div>
                <?php endif; ?>
                <?php if ($hata !== ''): ?>
                    <div class="hata-mesaji" style="margin-bottom: 14px; grid-column: 1 / -1;"><?php echo htmlspecialchars($hata); ?></div>
                <?php endif; ?>

                <section class="admin-pages-card">
                    <h2><?php echo (int)$editPage['id'] > 0 ? 'Sayfa Duzenle' : 'Yeni Sayfa Ekle'; ?></h2>
                    <form method="POST" class="plak-form admin-pages-form-grid">
                        <input type="hidden" name="id" value="<?php echo (int)$editPage['id']; ?>">

                        <div class="form-grup">
                            <label for="slug">Slug</label>
                            <input id="slug" type="text" name="slug" value="<?php echo htmlspecialchars($editPage['slug']); ?>" required>
                        </div>

                        <div class="form-grup">
                            <label for="title">Baslik</label>
                            <input id="title" type="text" name="title" value="<?php echo htmlspecialchars($editPage['title']); ?>" required>
                        </div>

                        <div class="form-grup">
                            <label for="section">Bolum</label>
                            <select id="section" name="section" required>
                                <option value="kurumsal" <?php echo $editPage['section'] === 'kurumsal' ? 'selected' : ''; ?>>Kurumsal</option>
                                <option value="yardim" <?php echo $editPage['section'] === 'yardim' ? 'selected' : ''; ?>>Yardim</option>
                                <option value="musteri" <?php echo $editPage['section'] === 'musteri' ? 'selected' : ''; ?>>Musteri Iliskileri</option>
                            </select>
                        </div>

                        <div class="form-grup full">
                            <label for="content">Icerik</label>
                            <textarea id="content" name="content" rows="8" required style="width: 100%; padding: 12px;"><?php echo htmlspecialchars($editPage['content']); ?></textarea>
                        </div>

                        <div class="form-grup full" style="display: flex; align-items: center; gap: 8px;">
                            <input id="is_published" type="checkbox" name="is_published" <?php echo (int)$editPage['is_published'] === 1 ? 'checked' : ''; ?>>
                            <label for="is_published" style="margin: 0;">Yayinda</label>
                        </div>

                        <button type="submit" class="gonder-btn full">Kaydet</button>
                    </form>
                </section>

                <section class="admin-pages-card">
                    <h2>Sayfa Listesi</h2>
                    <div class="admin-pages-table">
                        <table class="admin-tablo">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Baslik</th>
                                    <th>Slug</th>
                                    <th>Bolum</th>
                                    <th>Durum</th>
                                    <th>Islem</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pages as $page): ?>
                                    <tr>
                                        <td>#<?php echo (int)$page['id']; ?></td>
                                        <td><?php echo htmlspecialchars($page['title']); ?></td>
                                        <td><?php echo htmlspecialchars($page['slug']); ?></td>
                                        <td><?php echo htmlspecialchars($page['section']); ?></td>
                                        <td><?php echo (int)$page['is_published'] === 1 ? 'Yayinda' : 'Taslak'; ?></td>
                                        <td><a class="btn-guncelle" href="admin_pages.php?edit=<?php echo (int)$page['id']; ?>">Duzenle</a></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>
    </main>

    <footer class="vvr-footer">
        <div class="vvr-container">
            <div class="footer-bottom">
                <p>&copy; 2026 Vintage Vibe Records Admin. Tüm Hakları Saklıdır.</p>
            </div>
        </div>
    </footer>
    <script>
        (function() {
            const toast = document.getElementById('admin-toast');
            if (toast) {
                setTimeout(() => {
                    toast.style.opacity = '0';
                    toast.style.transition = 'opacity 0.25s ease';
                    setTimeout(() => toast.remove(), 260);
                }, 2600);
            }
        })();
    </script>
</body>

</html>