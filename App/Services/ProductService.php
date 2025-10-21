<?php

namespace App\Services;

use App\Repositories\ProductRepository;
use Connectivity\DB;

class ProductService
{
    protected ProductRepository $repo;

    public function __construct()
    {
        $db = new DB();
        $this->repo = new ProductRepository($db);
    }

    public function getAll(): array
    {
        return $this->repo->all();
    }

    public function getById(int $id): ?array
    {
        return $this->repo->findById($id);
    }

    public function exists(int $id): bool
    {
        return $this->repo->findById($id) !== null;
    }
}