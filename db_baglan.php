<?php
// Session başlat ve sepet verilerini initialize et
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Sepet dizisi yoksa veya array değilse, initialize et
if (!isset($_SESSION['sepet']) || !is_array($_SESSION['sepet'])) {
    $_SESSION['sepet'] = [];
}

// Veritabanı yapılandırma ayarları
$host = 'localhost';
$dbname = 'plak_dukkani_db';
$username = 'root'; // XAMPP'ın varsayılan MySQL kullanıcı adı
$password = '';     // XAMPP'ta varsayılan şifre boştur

try {
    // PDO nesnesini oluşturarak veritabanına bağlanıyoruz (UTF-8 karakter seti ile)
    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);

    // Hata modunu Exception (İstisna) fırlatacak şekilde ayarlıyoruz
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Bağlantı testini yapmak istersen aşağıdaki satırın başındaki yorumu kaldırabilirsin
    // echo "Veritabanı bağlantısı başarılı! Rock 'n' Roll!";

} catch (PDOException $e) {
    // Eğer bir hata olursa, try bloğu kırılır ve catch bloğu çalışır
    die("Veritabanı bağlantı hatası: " . $e->getMessage());
}
