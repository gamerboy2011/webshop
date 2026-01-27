<?php

class ProductController
{
    private ProductModel $model;

    public function __construct()
    {
        global $pdo;

        if (!isset($pdo) || !$pdo instanceof PDO) {
            die("HIBA: PDO nem elérhető");
        }

        $this->model = new ProductModel($pdo);
    }

    /**
     * Főoldal / terméklista
     */
    public function index(): void
    {
        // MINDIG definiáljuk
        $products = [];

        // gender csak akkor, ha van
        $gender = $_GET['gender'] ?? null;

        if ($gender && method_exists($this->model, 'getByGender')) {
            $products = $this->model->getByGender($gender);
        } else {
            $products = $this->model->getAll();
        }

        require __DIR__ . "/../views/pages/home.php";
    }

    /**
     * Egy termék oldala
     */
    public function show(): void
    {
        $product = null;

        $id = $_GET['product'] ?? null;

        if ($id && is_numeric($id) && method_exists($this->model, 'getById')) {
            $product = $this->model->getById((int)$id);
        }

        if (!$product) {
            die("A termék nem található");
        }

        require __DIR__ . "/../views/pages/product.php";
    }
}
