<?php

namespace App\Repositories;

use Connectivity\DB;
use Connectivity\DBConnectionInterface;

class OrderRepository
{

    protected $db;

    public function __construct(DBConnectionInterface $connection)
    {
        $this->db = $connection->connect();
    }

    public function save(array $data): void
    {
        $stmt = $this->db->prepare("INSERT INTO orders (product_id, quantity, price, created_at) VALUES (?, ?, ?, ?)");
        $stmt->execute([
            $data['product_id'],
            $data['quantity'],
            $data['price'],
            $data['date']
        ]);
    }
}