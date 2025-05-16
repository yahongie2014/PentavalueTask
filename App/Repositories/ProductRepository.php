<?php

namespace App\Repositories;

use Connectivity\DB;

class ProductRepository
{
    public function getNameById($id): ?array
    {
        $db = DB::connect();
        $stmt = $db->prepare("SELECT name FROM products WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }
}