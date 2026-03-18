<?php
// ===== SİTE HATALARı TESPITI =====
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'db_baglan.php';

$errors = [];
$warnings = [];
$info = [];

echo "<style>
body { font-family: Poppins, sans-serif; background: #0f0f11; color: #f4f4f5; padding: 20px; }
.container { max-width: 1200px; margin: 0 auto; }
h1 { color: #d4af37; }
.error { background: #ef4444; padding: 15px; margin: 10px 0; border-radius: 6px; }
.warning { background: #ff791a; padding: 15px; margin: 10px 0; border-radius: 6px; }
.info { background: #3b82f6; padding: 15px; margin: 10px 0; border-radius: 6px; }
.success { background: #10b981; padding: 15px; margin: 10px 0; border-radius: 6px; }
li { margin: 8px 0; }
</style>";

echo "<div class='container'><h1>🔍 Site Hata Taraması</h1>";

// ===== 1. VERİTABANı =====
echo "<h2>1. Veritabanı Kontrolü</h2>";
try {
    $test = $db->query("SELECT COUNT(*) as c FROM plaklar");
    $count = $test->fetchColumn();
    echo "<div class='success'>✅ Veritabanı bağlantısı: BAŞARILI</div>";
    echo "<div class='info'>Toplam ürün: $count</div>";
} catch (Exception $e) {
    $errors[] = "VT Bağlantı Hatası: " . $e->getMessage();
}

// ===== 2. RESİM DOSYALARI =====
echo "<h2>2. Görsel Dosyaları Kontrolü</h2>";
$images_dir = __DIR__ . '/images';
if (!is_dir($images_dir)) {
    $errors[] = "images/ klasörü bulunamadı!";
} else {
    $files = scandir($images_dir);
    $image_count = count($files) - 2; // . ve ..
    echo "<div class='success'>✅ images/ klasörü var ($image_count dosya)</div>";
    
    // Veritabanında olmayan resimleri kontrol et
    try {
        $images_in_db = $db->query("SELECT DISTINCT kapak_gorseli FROM plaklar WHERE kapak_gorseli IS NOT NULL AND kapak_gorseli != ''")->fetchAll(PDO::FETCH_COLUMN);
        $missing_images = [];
        foreach ($images_in_db as $img) {
            if (!file_exists($images_dir . '/' . $img)) {
                $missing_images[] = $img;
            }
        }
        if (!empty($missing_images)) {
            $warnings[] = "Eksik görsel dosyaları (" . count($missing_images) . "): " . implode(", ", array_slice($missing_images, 0, 5)) . "...";
        } else {
            echo "<div class='success'>✅ Tüm resim dosyaları var</div>";
        }
    } catch (Exception $e) {
        $warnings[] = "Resim kontrolü yapılamadı: " . $e->getMessage();
    }
}

// ===== 3. PHP DOSYALARI =====
echo "<h2>3. Kritik PHP Dosyaları</h2>";
$required_files = [
    'index.php' => 'Ana Sayfa',
    'detay.php' => 'Ürün Detayı',
    'login.php' => 'Giriş',
    'sepet.php' => 'Sepet',
    'sepete_ekle.php' => 'Sepete Ekle',
    'db_baglan.php' => 'VT Bağlantı',
    'css/style.css' => 'CSS',
    'js/main.js' => 'JavaScript'
];

foreach ($required_files as $file => $name) {
    $path = __DIR__ . '/' . $file;
    if (file_exists($path)) {
        $size = filesize($path);
        echo "<div class='success'>✅ $name ($file) - " . ($size > 0 ? "tamam ($size byte)" : "EMPTY") . "</div>";
        if ($size < 100) {
            $warnings[] = "$file çok küçük ya da boş olabilir ($size byte)";
        }
    } else {
        $errors[] = "Eksik Dosya: $name ($file)";
    }
}

// ===== 4. CSS KLASLARı =====
echo "<h2>4. CSS Sınıfları Kontrolü</h2>";
$css_file = __DIR__ . '/css/style.css';
if (file_exists($css_file)) {
    $css_content = file_get_contents($css_file);
    $required_classes = [
        'vvr-header' => 'Header',
        'vvr-navbar' => 'Navbar',
        'products-grid' => 'Ürün Grid',
        'product-card' => 'Ürün Kartı',
        'vvr-footer' => 'Footer',
        'newsletter-section' => 'Newsletter'
    ];
    
    foreach ($required_classes as $class => $name) {
        if (strpos($css_content, '.' . $class) !== false) {
            echo "<div class='success'>✅ CSS Sınıfı: .$class</div>";
        } else {
            $warnings[] = "CSS Sınıfı eksik: .$class ($name)";
        }
    }
}

// ===== 5. HTML TAGS =====
echo "<h2>5. Ana Sayfa HTML Yapısı</h2>";
$index_content = file_get_contents(__DIR__ . '/index.php');

$html_checks = [
    'DOCTYPE html' => 'HTML5',
    'charset=UTF-8' => 'Karakter Seti',
    'vvr-header' => 'Header',
    'vvr-navbar' => 'Navigation',
    'products-grid' => 'Ürün Tanesı',
    'newsletter' => 'Newsletter'
];

foreach ($html_checks as $check => $name) {
    if (strpos($index_content, $check) !== false) {
        echo "<div class='success'>✅ $name</div>";
    } else {
        $warnings[] = "HTML Yapı eksik: $name ($check)";
    }
}

// ===== 6. JAVASCRIPT =====
echo "<h2>6. JavaScript Fonksiyonları</h2>";
if (file_exists(__DIR__ . '/js/main.js')) {
    $js_content = file_get_contents(__DIR__ . '/js/main.js');
    $js_functions = [
        'initializeSearch' => 'Arama',
        'initializeCategoryFilter' => 'Kategori Filtre',
        'initializeNewsletterForm' => 'Newsletter'
    ];
    
    foreach ($js_functions as $func => $name) {
        if (strpos($js_content, $func) !== false) {
            echo "<div class='success'>✅ Fonksiyon: $func ($name)</div>";
        } else {
            $warnings[] = "JS Fonksiyonu eksik: $func";
        }
    }
}

// ===== 7. COLORS/TEMA =====
echo "<h2>7. Tema Renkleri Kontrolü</h2>";
if (file_exists($css_file)) {
    $css_content = file_get_contents($css_file);
    $colors = [
        '#0f0f11' => 'Arka Plan',
        '#d4af37' => 'Gold',
        '#f4f4f5' => 'Text'
    ];
    
    foreach ($colors as $color => $name) {
        if (strpos($css_content, $color) !== false) {
            echo "<div class='success'>✅ Renk: $color ($name)</div>";
        } else {
            $warnings[] = "Renk tanımı eksik: $color ($name)";
        }
    }
}

// ===== 8. DATABASE QUERIES =====
echo "<h2>8. Veritabanı Sorguları</h2>";
try {
    // Test sorgusu
    $test_queries = [
        "SELECT COUNT(*) FROM plaklar" => "Ürün Sayısı",
        "SELECT COUNT(*) FROM kategoriler" => "Kategori Sayısı",
        "SELECT * FROM plaklar LIMIT 1" => "İlk Ürün"
    ];
    
    foreach ($test_queries as $query => $name) {
        try {
            $result = $db->query($query);
            if ($result) {
                $data = $result->fetch();
                echo "<div class='success'>✅ Sorgu başarılı: $name</div>";
            }
        } catch (Exception $e) {
            $errors[] = "Sorgu hatası ($name): " . $e->getMessage();
        }
    }
} catch (Exception $e) {
    $errors[] = "VT Sorgu Kontrolü Başarısız: " . $e->getMessage();
}

// ===== ÖZET =====
echo "<h2>📊 Hata Özeti</h2>";

if (!empty($errors)) {
    echo "<h3 style='color: #ef4444;'>❌ HATALAR (" . count($errors) . ")</h3>";
    foreach ($errors as $error) {
        echo "<div class='error'>$error</div>";
    }
}

if (!empty($warnings)) {
    echo "<h3 style='color: #ff791a;'>⚠ UYARILAR (" . count($warnings) . ")</h3>";
    foreach ($warnings as $warning) {
        echo "<div class='warning'>$warning</div>";
    }
}

if (empty($errors) && empty($warnings)) {
    echo "<div class='success'><h3>✅ Hiçbir hata tespit edilmedi!</h3></div>";
}

echo "<h3 style='color: #10b981;'>ℹ BİLGİ (" . count($info) . ")</h3>";
echo "<div class='info'>Tarama Tamamlandı: " . date('Y-m-d H:i:s') . "</div>";
echo "</div>";
?>
