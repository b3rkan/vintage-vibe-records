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
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alışveriş Sepeti | Vintage Vibe Records</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Stylesheet -->
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
</head>

<body class="vvr-theme">

    <!-- TOP BAR -->
    <div class="vvr-topbar">
        <div class="vvr-container">
            <div class="topbar-left">
                <span class="topbar-text">🎵 Vintage Vibe Records - Premium Plak Koleksiyonu</span>
            </div>
            <div class="topbar-right">
                <a href="index.php" class="topbar-link">← Ana Sayfaya Dön</a>
            </div>
        </div>
    </div>

    <!-- HEADER -->
    <header class="vvr-header">
        <div class="vvr-container">
            <div class="header-wrapper">
                <div class="header-logo">
                    <a href="index.php" class="logo-link">
                        <span class="logo-text">Vintage Vibe Records</span>
                    </a>
                </div>
                <div class="header-search">
                    <form action="index.php" method="GET" class="search-form">
                        <input type="text" name="arama" class="search-input" placeholder="Sanatçı, Albüm Adı..." value="">
                        <button type="submit" class="search-btn">🔍</button>
                    </form>
                </div>
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

    <!-- MAIN CONTENT -->
    <main class="vvr-main">
        <div class="vvr-container">

            <!-- CART SECTION -->
            <section class="admin-liste-alani">
                <h2 style="text-align: center; color: var(--primary-gold); margin-bottom: 30px;">🛒 Alışveriş Sepetiniz</h2>

                <?php
                if (!isset($_SESSION['sepet']) || empty($_SESSION['sepet'])) {
                    echo "<div style='text-align: center; padding: 60px 20px;'>";
                    echo "  <p style='font-size: 1.2em; color: var(--text-muted); margin-bottom: 30px;'>Sepetinizde şu an hiç plak bulunmuyor</p>";
                    echo "  <a href='index.php' class='btn-guncelle' style='padding: 12px 30px; font-size: 1.1em;'>← Kataloğa Dön</a>";
                    echo "</div>";
                } else {
                    $_SESSION['sepet'] = vvr_normalize_cart_items($_SESSION['sepet']);
                    $genel_toplam = 0;

                    echo "<table class='admin-tablo' style='width: 100%;'>";
                    echo "<thead><tr>";
                    echo "  <th>Albüm / Sanatçı</th>";
                    echo "  <th style='width: 80px; text-align: center;'>Adet</th>";
                    echo "  <th style='width: 110px;'>Birim Fiyat</th>";
                    echo "  <th style='width: 110px; text-align: right;'>Toplam</th>";
                    echo "  <th style='width: 100px; text-align: center;'>İşlem</th>";
                    echo "</tr></thead>";
                    echo "<tbody>";

                    foreach ($_SESSION['sepet'] as $cartItem) {
                        $productId = $cartItem['id'] ?? null;
                        $quantity = (int)($cartItem['quantity'] ?? 1);

                        if (!$productId) continue;

                        $sorgu = $db->prepare("SELECT baslik, sanatci, fiyat FROM plaklar WHERE id = ?");
                        $sorgu->execute([$productId]);
                        $plak = $sorgu->fetch(PDO::FETCH_ASSOC);

                        if ($plak) {
                            $ara_toplam = (float)$plak['fiyat'] * $quantity;
                            $genel_toplam += $ara_toplam;
                            $fiyat_format = number_format((float)$plak['fiyat'], 2, ',', '.');
                            $toplam_format = number_format($ara_toplam, 2, ',', '.');

                            echo "<tr>";
                            echo "  <td>";
                            echo "    <strong>" . htmlspecialchars($plak['baslik']) . "</strong><br>";
                            echo "    <span style='font-size: 0.85em; color: var(--text-muted);'>" . htmlspecialchars($plak['sanatci']) . "</span>";
                            echo "  </td>";
                            echo "  <td style='text-align: center;'>$quantity</td>";
                            echo "  <td>$fiyat_format ₺</td>";
                            echo "  <td style='color: var(--primary-gold); font-weight: bold; text-align: right;'>$toplam_format ₺</td>";
                            echo "  <td style='text-align: center;'><a href='#' onclick='removeFromCart(" . $productId . "); return false;' class='btn-sil'>Sil</a></td>";
                            echo "</tr>";
                        }
                    }

                    echo "</tbody></table>";

                    // Toplam ve Ödeme
                    $genel_format = number_format($genel_toplam, 2, ',', '.');
                    echo "<div style='text-align: right; margin-top: 40px; padding: 20px; background: var(--surface-light); border-radius: 8px;'>";
                    echo "  <p style='font-size: 1.5em; margin-bottom: 20px;'>";
                    echo "    Genel Toplam: <span style='color: var(--primary-gold); font-weight: 700; font-size: 1.3em;'>$genel_format ₺</span>";
                    echo "  </p>";
                    echo "  <button class='sepete-ekle-btn' onclick=\"alert('Tebrikler! Siparişiniz başarıyla alındı.\\n\\nNot: Bu bir okul projesi simülasyonudur.');\" style='font-size: 1.1em; padding: 15px 40px;'>💳 Siparişi Tamamla (Ödeme)</button>";
                    echo "</div>";
                }
                ?>
            </section>

        </div>
    </main>

    <!-- FOOTER -->
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