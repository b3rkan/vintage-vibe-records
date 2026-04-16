<?php
require_once 'api/session_helper.php';

$productId = isset($_GET['id']) ? (string)$_GET['id'] : '';

if ($productId !== '') {
    $_SESSION['sepet'] = vvr_normalize_cart_items($_SESSION['sepet']);

    foreach ($_SESSION['sepet'] as $key => $item) {
        if ((string)($item['id'] ?? '') === $productId) {
            unset($_SESSION['sepet'][$key]);
            break;
        }
    }

    $_SESSION['sepet'] = array_values($_SESSION['sepet']);
}

header('Location: sepet.php');
exit;
