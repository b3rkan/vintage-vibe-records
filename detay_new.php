<?php
require_once 'db_baglan.php';

// ID kontrolü
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$id = (int)$_GET['id'];

// Ürün verisi
try {
    $sorgu = $db->prepare("
        SELECT p.id, p.baslik, p.sanatci, p.fiyat, p.kapak_gorseli, p.stok, p.baski_turu, p.cikis_yili, k.kategori_adi 
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
                <a href="index.php" style="color: var(--primary-gold); text-decoration: none; font-weight: 600;">← Vitrine Dön</a>
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
                        <div>
                            <h2><?php echo htmlspecialchars($plak['baslik']); ?></h2>
                            <h3><?php echo htmlspecialchars($plak['sanatci']); ?></h3>
                        </div>

                        <p class="fiyat-kocaman"><?php echo $fiyat; ?> ₺</p>

                        <ul class="ozellikler">
                            <li><strong>Kategori:</strong> <?php echo htmlspecialchars($plak['kategori_adi'] ?? 'Diğer'); ?></li>
                            <li><strong>Çıkış Yılı:</strong> <?php echo !empty($plak['cikis_yili']) && $plak['cikis_yili'] != '0000' ? (int)$plak['cikis_yili'] : 'Bilinmiyor'; ?></li>
                            <li><strong>Baskı Türü:</strong> <?php echo htmlspecialchars($plak['baski_turu'] ?? '-'); ?></li>
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
                            <a href="sepete_ekle.php?id=<?php echo (int)$plak['id']; ?>" class="sepete-ekle-btn">🛒 Sepete Ekle</a>
                        <?php else: ?>
                            <button class="sepete-ekle-btn" disabled style="opacity: 0.6; cursor: not-allowed;">Stokta Yok</button>
                        <?php endif; ?>
                    </div>

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