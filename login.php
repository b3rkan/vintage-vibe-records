<?php
session_start(); // Oturum yönetimini başlatır

$hata = "";

// Eğer form gönderildiyse
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $kullanici = $_POST['kullanici'];
    $sifre = $_POST['sifre'];

    // Güvenlik için belirlediğimiz yönetici bilgileri (Admin / 123456)
    if ($kullanici === 'admin' && $sifre === '123456') {
        // Giriş başarılıysa session (oturum) değişkenini oluştur ve admin panele yönlendir
        $_SESSION['admin_giris_yapti'] = true;
        header("Location: admin.php");
        exit;
    } else {
        $hata = "<div class='hata-mesaji' style='background-color:#c62828; color:white; padding:10px; border-radius:4px; margin-bottom:15px; text-align:center;'>Hatalı kullanıcı adı veya şifre!</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sisteme Giriş | Vintage Vibe</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .login-container { max-width: 400px; margin: 100px auto; background-color: #222; padding: 40px; border-radius: 8px; border: 1px solid #444; }
        .login-container h2 { text-align: center; color: #d4af37; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Yönetici Girişi</h2>
        <?php echo $hata; ?>
        <form action="login.php" method="POST" class="plak-form">
            <div class="form-grup">
                <label for="kullanici">Kullanıcı Adı:</label>
                <input type="text" id="kullanici" name="kullanici" required>
            </div>
            <div class="form-grup">
                <label for="sifre">Şifre:</label>
                <input type="password" id="sifre" name="sifre" required>
            </div>
            <button type="submit" class="gonder-btn">Giriş Yap</button>
        </form>
        <div style="text-align: center; margin-top: 15px;">
            <a href="index.php" style="color: #888; text-decoration: none;">&larr; Mağazaya Dön</a>
        </div>
    </div>
</body>
</html>