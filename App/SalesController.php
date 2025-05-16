<?php

namespace App;

use PDO;
use Predis\Client;

class SalesController
{
    private PDO $pdo;
    public string $apikey;
    public string $city;
    public string $openAI;

    public function __construct(string $dbClass)
    {
        $this->pdo = $dbClass::pdo();
        $this->apikey = $_ENV['WATHER_API_KEY'] ?? '';
        $this->city = $_ENV['CITY'] ?? 'Cairo';
        $this->openAI = $_ENV['OPENAI_API_KEY'] ?? '';
    }

    public function getAllProducts()
    {
        $products = $this->pdo->query("SELECT id, name, price FROM products")->fetchAll(PDO::FETCH_ASSOC);
        header('Content-Type: application/json');
        echo json_encode($products);
    }

    public function handleNewOrder()
    {
        $input = json_decode(file_get_contents('php://input'), true);
        $stmt = $this->pdo->prepare("INSERT INTO orders (product_id, quantity, price, created_at) VALUES (?, ?, ?, ?)");
        $stmt->execute([
            $input['product_id'],
            $input['quantity'],
            $input['price'],
            $input['date'] ?? date('Y-m-d H:i:s')
        ]);

        $stmt = $this->pdo->prepare("SELECT name FROM products WHERE id = ?");
        $stmt->execute([$input['product_id']]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        $orderData = [
            'product_name' => $product['name'] ?? 'Unknown',
            'quantity' => $input['quantity'],
            'price' => $input['price'],
            'date' => $input['date'] ?? date('Y-m-d H:i:s')
        ];
        header('Content-Type: application/json');
        echo json_encode(['status' => 'order saved']);
        $this->publishToWebSocket('new_order', $orderData);
    }

    public function getAnalytics()
    {
        $now = time();
        $oneMinAgo = date('Y-m-d H:i:s', $now - 60);

        $totalRevenue = $this->pdo->query("SELECT SUM(price * quantity) FROM orders")->fetchColumn() ?? 0;
        $topProducts = $this->pdo->query("SELECT p.name as product_name, SUM(o.quantity) as total_sold 
            FROM orders o JOIN products p ON o.product_id = p.id 
            GROUP BY o.product_id ORDER BY total_sold DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
        $recentRevenue = $this->pdo->query("SELECT SUM(price * quantity) FROM orders WHERE created_at >= '$oneMinAgo'")->fetchColumn() ?? 0;
        $recentCount = $this->pdo->query("SELECT COUNT(*) FROM orders WHERE created_at >= '$oneMinAgo'")->fetchColumn() ?? 0;

        header('Content-Type: application/json');
        echo json_encode([
            'total_revenue' => $totalRevenue,
            'top_products' => $topProducts,
            'revenue_last_minute' => $recentRevenue,
            'count_orders_last_minute' => $recentCount
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
        header('Content-Type: application/json');

        echo json_encode([
            'recommendations' => $prompt,
            'adjusted_prices' => $adjustedProducts,
        ], JSON_PRETTY_PRINT);
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
        header('Content-Type: application/json');

        echo json_encode(['status' => 'Database seeded with demo products and orders']);
    }

    private function getWeather(): ?float
    {
        $url = "https://api.openweathermap.org/data/2.5/weather?q=" . urlencode($this->city) . "&appid={$this->apikey}&units=metric";

        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
        ]);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error || !$response) {
            error_log("Weather API error for {$this->city}: " . $error);
            return null;
        }
        header('Content-Type: application/json');
        $data = json_decode($response, true);
        return $data['main']['temp'] ?? null;
    }

    private function getDynamicPrice(float $basePrice, float $temperature): float
    {
        if ($temperature > 30) {
            return round($basePrice * 1.1, 2);
        } elseif ($temperature < 10) {
            return round($basePrice * 0.9, 2);
        }
        return $basePrice;
    }

    public function getFrontPage()
    {
        header("Location: /public");
        exit;

    }

    public function getAPiRequest()
    {
        header("Location: /test-api.html");
        exit();

    }

}

?>