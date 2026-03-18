<?php
// ===== SESSION YÖNETİMİ =====
// SESSION HEADER'DAN ÖNCE BAŞLATILMALI!

// Session başlat
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// JSON header kanalı (API için) - SESSION'DAN SONRA!
header('Content-Type: application/json; charset=utf-8');

// Favoriler başlat
if (!isset($_SESSION['favoriler'])) {
    $_SESSION['favoriler'] = [];
}

// Sepet başlat
if (!isset($_SESSION['sepet'])) {
    $_SESSION['sepet'] = [];
}
