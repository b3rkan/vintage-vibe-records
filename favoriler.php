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

try {
    // Tüm plakları getir
    $sorgu = $db->query("
        SELECT p.id, p.baslik, p.sanatci, p.fiyat, p.kapak_gorseli, p.stok, p.baski_turu, p.cikis_yili, k.kategori_adi 
        FROM plaklar p 
        LEFT JOIN kategoriler k ON p.kategori_id = k.id 
        ORDER BY p.id DESC
    ");
    $tum_plaklar = $sorgu->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Veritabanı Hatası: " . htmlspecialchars($e->getMessage()));
}
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Favorilemiş Plaklar - Vintage Vibe Records">
    <title>Favorilerim | Vintage Vibe Records</title>

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

    <style>
        .empty-favorites {
            text-align: center;
            padding: 80px 20px;
            color: var(--text-muted);
        }

        .empty-favorites h3 {
            font-size: 1.5em;
            margin-bottom: 20px;
            color: var(--text-main);
        }

        .empty-favorites p {
            font-size: 1.1em;
            margin-bottom: 30px;
        }

        .empty-favorites a {
            background: var(--primary-accent);
            color: white;
            padding: 12px 30px;
            border-radius: 6px;
            text-decoration: none;
            display: inline-block;
            transition: var(--transition);
        }

        .empty-favorites a:hover {
            background: #8b2605;
            transform: scale(1.05);
        }
    </style>
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
            <h1 style="font-size: 2.5em; margin-bottom: 40px; color: var(--text-main);">❤️ Favorilerim</h1>

            <!-- FAVORİLER GRID -->
            <div class="products-grid" id="favorites-grid">
                <?php
                if (empty($_SESSION['favoriler'])) {
                    echo '<div class="empty-favorites" style="grid-column: 1/-1; text-align: center;"><h3>Henüz favori plak eklemediniz</h3><p>Beğendiğiniz plakları favorilere ekleyerek kolayca erişebilirsiniz.</p><a href="index.php">← Plak Koleksiyonuna Dön</a></div>';
                } else {
                    // Session favorilerini getir ve göster
                    foreach ($_SESSION['favoriler'] as $favoriteId) {
                        $fav_sorgu = $db->prepare("SELECT p.id, p.baslik, p.sanatci, p.fiyat, p.kapak_gorseli, p.cikis_yili, k.kategori_adi FROM plaklar p LEFT JOIN kategoriler k ON p.kategori_id = k.id WHERE p.id = ?");
                        $fav_sorgu->execute([(int)$favoriteId]);
                        $plak = $fav_sorgu->fetch(PDO::FETCH_ASSOC);

                        if ($plak) {
                            $fiyat = number_format((float)$plak['fiyat'], 2, ',', '.');
                            $resim = !empty($plak['kapak_gorseli']) ? 'images/' . htmlspecialchars($plak['kapak_gorseli']) : '';
                            $yil = (!empty($plak['cikis_yili']) && $plak['cikis_yili'] !== '0000') ? '📅 ' . (int)$plak['cikis_yili'] : '';
                ?>
                            <div class="product-card">
                                <div class="product-image-wrapper">
                                    <?php if ($resim): ?>
                                        <img src="<?php echo $resim; ?>" alt="<?php echo htmlspecialchars($plak['baslik']); ?>" loading="lazy" class="product-image">
                                    <?php else: ?>
                                        <div class="product-image-placeholder">📀 Kapak Yok</div>
                                    <?php endif; ?>
                                    <button class="favorite-btn active" data-product-id="<?php echo (int)$plak['id']; ?>" title="Favorilerden Çıkar">♥</button>
                                    <a href="detay.php?id=<?php echo (int)$plak['id']; ?>" class="product-link-overlay"></a>
                                </div>
                                <div class="product-info">
                                    <div class="product-category"><?php echo htmlspecialchars($plak['kategori_adi'] ?? 'Diğer'); ?></div>
                                    <h3 class="product-title"><?php echo htmlspecialchars($plak['baslik']); ?></h3>
                                    <p class="product-artist"><?php echo htmlspecialchars($plak['sanatci']); ?></p>
                                    <?php if ($yil): ?><p class="product-year"><?php echo $yil; ?></p><?php endif; ?>
                                    <div class="product-footer">
                                        <span class="product-price"><?php echo $fiyat; ?> ₺</span>
                                        <a href="detay.php?id=<?php echo (int)$plak['id']; ?>" class="product-view-btn">İncele</a>
                                    </div>
                                </div>
                            </div>
                <?php
                        }
                    }
                }
                ?>
            </div>
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