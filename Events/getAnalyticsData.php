<?php

namespace Event;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../Connectivity/DB.php';
require_once __DIR__ . '/../Connectivity/MySQLConnection.php';

use Connectivity\DB;
use PDO;

DB::init();
$pdo = DB::pdo();

function getAnalyticsData(PDO $pdo): array
{
    $now = time();
    $oneMinAgo = date('Y-m-d H:i:s', $now - 60);

    return [
        'total_revenue' => $pdo->query("SELECT SUM(price * quantity) FROM orders")->fetchColumn() ?? 0,
        'top_products' => $pdo->query("
            SELECT p.name AS product_name, SUM(o.quantity) AS total_sold 
            FROM orders o 
            JOIN products p ON o.product_id = p.id 
            GROUP BY o.product_id 
            ORDER BY total_sold DESC 
            LIMIT 5
        ")->fetchAll(PDO::FETCH_ASSOC),
        'revenue_last_minute' => $pdo->query("SELECT SUM(price * quantity) FROM orders WHERE created_at >= '$oneMinAgo'")->fetchColumn() ?? 0,
        'orders_last_minute' => $pdo->query("SELECT COUNT(*) FROM orders WHERE created_at >= '$oneMinAgo'")->fetchColumn() ?? 0,
    ];
}

function publishToWebSocket(string $event, array $data): void
{
    $payload = json_encode([
        'event' => $event,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

    $socket = @fsockopen("127.0.0.1", 8080, $errno, $errstr, 1);
    if ($socket) {
        fwrite($socket, $payload);
        fclose($socket);
    }
}

echo "🟢 Analytics Worker Started - Refresh every 60 seconds\n";

while (true) {
    $stats = getAnalyticsData($pdo);
    publishToWebSocket('analytics_updated', $stats);
    echo "🔄 Sent analytics at " . date('H:i:s') . "\n";
    sleep(60);
}
