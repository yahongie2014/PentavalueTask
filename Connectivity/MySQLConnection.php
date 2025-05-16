<?php

namespace Connectivity;

use PDO;
use PDOException;

class MySQLConnection implements DBConnectionInterface
{
    private string $host;
    private string $port;
    private string $dbname;
    private string $username;
    private string $password;
    private PDO $pdo;

    public function __construct()
    {
        $this->host = $_ENV['DB_HOST'] ?? '';
        $this->dbname = $_ENV['DB_NAME'] ?? '';
        $this->username = $_ENV['DB_USERNAME'] ?? '';
        $this->password = $_ENV['DB_PASSWORD'] ?? '';
        $this->port = $_ENV['DB_PORT'] ?? '';

    }

    public function connect(): PDO
    {
        try {
            $this->pdo = new PDO(
                "mysql:host={$this->host};port={$this->port};dbname={$this->dbname};charset=utf8mb4",
                $this->username,
                $this->password
            );
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $this->pdo;
        } catch (PDOException $e) {
            die(json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]));
        }
    }

    public function migrate(): void
    {
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS products (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            price FLOAT NOT NULL
        )");

        $this->pdo->exec("CREATE TABLE IF NOT EXISTS orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            product_id INT,
            quantity INT,
            price FLOAT,
            created_at DATETIME,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE ON UPDATE CASCADE
        )");
    }
}