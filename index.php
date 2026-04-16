<?php
// ===== VİNTAGE VIBE RECORDS - ANA SAYFA =====
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once 'db_baglan.php';

// Session favoriler ve sepet başlat
if (!isset($_SESSION['favoriler'])) {
    $_SESSION['favoriler'] = [];
}
if (!isset($_SESSION['sepet'])) {
    $_SESSION['sepet'] = [];
}

try {
    // ===== 1. PARAMETRELER =====
    $arama_kelimesi = isset($_GET['arama']) ? trim($_GET['arama']) : '';
    $secili_kategori = isset($_GET['kategori']) ? max(0, (int)$_GET['kategori']) : 0;
    $mevcut_sayfa = isset($_GET['sayfa']) ? max(1, (int)$_GET['sayfa']) : 1;
    $sirala = isset($_GET['sirala']) ? $_GET['sirala'] : 'yeni';
    $fiyat_min = isset($_GET['fiyat_min']) && $_GET['fiyat_min'] !== '' ? max(0, (float)$_GET['fiyat_min']) : null;
    $fiyat_max = isset($_GET['fiyat_max']) && $_GET['fiyat_max'] !== '' ? max(0, (float)$_GET['fiyat_max']) : null;
    $yil_min = isset($_GET['yil_min']) && $_GET['yil_min'] !== '' ? max(1900, (int)$_GET['yil_min']) : null;
    $yil_max = isset($_GET['yil_max']) && $_GET['yil_max'] !== '' ? max(1900, (int)$_GET['yil_max']) : null;
    $baski_turu_filter = isset($_GET['baski_turu']) ? trim($_GET['baski_turu']) : '';
    $stok_filter = isset($_GET['stok_durumu']) ? trim($_GET['stok_durumu']) : '';

    // ===== 2. SIRALAM =====
    $order_sql = "p.id DESC";
    if ($sirala == 'fiyat_artan') {
        $order_sql = "CAST(p.fiyat AS DECIMAL(10,2)) ASC";
    } elseif ($sirala == 'fiyat_azalan') {
        $order_sql = "CAST(p.fiyat AS DECIMAL(10,2)) DESC";
    } elseif ($sirala == 'eski') {
        $order_sql = "p.cikis_yili ASC";
    }

    // ===== 3. SAYFALAMA =====
    $urun_per_page = 12;
    $offset = ($mevcut_sayfa - 1) * $urun_per_page;

    // ===== 4. DİNAMİK FİLTRELER =====
    $where_sql = [];
    $sql_params = [];

    if (!empty($arama_kelimesi)) {
        $where_sql[] = "(p.baslik LIKE :arama1 OR p.sanatci LIKE :arama2)";
        $sql_params[':arama1'] = '%' . $arama_kelimesi . '%';
        $sql_params[':arama2'] = '%' . $arama_kelimesi . '%';
    }

    if ($secili_kategori > 0) {
        $where_sql[] = "p.kategori_id = :kategori_id";
        $sql_params[':kategori_id'] = $secili_kategori;
    }

    if ($baski_turu_filter !== '') {
        $where_sql[] = "p.baski_turu = :baski_turu";
        $sql_params[':baski_turu'] = $baski_turu_filter;
    }

    if ($fiyat_min !== null) {
        $where_sql[] = "CAST(p.fiyat AS DECIMAL(10,2)) >= :fiyat_min";
        $sql_params[':fiyat_min'] = $fiyat_min;
    }

    if ($fiyat_max !== null) {
        $where_sql[] = "CAST(p.fiyat AS DECIMAL(10,2)) <= :fiyat_max";
        $sql_params[':fiyat_max'] = $fiyat_max;
    }

    if ($yil_min !== null) {
        $where_sql[] = "p.cikis_yili >= :yil_min";
        $sql_params[':yil_min'] = $yil_min;
    }

    if ($yil_max !== null) {
        $where_sql[] = "p.cikis_yili <= :yil_max";
        $sql_params[':yil_max'] = $yil_max;
    }

    if ($stok_filter === 'stokta') {
        $where_sql[] = "p.stok > 0";
    } elseif ($stok_filter === 'az_stok') {
        $where_sql[] = "p.stok BETWEEN 1 AND 3";
    } elseif ($stok_filter === 'tukendi') {
        $where_sql[] = "p.stok <= 0";
    }

    $where_clause = !empty($where_sql) ? "WHERE " . implode(" AND ", $where_sql) : "";

    // ===== 5. TOPLAM URUN SAYISI =====
    $count_sql = "SELECT COUNT(*) as total FROM plaklar p $where_clause";
    $count_stmt = $db->prepare($count_sql);
    foreach ($sql_params as $param_key => $param_value) {
        $count_stmt->bindValue($param_key, $param_value);
    }
    $count_stmt->execute();

    $total_result = $count_stmt->fetch(PDO::FETCH_ASSOC);
    $toplam_kayit = $total_result['total'] ?? 0;
    $toplam_sayfa = max(1, ceil($toplam_kayit / $urun_per_page));

    // Sayfa hata kontrolü
    if ($mevcut_sayfa > $toplam_sayfa) {
        $mevcut_sayfa = $toplam_sayfa;
        $offset = ($mevcut_sayfa - 1) * $urun_per_page;
    }

    // ===== 6. KATEGORILER =====
    $kat_sql = "SELECT id, kategori_adi FROM kategoriler ORDER BY kategori_adi ASC";
    $kat_stmt = $db->prepare($kat_sql);
    $kat_stmt->execute();
    $kategoriler = $kat_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Baskı türü seçenekleri
    $baski_stmt = $db->query("SELECT DISTINCT baski_turu FROM plaklar WHERE baski_turu IS NOT NULL AND baski_turu != '' ORDER BY baski_turu ASC");
    $baski_turleri = $baski_stmt->fetchAll(PDO::FETCH_COLUMN);

    // ===== 7. ÜRÜNLER =====
    $product_sql = "SELECT p.id, p.baslik, p.sanatci, p.fiyat, p.kapak_gorseli, p.stok, p.baski_turu, p.cikis_yili, k.kategori_adi 
                   FROM plaklar p 
                   LEFT JOIN kategoriler k ON p.kategori_id = k.id 
                   $where_clause 
                   ORDER BY $order_sql 
                   LIMIT :limit OFFSET :offset";
    $product_stmt = $db->prepare($product_sql);
    foreach ($sql_params as $param_key => $param_value) {
        $product_stmt->bindValue($param_key, $param_value);
    }
    $product_stmt->bindValue(':limit', $urun_per_page, PDO::PARAM_INT);
    $product_stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $product_stmt->execute();

    $plaklar = $product_stmt->fetchAll(PDO::FETCH_ASSOC);

    // ===== 8. URL PARAMETRELER İ =====
    $url_params = [];
    if (!empty($arama_kelimesi)) $url_params[] = "arama=" . urlencode($arama_kelimesi);
    if ($secili_kategori > 0) $url_params[] = "kategori=" . $secili_kategori;
    if ($sirala !== 'yeni') $url_params[] = "sirala=" . urlencode($sirala);
    if ($fiyat_min !== null) $url_params[] = "fiyat_min=" . urlencode((string)$fiyat_min);
    if ($fiyat_max !== null) $url_params[] = "fiyat_max=" . urlencode((string)$fiyat_max);
    if ($yil_min !== null) $url_params[] = "yil_min=" . urlencode((string)$yil_min);
    if ($yil_max !== null) $url_params[] = "yil_max=" . urlencode((string)$yil_max);
    if ($baski_turu_filter !== '') $url_params[] = "baski_turu=" . urlencode($baski_turu_filter);
    if ($stok_filter !== '') $url_params[] = "stok_durumu=" . urlencode($stok_filter);
    $url_ek = !empty($url_params) ? "&" . implode("&", $url_params) : "";
} catch (PDOException $e) {
    die("Veritabanı Hatası: " . htmlspecialchars($e->getMessage()));
} catch (Exception $e) {
    die("Hata Oluştu: " . htmlspecialchars($e->getMessage()));
}
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Vintage Vibe Records - Özel ve nadir plaklar koleksiyonu">
    <title>Vintage Vibe Records | Premium Plak Dükkânı</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Stylesheet -->
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">

    <!-- Session Sepet - localStorage Senkronizasyon -->
    <script>
        // Server'daki session'daki sepet verilerini localStorage'a senkronize et (array format)
        <?php
        $cartArray = [];
        if (!empty($_SESSION['sepet']) && is_array($_SESSION['sepet'])) {
            foreach ($_SESSION['sepet'] as $cartItem) {
                if (isset($cartItem['id']) && isset($cartItem['quantity'])) {
                    $cartArray[] = [
                        'id' => (string)$cartItem['id'],
                        'quantity' => (int)$cartItem['quantity']
                    ];
                }
            }
        }
        ?>
        const serverCart = <?php echo json_encode($cartArray); ?>;
        localStorage.setItem('vvr_cart', JSON.stringify(serverCart));
    </script>
