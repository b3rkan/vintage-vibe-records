<?php
// ===== FAVORI SİSTEMİ TEST =====
session_start();

echo "<!DOCTYPE html>
<html lang='tr'>
<head>
    <meta charset='UTF-8'>
    <title>Favori Test</title>
    <style>
        body { font-family: Arial; max-width: 800px; margin: 50px auto; }
        .test-box { border: 1px solid #ddd; padding: 20px; margin: 10px 0; }
        button { padding: 10px 20px; background: #d4af37; border: none; border-radius: 4px; cursor: pointer; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
    </style>
</head>
<body>
    <h1>Favori Sistemi Test Sayfası</h1>
    
    <div class='test-box'>
        <h3>Session Durumu</h3>
        <p class='info'>Session ID: " . session_id() . "</p>
        <p class='info'>Favoriler: " . count($_SESSION['favoriler'] ?? []) . " ürün</p>
        <p class='info'>Sepet: " . count($_SESSION['sepet'] ?? []) . " ürün</p>
    </div>
    
    <div class='test-box'>
        <h3>Test Butonları</h3>
        <button onclick='testFavori(1)'>Test: Ürün 1'i Favorilere Ekle</button>
        <button onclick='testFavori(2)'>Test: Ürün 2'i Favorilere Ekle</button>
        <button onclick='testFavori(1)'>Test: Ürün 1'i Favorilerden Çıkar</button>
        <button onclick='location.reload()'>Sayfayı Yenile</button>
    </div>
    
    <div class='test-box'>
        <h3>Konsol Çıkışı</h3>
        <div id='output'></div>
    </div>
    
    <script>
        async function testFavori(productId) {
            const output = document.getElementById('output');
            output.innerHTML = '<p class=\"info\">Gönderiliyor...</p>';
            
            try {
                const response = await fetch('./api/favoriler_toggle.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ id: productId })
                });
                
                console.log('Response Status:', response.status);
                const text = await response.text();
                console.log('Response Text:', text);
                
                const data = JSON.parse(text);
                console.log('Response JSON:', data);
                
                if (data.success) {
                    output.innerHTML = '<p class=\"success\">✓ Başarılı: ' + data.message + '</p>';
                } else {
                    output.innerHTML = '<p class=\"error\">✗ Hata: ' + (data.message || 'Bilinmeyen hata') + '</p>';
                }
            } catch (error) {
                console.error('Hata:', error);
                output.innerHTML = '<p class=\"error\">✗ İstek Hatası: ' + error.message + '</p>';
            }
        }
    </script>
</body>
</html>";
