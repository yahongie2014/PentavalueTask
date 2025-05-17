<?php

namespace App\Repositories;

use Connectivity\DBConnectionInterface;

class ProductRepository
{
    protected \PDO $db;

    public function __construct(DBConnectionInterface $connection)
    {
        $this->db = $connection->connect();
    }

    public function all(): array
    {
        $stmt = $this->db->query("SELECT id, name, price FROM products");
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getNameById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT name FROM products WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }
}
