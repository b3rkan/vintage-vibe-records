<?php
session_start(); // Session hafızasını başlatıyoruz

// Eğer URL'den bir ürün ID'si geldiyse
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    
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
}

// İşlem bitince sepet sayfasına yönlendir
header("Location: sepet.php");
exit;
?>