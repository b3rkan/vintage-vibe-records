<?php 
require_once 'db_baglan.php'; 

// URL'de bir 'id' yoksa ana sayfaya geri gönderelim
if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$id = $_GET['id'];

// Seçilen plağı ve kategorisini veritabanından çekelim
$sorgu = $db->prepare("
    SELECT p.*, k.kategori_adi 
    FROM plaklar p 
    LEFT JOIN kategoriler k ON p.kategori_id = k.id 
    WHERE p.id = ?
");
$sorgu->execute([$id]);
$plak = $sorgu->fetch(PDO::FETCH_ASSOC);

// Eğer veritabanında böyle bir plak yoksa (kullanıcı URL'ye rastgele bir sayı yazdıysa)
if (!$plak) {
    die("<h2 style='text-align:center; color:white; margin-top:50px;'>Üzgünüz, aradığınız plak bulunamadı. <a href='index.php' style='color:#d4af37;'>Mağazaya Dön</a></h2>");
}

$fiyat = number_format($plak['fiyat'], 2, ',', '.');
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($plak['baslik']); ?> | Vintage Vibe</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <header>
        <div class="logo">
            <h1>Vintage Vibe Records</h1>
        </div>
        <nav>
            <ul>
                <li><a href="index.php">&larr; Vitrine Dön</a></li>
            </ul>
        </nav>
    </header>

    <main class="detay-main">
        <div class="detay-container">
            <div class="detay-sol">
                <?php if (!empty($plak['kapak_gorseli'])): ?>
                    <img src="images/<?php echo htmlspecialchars($plak['kapak_gorseli']); ?>" alt="<?php echo htmlspecialchars($plak['baslik']); ?> Kapağı">
                <?php else: ?>
                    <div class="gorsel-yok" style="height: 400px; font-size: 1.5em;">Kapak Görseli Yok</div>
                <?php endif; ?>
            </div>

            <div class="detay-sag">
                <h2><?php echo htmlspecialchars($plak['baslik']); ?></h2>
                <h3><?php echo htmlspecialchars($plak['sanatci']); ?></h3>
                
                <p class="fiyat-kocaman"><?php echo $fiyat; ?> TL</p>
                
                <ul class="ozellikler">
                    <li><strong>Kategori:</strong> <?php echo htmlspecialchars($plak['kategori_adi']); ?></li>
                    <li><strong>Çıkış Yılı:</strong> <?php echo htmlspecialchars($plak['cikis_yili']); ?></li>
                    <li><strong>Baskı Türü:</strong> <?php echo htmlspecialchars($plak['baski_turu']); ?></li>
                    <li><strong>Stok Durumu:</strong> <?php echo ($plak['stok'] > 0) ? $plak['stok'] . ' Adet Mevcut' : '<span style="color:var(--danger)">Tükendi</span>'; ?></li>
                </ul>

                <a href="sepete_ekle.php?id=<?php echo $plak['id']; ?>" class="sepete-ekle-btn" style="text-align:center; text-decoration:none; display:inline-block; box-sizing:border-box;">Sepete Ekle</a>
            </div>
        </div>
    </main>

    <footer>
        <p>&copy; 2026 Vintage Vibe Records. Tüm Hakları Saklıdır.</p>
    </footer>

</body>
</html>