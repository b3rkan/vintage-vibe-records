<?php
session_start();

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    // Eğer bu ID sepette varsa, diziden (hafızadan) tamamen sil
    if (isset($_SESSION['sepet'][$id])) {
        unset($_SESSION['sepet'][$id]);
    }
}

// Silme işleminden sonra tekrar sepete dön
header("Location: sepet.php");
exit;
?>