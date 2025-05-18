<?php

namespace Connectivity;

use PDO;

class DB implements DBConnectionInterface
{
    public static PDO $connection;
    private DBConnectionInterface $connector;

    public function __construct()
    {
        $driver = $_ENV['DB_DRIVER'] ?? 'mysql';

        if ($driver === 'sqlite') {
            $this->connector = new SQLiteConnection($_ENV['DB_PATH'] ?? null);
        } else {
            $this->connector = new MySQLConnection();
        }
    }

    public static function init(): void
    {
        $db = new self();
        self::$connection = $db->connector->connect();

        if (method_exists($db->connector, 'migrate')) {
            $db->connector->migrate();
        }
    }

    public function connect(): PDO
    {
        if (!isset(self::$connection)) {
            self::$connection = $this->connector->connect();
        }

        return self::$connection;
    }

    public function migrate(): void
    {
        if (method_exists($this->connector, 'migrate')) {
            $this->connector->migrate();
        }
    }
}
