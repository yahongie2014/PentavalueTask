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

    public function getAll()
    {
        return $this->repo->all();
    }
}
