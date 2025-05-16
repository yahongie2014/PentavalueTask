<?php

namespace App\Repositories;

use Connectivity\DB;

class OrderRepository
{
    public function save(array $data): void
    {
        $db = DB::connect();
        $stmt = $db->prepare("INSERT INTO orders (product_id, quantity, price, created_at) VALUES (?, ?, ?, ?)");
        $stmt->execute([
            $data['product_id'],
            $data['quantity'],
            $data['price'],
            $data['date']
        ]);
    }
}