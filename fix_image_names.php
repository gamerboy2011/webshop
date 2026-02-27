<?php
/**
 * Képfájlok átnevezése - szóközök eltávolítása
 */

require_once __DIR__ . '/app/config/database.php';

$basePath = __DIR__ . '/uploads/products';

// Összes product_img rekord
$stmt = $pdo->query("SELECT product_img_id, product_id, src FROM product_img WHERE src LIKE '% %'");
$images = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Átnevezendő képek: " . count($images) . "\n\n";

$updated = 0;
foreach ($images as $img) {
    $oldSrc = $img['src'];
    $oldPath = __DIR__ . '/' . $oldSrc;
    
    // Új fájlnév: szóközök -> aláhúzás
    $newSrc = str_replace(' ', '_', $oldSrc);
    $newPath = __DIR__ . '/' . $newSrc;
    
    // Fájl átnevezése
    if (file_exists($oldPath)) {
        if (rename($oldPath, $newPath)) {
            // Adatbázis frissítése
            $stmt = $pdo->prepare("UPDATE product_img SET src = ? WHERE product_img_id = ?");
            $stmt->execute([$newSrc, $img['product_img_id']]);
            $updated++;
            echo "✓ {$img['product_id']}: " . basename($newSrc) . "\n";
        } else {
            echo "✗ Nem sikerült átnevezni: $oldSrc\n";
        }
    } else {
        echo "? Nem található: $oldSrc\n";
    }
}

echo "\n=== KÉSZ ===\n";
echo "Frissítve: $updated kép\n";
