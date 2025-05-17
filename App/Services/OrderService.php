<?php

namespace App\Services;

use App\Repositories\OrderRepository;
use App\Repositories\ProductRepository;
use Connectivity\DB;

class OrderService
{
    protected $orderRepo;
    protected $productRepo;

    public function __construct()
    {
        $this->orderRepo = new OrderRepository(new DB());

        $this->productRepo = new ProductRepository(new DB());
    }

    public function createOrder(array $data): array
    {
        if (empty($data['product_id']) || empty($data['quantity']) || empty($data['price'])) {
            throw new \Exception("Missing required fields");
        }

        $date = $data['date'] ?? date('Y-m-d H:i:s');

        $this->orderRepo->save([
            'product_id' => $data['product_id'],
            'quantity' => $data['quantity'],
            'price' => $data['price'],
            'date' => $date
        ]);

        $product = $this->productRepo->getNameById($data['product_id']);

        $orderData = [
            'product_name' => $product['name'] ?? 'Unknown',
            'quantity' => $data['quantity'],
            'price' => $data['price'],
            'date' => $date
        ];

        return $orderData;
    }
}

?>