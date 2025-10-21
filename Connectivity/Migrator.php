<?php

namespace Connectivity;

use PDO;

/**
 * Simple database migration system
 */
class Migrator
{
    private PDO $pdo;
    private string $migrationsTable = 'migrations';

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->createMigrationsTable();
    }

    /**
     * Create migrations tracking table
     */
    private function createMigrationsTable(): void
    {
        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        if ($driver === 'mysql') {
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS {$this->migrationsTable} (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    migration VARCHAR(255) NOT NULL UNIQUE,
                    executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_migration (migration)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        } else {
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS {$this->migrationsTable} (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    migration TEXT NOT NULL UNIQUE,
                    executed_at TEXT DEFAULT CURRENT_TIMESTAMP
                )
            ");
            $this->pdo->exec("
                CREATE INDEX IF NOT EXISTS idx_migration 
                ON {$this->migrationsTable}(migration)
            ");
        }
    }

    /**
     * Run all pending migrations
     */
    public function migrate(): void
    {
        $migrations = $this->getPendingMigrations();

        foreach ($migrations as $migration) {
            echo "Running migration: {$migration['name']}\n";

            try {
                $this->pdo->beginTransaction();

                // Execute migration SQL
                call_user_func($migration['up'], $this->pdo);

                // Record migration
                $stmt = $this->pdo->prepare("
                    INSERT INTO {$this->migrationsTable} (migration) VALUES (?)
                ");
                $stmt->execute([$migration['name']]);

                $this->pdo->commit();
                echo "✓ Migration completed: {$migration['name']}\n";
            } catch (\Exception $e) {
                $this->pdo->rollBack();
                echo "✗ Migration failed: {$migration['name']}\n";
                echo "Error: {$e->getMessage()}\n";
                throw $e;
            }
        }

        if (empty($migrations)) {
            echo "No pending migrations.\n";
        }
    }

    /**
     * Get list of pending migrations
     */
    private function getPendingMigrations(): array
    {
        $executed = $this->pdo
            ->query("SELECT migration FROM {$this->migrationsTable}")
            ->fetchAll(PDO::FETCH_COLUMN);

        $available = $this->getAvailableMigrations();

        return array_filter($available, function ($migration) use ($executed) {
            return !in_array($migration['name'], $executed);
        });
    }

    /**
     * Define available migrations
     */
    private function getAvailableMigrations(): array
    {
        return [
            [
                'name' => '001_create_products_table',
                'up' => function (PDO $pdo) {
                    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

                    if ($driver === 'mysql') {
                        $pdo->exec("
                            CREATE TABLE IF NOT EXISTS products (
                                id INT AUTO_INCREMENT PRIMARY KEY,
                                name VARCHAR(255) NOT NULL,
                                price DECIMAL(10,2) NOT NULL,
                                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                                INDEX idx_name (name)
                            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
                        ");
                    } else {
                        $pdo->exec("
                            CREATE TABLE IF NOT EXISTS products (
                                id INTEGER PRIMARY KEY AUTOINCREMENT,
                                name TEXT NOT NULL,
                                price REAL NOT NULL,
                                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                                updated_at TEXT DEFAULT CURRENT_TIMESTAMP
                            )
                        ");
                        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_name ON products(name)");
                    }
                }
            ],
            [
                'name' => '002_create_orders_table',
                'up' => function (PDO $pdo) {
                    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

                    if ($driver === 'mysql') {
                        $pdo->exec("
                            CREATE TABLE IF NOT EXISTS orders (
                                id INT AUTO_INCREMENT PRIMARY KEY,
                                product_id INT NOT NULL,
                                quantity INT NOT NULL,
                                price DECIMAL(10,2) NOT NULL,
                                created_at DATETIME NOT NULL,
                                FOREIGN KEY (product_id) REFERENCES products(id) 
                                    ON DELETE CASCADE ON UPDATE CASCADE,
                                INDEX idx_created_at (created_at),
                                INDEX idx_product_id (product_id)
                            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
                        ");
                    } else {
                        $pdo->exec("
                            CREATE TABLE IF NOT EXISTS orders (
                                id INTEGER PRIMARY KEY AUTOINCREMENT,
                                product_id INTEGER NOT NULL,
                                quantity INTEGER NOT NULL,
                                price REAL NOT NULL,
                                created_at TEXT NOT NULL,
                                FOREIGN KEY(product_id) REFERENCES products(id)
                                    ON DELETE CASCADE ON UPDATE CASCADE
                            )
                        ");
                        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_created_at ON orders(created_at)");
                        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_product_id ON orders(product_id)");
                    }
                }
            ],
            [
                'name' => '003_add_indexes_for_performance',
                'up' => function (PDO $pdo) {
                    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

                    if ($driver === 'mysql') {
                        // Add composite index for common query pattern
                        $pdo->exec("
                            CREATE INDEX IF NOT EXISTS idx_orders_created_product 
                            ON orders(created_at, product_id)
                        ");
                    } else {
                        $pdo->exec("
                            CREATE INDEX IF NOT EXISTS idx_orders_created_product 
                            ON orders(created_at, product_id)
                        ");
                    }
                }
            ]
        ];
    }

    /**
     * Rollback last migration
     */
    public function rollback(): void
    {
        $last = $this->pdo
            ->query("SELECT migration FROM {$this->migrationsTable} ORDER BY id DESC LIMIT 1")
            ->fetch(PDO::FETCH_COLUMN);

        if (!$last) {
            echo "No migrations to rollback.\n";
            return;
        }

        echo "Rolling back migration: {$last}\n";

        try {
            $this->pdo->beginTransaction();

            // Delete migration record
            $stmt = $this->pdo->prepare("DELETE FROM {$this->migrationsTable} WHERE migration = ?");
            $stmt->execute([$last]);

            $this->pdo->commit();
            echo "✓ Rollback completed: {$last}\n";
            echo "Note: You may need to manually drop tables/indexes.\n";
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            echo "✗ Rollback failed: {$last}\n";
            echo "Error: {$e->getMessage()}\n";
            throw $e;
        }
    }

    /**
     * Get migration status
     */
    public function status(): array
    {
        $executed = $this->pdo
            ->query("SELECT migration, executed_at FROM {$this->migrationsTable} ORDER BY id")
            ->fetchAll(PDO::FETCH_ASSOC);

        $available = $this->getAvailableMigrations();
        $executedNames = array_column($executed, 'migration');

        $status = [];
        foreach ($available as $migration) {
            $status[] = [
                'name' => $migration['name'],
                'status' => in_array($migration['name'], $executedNames) ? 'executed' : 'pending'
            ];
        }

        return $status;
    }
}