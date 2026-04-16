<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!headers_sent()) {
    header('Content-Type: application/json; charset=utf-8');
}

if (!isset($_SESSION['admin_giris_yapti']) || $_SESSION['admin_giris_yapti'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim']);
    exit;
}

require_once __DIR__ . '/../db_baglan.php';

$q = trim($_GET['q'] ?? '');

function vvr_escape($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

try {
    $baseSql = "SELECT p.id, p.baslik, p.sanatci, p.fiyat, p.kapak_gorseli, k.kategori_adi
                FROM plaklar p
                LEFT JOIN kategoriler k ON p.kategori_id = k.id";

    if ($q === '') {
        $stmt = $db->query($baseSql . " ORDER BY p.id DESC");
    } else {
        $sql = $baseSql . "
            WHERE p.baslik LIKE :q
               OR p.sanatci LIKE :q
               OR p.format LIKE :q
               OR p.firma LIKE :q
               OR p.baski_turu LIKE :q
               OR p.kondisyon_kapak LIKE :q
               OR p.kondisyon_plak LIKE :q
               OR k.kategori_adi LIKE :q
            ORDER BY p.id DESC";
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':q', '%' . $q . '%', PDO::PARAM_STR);
        $stmt->execute();
    }

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    ob_start();
    if (empty($rows)) {
        echo '<tr><td colspan="7" style="text-align:center; padding: 20px;">Arama sonucu bulunamadı.</td></tr>';
    } else {
        foreach ($rows as $plak) {
            echo '<tr>';
            echo '<td>';
            if (!empty($plak['kapak_gorseli'])) {
                echo '<img src="images/' . vvr_escape($plak['kapak_gorseli']) . '" alt="Kapak" class="admin-tablo-img">';
            } else {
                echo '<div class="admin-gorsel-yok">Yok</div>';
            }
            echo '</td>';
            echo '<td>#' . (int)$plak['id'] . '</td>';
            echo '<td><strong>' . vvr_escape($plak['baslik']) . '</strong></td>';
            echo '<td>' . vvr_escape($plak['sanatci']) . '</td>';
            echo '<td><span class="admin-tur-etiket">' . vvr_escape($plak['kategori_adi']) . '</span></td>';
            echo '<td class="admin-fiyat">' . number_format((float)$plak['fiyat'], 2, ',', '.') . ' TL</td>';
            echo '<td class="admin-islemler">';
            echo '<a href="guncelle.php?id=' . (int)$plak['id'] . '" class="btn-guncelle">Düzenle</a> ';
            echo '<a href="sil.php?id=' . (int)$plak['id'] . '" class="btn-sil" onclick="return confirm(\'Bu plağı silmek istediğinize emin misiniz?\');">Sil</a>';
            echo '</td>';
            echo '</tr>';
        }
    }

    $rowsHtml = ob_get_clean();

    echo json_encode([
        'success' => true,
        'rows_html' => $rowsHtml,
        'count' => count($rows),
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Arama sırasında hata oluştu.',
    ]);
}
