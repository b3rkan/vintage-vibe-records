<?php
session_start();

if (!isset($_SESSION['admin_giris_yapti']) || $_SESSION['admin_giris_yapti'] !== true) {
    header('Location: login.php');
    exit;
}

require_once 'db_baglan.php';

// Eğer URL'de ID yoksa direkt admin paneline at
if (!isset($_GET['id'])) {
    header("Location: admin.php");
    exit;
}

$id = (int)$_GET['id'];

if ($id <= 0) {
    header('Location: admin.php');
    exit;
}

$hata = '';

// Form gönderildiyse (POST işlemi olduysa) veritabanını güncelle
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $baslik = trim($_POST['baslik'] ?? '');
    $sanatci = trim($_POST['sanatci'] ?? '');
    $format = trim($_POST['format'] ?? '');
    $firma = trim($_POST['firma'] ?? '');
    $fiyat = (float)($_POST['fiyat'] ?? 0);
    $stok = (int)($_POST['stok'] ?? 0);
    $kategori_id = (int)($_POST['kategori_id'] ?? 0);
    $baski_turu = trim($_POST['baski_turu'] ?? '');
    $kondisyon_kapak = trim($_POST['kondisyon_kapak'] ?? '');
    $kondisyon_plak = trim($_POST['kondisyon_plak'] ?? '');

    $izinli_baski_turleri = ['Dönem Baskı', 'Yeni Basım'];
    $izinli_kondisyonlar = ['SS', 'M', 'NM', 'EXL+', 'EXL-', 'VG+', 'VG'];

    if (
        $baslik === '' ||
        $sanatci === '' ||
        $format === '' ||
        $firma === '' ||
        $fiyat <= 0 ||
        $stok < 0 ||
        $kategori_id <= 0 ||
        !in_array($baski_turu, $izinli_baski_turleri, true) ||
        !in_array($kondisyon_kapak, $izinli_kondisyonlar, true) ||
        !in_array($kondisyon_plak, $izinli_kondisyonlar, true)
    ) {
        $hata = 'Lütfen tüm alanları doğru biçimde doldurun.';
    } else {
        $guncelle = $db->prepare("UPDATE plaklar SET baslik = ?, sanatci = ?, format = ?, firma = ?, fiyat = ?, stok = ?, kategori_id = ?, baski_turu = ?, kondisyon_kapak = ?, kondisyon_plak = ? WHERE id = ?");
        $guncelle->execute([$baslik, $sanatci, $format, $firma, $fiyat, $stok, $kategori_id, $baski_turu, $kondisyon_kapak, $kondisyon_plak, $id]);

        header("Location: admin.php?mesaj=guncellendi");
        exit;
    }
}

// Mevcut plak bilgilerini çek (Formun içine doldurmak için)
$sorgu = $db->prepare("SELECT * FROM plaklar WHERE id = ?");
$sorgu->execute([$id]);
$plak = $sorgu->fetch(PDO::FETCH_ASSOC);

if (!$plak) {
    die("Plak bulunamadı.");
}

