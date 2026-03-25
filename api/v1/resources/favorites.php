<?php
/**
 * Favorites API
 * GET /api/v1/favorites - Kedvencek listázása
 * POST /api/v1/favorites - Kedvencekhez adás
 * DELETE /api/v1/favorites/{id} - Kedvencekből törlés
 */

require_once __DIR__ . '/../../../app/models/favouritemodel.php';

global $pdo;
$favoriteModel = new FavouriteModel($pdo);
$resourceId = $segments[1] ?? null;

// User ID ellenőrzése
$userId = $_SESSION['user_id'] ?? null;

if (!$userId) {
    ApiResponse::unauthorized('Bejelentkezés szükséges a kedvencek kezeléséhez');
}

switch ($method) {
    case 'GET':
        // GET /api/v1/favorites
        $favorites = $favoriteModel->getUserFavorites($userId);
        
        ApiResponse::success([
            'favorites' => $favorites,
            'count' => count($favorites)
        ]);
        break;
        
    case 'POST':
        // POST /api/v1/favorites
        $productId = $input['product_id'] ?? null;
        
        if (!$productId) {
            ApiResponse::badRequest('Hiányzó paraméter: product_id');
        }
        
        // Ellenőrzés, hogy már kedvenc-e
        if ($favoriteModel->isFavorite($userId, $productId)) {
            ApiResponse::badRequest('A termék már a kedvencek között van');
        }
        
        // Hozzáadás
        $stmt = $pdo->prepare("INSERT INTO favorites (user_id, product_id) VALUES (?, ?)");
        $result = $stmt->execute([$userId, $productId]);
        
        if ($result) {
            ApiResponse::created(['message' => 'Termék hozzáadva a kedvencekhez']);
        } else {
            ApiResponse::serverError('Nem sikerült hozzáadni a kedvencekhez');
        }
        break;
        
    case 'DELETE':
        // DELETE /api/v1/favorites/{id}
        if (!$resourceId) {
            ApiResponse::badRequest('Hiányzó termék ID');
        }
        
        $result = $favoriteModel->remove($userId, $resourceId);
        
        if ($result) {
            ApiResponse::noContent();
        } else {
            ApiResponse::notFound('A termék nem található a kedvencek között');
        }
        break;
        
    default:
        ApiResponse::methodNotAllowed(['GET', 'POST', 'DELETE']);
}
