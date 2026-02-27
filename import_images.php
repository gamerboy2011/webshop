<?php
/**
 * Terméképek importálása külső mappából
 * Futtatás: php import_images.php
 */

require_once __DIR__ . '/app/config/database.php';

$sourcePath = '/Volumes/T7 Shield/vizsga_remek_media/Products';
$destPath = __DIR__ . '/uploads/products';

// Létrehozzuk a cél mappát
if (!is_dir($destPath)) {
    mkdir($destPath, 0755, true);
}

// Kategória -> product_type_id és subtype mapping
$categoryMap = [
    'Sneakers' => ['type_id' => 3, 'subtypes' => ['shoes' => 8, 'sandals' => 9]],
    'Clothes' => ['type_id' => 2, 'subtypes' => [
        'Hoodie' => 2, 'T-Shirt' => 1, 'Pants' => 4, 
        'Essentials' => 2, 'Supreme' => 1, 'Vlone' => 1, 'Unreal' => 2, 'Kaws' => 1
    ]],
    'Accesories' => ['type_id' => 1, 'subtypes' => ['Eyewear' => 12, 'Socks' => 12]]
];

// Vendor keresése/létrehozása
function getOrCreateVendor($pdo, $name) {
    $stmt = $pdo->prepare("SELECT vendor_id FROM vendor WHERE name = ?");
    $stmt->execute([$name]);
    $id = $stmt->fetchColumn();
    
    if (!$id) {
        $stmt = $pdo->prepare("INSERT INTO vendor (name) VALUES (?)");
        $stmt->execute([$name]);
        $id = $pdo->lastInsertId();
        echo "  ✓ Új márka létrehozva: $name (ID: $id)\n";
    }
    return $id;
}

// Termék keresése/létrehozása
function getOrCreateProduct($pdo, $name, $vendorId, $subtypeId, $genderId = 3) {
    // Keresés pontos névvel
    $stmt = $pdo->prepare("SELECT product_id FROM product WHERE name = ?");
    $stmt->execute([$name]);
    $id = $stmt->fetchColumn();
    
    if (!$id) {
        // Új termék létrehozása
        $price = rand(15000, 85000);
        $stmt = $pdo->prepare("
            INSERT INTO product (name, description, price, is_active, gender_id, color_id, vendor_id, subtype_id, is_sale)
            VALUES (?, ?, ?, 1, ?, 1, ?, ?, 0)
        ");
        $stmt->execute([$name, $name, $price, $genderId, $vendorId, $subtypeId]);
        $id = $pdo->lastInsertId();
        echo "  ✓ Új termék létrehozva: $name (ID: $id)\n";
    }
    return $id;
}

// Kép hozzáadása
function addProductImage($pdo, $productId, $srcPath, $position) {
    // Ellenőrzés, hogy már létezik-e
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM product_img WHERE product_id = ? AND src = ?");
    $stmt->execute([$productId, $srcPath]);
    if ($stmt->fetchColumn() > 0) {
        return false;
    }
    
    $stmt = $pdo->prepare("INSERT INTO product_img (product_id, src, position) VALUES (?, ?, ?)");
    $stmt->execute([$productId, $srcPath, $position]);
    return true;
}

echo "=== TERMÉKÉPEK IMPORTÁLÁSA ===\n\n";

$totalProducts = 0;
$totalImages = 0;

// Végigmegyünk a kategóriákon
foreach (['Sneakers', 'Clothes', 'Accesories'] as $category) {
    $categoryPath = "$sourcePath/$category";
    if (!is_dir($categoryPath)) continue;
    
    echo "\n📁 Kategória: $category\n";
    
    $typeId = $categoryMap[$category]['type_id'];
    
    // Almappák (márkák vagy alkategóriák)
    foreach (scandir($categoryPath) as $subDir) {
        if ($subDir[0] === '.') continue;
        $subPath = "$categoryPath/$subDir";
        if (!is_dir($subPath)) continue;
        
        // Ellenőrizzük, hogy márka vagy termékmappa
        $subItems = scandir($subPath);
        $hasProductFolders = false;
        foreach ($subItems as $item) {
            if ($item[0] !== '.' && is_dir("$subPath/$item")) {
                $hasProductFolders = true;
                break;
            }
        }
        
        if ($hasProductFolders) {
            // Ez egy márka mappa
            $vendorName = $subDir;
            $vendorId = getOrCreateVendor($pdo, $vendorName);
            
            // Subtype meghatározása
            $subtypeId = $categoryMap[$category]['subtypes'][$subDir] ?? 
                         ($category === 'Sneakers' ? 8 : ($category === 'Clothes' ? 1 : 12));
            
            echo "\n  🏷️  Márka: $vendorName\n";
            
            // Termékmappák
            foreach (scandir($subPath) as $productDir) {
                if ($productDir[0] === '.') continue;
                $productPath = "$subPath/$productDir";
                if (!is_dir($productPath)) continue;
                
                $productName = $productDir;
                $productId = getOrCreateProduct($pdo, $productName, $vendorId, $subtypeId);
                $totalProducts++;
                
                // Létrehozzuk a termék képmappáját
                $productDestDir = "$destPath/$productId";
                if (!is_dir($productDestDir)) {
                    mkdir($productDestDir, 0755, true);
                }
                
                // Képek másolása
                $position = 1;
                $images = glob("$productPath/*.{jpg,jpeg,png,webp}", GLOB_BRACE);
                foreach ($images as $imgPath) {
                    $imgName = basename($imgPath);
                    $destImgPath = "$productDestDir/$imgName";
                    $relPath = "uploads/products/$productId/$imgName";
                    
                    if (!file_exists($destImgPath)) {
                        copy($imgPath, $destImgPath);
                    }
                    
                    if (addProductImage($pdo, $productId, $relPath, $position)) {
                        $totalImages++;
                        $position++;
                    }
                }
                
                echo "    📦 $productName - " . count($images) . " kép\n";
            }
        }
    }
}

echo "\n=== KÉSZ ===\n";
echo "Termékek: $totalProducts\n";
echo "Új képek: $totalImages\n";
