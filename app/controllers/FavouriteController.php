<?php

class FavouriteController
{
    private FavouriteModel $favouriteModel;

    public function __construct(PDO $pdo)
    {
        $this->favouriteModel = new FavouriteModel($pdo);
    }

    public function toggle(): void
    {
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['error' => 'not_logged_in']);
            return;
        }

        $productId = intval($_POST['product_id'] ?? 0);

        if ($productId <= 0) {
            echo json_encode(['error' => 'invalid_product']);
            return;
        }

        $this->favouriteModel->toggle($_SESSION['user_id'], $productId);

        echo json_encode(['success' => true]);
    }
}
