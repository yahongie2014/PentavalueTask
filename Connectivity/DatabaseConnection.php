<?php

namespace Connectivity;

use PDO;

interface DatabaseConnection
{
    public function connect(): PDO;

    public function migrate(): void;
}

