<?php
// ===== API: FAVORİLER TOGGLE =====

require_once 'session_helper.php';

// JSON header - session'dan SONRA!
header('Content-Type: application/json; charset=utf-8');

// POST istek kontrolü
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'POST isteği gereklidir']);
    exit;
}

try {
    // JSON veya form data'dan al
    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true);

    if ($data === null && !empty($rawInput)) {
        $data = $_POST; // Fallback
    }

    $productId = isset($data['id']) ? (string)$data['id'] : null;

    if (!$productId) {
        echo json_encode(['success' => false, 'message' => 'Ürün ID eksik']);
        exit;
    }

    // Favoriler arrayini güvenli kıl
    $_SESSION['favoriler'] = vvr_normalize_favorites($_SESSION['favoriler']);

    // Favorilerde var mı kontrol et - döngü ile güvenli
    $is_favorite = false;
    $found = false;

    foreach ($_SESSION['favoriler'] as $key => $favId) {
        if ((string)$favId === $productId) {
            unset($_SESSION['favoriler'][$key]);
            $found = true;
            break;
        }
    }

    if (!$found) {
        // Ekle
        $_SESSION['favoriler'][] = $productId;
        $is_favorite = true;
        $message = 'Favorilere eklendi! ♥';
    } else {
        // Çıkart
        $is_favorite = false;
        $message = 'Favorilerden çıkarıldı';
    }

    // Array'ı yeniden indexle
    $_SESSION['favoriler'] = array_values($_SESSION['favoriler']);

    echo json_encode([
        'success' => true,
        'message' => $message,
        'is_favorite' => $is_favorite,
        'favorites' => $_SESSION['favoriler']
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Hata: ' . $e->getMessage()
    ]);
}
