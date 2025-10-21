<?php

namespace App\Repositories;

use PDO;
use Connectivity\DBConnectionInterface;

class ProductRepository
{
    protected PDO $db;

    public function __construct(DBConnectionInterface $connection)
    {
        $this->db = $connection->connect();
    }

    public function all(): array
    {
        $stmt = $this->db->query("SELECT id, name, price FROM products ORDER BY name");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT id, name, price FROM products WHERE id = ?");
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    public function getNameById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT name FROM products WHERE id = ?");
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }
}