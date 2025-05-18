<?php
//OrderModel

namespace App\Repositories;

use PDO;
use Connectivity\DBConnectionInterface;

class OrderRepository
{

    protected $db;

    public function __construct(DBConnectionInterface $connection)
    {
        $this->db = $connection->connect();
    }

    public function save(array $data): void
    {
        $stmt = $this->db->prepare("INSERT INTO orders (product_id, quantity, price, created_at) VALUES (?, ?, ?, ?)");
        $stmt->execute([
            $data['product_id'],
            $data['quantity'],
            $data['price'],
            $data['date']
        ]);
    }

    public function getTotalRevenue(): float
    {
        return $this->db->query("SELECT SUM(price * quantity) FROM orders")->fetchColumn() ?? 0;
    }

    public function getTopProducts(int $limit = 5): array
    {
        return $this->db->query("
            SELECT p.name AS product_name, SUM(o.quantity) AS total_sold
            FROM orders o
            JOIN products p ON o.product_id = p.id
            GROUP BY o.product_id
            ORDER BY total_sold DESC
            LIMIT $limit
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getRecentRevenue(string $since): float
    {
        $stmt = $this->db->prepare("SELECT SUM(price * quantity) FROM orders WHERE created_at >= ?");
        $stmt->execute([$since]);
        return $stmt->fetchColumn() ?? 0;
    }

    public function getRecentCount(string $since): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM orders WHERE created_at >= ?");
        $stmt->execute([$since]);
        return $stmt->fetchColumn() ?? 0;
    }

    public function getTopOrderLastMinute(int $minQty = 2): ?array
    {
        $stmt = $this->db->prepare("
            SELECT p.name AS product_name, SUM(o.quantity) AS total_sold
            FROM orders o
            JOIN products p ON o.product_id = p.id
            WHERE o.created_at >= NOW() - INTERVAL 1 MINUTE
            GROUP BY o.product_id
            HAVING total_sold > ?
            ORDER BY total_sold DESC
            LIMIT 1
        ");
        $stmt->execute([$minQty]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }


}