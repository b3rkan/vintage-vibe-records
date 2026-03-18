<?php
// ===== API: SEPET BİLGİSİ =====
// Session başlat
session_start();

// Sepet başlat
if (!isset($_SESSION['sepet'])) {
    $_SESSION['sepet'] = [];
}

// JSON header
header('Content-Type: application/json; charset=utf-8');

// Toplam ürün sayısını hesapla
$totalItems = 0;
foreach ($_SESSION['sepet'] as $item) {
    $totalItems += $item['quantity'];
}

echo json_encode([
    'success' => true,
    'cart_total' => $totalItems,
    'cart' => $_SESSION['sepet']
]);
