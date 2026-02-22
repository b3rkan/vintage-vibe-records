<?php
session_start();
require_once 'db_baglan.php';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sepetim | Vintage Vibe</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <header>
        <div class="logo">
            <h1>Vintage Vibe Records</h1>
        </div>
        <nav>
            <ul>
                <li><a href="index.php">Ana Sayfa</a></li>
                <li><a href="sepet.php" style="color: var(--primary-gold);">Sepetim</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <section class="admin-liste-alani" style="margin-top: 50px;">
            <h2 style="text-align: center; color: var(--primary-gold); margin-bottom: 30px;">Alışveriş Sepetiniz</h2>

            <?php
            // Eğer sepet boşsa veya hiç oluşturulmadıysa
            if (!isset($_SESSION['sepet']) || empty($_SESSION['sepet'])) {
                echo "<p style='text-align:center; color: var(--text-muted); font-size:1.1em;'>Sepetinizde şu an hiç plak bulunmuyor.</p>";
                echo "<div style='text-align:center; margin-top:30px;'><a href='index.php' class='btn-guncelle' style='padding:12px 25px; font-size:1.1em;'>Kataloğa Dön ve Alışverişe Başla</a></div>";
            } else {
                // Sepet doluysa tabloyu çizmeye başla
                $genel_toplam = 0;
                
                echo "<table class='admin-tablo'>";
                echo "<thead><tr><th>Albüm / Sanatçı</th><th>Adet</th><th>Birim Fiyat</th><th>Toplam</th><th>İşlem</th></tr></thead>";
                echo "<tbody>";

                // Hafızadaki sepette dönüyoruz ($id = plağın ids'i, $miktar = kaç adet eklendiği)
                foreach ($_SESSION['sepet'] as $id => $miktar) {
                    // ID'ye göre plağın güncel fiyatını ve adını veritabanından çekiyoruz
                    $sorgu = $db->prepare("SELECT baslik, sanatci, fiyat FROM plaklar WHERE id = ?");
                    $sorgu->execute([$id]);
                    $plak = $sorgu->fetch(PDO::FETCH_ASSOC);

                    if ($plak) {
                        $ara_toplam = $plak['fiyat'] * $miktar;
                        $genel_toplam += $ara_toplam;

                        echo "<tr>";
                        echo "<td><strong>" . htmlspecialchars($plak['baslik']) . "</strong> <br> <span style='font-size:0.85em; color:var(--text-muted);'>" . htmlspecialchars($plak['sanatci']) . "</span></td>";
                        echo "<td>" . $miktar . "</td>";
                        echo "<td>" . number_format($plak['fiyat'], 2, ',', '.') . " TL</td>";
                        echo "<td style='color:var(--primary-gold); font-weight:bold;'>" . number_format($ara_toplam, 2, ',', '.') . " TL</td>";
                        echo "<td><a href='sepet_sil.php?id=" . $id . "' class='btn-sil'>Sepetten Çıkar</a></td>";
                        echo "</tr>";
                    }
                }

                echo "</tbody></table>";

                // Genel Toplam ve Ödeme Butonu Alanı
                echo "<div style='text-align: right; margin-top: 30px; font-size: 1.6em; color: var(--text-main);'>";
                echo "Genel Toplam: <strong style='color: var(--primary-gold);'>" . number_format($genel_toplam, 2, ',', '.') . " TL</strong>";
                echo "</div>";

                echo "<div style='text-align: right; margin-top: 20px;'>";
                echo "<button class='sepete-ekle-btn' onclick='alert(\"Tebrikler! Siparişiniz başarıyla alındı. (Bu bir okul projesi simülasyonudur.)\");'>Siparişi Tamamla (Ödeme)</button>";
                echo "</div>";
            }
            ?>
        </section>
    </main>

</body>
</html>