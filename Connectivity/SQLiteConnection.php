<?php

namespace Connectivity;

use PDO;
use PDOException;

class SQLiteConnection implements DBConnectionInterface
{
    private string $path;

    public function __construct(?string $path = null)
    {
        $this->path = $path ?? ($_ENV['DB_PATH'] ?? __DIR__ . '/../sales.sqlite');
    }

    public function connect(): PDO
    {
        try {
            $pdo = new PDO("sqlite:" . $this->path);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $pdo;
        } catch (PDOException $e) {
            throw new \RuntimeException("SQLite connection failed: " . $e->getMessage());
        }
    }

    public function migrate(): void
    {
        $pdo = $this->connect();

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS products (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                price REAL NOT NULL
            );
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS orders (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                product_id INTEGER,
                quantity INTEGER,
                price REAL,
                created_at TEXT,
                FOREIGN KEY(product_id) REFERENCES products(id)
            );
        ");
    }

}
