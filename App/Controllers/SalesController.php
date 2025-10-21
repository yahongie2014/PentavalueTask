<?php

namespace App\Controllers;

use PDO;
use Predis\Client;
use App\Services\OrderService;
use App\Helpers\ResponseHelper;
use App\Services\ProductService;
use App\Validators\OrderValidator;
use function Symfony\Component\Routing\Loader\Configurator\env;

class SalesController extends BaseController
{
    private const CACHE_TTL = 55;
    private const HOT_TEMP_THRESHOLD = 30;
    private const COLD_TEMP_THRESHOLD = 15;
    private const PRICE_INCREASE_MULTIPLIER = 1.10;
    private const PRICE_DECREASE_MULTIPLIER = 0.90;
    protected OrderService $orderService;
    protected ProductService $productService;
    private string $apikey;
    private string $city;
    private string $openAI;

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

        try {
            $products = $this->productService->getAll();

            return ResponseHelper::json([
                'data' => $products,
                'count' => count($products)
            ]);
        } catch (\Exception $e) {
            error_log("Error fetching products: " . $e->getMessage());
            return $this->serverError("Unable to fetch products");
        }
    }

    public function handleNewOrder()
    {
        $this->only('POST');

        $request = $this->input();
        if (!$request) {
            return $this->json(['error' => 'Invalid JSON'], 400);
        }

        // Validate input
        $validation = OrderValidator::validate($request);
        if (!$validation['valid']) {
            return $this->json(['error' => $validation['errors']], 400);
        }

        try {
            // Verify product exists and price matches
            $product = $this->productService->getById($request['product_id']);
            if (!$product) {
                return $this->json(['error' => 'Product not found'], 404);
            }

            if (abs($product['price'] - $request['price']) > 0.01) {
                return $this->json(['error' => 'Price mismatch'], 400);
            }

            $this->pdo->beginTransaction();

            $order = $this->orderService->createOrder($request);

            $this->pdo->commit();

            // Clear analytics cache
            $this->clearAnalyticsCache();

            return $this->json([
                'data' => $order,
                'status' => 'order saved'
            ], 201);

        } catch (\Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            error_log("Order creation error: " . $e->getMessage());
            return $this->serverError("Unable to create order");
        }
    }

    private function clearAnalyticsCache(): void
    {
        try {
            if (!class_exists(Client::class)) {
                return;
            }

            $redis = new Client();
            $redis->del(['analytics:dashboard']);
        } catch (\Exception $e) {
            error_log("Redis cache clear error: " . $e->getMessage());
        }
    }

    public function getAnalytics()
    {
        $this->only('GET');

        try {
            $cached = $this->getCachedAnalytics();
            if ($cached) {
                return $this->json($cached);
            }

            $now = time();
            $oneMinAgo = date('Y-m-d H:i:s', $now - 60);

            $analytics = [
                'total_revenue' => $this->orderService->getTotalRevenue(),
                'top_products' => $this->orderService->getTopProducts(),
                'orders_last_minute' => $this->orderService->getTopOrderLastMinute(),
                'revenue_last_minute' => $this->orderService->getRecentRevenue($oneMinAgo),
                'count_orders_last_minute' => $this->orderService->getRecentCount($oneMinAgo),
            ];

            // Cache for 55 seconds
            $this->cacheAnalytics($analytics);

            return $this->json($analytics);

        } catch (\Exception $e) {
            error_log("Analytics error: " . $e->getMessage());
            return $this->serverError("Unable to fetch analytics",$e);
        }
    }

    private function getCachedAnalytics(): ?array
    {
        try {
            // Ensure Redis client exists
            if (!class_exists(Client::class)) {
                return null;
            }

            // Create Redis connection
            $redis = new Client([
                'scheme' => 'tcp',
                'host' => $_ENV['REDIS_HOST'] ?: '127.0.0.1',
                'port' => $_ENV['REDIS_PORT'] ?: 6379,
                'password' => $_ENV['REDIS_PASSWORD'] !== false ? '' : null,
            ]);

            // Try to fetch the cached analytics data
            $cached = $redis->get('analytics:dashboard');

            if ($cached) {
                $decoded = json_decode($cached, true);
                return is_array($decoded) ? $decoded : null;
            }
        } catch (\Exception $e) {
            error_log('Redis cache read error: ' . $e->getMessage());
        }

        return null;
    }

    private function cacheAnalytics(array $data): void
    {
        try {
            if (!class_exists(Client::class)) {
                return;
            }

            $redis = new Client();
            $redis->setex('analytics:dashboard', self::CACHE_TTL, json_encode($data));
        } catch (\Exception $e) {
            error_log("Redis cache write error: " . $e->getMessage());
        }
    }

    public function getAIRecommendations()
    {
        $this->only('GET');

        try {
            $temp = $this->getWeather();

            if ($temp === null) {
                return $this->json([
                    'error' => 'Unable to fetch weather data',
                    'recommendations' => 'Weather service unavailable'
                ], 503);
            }

            $recommendations = $this->generateRecommendations($temp);

            return ResponseHelper::json($recommendations);

        } catch (\Exception $e) {
            error_log("Recommendations error: " . $e->getMessage());
            return $this->serverError("Unable to generate recommendations");
        }
    }

    private function getWeather(): ?float
    {
        if (empty($this->apikey)) {
            error_log("Weather API key not configured");
            return null;
        }

        $url = "https://api.openweathermap.org/data/2.5/weather?q=" .
            urlencode($this->city) .
            "&appid={$this->apikey}&units=metric";

        $response = $this->curlRequest($url);

        if (!$response) {
            error_log("Weather API error for {$this->city}");
            return null;
        }

        $data = json_decode($response, true);

        if (!isset($data['main']['temp'])) {
            error_log("Invalid weather API response");
            return null;
        }

        return (float)$data['main']['temp'];
    }

    private function generateRecommendations(float $temp): array
    {
        $recommendedTypes = '';
        $suggestedType = '';
        $suggestedProducts = [];
        $adjustedProducts = [];

        if ($temp > self::HOT_TEMP_THRESHOLD) {
            $recommendedTypes = 'cold drinks like Juice, Water, Cola';
            $suggestedType = 'cold';
            $suggestedProducts = ['Cola', 'Water', 'Juice'];
        } elseif ($temp < self::COLD_TEMP_THRESHOLD) {
            $recommendedTypes = 'hot drinks like Tea and Coffee';
            $suggestedType = 'hot';
            $suggestedProducts = ['Tea', 'Coffee'];
        } else {
            $recommendedTypes = 'balanced drinks depending on demand';
            $suggestedType = 'neutral';
            $suggestedProducts = ['Water', 'Tea'];
        }

        $allProducts = $this->productService->getAll();

        foreach ($allProducts as $product) {
            if (!in_array($product['name'], $suggestedProducts)) {
                continue;
            }

            $adjusted = $this->calculateDynamicPrice(
                $product['price'],
                $temp,
                $product['name']
            );

            $adjustedProducts[] = [
                'product' => $product['name'],
                'original_price' => $product['price'] . ' LE',
                'adjusted_price' => $adjusted . ' LE',
                'change' => round((($adjusted - $product['price']) / $product['price']) * 100, 1) . '%'
            ];
        }

        $prompt = "Temperature: {$temp}°C. " .
            "Suggested promotion: {$recommendedTypes}. " .
            "Pricing strategy: {$suggestedType} drinks prioritized.";

        return [
            'current_temperature' => $temp,
            'recommendations' => $prompt,
            'adjusted_prices' => $adjustedProducts,
            'suggested_products' => $suggestedProducts
        ];
    }

    private function calculateDynamicPrice(float $basePrice, float $temp, string $productName): float
    {
        $coldDrinks = ['Juice', 'Water', 'Cola'];
        $hotDrinks = ['Tea', 'Coffee'];

        if ($temp > self::HOT_TEMP_THRESHOLD && in_array($productName, $coldDrinks)) {
            return round($basePrice * self::PRICE_INCREASE_MULTIPLIER, 2);
        }

        if ($temp < self::COLD_TEMP_THRESHOLD && in_array($productName, $hotDrinks)) {
            return round($basePrice * self::PRICE_DECREASE_MULTIPLIER, 2);
        }

        return $basePrice;
    }

    public function seedDatabase()
    {
        $this->only('GET');

        try {
            $this->pdo->beginTransaction();

            $this->pdo->exec("DELETE FROM orders");
            $this->pdo->exec("DELETE FROM products");

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

            $this->pdo->commit();

            // Clear cache after seeding
            $this->clearAnalyticsCache();

            return ResponseHelper::json([
                'message' => 'Database seeded with demo products and orders',
                'products_created' => count($products),
                'orders_created' => count($productMap) * 5
            ]);

        } catch (\Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            error_log("Seeding error: " . $e->getMessage());
            return $this->serverError("Unable to seed database");
        }
    }
}

?>