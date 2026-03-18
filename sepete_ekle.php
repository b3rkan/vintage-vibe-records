<?php
session_start(); // Session hafızasını başlatıyoruz

// AJAX request kontrolü
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// Eğer URL'den bir ürün ID'si geldiyse
if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    // Eğer daha önce 'sepet' adında bir hafıza dizisi oluşturulmadıysa, boş bir dizi oluştur
    if (!isset($_SESSION['sepet'])) {
        $_SESSION['sepet'] = array();
    }
    
    // Eğer bu plak zaten sepette varsa miktarını 1 artır, yoksa 1 adet olarak sepete ekle
    if (isset($_SESSION['sepet'][$id])) {
        $_SESSION['sepet'][$id]++;
    } else {
        $_SESSION['sepet'][$id] = 1;
    }
    
    // AJAX ise JSON döndür
    if ($isAjax) {
        header('Content-Type: application/json');
        
        // Session'daki sepeti localStorage formatına çevir
        $cartArray = [];
        foreach ($_SESSION['sepet'] as $productId => $quantity) {
            $cartArray[] = ['id' => (string)$productId, 'quantity' => (int)$quantity];
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Sepete eklendi',
            'cart_count' => array_sum($_SESSION['sepet']),
            'cart' => $cartArray
        ]);
        exit;
    }
}

// Normal request ise sepet sayfasına yönlendir
header("Location: sepet.php");
exit;
?>