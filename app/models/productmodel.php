<?php

class ProductModel
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getAll(): array
    {
        $stmt = $this->pdo->query("
            SELECT product_id, name, price
            FROM product
            WHERE is_active = 1
        ");
        return $stmt->fetchAll() ?: [];
    }

    public function getById(int $id): array|false
    {
        $stmt = $this->pdo->prepare("
            SELECT product_id, name, description, price
            FROM product
            WHERE product_id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    // opcionális – ha még nincs kész, NEM HAL MEG
    public function getByGender(string $gender): array
    {
        return [];
    }
}
