<?php
require_once __DIR__ . "/../library/config.php";

header("Content-Type: application/json; charset=utf-8");

$city = $_GET['city'] ?? '';

if (!$city || strlen($city) < 2) {
    echo json_encode(["postcode" => ""]);
    exit;
}

// Ékezetek eltávolítása
function normalize($str) {
    $search  = ['á','é','í','ó','ö','ő','ú','ü','ű','Á','É','Í','Ó','Ö','Ő','Ú','Ü','Ű'];
    $replace = ['a','e','i','o','o','o','u','u','u','a','e','i','o','o','o','u','u','u'];
    return strtolower(str_replace($search, $replace, $str));
}

$normalizedCity = normalize($city);

// Lekérdezés
$stmt = $pdo->query("SELECT city_name, postcode FROM city");
$result = $stmt->fetchAll();

$foundPostcode = "";

// Csak pontos egyezés!
foreach ($result as $row) {
    if (normalize($row['city_name']) === $normalizedCity) {
        $foundPostcode = $row['postcode'];
        break;
    }
}

echo json_encode([
    "postcode" => $foundPostcode
]);
