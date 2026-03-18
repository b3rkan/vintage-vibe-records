<?php 
// ===== VİNTAGE VIBE RECORDS - ANA SAYFA =====
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once 'db_baglan.php';

try {
    // ===== 1. PARAMETRELER =====
    $arama_kelimesi = isset($_GET['arama']) ? trim($_GET['arama']) : '';
    $secili_kategori = isset($_GET['kategori']) ? max(0, (int)$_GET['kategori']) : 0;
    $mevcut_sayfa = isset($_GET['sayfa']) ? max(1, (int)$_GET['sayfa']) : 1;
    $sirala = isset($_GET['sirala']) ? $_GET['sirala'] : 'yeni';
    
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
    
    // ===== 4. TOPLAM URUN SAYISI =====
    if (!empty($arama_kelimesi)) {
        $count_sql = "SELECT COUNT(*) as total FROM plaklar WHERE baslik LIKE ? OR sanatci LIKE ?";
        $count_stmt = $db->prepare($count_sql);
        $count_stmt->execute(['%' . $arama_kelimesi . '%', '%' . $arama_kelimesi . '%']);
    } elseif ($secili_kategori > 0) {
        $count_sql = "SELECT COUNT(*) as total FROM plaklar WHERE kategori_id = ?";
        $count_stmt = $db->prepare($count_sql);
        $count_stmt->execute([$secili_kategori]);
    } else {
        $count_sql = "SELECT COUNT(*) as total FROM plaklar";
        $count_stmt = $db->query($count_sql);
    }
    
    $total_result = $count_stmt->fetch(PDO::FETCH_ASSOC);
    $toplam_kayit = $total_result['total'] ?? 0;
    $toplam_sayfa = max(1, ceil($toplam_kayit / $urun_per_page));
    
    // Sayfa hata kontrolü
    if ($mevcut_sayfa > $toplam_sayfa) {
        $mevcut_sayfa = $toplam_sayfa;
        $offset = ($mevcut_sayfa - 1) * $urun_per_page;
    }
    
    // ===== 5. KATEGORILER =====
    $kat_sql = "SELECT id, kategori_adi FROM kategoriler ORDER BY kategori_adi ASC";
    $kat_stmt = $db->prepare($kat_sql);
    $kat_stmt->execute();
    $kategoriler = $kat_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // ===== 6. ÜRÜNLER =====
    if (!empty($arama_kelimesi)) {
        $product_sql = "SELECT p.id, p.baslik, p.sanatci, p.fiyat, p.kapak_gorseli, p.stok, p.baski_turu, p.cikis_yili, k.kategori_adi 
                       FROM plaklar p 
                       LEFT JOIN kategoriler k ON p.kategori_id = k.id 
                       WHERE p.baslik LIKE ? OR p.sanatci LIKE ? 
                       ORDER BY $order_sql 
                       LIMIT ? OFFSET ?";
        $product_stmt = $db->prepare($product_sql);
        $product_stmt->bindValue(1, '%' . $arama_kelimesi . '%', PDO::PARAM_STR);
        $product_stmt->bindValue(2, '%' . $arama_kelimesi . '%', PDO::PARAM_STR);
        $product_stmt->bindValue(3, $urun_per_page, PDO::PARAM_INT);
        $product_stmt->bindValue(4, $offset, PDO::PARAM_INT);
        $product_stmt->execute();
    } elseif ($secili_kategori > 0) {
        $product_sql = "SELECT p.id, p.baslik, p.sanatci, p.fiyat, p.kapak_gorseli, p.stok, p.baski_turu, p.cikis_yili, k.kategori_adi 
                       FROM plaklar p 
                       LEFT JOIN kategoriler k ON p.kategori_id = k.id 
                       WHERE p.kategori_id = ? 
                       ORDER BY $order_sql 
                       LIMIT ? OFFSET ?";
        $product_stmt = $db->prepare($product_sql);
        $product_stmt->bindValue(1, $secili_kategori, PDO::PARAM_INT);
        $product_stmt->bindValue(2, $urun_per_page, PDO::PARAM_INT);
        $product_stmt->bindValue(3, $offset, PDO::PARAM_INT);
        $product_stmt->execute();
    } else {
        $product_sql = "SELECT p.id, p.baslik, p.sanatci, p.fiyat, p.kapak_gorseli, p.stok, p.baski_turu, p.cikis_yili, k.kategori_adi 
                       FROM plaklar p 
                       LEFT JOIN kategoriler k ON p.kategori_id = k.id 
                       ORDER BY $order_sql 
                       LIMIT ? OFFSET ?";
        $product_stmt = $db->prepare($product_sql);
        $product_stmt->bindValue(1, $urun_per_page, PDO::PARAM_INT);
        $product_stmt->bindValue(2, $offset, PDO::PARAM_INT);
        $product_stmt->execute();
    }
    
    $plaklar = $product_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // ===== 7. URL PARAMETRELER İ =====
    $url_params = [];
    if (!empty($arama_kelimesi)) $url_params[] = "arama=" . urlencode($arama_kelimesi);
    if ($secili_kategori > 0) $url_params[] = "kategori=" . $secili_kategori;
    if ($sirala !== 'yeni') $url_params[] = "sirala=" . urlencode($sirala);
    $url_ek = !empty($url_params) ? "&" . implode("&", $url_params) : "";
    
} catch (PDOException $e) {
    die("Veritabanı Hatası: " . htmlspecialchars($e->getMessage()));
} catch (Exception $e) {
    die("Hata Oluştu: " . htmlspecialchars($e->getMessage()));
}
?><!DOCTYPE html>
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
</head>
<body class="vvr-theme">

