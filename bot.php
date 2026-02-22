<?php
require_once 'db_baglan.php';

// 60 adet görsel indirme işlemi uzun süreceği için PHP'nin zaman aşımı sınırını kaldırıyoruz
set_time_limit(0); 

echo "<div style='font-family: Arial, sans-serif; background: #1a1a1a; color: #fff; padding: 20px; border-radius: 10px;'>";
echo "<h2 style='color: #d4af37;'>Vintage Vibe - Dev API Entegrasyonu (60 Plak) Çalışıyor...</h2>";
echo "<p style='color: #bbb;'>Lütfen sayfa yüklenmeyi bitirene kadar sekmeyi KAPATMAYIN. Bu işlem yaklaşık 1-2 dakika sürebilir.</p><ul>";

$cekilecek_plaklar = [
    // --- KATEGORİ 1: DÖNEM YERLİ LP (15 Adet Albüm) ---
    ['terim' => 'Barış Manço 2023', 'tip' => 'album', 'kategori_id' => 1],
    ['terim' => 'Cem Karaca Yoksulluk Kader Olamaz', 'tip' => 'album', 'kategori_id' => 1],
    ['terim' => 'Erkin Koray Elektronik Türküler', 'tip' => 'album', 'kategori_id' => 1],
    ['terim' => 'Moğollar Anadolu Pop', 'tip' => 'album', 'kategori_id' => 1],
    ['terim' => 'Selda Bağcan Türkülerimiz', 'tip' => 'album', 'kategori_id' => 1],
    ['terim' => '3 Hürel 3 Hürel', 'tip' => 'album', 'kategori_id' => 1],
    ['terim' => 'Barış Manço Sözüm Meclisten Dışarı', 'tip' => 'album', 'kategori_id' => 1],
    ['terim' => 'Cem Karaca Safinaz', 'tip' => 'album', 'kategori_id' => 1],
    ['terim' => 'Fikret Kızılok Zaman Zaman', 'tip' => 'album', 'kategori_id' => 1],
    ['terim' => 'Edip Akbayram Nedir Ne Değildir', 'tip' => 'album', 'kategori_id' => 1],
    ['terim' => 'MFÖ Ele Güne Karşı', 'tip' => 'album', 'kategori_id' => 1],
    ['terim' => 'Bülent Ortaçgil Benimle Oynar mısın', 'tip' => 'album', 'kategori_id' => 1],
    ['terim' => 'Hardal Nasıl Ne Zaman', 'tip' => 'album', 'kategori_id' => 1],
    ['terim' => 'Ersen ve Dadaşlar', 'tip' => 'album', 'kategori_id' => 1],
    ['terim' => 'Barış Manço Yeni Bir Gün', 'tip' => 'album', 'kategori_id' => 1],

    // --- KATEGORİ 2: YABANCI LP (15 Adet Albüm - Classic Rock / Heavy Metal) ---
    ['terim' => 'Black Sabbath Paranoid', 'tip' => 'album', 'kategori_id' => 2],
    ['terim' => 'Ozzy Osbourne Blizzard of Ozz', 'tip' => 'album', 'kategori_id' => 2],
    ['terim' => 'Motörhead Overkill', 'tip' => 'album', 'kategori_id' => 2],
    ['terim' => 'The Doors L.A. Woman', 'tip' => 'album', 'kategori_id' => 2],
    ['terim' => 'Pink Floyd Dark Side of the Moon', 'tip' => 'album', 'kategori_id' => 2],
    ['terim' => 'Led Zeppelin IV', 'tip' => 'album', 'kategori_id' => 2],
    ['terim' => 'Deep Purple Machine Head', 'tip' => 'album', 'kategori_id' => 2],
    ['terim' => 'AC/DC Back in Black', 'tip' => 'album', 'kategori_id' => 2],
    ['terim' => 'Iron Maiden The Number of the Beast', 'tip' => 'album', 'kategori_id' => 2],
    ['terim' => 'The Beatles Abbey Road', 'tip' => 'album', 'kategori_id' => 2],
    ['terim' => 'Queen A Night at the Opera', 'tip' => 'album', 'kategori_id' => 2],
    ['terim' => 'Jimi Hendrix Are You Experienced', 'tip' => 'album', 'kategori_id' => 2],
    ['terim' => 'Metallica Master of Puppets', 'tip' => 'album', 'kategori_id' => 2],
    ['terim' => 'Guns N Roses Appetite for Destruction', 'tip' => 'album', 'kategori_id' => 2],
    ['terim' => 'David Bowie Ziggy Stardust', 'tip' => 'album', 'kategori_id' => 2],

    // --- KATEGORİ 3: YERLİ 45 LİK (15 Adet Single Şarkı) ---
    ['terim' => 'Erkin Koray Estarabim', 'tip' => 'song', 'kategori_id' => 3],
    ['terim' => 'Selda Bağcan Yuh Yuh', 'tip' => 'song', 'kategori_id' => 3],
    ['terim' => 'Barış Manço Gülpembe', 'tip' => 'song', 'kategori_id' => 3],
    ['terim' => 'Cem Karaca Tamirci Çırağı', 'tip' => 'song', 'kategori_id' => 3],
    ['terim' => 'Moğollar Ağrı Dağı Efsanesi', 'tip' => 'song', 'kategori_id' => 3],
    ['terim' => '3 Hürel Bir Sevmek Bin Defa Ölmek Demekmiş', 'tip' => 'song', 'kategori_id' => 3],
    ['terim' => 'Fikret Kızılok Gönül', 'tip' => 'song', 'kategori_id' => 3],
    ['terim' => 'Edip Akbayram Aldırma Gönül', 'tip' => 'song', 'kategori_id' => 3],
    ['terim' => 'Erkin Koray Çöpçüler', 'tip' => 'song', 'kategori_id' => 3],
    ['terim' => 'Barış Manço Dağlar Dağlar', 'tip' => 'song', 'kategori_id' => 3],
    ['terim' => 'Cem Karaca Namus Belası', 'tip' => 'song', 'kategori_id' => 3],
    ['terim' => 'Beyaz Kelebekler Sen Gidince', 'tip' => 'song', 'kategori_id' => 3],
    ['terim' => 'Silüetler Lorke', 'tip' => 'song', 'kategori_id' => 3],
    ['terim' => 'Apaşlar Resimdeki Gözyaşları', 'tip' => 'song', 'kategori_id' => 3],
    ['terim' => 'Yavuz Çetin Yaşamak İstemem', 'tip' => 'song', 'kategori_id' => 3],

    // --- KATEGORİ 4: YABANCI 45 LİK (15 Adet Single Şarkı) ---
    ['terim' => 'The Beatles Hey Jude', 'tip' => 'song', 'kategori_id' => 4],
    ['terim' => 'Led Zeppelin Immigrant Song', 'tip' => 'song', 'kategori_id' => 4],
    ['terim' => 'Deep Purple Smoke on the Water', 'tip' => 'song', 'kategori_id' => 4],
    ['terim' => 'Black Sabbath Iron Man', 'tip' => 'song', 'kategori_id' => 4],
    ['terim' => 'Ozzy Osbourne Crazy Train', 'tip' => 'song', 'kategori_id' => 4],
    ['terim' => 'Motörhead Ace of Spades', 'tip' => 'song', 'kategori_id' => 4],
    ['terim' => 'Pink Floyd Another Brick in the Wall', 'tip' => 'song', 'kategori_id' => 4],
    ['terim' => 'Queen Bohemian Rhapsody', 'tip' => 'song', 'kategori_id' => 4],
    ['terim' => 'AC/DC Highway to Hell', 'tip' => 'song', 'kategori_id' => 4],
    ['terim' => 'The Rolling Stones Paint It Black', 'tip' => 'song', 'kategori_id' => 4],
    ['terim' => 'The Doors Light My Fire', 'tip' => 'song', 'kategori_id' => 4],
    ['terim' => 'Jimi Hendrix Purple Haze', 'tip' => 'song', 'kategori_id' => 4],
    ['terim' => 'Eagles Hotel California', 'tip' => 'song', 'kategori_id' => 4],
    ['terim' => 'Aerosmith Dream On', 'tip' => 'song', 'kategori_id' => 4],
    ['terim' => 'The Animals House of the Rising Sun', 'tip' => 'song', 'kategori_id' => 4],
];

