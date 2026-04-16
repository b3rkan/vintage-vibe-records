<?php
require_once 'db_baglan.php';

$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($productId <= 0) {
    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Ürün ID eksik']);
        exit;
    }

    header('Location: index.php');
    exit;
}

$stmt = $db->prepare('SELECT id, stok FROM plaklar WHERE id = ?');
$stmt->execute([$productId]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Ürün bulunamadı']);
        exit;
    }

    header('Location: index.php');
    exit;
}

if ((int)$product['stok'] <= 0) {
    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Ürün stoğu tükendi']);
        exit;
    }

    header('Location: detay.php?id=' . $productId);
    exit;
}

$_SESSION['sepet'] = vvr_normalize_cart_items($_SESSION['sepet']);

$found = false;
foreach ($_SESSION['sepet'] as &$item) {
    if ((string)($item['id'] ?? '') === (string)$productId) {
        $item['quantity']++;
        $found = true;
        break;
    }
}
unset($item);

if (!$found) {
    $_SESSION['sepet'][] = [
        'id' => (string)$productId,
        'quantity' => 1,
    ];
}

$cartTotal = vvr_cart_total($_SESSION['sepet']);

if ($isAjax) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => true,
        'message' => '✓ Ürün sepetinize eklendi',
        'cart_total' => $cartTotal,
        'cart' => $_SESSION['sepet'],
    ]);
    exit;
}

header('Location: sepet.php');
exit;
