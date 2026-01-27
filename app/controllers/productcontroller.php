<?php

require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../models/ProductModel.php";

class ProductController
{
    private ProductModel $model;

    public function __construct()
    {
        global $pdo;
        $this->model = new ProductModel($pdo);
    }

    /**
     * Főoldal / terméklista
     */
    public function index(): void
    {
        $products = $this->model->getAll();
        require __DIR__ . "/../views/pages/home.php";
    }

    /**
     * Egy termék részletei
     */
    public function show(): void
    {
        if (!isset($_GET['id'])) {
            echo "Nincs termék ID";
            return;
        }

        $id = (int)$_GET['id'];
        $product = $this->model->getById($id);

        require __DIR__ . "/../views/pages/product.php";
    }
}