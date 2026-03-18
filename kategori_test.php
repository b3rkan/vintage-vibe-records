<?php
require_once 'db_baglan.php';

echo "<h2>📊 KATEGORİ VE ÜRÜN ANALİZİ</h2>";

// 1. TÜM KATEGORİLER
echo "<h3>1️⃣ KATEGORİLER</h3>";
$kat_sql = "SELECT id, kategori_adi FROM kategoriler ORDER BY kategori_adi ASC";
$kat_stmt = $db->query($kat_sql);
$kategoriler = $kat_stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($kategoriler as $kat) {
    echo $kat['id'] . " => " . $kat['kategori_adi'] . "<br>";
}

// 2. HER KATEGORİDE KAÇ ÜRÜN
echo "<h3>2️⃣ HER KATEGORİDE ÜRÜN SAYISI</h3>";
$sayı_sql = "SELECT k.kategori_adi, COUNT(p.id) as sayi FROM kategoriler k 
             LEFT JOIN plaklar p ON k.id = p.kategori_id 
             GROUP BY k.id, k.kategori_adi";
$sayı_stmt = $db->query($sayı_sql);
$sayılar = $sayı_stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($sayılar as $s) {
    echo $s['kategori_adi'] . " => " . $s['sayi'] . " ürün<br>";
}

// 3. KATEGORİ ID=3'TE (Yabancı 45'lik) KAÇ ÜRÜN VAR?
echo "<h3>3️⃣ KATEGORİ ID=3 (Yabancı 45'lik) ÜRÜNLERI</h3>";
$yabanci_sql = "SELECT id, baslik, sanatci, kategori_id FROM plaklar WHERE kategori_id = 3 LIMIT 5";
$yabanci_stmt = $db->query($yabanci_sql);
$yabanci_urunler = $yabanci_stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($yabanci_urunler)) {
    echo "❌ KATEGORİ ID=3'TE ÜRÜN YOK!<br>";
} else {
    echo "✅ " . count($yabanci_urunler) . " ürün bulundu:<br>";
    foreach ($yabanci_urunler as $u) {
        echo "  - " . $u['baslik'] . " | " . $u['sanatci'] . " (kategori_id=" . $u['kategori_id'] . ")<br>";
    }
}

// 4. TÜM ÜRÜNLERIN KATEGORİ_ID DAĞILIMI
echo "<h3>4️⃣ TÜM ÜRÜNLERDE KATEGORİ_ID DAĞILIMI</h3>";
$dist_sql = "SELECT kategori_id, COUNT(*) as sayi FROM plaklar GROUP BY kategori_id ORDER BY kategori_id";
$dist_stmt = $db->query($dist_sql);
$dist = $dist_stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($dist as $d) {
    echo "kategori_id=" . ($d['kategori_id'] ?? 'NULL') . " => " . $d['sayi'] . " ürün<br>";
}
?>
