<?php

namespace Connectivity;

use PDO;

class DB
{
    public static PDO $connection;

    public static function init(): void
    {
        $conn = new MySQLConnection();
        self::$connection = $conn->connect();
        $conn->migrate();
    }

    public static function connect(): PDO
    {
        if (self::$connection === null) {
            $conn = new MySQLConnection();
            self::$connection = $conn->connect();
             $conn->migrate();
        }

        return self::$connection;
    }


    public static function pdo(): PDO
    {
        return self::$connection;
    }
}