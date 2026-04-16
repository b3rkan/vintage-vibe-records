<?php
// ===== API: SEPETTEN SİL =====

require_once 'session_helper.php';

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

// Sepeti tek bir formata getirip ürünü kaldır
$_SESSION['sepet'] = vvr_normalize_cart_items($_SESSION['sepet']);

foreach ($_SESSION['sepet'] as $key => $item) {
    if ((string)($item['id'] ?? '') === $productId) {
        unset($_SESSION['sepet'][$key]);
        break;
    }
}

// Array'i yeniden indexle (boş alanları temizle)
$_SESSION['sepet'] = array_values($_SESSION['sepet']);

// Toplam ürün sayısını hesapla
$totalItems = vvr_cart_total($_SESSION['sepet']);

echo json_encode([
    'success' => true,
    'message' => 'Ürün sepetinizden çıkarıldı',
    'cart_total' => $totalItems,
    'cart' => $_SESSION['sepet']
]);
