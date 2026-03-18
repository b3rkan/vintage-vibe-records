<?php
// ===== API: SEPETE EKLE =====
// Session başlat
session_start();

// Sepet başlat
if (!isset($_SESSION['sepet'])) {
    $_SESSION['sepet'] = [];
}

// JSON header
header('Content-Type: application/json; charset=utf-8');

// Database bağlantısı
require_once '../db_baglan.php';

// POST isteği kontrolü
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'POST isteği gereklidir']);
    exit;
}

// JSON veya form data'dan al
$data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$productId = $data['id'] ?? null;

if (!$productId) {
    echo json_encode(['success' => false, 'message' => 'Ürün ID\'si eksik']);
    exit;
}

$productId = (string)$productId;

// Ürünü veritabanından kontrol et (stok var mı?)
try {
    $sql = "SELECT id, baslik, sanatci, fiyat, stok FROM plaklar WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$productId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'Ürün bulunamadı']);
        exit;
    }

    if ($product['stok'] <= 0) {
        echo json_encode(['success' => false, 'message' => 'Ürün stoğu tükendi']);
        exit;
    }

    // Sepete ekle veya miktarı artır
    $found = false;
    foreach ($_SESSION['sepet'] as &$item) {
        if ($item['id'] === $productId) {
            $item['quantity']++;
            $found = true;
            break;
        }
    }

    if (!$found) {
        $_SESSION['sepet'][] = [
            'id' => $productId,
            'quantity' => 1
        ];
    }

    // Toplam ürün sayısını hesapla
    $totalItems = 0;
    foreach ($_SESSION['sepet'] as $item) {
        $totalItems += $item['quantity'];
    }

    echo json_encode([
        'success' => true,
        'message' => '✓ Ürün sepetinize eklenmiştir',
        'cart_total' => $totalItems,
        'cart' => $_SESSION['sepet']
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Hata: ' . $e->getMessage()]);
}
