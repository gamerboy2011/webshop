<?php
/**
 * Cities API
 * GET /api/v1/cities?postcode={code} - Város keresése irányítószám alapján
 */

global $pdo;

switch ($method) {
    case 'GET':
        // GET /api/v1/cities?postcode={code}
        $postcode = $queryParams['postcode'] ?? null;
        
        if (!$postcode) {
            ApiResponse::badRequest('Hiányzó paraméter: postcode');
        }
        
        // Irányítószám validálás (4 számjegy)
        $postcode = preg_replace('/[^0-9]/', '', $postcode);
        if (strlen($postcode) !== 4) {
            ApiResponse::badRequest('Érvénytelen irányítószám formátum (4 számjegy szükséges)');
        }
        
        // Város keresése az adatbázisban
        $stmt = $pdo->prepare("SELECT city_name FROM city WHERE postcode = ? LIMIT 1");
        $stmt->execute([(int)$postcode]);
        $cityName = $stmt->fetchColumn();
        
        if ($cityName) {
            ApiResponse::success([
                'postcode' => $postcode,
                'city' => $cityName
            ]);
        } else {
            ApiResponse::notFound('Nem található város ehhez az irányítószámhoz');
        }
        break;
        
    default:
        ApiResponse::methodNotAllowed(['GET']);
}
