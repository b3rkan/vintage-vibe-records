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
    $desired_columns = [
        'id',
        'baslik',
        'sanatci',
        'format',
        'firma',
        'label',
        'edition',
        'catalog_no',
        'rpm',
        'vinyl_weight',
        'color_variant',
        'aciklama',
        'tracklist',
        'audio_preview_url',
        'gallery_images',
        'kondisyon_kapak',
        'kondisyon_plak',
        'fiyat',
        'kapak_gorseli',
        'stok',
        'baski_turu',
        'cikis_yili',
        'kategori_id'
    ];

    $column_stmt = $db->query("SHOW COLUMNS FROM plaklar");
    $existing_columns = array_fill_keys($column_stmt->fetchAll(PDO::FETCH_COLUMN), true);
    $select_columns = [];

    foreach ($desired_columns as $column) {
        if (isset($existing_columns[$column])) {
            $select_columns[] = "p.$column";
        } else {
            $select_columns[] = "NULL AS `$column`";
        }
    }

    $select_sql = implode(', ', $select_columns);

    $sorgu = $db->prepare("
        SELECT $select_sql, k.kategori_adi 
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

    $description = trim($plak['aciklama'] ?? '');
    $tracklist_raw = trim($plak['tracklist'] ?? '');
    $tracklist_items = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $tracklist_raw)));
    $label = trim($plak['label'] ?? '') !== '' ? $plak['label'] : ($plak['firma'] ?? '');
    $edition = trim($plak['edition'] ?? '');
    $catalogNo = trim($plak['catalog_no'] ?? '');
    $rpm = trim($plak['rpm'] ?? '');
    $weight = trim($plak['vinyl_weight'] ?? '');
    $colorVariant = trim($plak['color_variant'] ?? '');
    $audioPreview = trim($plak['audio_preview_url'] ?? '');

    $gallery_items = [];
    if ($resim_yolu !== '') {
        $gallery_items[] = $resim_yolu;
    }

    $gallery_raw = trim($plak['gallery_images'] ?? '');
    if ($gallery_raw !== '') {
        $gallery_parts = array_filter(array_map('trim', explode(',', $gallery_raw)));
        foreach ($gallery_parts as $item) {
            if ($item === '') {
                continue;
            }
            if (preg_match('/^https?:\/\//i', $item)) {
                $path = $item;
            } else {
                $path = ltrim($item, '/');
                if (!file_exists($path)) {
                    continue;
                }
            }
            if (!in_array($path, $gallery_items, true)) {
                $gallery_items[] = $path;
            }
        }
    }

    $specs = [
        'Plak Şirketi' => $label,
        'Baskı Versiyonu' => $edition,
        'Katalog No' => $catalogNo,
        'Devir (RPM)' => $rpm,
        'Plak Ağırlığı' => $weight,
        'Renk Varyantı' => $colorVariant,
        'Format' => $plak['format'] ?? '',
        'Baskı Türü' => $plak['baski_turu'] ?? ''
    ];

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
            <div class="detail-back">
                <a href="index.php" class="back-link">← Vitrine Dön</a>
            </div>

            <!-- DETAIL SECTION -->
            <section class="detay-main">
                <div class="detay-container reveal">

                    <!-- LEFT: IMAGE -->
                    <div class="detay-sol">
                        <div class="detail-gallery">
                            <div class="detail-main-image">
                                <?php if ($resim_yolu): ?>
                                    <img id="detail-main-image" src="<?php echo $resim_yolu; ?>" alt="<?php echo htmlspecialchars($plak['baslik']); ?>" loading="lazy">
                                <?php else: ?>
                                    <div class="gorsel-yok detail-image-placeholder">📀 Kapak Görseli Yok</div>
                                <?php endif; ?>
                                <?php if ($resim_yolu): ?>
                                    <button class="detail-zoom-btn" type="button" aria-label="Kapağı büyüt">🔍</button>
                                <?php endif; ?>
                            </div>
                            <?php if (count($gallery_items) > 1): ?>
                                <div class="detail-thumbs">
                                    <?php foreach ($gallery_items as $index => $item): ?>
                                        <button type="button" class="detail-thumb <?php echo $index === 0 ? 'is-active' : ''; ?>" data-src="<?php echo htmlspecialchars($item); ?>">
                                            <img src="<?php echo htmlspecialchars($item); ?>" alt="<?php echo htmlspecialchars($plak['baslik']); ?> küçük görsel">
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- RIGHT: DETAILS -->
                    <div class="detay-sag">
                        <div class="detail-top">
                            <div>
                                <h2><?php echo htmlspecialchars($plak['baslik']); ?></h2>
                                <h3><?php echo htmlspecialchars($plak['sanatci']); ?></h3>
                            </div>
                            <button class="favorite-btn-detay" data-product-id="<?php echo (int)$plak['id']; ?>" title="Favorilere Ekle">♡</button>
                        </div>

                        <div class="detail-quick">
                            <?php if (!empty($plak['format'])): ?>
                                <span class="detail-chip"><?php echo htmlspecialchars($plak['format']); ?></span>
                            <?php endif; ?>
                            <?php if (!empty($plak['baski_turu'])): ?>
                                <span class="detail-chip"><?php echo htmlspecialchars($plak['baski_turu']); ?></span>
                            <?php endif; ?>
                            <?php if (!empty($plak['cikis_yili']) && $plak['cikis_yili'] != '0000'): ?>
                                <span class="detail-chip"><?php echo (int)$plak['cikis_yili']; ?></span>
                            <?php endif; ?>
                            <?php if (!empty($label)): ?>
                                <span class="detail-chip"><?php echo htmlspecialchars($label); ?></span>
                            <?php endif; ?>
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
                                    echo '<span class="detail-stock detail-stock--out">Tükendi</span>';
                                } elseif ((int)$plak['stok'] <= 3) {
                                    echo '<span class="detail-stock detail-stock--low">Son ' . (int)$plak['stok'] . ' Adet Kaldı</span>';
                                } else {
                                    echo '<span class="detail-stock detail-stock--in">' . (int)$plak['stok'] . ' Adet Mevcut</span>';
                                }
                                ?>
                            </li>
                        </ul>

                        <?php if ((int)$plak['stok'] > 0): ?>
                            <form id="cart-form" class="detail-actions" action="sepete_ekle.php" method="GET" data-product-id="<?php echo (int)$plak['id']; ?>" data-stock="<?php echo (int)$plak['stok']; ?>">
                                <input type="hidden" name="id" value="<?php echo (int)$plak['id']; ?>">
                                <button type="submit" class="sepete-ekle-btn">🛒 Sepete Ekle</button>
                            </form>
                        <?php else: ?>
                            <button class="sepete-ekle-btn detail-disabled" disabled>Stokta Yok</button>
                        <?php endif; ?>

                        <div class="detail-tabs">
                            <button class="detail-tab active" data-tab="overview" type="button">Genel</button>
                            <button class="detail-tab" data-tab="tracklist" type="button">Parça Listesi</button>
                            <button class="detail-tab" data-tab="specs" type="button">Teknik</button>
                            <button class="detail-tab" data-tab="preview" type="button">Dinle</button>
                        </div>

                        <div class="detail-tab-panels">
                            <div class="detail-panel active" id="tab-overview">
                                <h4>Albüm Hakkında</h4>
                                <?php if ($description !== ''): ?>
                                    <p><?php echo nl2br(htmlspecialchars($description)); ?></p>
                                <?php else: ?>
                                    <div class="detail-empty">Bu albüm için açıklama yakında eklenecek.</div>
                                <?php endif; ?>
                                <h4 style="margin-top: 18px;">Teslimat ve İade</h4>
                                <p>24-48 saat içinde kargoya verilir. Hasarlı teslimatlarda kolay iade desteği sunarız.</p>
                            </div>

                            <div class="detail-panel" id="tab-tracklist">
                                <h4>Parça Listesi</h4>
                                <?php if (!empty($tracklist_items)): ?>
                                    <ol class="detail-tracklist">
                                        <?php foreach ($tracklist_items as $track): ?>
                                            <li><?php echo htmlspecialchars($track); ?></li>
                                        <?php endforeach; ?>
                                    </ol>
                                <?php else: ?>
                                    <div class="detail-empty">Parça listesi yakında eklenecek.</div>
                                <?php endif; ?>
                            </div>

                            <div class="detail-panel" id="tab-specs">
                                <h4>Teknik Bilgiler</h4>
                                <ul class="detail-specs">
                                    <?php foreach ($specs as $labelText => $value): ?>
                                        <?php if (!empty($value)): ?>
                                            <li><strong><?php echo htmlspecialchars($labelText); ?>:</strong> <?php echo htmlspecialchars($value); ?></li>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </ul>
                            </div>

                            <div class="detail-panel" id="tab-preview">
                                <h4>Ön İzleme</h4>
                                <?php if ($audioPreview !== ''): ?>
                                    <div class="audio-player-wrapper" style="display: flex; align-items: center; gap: 20px;">
                                        <div class="audio-vinyl-spinner" id="audio-vinyl">
                                            <svg viewBox="0 0 24 24" width="70" height="70" style="fill: transparent;">
                                                <circle cx="12" cy="12" r="11" stroke="var(--border-color)" stroke-width="0.5" fill="#111" />
                                                <circle cx="12" cy="12" r="4" fill="var(--primary-accent)" />
                                                <circle cx="12" cy="12" r="1" fill="#fff" />
                                                <path d="M12 2 a10 10 0 0 1 10 10" stroke="#333" stroke-width="0.5" fill="none" />
                                                <path d="M12 4 a8 8 0 0 1 8 8" stroke="#333" stroke-width="0.5" fill="none" />
                                                <path d="M12 6 a6 6 0 0 1 6 6" stroke="#333" stroke-width="0.5" fill="none" />
                                            </svg>
                                        </div>
                                        <div style="flex: 1;">
                                            <audio id="vvr-audio" class="detail-audio" controls preload="none" style="width: 100%;">
                                                <source src="<?php echo htmlspecialchars($audioPreview); ?>">
                                                Tarayıcınız ses etiketini desteklemiyor.
                                            </audio>
                                            <div class="audio-wave" aria-hidden="true">
                                                <span></span>
                                                <span></span>
                                                <span></span>
                                                <span></span>
                                                <span></span>
                                                <span></span>
                                                <span></span>
                                                <span></span>
                                                <span></span>
                                                <span></span>
                                                <span></span>
                                                <span></span>
                                            </div>
                                        </div>
                                    </div>
                                    <script>
                                        const aud = document.getElementById('vvr-audio');
                                        const vinyl = document.getElementById('audio-vinyl');
                                        if (aud && vinyl) {
                                            aud.addEventListener('play', () => vinyl.classList.add('spinning-vinyl-icon'));
                                            aud.addEventListener('pause', () => vinyl.classList.remove('spinning-vinyl-icon'));
                                            aud.addEventListener('ended', () => vinyl.classList.remove('spinning-vinyl-icon'));
                                        }
                                    </script>
                                <?php else: ?>
                                    <div class="detail-empty">Ön izleme yakında eklenecek.</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                </div>
            </section>

            <?php if (!empty($benzer_urunler)): ?>
                <section class="detail-related reveal">
                    <h3 class="detail-related-title">İlgili Ürünler</h3>
                    <div class="detail-related-grid">
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

            <div class="detail-lightbox" id="detail-lightbox" aria-hidden="true">
                <div class="detail-lightbox-content">
                    <button class="detail-lightbox-close" type="button" aria-label="Kapat">×</button>
                    <img id="detail-lightbox-image" src="" alt="">
                </div>
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