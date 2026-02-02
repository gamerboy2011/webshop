<?php
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

$postcode = $_GET['postcode'] ?? '';

if (!preg_match('/^\d{4}$/', $postcode)) {
    echo json_encode([]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT city_id, city_name
    FROM city
    WHERE postcode = ?
    ORDER BY city_name
");
$stmt->execute([$postcode]);

echo json_encode($stmt->fetchAll());