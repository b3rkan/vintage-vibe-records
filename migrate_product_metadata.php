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
    vvr_add_column_if_missing($db, 'plaklar', 'kondisyon_kapak', "VARCHAR(20) NULL AFTER `baski_turu`");
    vvr_add_column_if_missing($db, 'plaklar', 'kondisyon_plak', "VARCHAR(20) NULL AFTER `kondisyon_kapak`");
    echo "\nMigration tamamlandı.\n";
} catch (Exception $e) {
    http_response_code(500);
    echo "Hata: " . $e->getMessage() . "\n";
}
