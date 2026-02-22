<?php 
require_once 'db_baglan.php'; 

// 1. Parametreleri Yakalama
$arama_kelimesi = isset($_GET['arama']) ? trim($_GET['arama']) : '';
$secili_kategori = isset($_GET['kategori']) ? (int)$_GET['kategori'] : 0;
$mevcut_sayfa = isset($_GET['sayfa']) ? (int)$_GET['sayfa'] : 1;
$sirala = isset($_GET['sirala']) ? $_GET['sirala'] : 'yeni'; 

// 2. Sıralama Mantığı
switch ($sirala) {
    case 'fiyat_artan': $order_sql = "p.fiyat ASC"; break;
    case 'fiyat_azalan': $order_sql = "p.fiyat DESC"; break;
    case 'eski': $order_sql = "p.cikis_yili ASC"; break;
    default: $order_sql = "p.id DESC"; break;
}

// 3. Sayfalama Ayarları
$sayfa_basina_kayit = 12; 
$offset = ($mevcut_sayfa - 1) * $sayfa_basina_kayit;

// 4. Toplam Sayfa Sayısını Hesaplama (Hata Giderildi)
if (!empty($arama_kelimesi)) {
    $say_sorgu = $db->prepare("SELECT COUNT(*) FROM plaklar WHERE baslik LIKE :kelime OR sanatci LIKE :kelime");
    $say_sorgu->execute(['kelime' => '%' . $arama_kelimesi . '%']);
} elseif ($secili_kategori > 0) {
    $say_sorgu = $db->prepare("SELECT COUNT(*) FROM plaklar WHERE kategori_id = :kat_id");
    $say_sorgu->execute(['kat_id' => $secili_kategori]);
} else {
    $say_sorgu = $db->query("SELECT COUNT(*) FROM plaklar");
}
$toplam_kayit = $say_sorgu->fetchColumn();
$toplam_sayfa = ceil($toplam_kayit / $sayfa_basina_kayit);

// 5. Kategori Listesini Çekme (Hata Giderildi)
$kat_sorgu = $db->query("SELECT * FROM kategoriler ORDER BY kategori_adi ASC");
$kategoriler = $kat_sorgu->fetchAll(PDO::FETCH_ASSOC);

// URL Parametrelerini koruma
$url_ek = "";
if (!empty($arama_kelimesi)) $url_ek .= "&arama=" . urlencode($arama_kelimesi);
if ($secili_kategori > 0) $url_ek .= "&kategori=" . $secili_kategori;
$url_ek .= "&sirala=" . $sirala;
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vintage Vibe | Plak Mağazası</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="top-bar-serit">
    <div class="top-bar-icerik">
        Klasik Rock ve Heavy Metal'in Altın Çağı: Orijinal dönem baskıları ve özenle seçilmiş vintage plaklar.
    </div>
