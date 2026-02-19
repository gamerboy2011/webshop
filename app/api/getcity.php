<?php
require_once __DIR__ . "/../library/config.php";

header("Content-Type: application/json; charset=utf-8");

$zip = $_GET['zip'] ?? '';

if (!$zip || strlen($zip) !== 4) {
    echo json_encode(["city" => ""]);
    exit;
}

$stmt = $pdo->prepare("SELECT city_name FROM city WHERE postcode = ?");
$stmt->execute([$zip]);

$city = $stmt->fetchColumn();

echo json_encode([
    "city" => $city ?: ""
]);
