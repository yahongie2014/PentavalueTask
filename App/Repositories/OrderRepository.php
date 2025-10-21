<?php

namespace App\Repositories;

use PDO;
use Connectivity\DBConnectionInterface;

class OrderRepository
{
    protected PDO $db;

    public function __construct(DBConnectionInterface $connection)
    {
        $this->db = $connection->connect();
    }

    public function save(array $data): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO orders (product_id, quantity, price, created_at) VALUES (?, ?, ?, ?)"
        );

        $stmt->execute([
            $data['product_id'],
            $data['quantity'],
            $data['price'],
            $data['date']
        ]);
    }

    public function getTotalRevenue(): float
    {
        $stmt = $this->db->query("SELECT SUM(price * quantity) FROM orders");
        return (float)($stmt->fetchColumn() ?? 0);
    }

    public function getTopProducts(int $limit = 5): array
    {
        $limit = max(1, (int)$limit);

        $sql = "
        SELECT 
            p.name AS product_name, 
            SUM(o.quantity) AS total_sold
        FROM orders o
        JOIN products p ON o.product_id = p.id
        GROUP BY o.product_id, p.name
        ORDER BY total_sold DESC
        LIMIT {$limit}
    ";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getRecentRevenue(string $since): float
    {
        $stmt = $this->db->prepare(
            "SELECT SUM(price * quantity) FROM orders WHERE created_at >= ?"
        );

        $stmt->execute([$since]);
        return (float)($stmt->fetchColumn() ?? 0);
    }

    public function getRecentCount(string $since): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM orders WHERE created_at >= ?"
        );

        $stmt->execute([$since]);
        return (int)($stmt->fetchColumn() ?? 0);
    }

    public function getTopOrderLastMinute(int $minQty = 2): ?array
    {
        // Database-agnostic approach using PHP time calculation
        $oneMinuteAgo = date('Y-m-d H:i:s', strtotime('-1 minute'));

        $stmt = $this->db->prepare("
            SELECT p.name AS product_name, SUM(o.quantity) AS total_sold
            FROM orders o
            JOIN products p ON o.product_id = p.id
            WHERE o.created_at >= ?
            GROUP BY o.product_id, p.name
            HAVING total_sold > ?
            ORDER BY total_sold DESC
            LIMIT 1
        ");

        $stmt->execute([$oneMinuteAgo, $minQty]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    public function getRecentOrders(int $limit = 20, int $minutes = 60): array
    {
        $since = date('Y-m-d H:i:s', strtotime("-{$minutes} minutes"));

        $stmt = $this->db->prepare("
            SELECT p.name AS product_name, SUM(o.quantity) AS total_ordered
            FROM orders o
            JOIN products p ON o.product_id = p.id
            WHERE o.created_at >= ?
            GROUP BY o.product_id, p.name
            ORDER BY total_ordered DESC
            LIMIT ?
        ");

        $stmt->execute([$since, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}