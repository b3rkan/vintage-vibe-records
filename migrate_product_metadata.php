<?php
require_once __DIR__ . '/db_baglan.php';

function vvr_column_exists(PDO $db, $table, $column)
{
    $stmt = $db->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
    $stmt->execute([$column]);
    return (bool)$stmt->fetch(PDO::FETCH_ASSOC);
}

function vvr_add_column_if_missing(PDO $db, $table, $column, $definition)
{
    if (vvr_column_exists($db, $table, $column)) {
        echo "- $column zaten var\n";
        return;
    }

    $sql = "ALTER TABLE `$table` ADD COLUMN `$column` $definition";
    $db->exec($sql);
    echo "+ $column eklendi\n";
}

header('Content-Type: text/plain; charset=utf-8');
echo "Vintage Vibe Records - Ürün Meta Migration\n";
echo "==========================================\n";

try {
    vvr_add_column_if_missing($db, 'plaklar', 'format', "VARCHAR(50) NULL AFTER `sanatci`");
    vvr_add_column_if_missing($db, 'plaklar', 'firma', "VARCHAR(100) NULL AFTER `format`");
    vvr_add_column_if_missing($db, 'plaklar', 'label', "VARCHAR(100) NULL AFTER `firma`");
    vvr_add_column_if_missing($db, 'plaklar', 'edition', "VARCHAR(60) NULL AFTER `label`");
    vvr_add_column_if_missing($db, 'plaklar', 'catalog_no', "VARCHAR(50) NULL AFTER `edition`");
    vvr_add_column_if_missing($db, 'plaklar', 'rpm', "VARCHAR(20) NULL AFTER `catalog_no`");
    vvr_add_column_if_missing($db, 'plaklar', 'vinyl_weight', "VARCHAR(20) NULL AFTER `rpm`");
    vvr_add_column_if_missing($db, 'plaklar', 'color_variant', "VARCHAR(40) NULL AFTER `vinyl_weight`");
    vvr_add_column_if_missing($db, 'plaklar', 'aciklama', "TEXT NULL AFTER `color_variant`");
    vvr_add_column_if_missing($db, 'plaklar', 'tracklist', "TEXT NULL AFTER `aciklama`");
    vvr_add_column_if_missing($db, 'plaklar', 'audio_preview_url', "VARCHAR(255) NULL AFTER `tracklist`");
    vvr_add_column_if_missing($db, 'plaklar', 'gallery_images', "TEXT NULL AFTER `audio_preview_url`");
    vvr_add_column_if_missing($db, 'plaklar', 'kondisyon_kapak', "VARCHAR(20) NULL AFTER `baski_turu`");
    vvr_add_column_if_missing($db, 'plaklar', 'kondisyon_plak', "VARCHAR(20) NULL AFTER `kondisyon_kapak`");
    echo "\nMigration tamamlandı.\n";
} catch (Exception $e) {
    http_response_code(500);
    echo "Hata: " . $e->getMessage() . "\n";
}
