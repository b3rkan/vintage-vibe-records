<?php
// ===== API: SEPET BİLGİSİ =====

require_once 'session_helper.php';

// JSON header
header('Content-Type: application/json; charset=utf-8');

// Toplam ürün sayısını hesapla
$totalItems = vvr_cart_total($_SESSION['sepet']);

echo json_encode([
    'success' => true,
    'cart_total' => $totalItems,
    'cart' => $_SESSION['sepet']
]);
