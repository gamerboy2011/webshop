<?php
/**
 * Magyar irányítószám - város lookup API (adatbázisból)
 * GET /api/postal.php?zip=2000 → {"city": "Szentendre"}
 * GET /api/postal.php?city=Szentendre → {"zips": ["2000"], "zip": "2000"}
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../app/config/database.php';

$zip = $_GET['zip'] ?? null;
$city = $_GET['city'] ?? null;

if ($zip) {
    $zip = preg_replace('/[^0-9]/', '', $zip);
    $stmt = $pdo->prepare("SELECT city_name FROM city WHERE postcode = ? LIMIT 1");
    $stmt->execute([(int)$zip]);
    $result = $stmt->fetchColumn();
    echo json_encode(['city' => $result ?: null]);
    
} elseif ($city) {
    $stmt = $pdo->prepare("SELECT postcode FROM city WHERE city_name LIKE ? ORDER BY postcode LIMIT 10");
    $stmt->execute([$city . '%']);
    $zips = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo json_encode(['zips' => $zips, 'zip' => $zips[0] ?? null]);
    
} else {
    echo json_encode(['error' => 'Missing zip or city parameter']);
}
