<?php
session_start();
// Eğer admin girişi yapılmamışsa, login sayfasına geri postala
if (!isset($_SESSION['admin_giris_yapti']) || $_SESSION['admin_giris_yapti'] !== true) {
    header("Location: login.php");
    exit;
}
require_once 'db_baglan.php';
$adminSearchQuery = trim($_GET['urun_ara'] ?? '');

function vvr_get_admin_products(PDO $db, $searchQuery = '')
{
    $baseSql = "SELECT p.*, k.kategori_adi 
                FROM plaklar p 
                LEFT JOIN kategoriler k ON p.kategori_id = k.id";

    if ($searchQuery === '') {
        $stmt = $db->query($baseSql . " ORDER BY p.id DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

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
    $stmt->bindValue(':q', '%' . $searchQuery . '%', PDO::PARAM_STR);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$plaklar = vvr_get_admin_products($db, $adminSearchQuery);

// Eğer admin panelinde "Yeni Ekle" formu da varsa kategorileri de çekmemiz gerekir
$kat_sorgu = $db->query("SELECT * FROM kategoriler ORDER BY kategori_adi ASC");
$kategoriler = $kat_sorgu->fetchAll(PDO::FETCH_ASSOC);

$mesaj = "";
$toastMesaj = '';
$toastTip = 'success';
if (isset($_GET['mesaj']) && $_GET['mesaj'] === 'eklendi') {
    $mesaj = "<div class='basari-mesaji'>Plak başarıyla kataloğa eklendi!</div>";
    $toastMesaj = 'Plak başarıyla kataloğa eklendi!';
} elseif (isset($_GET['mesaj']) && $_GET['mesaj'] === 'guncellendi') {
    $mesaj = "<div class='basari-mesaji'>Plak başarıyla güncellendi!</div>";
    $toastMesaj = 'Plak başarıyla güncellendi!';
} elseif (isset($_GET['mesaj']) && $_GET['mesaj'] === 'silindi') {
    $mesaj = "<div class='basari-mesaji'>Plak başarıyla silindi!</div>";
    $toastMesaj = 'Plak başarıyla silindi!';
}

// Form gönderildiyse (POST isteği geldiyse) bu blok çalışır
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $baslik = trim($_POST['baslik'] ?? '');
    $sanatci = trim($_POST['sanatci'] ?? '');
    $format = trim($_POST['format'] ?? '');
    $firma = trim($_POST['firma'] ?? '');
    $cikis_yili = (int)($_POST['cikis_yili'] ?? 0);
    $kategori_id = (int)($_POST['kategori_id'] ?? 0);
    $fiyat = (float)($_POST['fiyat'] ?? 0);
    $baski_turu = trim($_POST['baski_turu'] ?? '');
    $kondisyon_kapak = trim($_POST['kondisyon_kapak'] ?? '');
    $kondisyon_plak = trim($_POST['kondisyon_plak'] ?? '');

    $izinli_baski_turleri = ['Dönem Baskı', 'Yeni Basım'];
    $izinli_kondisyonlar = ['SS', 'M', 'NM', 'EXL+', 'EXL-', 'VG+', 'VG'];

    if (
        $baslik === '' ||
        $sanatci === '' ||
        $format === '' ||
        $firma === '' ||
        $cikis_yili < 1900 ||
        $kategori_id <= 0 ||
        $fiyat <= 0 ||
        !in_array($baski_turu, $izinli_baski_turleri, true) ||
        !in_array($kondisyon_kapak, $izinli_kondisyonlar, true) ||
        !in_array($kondisyon_plak, $izinli_kondisyonlar, true)
    ) {
        $mesaj = "<div class='hata-mesaji'>Lütfen tüm alanları doğru biçimde doldurun.</div>";
        $toastMesaj = 'Lütfen tüm alanları doğru biçimde doldurun.';
        $toastTip = 'error';
    } else {
        // SQL Injection'ı önlemek için PDO prepare metodunu kullanıyoruz
        $sorgu = $db->prepare("INSERT INTO plaklar (baslik, sanatci, format, firma, cikis_yili, kategori_id, fiyat, baski_turu, kondisyon_kapak, kondisyon_plak) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $ekle = $sorgu->execute([$baslik, $sanatci, $format, $firma, $cikis_yili, $kategori_id, $fiyat, $baski_turu, $kondisyon_kapak, $kondisyon_plak]);

        if ($ekle) {
            header('Location: admin.php?mesaj=eklendi');
            exit;
        }

        $mesaj = "<div class='hata-mesaji'>Plak eklenirken bir hata oluştu.</div>";
        $toastMesaj = 'Plak eklenirken bir hata oluştu.';
        $toastTip = 'error';
    }
}

// Formdaki dropdown (seçim kutusu) için kategorileri veritabanından çekiyoruz
$kat_sorgu = $db->query("SELECT * FROM kategoriler");
$kategoriler = $kat_sorgu->fetchAll(PDO::FETCH_ASSOC);
// ... (Kategorileri çektiğimiz satırın hemen altına bunu ekle) ...

// Sayfanın altındaki tablo için tüm plakları çekiyoruz
$plak_sorgu = $db->query("SELECT p.id, p.baslik, p.sanatci, p.fiyat, k.kategori_adi FROM plaklar p LEFT JOIN kategoriler k ON p.kategori_id = k.id ORDER BY p.id DESC");
$tum_plaklar = $plak_sorgu->fetchAll(PDO::FETCH_ASSOC);
?>


<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Paneli | Yeni Plak Ekle</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .admin-shell {
            display: grid;
            grid-template-columns: 1.35fr 1fr;
            gap: 24px;
            margin-top: 28px;
            margin-bottom: 40px;
        }

        .admin-card {
            background: var(--surface-white);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.06);
        }

        .admin-card h2 {
            margin-bottom: 16px;
            font-size: 1.5em;
            color: var(--text-main);
        }

        .admin-toolbar {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 10px;
        }

        .admin-action-link {
            display: inline-block;
            background: var(--surface-light);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 8px 12px;
            font-size: 0.9em;
            transition: var(--transition);
        }

        .admin-action-link:hover {
            border-color: var(--primary-accent);
            color: var(--primary-accent);
        }

        .admin-table-wrap {
            overflow-x: auto;
            border-radius: 10px;
        }

        .admin-filter-box {
            margin-top: 14px;
            margin-bottom: 14px;
            display: grid;
            grid-template-columns: 1fr;
            gap: 8px;
        }

        .admin-filter-input {
            width: 100%;
            padding: 11px 12px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            background: #fff;
            color: var(--text-main);
            font-family: 'Poppins', sans-serif;
        }

        .admin-filter-input:focus {
            outline: none;
            border-color: var(--primary-accent);
            box-shadow: 0 0 0 3px rgba(173, 49, 7, 0.12);
        }

        .admin-form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px 14px;
        }

        .admin-form-grid .form-grup {
            margin-bottom: 0;
        }

        .admin-form-grid .full {
            grid-column: 1 / -1;
        }

        .admin-toast {
            position: fixed;
            right: 18px;
            bottom: 18px;
            min-width: 280px;
            max-width: 420px;
            background: #10b981;
            color: #fff;
            border-radius: 10px;
            padding: 12px 14px;
            box-shadow: 0 10px 26px rgba(0, 0, 0, 0.25);
            z-index: 9999;
            animation: adminToastIn 0.25s ease-out;
        }

        .admin-toast.error {
            background: #ef4444;
        }

        @keyframes adminToastIn {
            from {
                transform: translateY(10px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        @media (max-width: 1024px) {
            .admin-shell {
                grid-template-columns: 1fr;
            }

            .admin-form-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

</head>

<body class="vvr-theme">

    <?php if ($toastMesaj !== ''): ?>
        <div id="admin-toast" class="admin-toast <?php echo $toastTip === 'error' ? 'error' : ''; ?>">
            <?php echo htmlspecialchars($toastMesaj); ?>
        </div>
    <?php endif; ?>

    <div class="vvr-topbar">
        <div class="vvr-container">
            <div class="topbar-left">
                <span class="topbar-text">Vintage Vibe Records - Yönetim Alanı</span>
            </div>
            <div class="topbar-right">
                <a href="index.php" class="topbar-link">← Mağazaya Dön</a>
            </div>
        </div>
    </div>

    <header class="vvr-header">
        <div class="vvr-container">
            <div class="header-wrapper">
                <div class="header-logo">
                    <a href="admin.php" class="logo-link">
                        <span class="logo-text">Vintage Vibe Admin</span>
                    </a>
                </div>
                <div class="header-search">
                    <form action="admin.php" method="GET" class="search-form">
                        <input type="text" name="urun_ara" class="search-input" placeholder="Tüm ürünlerde ara..." value="<?php echo htmlspecialchars($adminSearchQuery); ?>">
                        <button type="submit" class="search-btn">🔎</button>
                    </form>
                </div>
                <div class="header-icons">
                    <a href="admin_pages.php" class="header-icon" title="Sayfa Yönetimi">📄</a>
                    <a href="index.php" class="header-icon" title="Vitrin">🏠</a>
                    <a href="cikis.php" class="header-icon" title="Çıkış">↩</a>
                </div>
            </div>
        </div>
    </header>

    <main class="vvr-main">
        <div class="vvr-container">
            <div class="admin-shell">
                <section class="admin-card">
                    <h2>Katalogdaki Plaklar</h2>
                    <div class="admin-toolbar">
                        <a class="admin-action-link" href="admin_pages.php">Sayfa Yönetimi</a>
                        <a class="admin-action-link" href="migrate_product_metadata.php">Meta Migration</a>
                        <a class="admin-action-link" href="diagnose.php">Sistem Tanı</a>
                    </div>
                    <div class="admin-filter-box">
                        <input id="productSearchInput" class="admin-filter-input" type="text" placeholder="Canlı genel arama: tüm ürünler" value="<?php echo htmlspecialchars($adminSearchQuery); ?>">
                    </div>
                    <div class="admin-table-wrap" style="margin-top: 14px;">
                        <table id="productTable" class="admin-tablo">
                            <thead>
                                <tr>
                                    <th>Görsel</th>
                                    <th>ID</th>
                                    <th>Albüm</th>
                                    <th>Sanatçı</th>
                                    <th>Tür</th>
                                    <th>Fiyat</th>
                                    <th>İşlemler</th>
                                </tr>
                            </thead>
                            <tbody id="productTableBody">
                                <?php foreach ($plaklar as $plak): ?>
                                    <tr>
                                        <td>
                                            <?php if (!empty($plak['kapak_gorseli'])): ?>
                                                <img src="images/<?php echo htmlspecialchars($plak['kapak_gorseli']); ?>" alt="Kapak" class="admin-tablo-img">
                                            <?php else: ?>
                                                <div class="admin-gorsel-yok">Yok</div>
                                            <?php endif; ?>
                                        </td>
                                        <td>#<?php echo $plak['id']; ?></td>
                                        <td><strong><?php echo htmlspecialchars($plak['baslik']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($plak['sanatci']); ?></td>
                                        <td><span class="admin-tur-etiket"><?php echo htmlspecialchars($plak['kategori_adi']); ?></span></td>
                                        <td class="admin-fiyat"><?php echo number_format($plak['fiyat'], 2, ',', '.'); ?> TL</td>
                                        <td class="admin-islemler">
                                            <a href="guncelle.php?id=<?php echo $plak['id']; ?>" class="btn-guncelle">Düzenle</a>
                                            <a href="sil.php?id=<?php echo $plak['id']; ?>" class="btn-sil" onclick="return confirm('Bu plağı silmek istediğinize emin misiniz?');">Sil</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="admin-card">
                    <h2>Yeni Plak Ekle</h2>

                    <form action="admin.php" method="POST" class="plak-form admin-form-grid">
                        <div class="form-grup">
                            <label for="baslik">Albüm Adı:</label>
                            <input type="text" id="baslik" name="baslik" required>
                        </div>

                        <div class="form-grup">
                            <label for="sanatci">Sanatçı/Grup:</label>
                            <input type="text" id="sanatci" name="sanatci" required>
                        </div>

                        <div class="form-grup">
                            <label for="format">Format:</label>
                            <input type="text" id="format" name="format" placeholder="LP, CD, 45'lik" required>
                        </div>

                        <div class="form-grup">
                            <label for="firma">Firma/Label:</label>
                            <input type="text" id="firma" name="firma" placeholder="Blue Note, ECM, Sony..." required>
                        </div>

                        <div class="form-grup">
                            <label for="cikis_yili">Çıkış Yılı:</label>
                            <input type="number" id="cikis_yili" name="cikis_yili" min="1900" max="2026" required>
                        </div>

                        <div class="form-grup">
                            <label for="kategori_id">Tür (Kategori):</label>
                            <select id="kategori_id" name="kategori_id" required>
                                <option value="">Seçiniz...</option>
                                <?php foreach ($kategoriler as $kat): ?>
                                    <option value="<?php echo $kat['id']; ?>"><?php echo $kat['kategori_adi']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-grup">
                            <label for="baski_turu">Baskı Türü:</label>
                            <select id="baski_turu" name="baski_turu" required>
                                <option value="Dönem Baskı">Dönem Baskı</option>
                                <option value="Yeni Basım">Yeni Basım</option>
                            </select>
                        </div>

                        <div class="form-grup">
                            <label for="kondisyon_kapak">Kondisyon (Kapak):</label>
                            <select id="kondisyon_kapak" name="kondisyon_kapak" required>
                                <option value="SS">SS</option>
                                <option value="M">M</option>
                                <option value="NM">NM</option>
                                <option value="EXL+">EXL+</option>
                                <option value="EXL-">EXL-</option>
                                <option value="VG+">VG+</option>
                                <option value="VG">VG</option>
                            </select>
                        </div>

                        <div class="form-grup">
                            <label for="kondisyon_plak">Kondisyon (Plak):</label>
                            <select id="kondisyon_plak" name="kondisyon_plak" required>
                                <option value="SS">SS</option>
                                <option value="M">M</option>
                                <option value="NM">NM</option>
                                <option value="EXL+">EXL+</option>
                                <option value="EXL-">EXL-</option>
                                <option value="VG+">VG+</option>
                                <option value="VG">VG</option>
                            </select>
                        </div>

                        <div class="form-grup">
                            <label for="fiyat">Fiyat (TL):</label>
                            <input type="number" step="0.01" id="fiyat" name="fiyat" required>
                        </div>

                        <button type="submit" class="gonder-btn full">Kataloğa Ekle</button>
                    </form>
                </section>
            </div>
        </div>
    </main>

    <footer class="vvr-footer">
        <div class="vvr-container">
            <div class="footer-bottom">
                <p>&copy; 2026 Vintage Vibe Records Admin. Tüm Hakları Saklıdır.</p>
            </div>
        </div>
    </footer>

    <script>
        (function() {
            const input = document.getElementById('productSearchInput');
            const tableBody = document.getElementById('productTableBody');

            function debounce(fn, delay) {
                let timer = null;
                return function(...args) {
                    clearTimeout(timer);
                    timer = setTimeout(() => fn.apply(this, args), delay);
                };
            }

            if (input && tableBody) {
                const fetchRows = debounce(function() {
                    const q = input.value.trim();
                    const url = 'api/admin_product_search.php?q=' + encodeURIComponent(q);

                    fetch(url, {
                            credentials: 'same-origin'
                        })
                        .then((response) => response.json())
                        .then((data) => {
                            if (!data.success) {
                                return;
                            }
                            tableBody.innerHTML = data.rows_html;
                            const nextUrl = new URL(window.location.href);
                            if (q) {
                                nextUrl.searchParams.set('urun_ara', q);
                            } else {
                                nextUrl.searchParams.delete('urun_ara');
                            }
                            window.history.replaceState({}, '', nextUrl.toString());
                        })
                        .catch(() => {
                            // Canlı aramada olası ağ hatalarını sessiz geçiyoruz.
                        });
                }, 250);

                input.addEventListener('input', fetchRows);

                input.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                    }
                });
            }

            const toast = document.getElementById('admin-toast');
            if (toast) {
                setTimeout(() => {
                    toast.style.opacity = '0';
                    toast.style.transition = 'opacity 0.25s ease';
                    setTimeout(() => toast.remove(), 260);
                }, 2600);
            }
        })();
    </script>

</body>

</html>