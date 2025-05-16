<?php

namespace App\Services;

use App\Repositories\ProductRepository;

class ProductService
{
    protected ProductRepository $repo;

    public function __construct()
    {
        $this->repo = new ProductRepository();
    }

    public function getAll(): array
    {
        return $this->repo->all();
    }
}
