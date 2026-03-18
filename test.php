<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'db_baglan.php';

// Test 1: Kategoriler
echo "<h2>Test 1: Kategoriler</h2>";
try {
    $kat_sorgu = $db->query("SELECT * FROM kategoriler ORDER BY kategori_adi ASC");
    $kategoriler = $kat_sorgu->fetchAll(PDO::FETCH_ASSOC);
    echo "Kategoriler sayısı: " . count($kategoriler) . "<br>";
    foreach ($kategoriler as $kat) {
        echo "- " . $kat['kategori_adi'] . " (ID: " . $kat['id'] . ")<br>";
    }
} catch (Exception $e) {
    echo "HATA: " . $e->getMessage();
}

// Test 2: Ürünler
echo "<h2>Test 2: İlk 5 Ürün</h2>";
try {
    $sorgu = $db->prepare("SELECT p.*, k.kategori_adi FROM plaklar p LEFT JOIN kategoriler k ON p.kategori_id = k.id LIMIT 5");
    $sorgu->execute();
    $plaklar = $sorgu->fetchAll(PDO::FETCH_ASSOC);
    echo "Ürün sayısı: " . count($plaklar) . "<br>";
    foreach ($plaklar as $plak) {
        echo "- " . $plak['baslik'] . " / " . $plak['sanatci'] . " (Kategori: " . $plak['kategori_adi'] . ")<br>";
    }
} catch (Exception $e) {
    echo "HATA: " . $e->getMessage();
}

// Test 3: Görsel kontrolü
echo "<h2>Test 3: Görsel Dosyaları</h2>";
$images_dir = __DIR__ . '/images';
if (is_dir($images_dir)) {
    $files = scandir($images_dir);
    echo "Görsel sayısı: " . (count($files) - 2) . "<br>";
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            echo "- $file<br>";
        }
    }
} else {
    echo "HATA: images klasörü bulunamadı!<br>";
}

// Test 4: Sorgu performansı
echo "<h2>Test 4: Sorgu Performansı</h2>";
$start = microtime(true);
$sorgu = $db->query("SELECT COUNT(*) FROM plaklar");
$count = $sorgu->fetchColumn();
$end = microtime(true);
echo "Toplam ürün: " . $count . " (Sorgu süresi: " . round(($end - $start) * 1000, 2) . "ms)<br>";

?>
