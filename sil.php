<?php
session_start();

if (!isset($_SESSION['admin_giris_yapti']) || $_SESSION['admin_giris_yapti'] !== true) {
    header('Location: login.php');
    exit;
}

require_once 'db_baglan.php';

// URL'den ID gelip gelmediğini kontrol et
if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];

    if ($id <= 0) {
        header('Location: admin.php');
        exit;
    }

    // 1. Önce sunucuda yer kaplamaması için plağın kapak görselini images klasöründen silelim
    $sorgu_resim = $db->prepare("SELECT kapak_gorseli FROM plaklar WHERE id = ?");
    $sorgu_resim->execute([$id]);
    $resim = $sorgu_resim->fetchColumn();

    // Eğer resim varsa ve varsayılan resim değilse sil
    $safeImage = basename((string)$resim);
    if ($safeImage !== '' && preg_match('/^[a-zA-Z0-9._-]+$/', $safeImage) && file_exists('images/' . $safeImage)) {
        unlink('images/' . $safeImage);
    }

    // 2. Plağı veritabanından sil
    $sorgu = $db->prepare("DELETE FROM plaklar WHERE id = ?");
    $sorgu->execute([$id]);
}

// İşlem bitince admin paneline geri yönlendir
header("Location: admin.php?mesaj=silindi");
exit;