// Kategorileri çek (Açılır liste - Dropdown için)
$kat_sorgu = $db->query("SELECT * FROM kategoriler ORDER BY id ASC");
$kategoriler = $kat_sorgu->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <title>Plak Düzenle - Vintage Vibe</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* Düzenleme sayfasına özel basit bir form tasarımı */
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        .duzenle-form-kapsayici {
            background: var(--surface-color, #1a1a1a);
            padding: 30px;
            border-radius: 8px;
            width: 100%;
            max-width: 500px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.5);
        }

        .form-grup {
            margin-bottom: 15px;
        }

        .form-grup label {
            display: block;
            margin-bottom: 5px;
            color: var(--primary-gold, #d4af37);
            font-weight: 600;
        }

        .form-grup input,
        .form-grup select {
            width: 100%;
            padding: 10px;
            background: #333;
            border: 1px solid #444;
            color: #fff;
            border-radius: 4px;
            font-family: 'Poppins', sans-serif;
        }

        .btn-kaydet {
            width: 100%;
            padding: 12px;
            background: var(--primary-gold, #d4af37);
            color: #000;
            font-weight: bold;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 10px;
        }

        .btn-iptal {
            display: block;
            text-align: center;
            margin-top: 15px;
            color: #aaa;
            text-decoration: none;
        }
    </style>
</head>

<body>

    <div class="duzenle-form-kapsayici">
        <h2 style="text-align: center; color: var(--primary-gold, #d4af37); margin-bottom: 20px;">Plak Düzenle: #<?php echo $plak['id']; ?></h2>
        <?php if (!empty($hata)): ?>
            <div class="hata-mesaji" style="margin-bottom: 15px;"><?php echo htmlspecialchars($hata); ?></div>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="form-grup">
                <label>Albüm / Şarkı Adı</label>
                <input type="text" name="baslik" value="<?php echo htmlspecialchars($plak['baslik']); ?>" required>
            </div>

            <div class="form-grup">
                <label>Sanatçı</label>
                <input type="text" name="sanatci" value="<?php echo htmlspecialchars($plak['sanatci']); ?>" required>
            </div>

            <div class="form-grup">
                <label>Format</label>
                <input type="text" name="format" value="<?php echo htmlspecialchars($plak['format'] ?? ''); ?>" required>
            </div>

            <div class="form-grup">
                <label>Firma/Label</label>
                <input type="text" name="firma" value="<?php echo htmlspecialchars($plak['firma'] ?? ''); ?>" required>
            </div>

            <div class="form-grup">
                <label>Kategori</label>
                <select name="kategori_id" required>
                    <?php foreach ($kategoriler as $kat): ?>
                        <option value="<?php echo $kat['id']; ?>" <?php echo ($kat['id'] == $plak['kategori_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($kat['kategori_adi']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-grup">
                <label>Baskı Türü</label>
                <select name="baski_turu">
                    <option value="Dönem Baskı" <?php echo ($plak['baski_turu'] == 'Dönem Baskı') ? 'selected' : ''; ?>>Dönem Baskı</option>
                    <option value="Yeni Basım" <?php echo ($plak['baski_turu'] == 'Yeni Basım') ? 'selected' : ''; ?>>Yeni Basım</option>
                </select>
            </div>

            <div class="form-grup">
                <label>Kondisyon (Kapak)</label>
                <select name="kondisyon_kapak" required>
                    <?php foreach (['SS', 'M', 'NM', 'EXL+', 'EXL-', 'VG+', 'VG'] as $kondisyon): ?>
                        <option value="<?php echo $kondisyon; ?>" <?php echo (($plak['kondisyon_kapak'] ?? '') === $kondisyon) ? 'selected' : ''; ?>><?php echo $kondisyon; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-grup">
                <label>Kondisyon (Plak)</label>
                <select name="kondisyon_plak" required>
                    <?php foreach (['SS', 'M', 'NM', 'EXL+', 'EXL-', 'VG+', 'VG'] as $kondisyon): ?>
                        <option value="<?php echo $kondisyon; ?>" <?php echo (($plak['kondisyon_plak'] ?? '') === $kondisyon) ? 'selected' : ''; ?>><?php echo $kondisyon; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-grup">
                <label>Fiyat (TL)</label>
                <input type="number" step="0.01" name="fiyat" value="<?php echo $plak['fiyat']; ?>" required>
            </div>

            <div class="form-grup">
                <label>Stok Adedi</label>
                <input type="number" name="stok" value="<?php echo $plak['stok']; ?>" required>
            </div>

            <button type="submit" class="btn-kaydet">Değişiklikleri Kaydet</button>
            <a href="admin.php" class="btn-iptal">Vazgeç ve Geri Dön</a>
        </form>
    </div>

</body>

</html>