<?php
require_once 'db_baglan.php';

// URL'den ID gelip gelmediğini kontrol et
if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // 1. Önce sunucuda yer kaplamaması için plağın kapak görselini images klasöründen silelim
    $sorgu_resim = $db->prepare("SELECT kapak_gorseli FROM plaklar WHERE id = ?");
    $sorgu_resim->execute([$id]);
    $resim = $sorgu_resim->fetchColumn();
    
    // Eğer resim varsa ve varsayılan resim değilse sil
    if ($resim && file_exists('images/' . $resim)) {
        unlink('images/' . $resim);
    }

    // 2. Plağı veritabanından sil
    $sorgu = $db->prepare("DELETE FROM plaklar WHERE id = ?");
    $sorgu->execute([$id]);
}

// İşlem bitince admin paneline geri yönlendir
header("Location: admin.php?mesaj=silindi");
exit;
?>