$basarili = 0;
$hatali = 0;

foreach ($cekilecek_plaklar as $plak) {
    $aranacak = urlencode($plak['terim']);
    $entity = $plak['tip']; // 'album' veya 'song'
    
    // iTunes API URL'sini oluştur
    $api_url = "https://itunes.apple.com/search?term={$aranacak}&entity={$entity}&limit=1";
    
    // API'ye cURL ile bağlan
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $cevap = curl_exec($ch);
    curl_close($ch);
    
    $veri = json_decode($cevap, true);
    
    if (isset($veri['results'][0])) {
        $sonuc = $veri['results'][0];
        
        // Single (song) ise trackName, Album ise collectionName alıyoruz
        $baslik = ($entity == 'song') ? $sonuc['trackName'] . " (45'lik)" : $sonuc['collectionName'];
        $sanatci = $sonuc['artistName'];
        $cikis_yili = substr($sonuc['releaseDate'], 0, 4);
        
        // Fiyatlandırma ve Stok
        $fiyat = ($entity == 'song') ? rand(300, 800) : rand(1200, 3500); 
        $stok = rand(0, 5); // Bazıları tükendi olarak gelsin
        $baski_turu = 'Dönem Baskı'; // Hepsi Vintage Vibe ruhuna uygun dönem baskı
        
        // 600x600 Yüksek Çözünürlüklü Kapak
        $orijinal_resim = str_replace('100x100bb', '600x600bb', $sonuc['artworkUrl100']);
        $resim_adi = "api_" . md5($baslik . $sanatci) . ".jpg";
        $kayit_yolu = __DIR__ . '/images/' . $resim_adi;
        
        // Kapak görselini indir ve kaydet
        $resim_verisi = @file_get_contents($orijinal_resim);
        if ($resim_verisi !== false) {
            file_put_contents($kayit_yolu, $resim_verisi);
        } else {
            $resim_adi = ''; 
        }
        
        // Veritabanına Kayıt
        $sorgu = $db->prepare("INSERT INTO plaklar (baslik, sanatci, cikis_yili, fiyat, stok, kategori_id, baski_turu, kapak_gorseli) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $sorgu->execute([$baslik, $sanatci, $cikis_yili, $fiyat, $stok, $plak['kategori_id'], $baski_turu, $resim_adi]);
        
        echo "<li style='margin-bottom: 5px; font-size: 0.9em;'>✅ <b>{$sanatci}</b> - {$baslik}</li>";
        $basarili++;
    } else {
        echo "<li style='color: #ef4444; margin-bottom: 5px; font-size: 0.9em;'>❌ <i>{$plak['terim']}</i> bulunamadı.</li>";
        $hatali++;
    }
}

echo "</ul>";
echo "<hr style='border-color: #333;'>";
echo "<h3 style='color: #10b981;'>🎉 İşlem Tamam! {$basarili} Plak Eklendi, {$hatali} Hata.</h3>";
echo "<a href='index.php' style='display: inline-block; margin-top: 15px; padding: 10px 20px; background: #d4af37; color: #000; text-decoration: none; border-radius: 5px; font-weight: bold;'>Ana Sayfaya Dön ve Vitrini Gör</a>";
echo "</div>";
?>