<!-- ===== TOP BAR ===== -->
<div class="vvr-topbar">
    <div class="vvr-container">
        <div class="topbar-left">
            <span class="topbar-text">🎵 Vintage Vibe Records - Premium Plak Koleksiyonu</span>
        </div>
        <div class="topbar-right">
            <a href="#" class="topbar-link">Gümrük Sözleşmesi</a>
            <span class="divider">|</span>
            <a href="#" class="topbar-link">İletişim</a>
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
                    <span class="logo-text">🎛️ Vintage Vibe</span>
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
                <a href="sepet.php" class="header-icon" title="Favorilerim">♡</a>
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
                    <?php foreach($kategoriler as $kat): ?>
                    <a href="index.php?kategori=<?php echo $kat['id']; ?>" 
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
                    <?php if($secili_kategori > 0): ?>
                    <input type="hidden" name="kategori" value="<?php echo $secili_kategori; ?>">
                    <?php endif; ?>
                    <?php if(!empty($arama_kelimesi)): ?>
                    <input type="hidden" name="arama" value="<?php echo htmlspecialchars($arama_kelimesi); ?>">
                    <?php endif; ?>
                </form>
            </div>

        </div>
    </div>
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
                        foreach($kategoriler as $k) {
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
                            
                            <?php if ((int)$plak['stok'] <= 0): ?>
                                <div class="product-badge out-of-stock">Tükendi</div>
                            <?php elseif ((int)$plak['stok'] <= 3): ?>
                                <div class="product-badge low-stock">Son <?php echo (int)$plak['stok']; ?> Kaldı</div>
                            <?php elseif ($plak['baski_turu'] == 'Dönem Baskı'): ?>
                                <div class="product-badge vintage">Dönem Baskı</div>
                            <?php endif; ?>

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
                    <li><a href="#">Hakkımızda</a></li>
                    <li><a href="#">İletişim</a></li>
                    <li><a href="#">Blog</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h4>Yardım</h4>
                <ul>
                    <li><a href="#">Gümrük Sözleşmesi</a></li>
                    <li><a href="#">Teslimat & İade</a></li>
                    <li><a href="#">Gizlilik Politikası</a></li>
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