<?php
session_start();

$hata = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $kullanici = $_POST['kullanici'] ?? '';
    $sifre = $_POST['sifre'] ?? '';

    if ($kullanici === 'admin' && $sifre === '123456') {
        $_SESSION['admin_giris_yapti'] = true;
        header("Location: admin.php");
        exit;
    } else {
        $hata = "Hatalı kullanıcı adı veya şifre!";
    }
}
?><!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yönetici Girişi | Vintage Vibe Records</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Style -->
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
</head>
<body class="vvr-theme">

<!-- TOP BAR -->
<div class="vvr-topbar">
    <div class="vvr-container">
        <div class="topbar-left">
            <span class="topbar-text">🎵 Vintage Vibe Records - Premium Plak Koleksiyonu</span>
        </div>
        <div class="topbar-right">
            <a href="index.php" class="topbar-link">← Ana Sayfaya Dön</a>
        </div>
    </div>
</div>

<!-- LOGIN CONTAINER -->
<div class="vvr-container" style="display: flex; align-items: center; justify-content: center; min-height: calc(100vh - 200px); padding: 40px 20px;">
    <div class="login-container">
        <h2>🔐 Yönetici Girişi</h2>
        
        <?php if (!empty($hata)): ?>
            <div class="hata-mesaji"><?php echo htmlspecialchars($hata); ?></div>
        <?php endif; ?>
        
        <form method="POST" class="plak-form">
            <div class="form-grup">
                <label for="kullanici">Kullanıcı Adı:</label>
                <input type="text" id="kullanici" name="kullanici" required autofocus>
            </div>
            
            <div class="form-grup">
                <label for="sifre">Şifre:</label>
                <input type="password" id="sifre" name="sifre" required>
            </div>
            
            <button type="submit">Giriş Yap</button>
        </form>

        <div style="text-align: center; margin-top: 20px; color: var(--text-muted); font-size: 0.85em;">
            <p>Test Hesabı: admin / 123456</p>
        </div>
    </div>
</div>

<!-- FOOTER -->
<footer class="vvr-footer">
    <div class="vvr-container">
        <div class="footer-bottom">
            <p>&copy; 2026 Vintage Vibe Records. Tüm Hakları Saklıdır.</p>
        </div>
    </div>
</footer>

<script src="js/main.js?v=<?php echo time(); ?>"></script>
</body>
</html>