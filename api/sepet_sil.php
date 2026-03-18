<?php
// ===== API: SEPETTEN SİL =====
// Session başlat
session_start();

// Sepet başlat
if (!isset($_SESSION['sepet'])) {
    $_SESSION['sepet'] = [];
}

// JSON header
header('Content-Type: application/json; charset=utf-8');

// POST isteği kontrolü
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'POST veya GET isteği gereklidir']);
    exit;
}

// JSON, GET veya POST'dan ID'yi al
$data = json_decode(file_get_contents('php://input'), true) ?? [];
$productId = $data['id'] ?? $_GET['id'] ?? $_POST['id'] ?? null;

if (!$productId) {
    echo json_encode(['success' => false, 'message' => 'Ürün ID\'si eksik']);
    exit;
}

$productId = (string)$productId;

// Sepetten kaldır (yeni format için döngü)
foreach ($_SESSION['sepet'] as $key => $item) {
    if ((string)$item['id'] === $productId) {
        unset($_SESSION['sepet'][$key]);
        break;
    }
}

// Array'i yeniden indexle (boş alanları temizle)
$_SESSION['sepet'] = array_values($_SESSION['sepet']);

// Toplam ürün sayısını hesapla
$totalItems = 0;
foreach ($_SESSION['sepet'] as $item) {
    $totalItems += $item['quantity'];
}

echo json_encode([
    'success' => true,
    'message' => 'Ürün sepetinizden çıkarıldı',
    'cart_total' => $totalItems,
    'cart' => $_SESSION['sepet']
]);
