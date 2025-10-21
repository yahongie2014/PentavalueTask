<?php

//Order Factory

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
    public function getTotalRevenue(): float
    {
        return $this->orderRepo->getTotalRevenue();
    }

    public function getTopProducts(int $limit = 5): array
    {
        return $this->orderRepo->getTopProducts($limit);
    }

    public function getRecentRevenue(string $since): float
    {
        return $this->orderRepo->getRecentRevenue($since);
    }

    public function getRecentCount(string $since): int
    {
        return $this->orderRepo->getRecentCount($since);
    }

    public function getTopOrderLastMinute(int $minQty = 2): ?array
    {
        return $this->orderRepo->getTopOrderLastMinute($minQty);
    }

}

?>