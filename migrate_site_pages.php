<?php
require_once __DIR__ . '/db_baglan.php';

header('Content-Type: text/plain; charset=utf-8');

echo "Vintage Vibe Records - Site Pages Migration\n";
echo "===========================================\n";

try {
    $db->exec("CREATE TABLE IF NOT EXISTS site_pages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        slug VARCHAR(120) NOT NULL UNIQUE,
        title VARCHAR(200) NOT NULL,
        section VARCHAR(50) NOT NULL DEFAULT 'kurumsal',
        content MEDIUMTEXT NOT NULL,
        is_published TINYINT(1) NOT NULL DEFAULT 1,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    echo "+ site_pages tablosu hazır\n";

    $seedPages = [
        [
            'slug' => 'hakkimizda',
            'title' => 'Hakkımızda',
            'section' => 'musteri',
            'content' => "Vintage Vibe Records, müzik tutkunlarına özel seçilmiş plak ve koleksiyon ürünleri sunmak için kuruldu.\n\nAmacımız kaliteli baskıları doğru kondisyon bilgisiyle bir araya getirerek güvenilir bir alışveriş deneyimi sağlamaktır.",
        ],
        [
            'slug' => 'gumruk-sozlesmesi',
            'title' => 'Gümrük Sözleşmesi',
            'section' => 'yardim',
            'content' => "Yurt dışı gönderimlerde gümrük uygulamaları ülkeye göre değişebilir.\n\nSipariş verilmeden önce ülkenize ait gümrük prosedürlerini kontrol etmenizi öneririz.",
        ],
        [
            'slug' => 'teslimat-iade',
            'title' => 'Teslimat ve İade',
            'section' => 'yardim',
            'content' => "Siparişleriniz güvenli paketleme ile hazırlanır.\n\nTeslimat sonrası hasarlı ürünlerde 14 gün içinde iade/değişim talebi oluşturabilirsiniz.",
        ],
        [
            'slug' => 'gizlilik-politikasi',
            'title' => 'Gizlilik Politikası',
            'section' => 'yardim',
            'content' => "Kişisel verileriniz KVKK kapsamında korunur.\n\nBilgileriniz yalnızca sipariş ve müşteri hizmetleri süreçlerinde kullanılır.",
        ],
        [
            'slug' => 'sss',
            'title' => 'Sıkça Sorulan Sorular',
            'section' => 'musteri',
            'content' => "Kondisyon notları nasıl belirleniyor?\n- SS: Jelatini açılmamış\n- NM: Çok temiz\n- VG+: İyi durumda\n\nSiparişler ne zaman kargolanır?\n- İş günlerinde 24 saat içinde hazırlanır.",
        ],
    ];

    $selectStmt = $db->prepare('SELECT id FROM site_pages WHERE slug = ?');
    $insertStmt = $db->prepare('INSERT INTO site_pages (slug, title, section, content, is_published) VALUES (?, ?, ?, ?, 1)');

    foreach ($seedPages as $page) {
        $selectStmt->execute([$page['slug']]);
        if ($selectStmt->fetch(PDO::FETCH_ASSOC)) {
            echo "- {$page['slug']} zaten var\n";
            continue;
        }

        $insertStmt->execute([$page['slug'], $page['title'], $page['section'], $page['content']]);
        echo "+ {$page['slug']} eklendi\n";
    }

    echo "\nMigration tamamlandı.\n";
} catch (Exception $e) {
    http_response_code(500);
    echo "Hata: " . $e->getMessage() . "\n";
}