</head>

<body class="vvr-theme">

    <!-- ===== TOP BAR ===== -->
    <div class="vvr-topbar">
        <div class="vvr-container">
            <div class="topbar-left">
                <span class="topbar-text">🎵 Vintage Vibe Records - Premium Plak Koleksiyonu</span>
            </div>
            <div class="topbar-right">
                <a href="page.php?slug=gumruk-sozlesmesi" class="topbar-link">Gümrük Sözleşmesi</a>
                <span class="divider">|</span>
                <a href="page.php?slug=hakkimizda" class="topbar-link">Hakkımızda</a>
            </div>
        </div>
    </div>

    <!-- ===== HEADER ===== -->
    <header class="vvr-header">
        <div class="vvr-container">
            <div class="header-wrapper">

                <!-- LOGO -->
                <div class="header-logo">
                    <a href="index.php" class="logo-link">
                        <span class="logo-text">Vintage Vibe Records</span>
                    </a>
                </div>

                <!-- SEARCH -->
                <div class="header-search">
                    <form action="index.php" method="GET" class="search-form">
                        <input
                            type="text"
                            name="arama"
                            class="search-input"
                            placeholder="Sanatçı, Albüm Adı..."
                            value="<?php echo htmlspecialchars($arama_kelimesi); ?>">
                        <button type="submit" class="search-btn">🔍</button>
                    </form>
                </div>

                <!-- ICONS -->
                <div class="header-icons">
                    <a href="login.php" class="header-icon" title="Hesabım">👤</a>
                    <a href="favoriler.php" class="header-icon" title="Favorilerim">♡</a>
                    <a href="sepet.php" class="header-icon" title="Sepetim">
                        🛒
                        <span class="icon-badge">0</span>
                    </a>
                </div>

            </div>
        </div>
    </header>

    <!-- ===== NAVBAR ===== -->
    <div class="vvr-navbar">
        <div class="vvr-container">
            <div class="navbar-wrapper">

                <!-- KATEGORİLER -->
                <div class="categories-menu">
                    <span class="categories-label">Kategoriler:</span>
                    <div class="categories-list">
                        <a href="index.php" class="category-item <?php echo ($secili_kategori == 0 && empty($arama_kelimesi)) ? 'active' : ''; ?>">
                            Tüm Ürünler
                        </a>
                        <?php foreach ($kategoriler as $kat):
                            $kat_url = "index.php?kategori=" . $kat['id'];
                            if (!empty($arama_kelimesi)) $kat_url .= "&arama=" . urlencode($arama_kelimesi);
                            if ($sirala !== 'yeni') $kat_url .= "&sirala=" . urlencode($sirala);
                        ?>
                            <a href="<?php echo $kat_url; ?>"
                                class="category-item <?php echo ($secili_kategori == $kat['id']) ? 'active' : ''; ?>">
                                <?php echo htmlspecialchars($kat['kategori_adi']); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- SIRALA -->
                <div class="navbar-controls">
                    <form method="GET" class="sort-form">
                        <select name="sirala" class="sort-select" onchange="this.form.submit()">
                            <option value="yeni">En Yeniler</option>
                            <option value="fiyat_artan" <?php echo $sirala == 'fiyat_artan' ? 'selected' : ''; ?>>Fiyat ↑</option>
                            <option value="fiyat_azalan" <?php echo $sirala == 'fiyat_azalan' ? 'selected' : ''; ?>>Fiyat ↓</option>
                            <option value="eski" <?php echo $sirala == 'eski' ? 'selected' : ''; ?>>Eski Kayıtlar</option>
                        </select>
                        <?php if ($secili_kategori > 0): ?>
                            <input type="hidden" name="kategori" value="<?php echo $secili_kategori; ?>">
                        <?php endif; ?>
                        <?php if (!empty($arama_kelimesi)): ?>
                            <input type="hidden" name="arama" value="<?php echo htmlspecialchars($arama_kelimesi); ?>">
                        <?php endif; ?>
                        <?php if ($fiyat_min !== null): ?>
                            <input type="hidden" name="fiyat_min" value="<?php echo htmlspecialchars((string)$fiyat_min); ?>">
                        <?php endif; ?>
                        <?php if ($fiyat_max !== null): ?>
                            <input type="hidden" name="fiyat_max" value="<?php echo htmlspecialchars((string)$fiyat_max); ?>">
                        <?php endif; ?>
                        <?php if ($yil_min !== null): ?>
                            <input type="hidden" name="yil_min" value="<?php echo htmlspecialchars((string)$yil_min); ?>">
                        <?php endif; ?>
                        <?php if ($yil_max !== null): ?>
                            <input type="hidden" name="yil_max" value="<?php echo htmlspecialchars((string)$yil_max); ?>">
                        <?php endif; ?>
                        <?php if ($baski_turu_filter !== ''): ?>
                            <input type="hidden" name="baski_turu" value="<?php echo htmlspecialchars($baski_turu_filter); ?>">
                        <?php endif; ?>
                        <?php if ($stok_filter !== ''): ?>
                            <input type="hidden" name="stok_durumu" value="<?php echo htmlspecialchars($stok_filter); ?>">
                        <?php endif; ?>
                    </form>
                </div>

            </div>
        </div>
    </div>

    <div class="vvr-container" style="padding-top: 18px; padding-bottom: 10px;">
        <form method="GET" style="background: var(--surface-light); border: 1px solid var(--border-color); border-radius: 8px; padding: 14px; display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 10px; align-items: end;">
            <input type="hidden" name="kategori" value="<?php echo (int)$secili_kategori; ?>">
            <?php if (!empty($arama_kelimesi)): ?>
                <input type="hidden" name="arama" value="<?php echo htmlspecialchars($arama_kelimesi); ?>">
            <?php endif; ?>
            <input type="hidden" name="sirala" value="<?php echo htmlspecialchars($sirala); ?>">

            <div>
                <label for="fiyat_min" style="font-size: 0.8em; color: var(--text-muted); display: block; margin-bottom: 4px;">Min Fiyat</label>
                <input id="fiyat_min" type="number" step="0.01" min="0" name="fiyat_min" value="<?php echo htmlspecialchars((string)($fiyat_min ?? '')); ?>" class="search-input" style="border: 1px solid var(--border-color); border-radius: 6px; background: white;">
            </div>

            <div>
                <label for="fiyat_max" style="font-size: 0.8em; color: var(--text-muted); display: block; margin-bottom: 4px;">Max Fiyat</label>
                <input id="fiyat_max" type="number" step="0.01" min="0" name="fiyat_max" value="<?php echo htmlspecialchars((string)($fiyat_max ?? '')); ?>" class="search-input" style="border: 1px solid var(--border-color); border-radius: 6px; background: white;">
            </div>

            <div>
                <label for="yil_min" style="font-size: 0.8em; color: var(--text-muted); display: block; margin-bottom: 4px;">Min Yıl</label>
                <input id="yil_min" type="number" min="1900" max="2100" name="yil_min" value="<?php echo htmlspecialchars((string)($yil_min ?? '')); ?>" class="search-input" style="border: 1px solid var(--border-color); border-radius: 6px; background: white;">
            </div>

            <div>
                <label for="yil_max" style="font-size: 0.8em; color: var(--text-muted); display: block; margin-bottom: 4px;">Max Yıl</label>
                <input id="yil_max" type="number" min="1900" max="2100" name="yil_max" value="<?php echo htmlspecialchars((string)($yil_max ?? '')); ?>" class="search-input" style="border: 1px solid var(--border-color); border-radius: 6px; background: white;">
            </div>

            <div>
                <label for="baski_turu" style="font-size: 0.8em; color: var(--text-muted); display: block; margin-bottom: 4px;">Baskı Türü</label>
                <select id="baski_turu" name="baski_turu" class="sort-select" style="width: 100%; border: 1px solid var(--border-color); border-radius: 6px; background: white;">
                    <option value="">Tümü</option>
                    <?php foreach ($baski_turleri as $baski_turu): ?>
                        <option value="<?php echo htmlspecialchars($baski_turu); ?>" <?php echo $baski_turu_filter === $baski_turu ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($baski_turu); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="stok_durumu" style="font-size: 0.8em; color: var(--text-muted); display: block; margin-bottom: 4px;">Stok</label>
                <select id="stok_durumu" name="stok_durumu" class="sort-select" style="width: 100%; border: 1px solid var(--border-color); border-radius: 6px; background: white;">
                    <option value="">Tümü</option>
                    <option value="stokta" <?php echo $stok_filter === 'stokta' ? 'selected' : ''; ?>>Stokta</option>
                    <option value="az_stok" <?php echo $stok_filter === 'az_stok' ? 'selected' : ''; ?>>Az Stok (1-3)</option>
                    <option value="tukendi" <?php echo $stok_filter === 'tukendi' ? 'selected' : ''; ?>>Tükendi</option>
                </select>
            </div>

            <button type="submit" class="newsletter-btn" style="padding: 10px 14px;">Filtrele</button>
        </form>
    </div>

    <!-- ===== MAIN CONTENT ===== -->
    <main class="vvr-main">
        <div class="vvr-container">

            <!-- PRODUCTS SECTION -->
            <section class="products-section">

                <!-- BAŞLIK -->
                <div class="products-header">
                    <h2 class="products-title">
                        <?php
                        if (!empty($arama_kelimesi)) {
                            echo '"' . htmlspecialchars($arama_kelimesi) . '" Arama Sonuçları';
                        } elseif ($secili_kategori > 0) {
                            $kat_name = 'Kategori';
                            foreach ($kategoriler as $k) {
                                if ($k['id'] == $secili_kategori) {
                                    $kat_name = $k['kategori_adi'];
                                    break;
                                }
                            }
                            echo htmlspecialchars($kat_name);
                        } else {
                            echo 'Tüm Ürünler';
                        }
                        ?>
                    </h2>
                    <p class="products-count">Toplam: <strong><?php echo $toplam_kayit; ?></strong> ürün</p>
                </div>

                <!-- GRID -->
                <div class="products-grid">
                    <?php if (count($plaklar) > 0): ?>
                        <?php foreach ($plaklar as $plak):
                            $fiyat_format = number_format((float)$plak['fiyat'], 2, ',', '.');
                            $resim = '';
                            if (!empty($plak['kapak_gorseli'])) {
                                $path = 'images/' . htmlspecialchars($plak['kapak_gorseli']);
                                if (file_exists($path)) {
                                    $resim = $path;
                                }
                            }
                        ?>
                            <div class="product-card">
                                <div class="product-image-wrapper">
                                    <?php if ($resim): ?>
                                        <img src="<?php echo $resim; ?>" alt="<?php echo htmlspecialchars($plak['baslik']); ?>" class="product-image" loading="lazy">
                                    <?php else: ?>
                                        <div class="product-image-placeholder">📀 Kapak Yok</div>
                                    <?php endif; ?>

                                    <!-- FAVORİLER KALBİ -->
                                    <button class="favorite-btn" data-product-id="<?php echo (int)$plak['id']; ?>" title="Favorilere Ekle">♡</button>

                                    <a href="detay.php?id=<?php echo (int)$plak['id']; ?>" class="product-link-overlay"></a>
                                </div>

                                <div class="product-info">
                                    <div class="product-category"><?php echo htmlspecialchars($plak['kategori_adi'] ?? 'Diğer'); ?></div>
                                    <h3 class="product-title"><?php echo htmlspecialchars($plak['baslik']); ?></h3>
                                    <p class="product-artist"><?php echo htmlspecialchars($plak['sanatci']); ?></p>
                                    <?php if (!empty($plak['cikis_yili']) && $plak['cikis_yili'] != '0000'): ?>
                                        <p class="product-year">📅 <?php echo (int)$plak['cikis_yili']; ?></p>
                                    <?php endif; ?>
                                    <div class="product-footer">
                                        <span class="product-price"><?php echo $fiyat_format; ?> ₺</span>
                                        <a href="detay.php?id=<?php echo (int)$plak['id']; ?>" class="product-view-btn">İncele</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-products" style="grid-column: 1 / -1; text-align: center; padding: 60px 20px;">
                            <p style="font-size: 1.3em; margin-bottom: 10px;">📭 Sonuç Bulunamadı</p>
                            <p>Lütfen arama kriterlerinizi değiştirerek tekrar deneyin.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- PAGINATION -->
                <?php if ($toplam_sayfa > 1): ?>
                    <div class="pagination">
                        <?php for ($i = 1; $i <= $toplam_sayfa; $i++): ?>
                            <a href="?sayfa=<?php echo $i . $url_ek; ?>"
                                class="page-link <?php echo ($i == $mevcut_sayfa) ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>

            </section>

            <!-- NEWSLETTER -->
            <section class="newsletter-section">
                <div class="newsletter-content">
                    <h3>Özel Teklifleri Kaçırmayın</h3>
                    <p>Yeni koleksiyonlar ve özel indirimler için bültenimize abone olun</p>
                    <form class="newsletter-form">
                        <input type="email" placeholder="E-mail adresiniz..." required>
                        <button type="submit" class="newsletter-btn">Abone Ol</button>
                    </form>
                </div>
            </section>

        </div>
    </main>

    <!-- ===== FOOTER ===== -->
    <footer class="vvr-footer">
        <div class="vvr-container">
            <div class="footer-content">
                <div class="footer-section">
                    <h4>Kurumsal</h4>
                    <ul>
                        <li><a href="page.php?slug=hakkimizda">Hakkımızda</a></li>
                        <li><a href="page.php?slug=sss">Soru Cevap</a></li>
                        <li><a href="admin_pages.php">Sayfa Yönetimi</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>Yardım</h4>
                    <ul>
                        <li><a href="page.php?slug=gumruk-sozlesmesi">Gümrük Sözleşmesi</a></li>
                        <li><a href="page.php?slug=teslimat-iade">Teslimat & İade</a></li>
                        <li><a href="page.php?slug=gizlilik-politikasi">Gizlilik Politikası</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>Sosyal Ağlar</h4>
                    <ul>
                        <li><a href="#">📘 Facebook</a></li>
                        <li><a href="https://www.instagram.com/groovelog/">📷 Instagram</a></li>
                        <li><a href="https://www.discogs.com/user/berkann">💿 Discogs</a></li>
                        <li><a href="#">🐦 Twitter</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>Ödeme Yöntemleri</h4>
                    <p style="color: var(--text-muted); font-size: 0.9em;">Kredi Kartı • Banka Transferi • Kripto Para</p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2026 Vintage Vibe Records. Tüm Hakları Saklıdır.</p>
            </div>
        </div>
    </footer>

    <script src="js/main.js?v=<?php echo time(); ?>"></script>
</body>

</html>