<?php
session_start();
require_once 'db_baglan.php';

// Session favoriler ve sepet başlat
if (!isset($_SESSION['favoriler'])) {
    $_SESSION['favoriler'] = [];
}
if (!isset($_SESSION['sepet'])) {
    $_SESSION['sepet'] = [];
}

// ID kontrolü
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$id = (int)$_GET['id'];

// Ürün verisi
try {
    $sorgu = $db->prepare("
        SELECT p.id, p.baslik, p.sanatci, p.format, p.firma, p.kondisyon_kapak, p.kondisyon_plak, p.fiyat, p.kapak_gorseli, p.stok, p.baski_turu, p.cikis_yili, p.kategori_id, k.kategori_adi 
        FROM plaklar p 
        LEFT JOIN kategoriler k ON p.kategori_id = k.id 
        WHERE p.id = ?
    ");
    $sorgu->execute([$id]);
    $plak = $sorgu->fetch(PDO::FETCH_ASSOC);

    if (!$plak) {
        http_response_code(404);
        die("<html><head><meta charset='UTF-8'></head><body style='background:#0f0f11; color:#f4f4f5; text-align:center; padding:50px; font-family:Poppins,sans-serif;'><h2>Üzgünüz, aradığınız plak bulunamadı</h2><br><a href='index.php' style='color:#d4af37; text-decoration:none;'>← Ana Sayfaya Dön</a></body></html>");
    }

    $fiyat = number_format((float)$plak['fiyat'], 2, ',', '.');
    $resim_yolu = '';
    if (!empty($plak['kapak_gorseli']) && file_exists('images/' . $plak['kapak_gorseli'])) {
        $resim_yolu = 'images/' . htmlspecialchars($plak['kapak_gorseli']);
    }

    $benzer_stmt = $db->prepare("
        SELECT id, baslik, sanatci, fiyat, kapak_gorseli, cikis_yili 
        FROM plaklar 
        WHERE kategori_id = ? AND id != ? 
        ORDER BY id DESC 
        LIMIT 4
    ");
    $benzer_stmt->execute([(int)$plak['kategori_id'], (int)$plak['id']]);
    $benzer_urunler = $benzer_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Veritabanı Hatası: " . htmlspecialchars($e->getMessage()));
}
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo htmlspecialchars($plak['baslik']); ?> - <?php echo htmlspecialchars($plak['sanatci']); ?>">
    <title><?php echo htmlspecialchars($plak['baslik']); ?> | Vintage Vibe Records</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Stylesheet -->
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">

    <!-- Session Sepet - localStorage Senkronizasyon -->
    <script>
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
                        <input type="text" name="arama" class="search-input" placeholder="Sanatçı, Albüm Adı..." value="">
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

    <!-- ===== MAIN CONTENT ===== -->
    <main class="vvr-main">
        <div class="vvr-container">

            <!-- BACK BUTTON -->
            <div style="margin-bottom: 30px;">
                <a href="index.php" style="color: var(--primary-accent); text-decoration: none; font-weight: 600; transition: var(--transition);" onmouseover="this.style.color='#8b2605'" onmouseout="this.style.color='var(--primary-accent)'">← Vitrine Dön</a>
            </div>

            <!-- DETAIL SECTION -->
            <section class="detay-main">
                <div class="detay-container">

                    <!-- LEFT: IMAGE -->
                    <div class="detay-sol">
                        <?php if ($resim_yolu): ?>
                            <img src="<?php echo $resim_yolu; ?>" alt="<?php echo htmlspecialchars($plak['baslik']); ?>" loading="lazy">
                        <?php else: ?>
                            <div class="gorsel-yok" style="width: 100%; max-width: 400px; height: 400px;">📀 Kapak Görseli Yok</div>
                        <?php endif; ?>
                    </div>

                    <!-- RIGHT: DETAILS -->
                    <div class="detay-sag">
                        <div style="display: flex; justify-content: space-between; align-items: start;">
                            <div>
                                <h2><?php echo htmlspecialchars($plak['baslik']); ?></h2>
                                <h3><?php echo htmlspecialchars($plak['sanatci']); ?></h3>
                            </div>
                            <button class="favorite-btn-detay" data-product-id="<?php echo (int)$plak['id']; ?>" title="Favorilere Ekle">♡</button>
                        </div>

                        <p class="fiyat-kocaman"><?php echo $fiyat; ?> ₺</p>

                        <ul class="ozellikler">
                            <li><strong>Kategori:</strong> <?php echo htmlspecialchars($plak['kategori_adi'] ?? 'Diğer'); ?></li>
                            <li><strong>Çıkış Yılı:</strong> <?php echo !empty($plak['cikis_yili']) && $plak['cikis_yili'] != '0000' ? (int)$plak['cikis_yili'] : 'Bilinmiyor'; ?></li>
                            <li><strong>Format:</strong> <?php echo htmlspecialchars($plak['format'] ?? '-'); ?></li>
                            <li><strong>Firma/Label:</strong> <?php echo htmlspecialchars($plak['firma'] ?? '-'); ?></li>
                            <li><strong>Baskı Türü:</strong> <?php echo htmlspecialchars($plak['baski_turu'] ?? '-'); ?></li>
                            <li><strong>Kondisyon/Kapak:</strong> <?php echo htmlspecialchars($plak['kondisyon_kapak'] ?? '-'); ?></li>
                            <li><strong>Kondisyon/Plak:</strong> <?php echo htmlspecialchars($plak['kondisyon_plak'] ?? '-'); ?></li>
                            <li><strong>Ürün Kodu:</strong> #VVR-<?php echo str_pad((string)(int)$plak['id'], 5, '0', STR_PAD_LEFT); ?></li>
                            <li>
                                <strong>Stok Durumu:</strong>
                                <?php
                                if ((int)$plak['stok'] <= 0) {
                                    echo '<span style="color: var(--danger);">Tükendi</span>';
                                } elseif ((int)$plak['stok'] <= 3) {
                                    echo '<span style="color: #ff791a;">Son ' . (int)$plak['stok'] . ' Adet Kaldı</span>';
                                } else {
                                    echo '<span style="color: var(--info);">' . (int)$plak['stok'] . ' Adet Mevcut</span>';
                                }
                                ?>
                            </li>
                        </ul>

                        <?php if ((int)$plak['stok'] > 0): ?>
                            <form id="cart-form" style="display: inline;" action="sepete_ekle.php" method="GET" data-product-id="<?php echo (int)$plak['id']; ?>" data-stock="<?php echo (int)$plak['stok']; ?>">
                                <input type="hidden" name="id" value="<?php echo (int)$plak['id']; ?>">
                                <button type="submit" class="sepete-ekle-btn">🛒 Sepete Ekle</button>
                            </form>
                        <?php else: ?>
                            <button class="sepete-ekle-btn" disabled style="opacity: 0.6; cursor: not-allowed;">Stokta Yok</button>
                        <?php endif; ?>
                    </div>

                </div>
            </section>

            <?php if (!empty($benzer_urunler)): ?>
                <section style="margin-top: 45px;">
                    <h3 style="margin-bottom: 16px; color: var(--text-main);">İlgili Ürünler</h3>
                    <div class="products-grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 18px;">
                        <?php foreach ($benzer_urunler as $urun):
                            $urun_fiyat = number_format((float)$urun['fiyat'], 2, ',', '.');
                            $urun_resim = '';
                            if (!empty($urun['kapak_gorseli']) && file_exists('images/' . $urun['kapak_gorseli'])) {
                                $urun_resim = 'images/' . htmlspecialchars($urun['kapak_gorseli']);
                            }
                        ?>
                            <article class="product-card">
                                <div class="product-image-wrapper">
                                    <?php if ($urun_resim): ?>
                                        <img src="<?php echo $urun_resim; ?>" alt="<?php echo htmlspecialchars($urun['baslik']); ?>" class="product-image" loading="lazy">
                                    <?php else: ?>
                                        <div class="product-image-placeholder">📀 Kapak Yok</div>
                                    <?php endif; ?>
                                    <a href="detay.php?id=<?php echo (int)$urun['id']; ?>" class="product-link-overlay"></a>
                                </div>
                                <div class="product-info">
                                    <h4 class="product-title"><?php echo htmlspecialchars($urun['baslik']); ?></h4>
                                    <p class="product-artist"><?php echo htmlspecialchars($urun['sanatci']); ?></p>
                                    <?php if (!empty($urun['cikis_yili']) && $urun['cikis_yili'] != '0000'): ?>
                                        <p class="product-year">📅 <?php echo (int)$urun['cikis_yili']; ?></p>
                                    <?php endif; ?>
                                    <div class="product-footer">
                                        <span class="product-price"><?php echo $urun_fiyat; ?> ₺</span>
                                        <a href="detay.php?id=<?php echo (int)$urun['id']; ?>" class="product-view-btn">İncele</a>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

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
                        <li><a href="#">📷 Instagram</a></li>
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