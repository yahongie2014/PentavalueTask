<?php

namespace Connectivity;

use PDO;

class DB implements DBConnectionInterface
{
    public static PDO $connection;

    public static function init(): void
    {
        $conn = new MySQLConnection();
        self::$connection = $conn->connect();
        $conn->migrate();
    }

    public function connect(): PDO
    {
        if (!isset(self::$connection)) {
            $conn = new MySQLConnection();
            self::$connection = $conn->connect();
        }

        return self::$connection;
    }

    public function migrate(): void
    {
        $conn = new MySQLConnection();
        $conn->migrate();
    }
}
