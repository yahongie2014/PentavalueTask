<?php

namespace App\Controllers;

use PDO;
use Predis\Client;
use App\Services\OrderService;
use App\Helpers\ResponseHelper;
use App\Services\ProductService;

class SalesController extends BaseController
{
    public $apikey;
    public $city;
    public $openAI;
    protected $orderService;
    protected $productService;

    public function __construct($db = null)
    {
        parent::__construct($db);

        $this->apikey = $_ENV['WATHER_API_KEY'] ?? '';
        $this->city = $_ENV['CITY'] ?? 'Cairo';
        $this->openAI = $_ENV['OPENAI_API_KEY'] ?? '';
        $this->orderService = new OrderService();
        $this->productService = new ProductService();

    }

    public function getFrontPage()
    {
        $this->redirect('/public/index.php');
    }

    public function getAPiRequest()
    {
        $this->redirect('/test-api.php');
    }

    public function getAllProducts()
    {
        $this->only('GET');
        $products = $this->productService->getAll();

        return ResponseHelper::json([
            'data' => $products,
            'count' => count($products)
        ]);
    }

    public function handleNewOrder()
    {
        $this->only('POST');

        $request = $this->input();
        if (!$request) return $this->json(['error' => 'Invalid JSON'], 400);

        try {
            $order = $this->orderService->createOrder($request);
            return $this->json(['data' => $order, 'status' => 'order saved']);
        } catch (\Exception $e) {
            return $this->serverError($e->getMessage());
        }

    }

    public function getAnalytics()
    {
        $now = time();
        $oneMinAgo = date('Y-m-d H:i:s', $now - 60);
        return $this->json([
            'total_revenue' => $this->orderService->getTotalRevenue(),
            'top_products' => $this->orderService->getTopProducts(),
            'orders_last_minute' => $this->orderService->getTopOrderLastMinute(),
            'revenue_last_minute' => $this->orderService->getRecentRevenue($oneMinAgo),
            'count_orders_last_minute' => $this->orderService->getRecentCount($oneMinAgo),
        ]);
    }

    public function getAIRecommendations()
    {
        $recentOrders = $this->pdo->query("SELECT p.name AS product_name, SUM(o.quantity) AS total_ordered
        FROM orders o
        JOIN products p ON o.product_id = p.id
        WHERE o.created_at >= NOW() - INTERVAL 60 MINUTE
        GROUP BY o.product_id
        ORDER BY total_ordered DESC
        LIMIT 20
    ")->fetchAll(PDO::FETCH_ASSOC);

        $temp = $this->getWeather();
        $recommendedTypes = '';
        $adjustedProducts = [];
        $suggestedType = '';
        $suggestedProducts = [];

        if ($temp !== null) {
            if ($temp > 30) {
                $recommendedTypes = 'cold drinks like Juice, Water, Cola';
                $suggestedType = 'cold';
                $suggestedProducts = ['Cola', 'Water', 'Juice'];
            } elseif ($temp < 15) {
                $recommendedTypes = 'hot drinks like Tea and Coffee';
                $suggestedType = 'hot';
                $suggestedProducts = ['Tea', 'Coffee'];
            } else {
                $recommendedTypes = 'balanced drinks depending on demand';
                $suggestedType = 'neutral';
                $suggestedProducts = ['Water', 'Tea'];
            }
        }
        $allProducts = $this->pdo->query("SELECT name, price FROM products")->fetchAll(PDO::FETCH_ASSOC);

        foreach ($allProducts as $product) {
            $adjusted = $product['price'];
            if (!in_array($product['name'], $suggestedProducts)) {
                continue;
            }
            if ($temp > 30 && in_array($product['name'], ['Juice', 'Water', 'Cola'])) {
                $adjusted = round($product['price'] * 1.10, 2);
            } elseif ($temp < 10 && in_array($product['name'], ['Tea', 'Coffee'])) {
                $adjusted = round($product['price'] * 0.90, 2);
            }

            $adjustedProducts[] = [
                'product' => $product['name'],
                'price' => $adjusted . ' LE'
            ];
        }

        $prompt = "Temperature: {$temp} C " .
            "Suggested promotion: {$recommendedTypes} " .
            "Adjusted pricing based on Weather: " . $suggestedType .
            " Like: " . implode(', ', $suggestedProducts);

        return ResponseHelper::json([
            'recommendations' => $prompt,
            'adjusted_prices' => $adjustedProducts,
        ]);

    }

    private function publishToWebSocket($event, $data)
    {
        $redis = new Client();
        $payload = $redis->publish('sales_channel', json_encode([
            'event' => $event,
            'data' => $data
        ]));

        $socket = @fsockopen("0.0.0.0", 8080, $errno, $errstr, 1);
        if ($socket) {
            fwrite($socket, $payload);
            fclose($socket);
        }
    }

    public function seedDatabase()
    {
//        $this->pdo->exec("DELETE FROM orders");
//        $this->pdo->exec("DELETE FROM products");

        $products = [
            ['Cola', 10.0],
            ['Water', 5.0],
            ['Coffee', 15.0],
            ['Tea', 12.0],
            ['Juice', 8.0]
        ];

        $stmt = $this->pdo->prepare("INSERT INTO products (name, price) VALUES (?, ?)");
        foreach ($products as $product) {
            $stmt->execute($product);
        }

        $productMap = $this->pdo->query("SELECT id, price FROM products")->fetchAll(PDO::FETCH_KEY_PAIR);
        $orderStmt = $this->pdo->prepare("INSERT INTO orders (product_id, quantity, price, created_at) VALUES (?, ?, ?, ?)");

        foreach ($productMap as $productId => $price) {
            for ($i = 0; $i < rand(3, 6); $i++) {
                $quantity = rand(1, 5);
                $date = date('Y-m-d H:i:s', strtotime('-' . rand(0, 10) . ' minutes'));
                $orderStmt->execute([$productId, $quantity, $price, $date]);
            }
        }
        return ResponseHelper::json([
            'message' => 'Database seeded with demo products and orders'
        ]);
    }

    private function getWeather()
    {
        $url = "https://api.openweathermap.org/data/2.5/weather?q=" . urlencode($this->city) . "&appid={$this->apikey}&units=metric";

        $response = $this->curlRequest($url);

        if (!$response) {
            error_log("Weather API error for {$this->city}");
            return null;
        }

        $data = json_decode($response, true);
        return $data['main']['temp'] ?? null;
    }

    private function getDynamicPrice($basePrice, $temperature): float
    {
        if ($temperature > 30) {
            return round($basePrice * 1.1, 2);
        } elseif ($temperature < 10) {
            return round($basePrice * 0.9, 2);
        }
        return $basePrice;
    }

}

?>