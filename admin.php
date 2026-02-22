<?php 
session_start();
// Eğer admin girişi yapılmamışsa, login sayfasına geri postala
if (!isset($_SESSION['admin_giris_yapti']) || $_SESSION['admin_giris_yapti'] !== true) {
    header("Location: login.php");
    exit;
}
require_once 'db_baglan.php'; 
$sorgu = $db->query("SELECT p.*, k.kategori_adi FROM plaklar p LEFT JOIN kategoriler k ON p.kategori_id = k.id ORDER BY p.id DESC");
$plaklar = $sorgu->fetchAll(PDO::FETCH_ASSOC);

// Eğer admin panelinde "Yeni Ekle" formu da varsa kategorileri de çekmemiz gerekir
$kat_sorgu = $db->query("SELECT * FROM kategoriler ORDER BY kategori_adi ASC");
$kategoriler = $kat_sorgu->fetchAll(PDO::FETCH_ASSOC);

$mesaj = "";

// Form gönderildiyse (POST isteği geldiyse) bu blok çalışır
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $baslik = $_POST['baslik'];
    $sanatci = $_POST['sanatci'];
    $cikis_yili = $_POST['cikis_yili'];
    $kategori_id = $_POST['kategori_id'];
    $fiyat = $_POST['fiyat'];
    $baski_turu = $_POST['baski_turu'];

    // SQL Injection'ı önlemek için PDO prepare metodunu kullanıyoruz
    $sorgu = $db->prepare("INSERT INTO plaklar (baslik, sanatci, cikis_yili, kategori_id, fiyat, baski_turu) VALUES (?, ?, ?, ?, ?, ?)");
    $ekle = $sorgu->execute([$baslik, $sanatci, $cikis_yili, $kategori_id, $fiyat, $baski_turu]);

    if ($ekle) {
        $mesaj = "<div class='basari-mesaji'>Plak başarıyla kataloğa eklendi!</div>";
    } else {
        $mesaj = "<div class='hata-mesaji'>Plak eklenirken bir hata oluştu.</div>";
    }
}

// Formdaki dropdown (seçim kutusu) için kategorileri veritabanından çekiyoruz
$kat_sorgu = $db->query("SELECT * FROM kategoriler");
$kategoriler = $kat_sorgu->fetchAll(PDO::FETCH_ASSOC);
// ... (Kategorileri çektiğimiz satırın hemen altına bunu ekle) ...

// Sayfanın altındaki tablo için tüm plakları çekiyoruz
$plak_sorgu = $db->query("SELECT p.id, p.baslik, p.sanatci, p.fiyat, k.kategori_adi FROM plaklar p LEFT JOIN kategoriler k ON p.kategori_id = k.id ORDER BY p.id DESC");
$tum_plaklar = $plak_sorgu->fetchAll(PDO::FETCH_ASSOC);
?>


<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Paneli | Yeni Plak Ekle</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    
</head>
<body>

    <header>
        <div class="logo">
            <h1>Yönetim Paneli</h1>
        </div>
        <nav>
            <ul>
                <li><a href="index.php">Ana Sayfaya Dön</a></li>
            </ul>
        </nav>
    </header>
<section class="admin-liste-alani">
            <h2>Katalogdaki Plaklar</h2>
<div class="admin-tablo-kapsayici">
    <table class="admin-tablo">
        <thead>
            <tr>
                <th>Görsel</th>
                <th>ID</th>
                <th>Albüm</th>
                <th>Sanatçı</th>
                <th>Tür</th>
                <th>Fiyat</th>
                <th>İşlemler</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($plaklar as $plak): ?>
            <tr>
                <td>
                    <?php if (!empty($plak['kapak_gorseli'])): ?>
                        <img src="images/<?php echo htmlspecialchars($plak['kapak_gorseli']); ?>" alt="Kapak" class="admin-tablo-img">
                    <?php else: ?>
                        <div class="admin-gorsel-yok">Yok</div>
                    <?php endif; ?>
                </td>
                <td>#<?php echo $plak['id']; ?></td>
                <td><strong><?php echo htmlspecialchars($plak['baslik']); ?></strong></td>
                <td><?php echo htmlspecialchars($plak['sanatci']); ?></td>
                <td><span class="admin-tur-etiket"><?php echo htmlspecialchars($plak['kategori_adi']); ?></span></td>
                <td class="admin-fiyat"><?php echo number_format($plak['fiyat'], 2, ',', '.'); ?> TL</td>
                <td class="admin-islemler">
                    <a href="guncelle.php?id=<?php echo $plak['id']; ?>" class="btn-guncelle">Düzenle</a>
                    <a href="sil.php?id=<?php echo $plak['id']; ?>" class="btn-sil" onclick="return confirm('Bu plağı silmek istediğinize emin misiniz?');">Sil</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
        </section>
    <main>
        <section class="admin-form-alani">
            <h2>Yeni Plak Ekle</h2>
            
            <?php echo $mesaj; ?> <form action="admin.php" method="POST" class="plak-form">
                <div class="form-grup">
                    <label for="baslik">Albüm Adı:</label>
                    <input type="text" id="baslik" name="baslik" required>
                </div>

                <div class="form-grup">
                    <label for="sanatci">Sanatçı/Grup:</label>
                    <input type="text" id="sanatci" name="sanatci" required>
                </div>

                <div class="form-grup">
                    <label for="cikis_yili">Çıkış Yılı:</label>
                    <input type="number" id="cikis_yili" name="cikis_yili" min="1900" max="2026" required>
                </div>

                <div class="form-grup">
                    <label for="kategori_id">Tür (Kategori):</label>
                    <select id="kategori_id" name="kategori_id" required>
                        <option value="">Seçiniz...</option>
                        <?php foreach($kategoriler as $kat): ?>
                            <option value="<?php echo $kat['id']; ?>"><?php echo $kat['kategori_adi']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-grup">
                    <label for="baski_turu">Baskı Türü:</label>
                    <select id="baski_turu" name="baski_turu" required>
                        <option value="Dönem Baskı">Dönem Baskı</option>
                        <option value="Yeni Basım">Yeni Basım</option>
                    </select>
                </div>

                <div class="form-grup">
                    <label for="fiyat">Fiyat (TL):</label>
                    <input type="number" step="0.01" id="fiyat" name="fiyat" required>
                </div>

                <button type="submit" class="gonder-btn">Kataloğa Ekle</button>
            </form>
        </section>
    </main>

</body>
</html>