</div>
    <header>
        
        <div class="logo"><h1>Vintage Vibe Records</h1></div>
        <nav>
            <ul>
                <li><a href="index.php">Ana Sayfa</a></li>
                <li><a href="sepet.php">Sepetim</a></li>
                <li><a href="login.php">Yönetici Girişi</a></li>
            </ul>
        </nav>
    </header>

    <main>


        <section class="plak-vitrini">
            <div class="arama-alani">
                <form action="index.php" method="GET">
                    <input type="text" name="arama" placeholder="Ara..." value="<?php echo htmlspecialchars($arama_kelimesi); ?>">
                    <select name="sirala" class="siralama-select" onchange="this.form.submit()">
                        <option value="yeni" <?php if($sirala == 'yeni') echo 'selected'; ?>>En Yeniler</option>
                        <option value="fiyat_artan" <?php if($sirala == 'fiyat_artan') echo 'selected'; ?>>Fiyat (Düşükten Yükseğe)</option>
                        <option value="fiyat_azalan" <?php if($sirala == 'fiyat_azalan') echo 'selected'; ?>>Fiyat (Yüksekten Düşüğe)</option>
                        <option value="eski" <?php if($sirala == 'eski') echo 'selected'; ?>>Eski Kayıtlara Göre</option>
                    </select>
                    <button type="submit">Ara</button>
                    <?php if($secili_kategori > 0): ?><input type="hidden" name="kategori" value="<?php echo $secili_kategori; ?>"><?php endif; ?>
                </form>
            </div>

            <div class="kategori-menusu">
                <a href="index.php" class="<?php echo ($secili_kategori == 0 && empty($arama_kelimesi)) ? 'aktif' : ''; ?>">Tümü</a>
                <?php foreach($kategoriler as $kat): ?>
                    <a href="index.php?kategori=<?php echo $kat['id']; ?>" class="<?php echo ($secili_kategori == $kat['id']) ? 'aktif' : ''; ?>">
                        <?php echo htmlspecialchars($kat['kategori_adi']); ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <div class="grid-container">
                <?php
                $limit_sql = " LIMIT " . (int)$sayfa_basina_kayit . " OFFSET " . (int)$offset;
                if (!empty($arama_kelimesi)) {
                    $sorgu = $db->prepare("SELECT p.*, k.kategori_adi FROM plaklar p LEFT JOIN kategoriler k ON p.kategori_id = k.id WHERE p.baslik LIKE :kelime OR p.sanatci LIKE :kelime ORDER BY $order_sql $limit_sql");
                    $sorgu->execute(['kelime' => '%' . $arama_kelimesi . '%']);
                } elseif ($secili_kategori > 0) {
                    $sorgu = $db->prepare("SELECT p.*, k.kategori_adi FROM plaklar p LEFT JOIN kategoriler k ON p.kategori_id = k.id WHERE p.kategori_id = :kat_id ORDER BY $order_sql $limit_sql");
                    $sorgu->execute(['kat_id' => $secili_kategori]);
                } else {
                    $sorgu = $db->prepare("SELECT p.*, k.kategori_adi FROM plaklar p LEFT JOIN kategoriler k ON p.kategori_id = k.id ORDER BY $order_sql $limit_sql");
                    $sorgu->execute();
                }
                $plaklar = $sorgu->fetchAll(PDO::FETCH_ASSOC);

                foreach ($plaklar as $plak): 
                    $fiyat = number_format($plak['fiyat'], 2, ',', '.');
                ?>
                <div class="plak-kart">
                    <div class="plak-kapak">
                        <div class="badge-konumlandirici">
                            <?php if ($plak['baski_turu'] == 'Dönem Baskı'): ?>
                                <span class="badge badge-donem">Dönem Baskı</span>
                            <?php endif; ?>
                            <?php if ($plak['stok'] <= 0): ?>
                                <span class="badge badge-tukendi">Tükendi</span>
                            <?php elseif ($plak['stok'] <= 3): ?>
                                <span class="badge badge-az-kaldi">Son <?php echo $plak['stok']; ?> Ürün!</span>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($plak['kapak_gorseli'])): ?>
                            <img src="images/<?php echo htmlspecialchars($plak['kapak_gorseli']); ?>" alt="<?php echo htmlspecialchars($plak['baslik']); ?>">
                        <?php else: ?>
                            <div class="gorsel-yok">Kapak Yok</div>
                        <?php endif; ?>
                    </div>
                    <h4><?php echo htmlspecialchars($plak['baslik']); ?></h4>
                    <p><strong><?php echo htmlspecialchars($plak['sanatci']); ?></strong></p>
                    <p style="font-size: 0.85em; color: var(--text-muted); margin: 5px 0;">
                        <?php echo htmlspecialchars($plak['kategori_adi']); ?> | <?php echo $plak['cikis_yili']; ?>
                    </p>
                    <span class="fiyat"><?php echo $fiyat; ?> TL</span>
                    <a href="detay.php?id=<?php echo $plak['id']; ?>" class="btn-incele">Plağı İncele</a>
                </div>
                <?php endforeach; ?>
            </div>

            <?php if ($toplam_sayfa > 1): ?>
            <div class="sayfalama-alani">
                <?php for ($i = 1; $i <= $toplam_sayfa; $i++): ?>
                    <a href="?sayfa=<?php echo $i . $url_ek; ?>" class="sayfa-btn <?php echo ($i == $mevcut_sayfa) ? 'aktif' : ''; ?>"><?php echo $i; ?></a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
        </section>
    </main>

    <footer><p>&copy; 2026 Vintage Vibe Records.</p></footer>
</body>
</html>