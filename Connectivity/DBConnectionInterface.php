<?php

namespace Connectivity;

use PDO;

interface DBConnectionInterface
{
    public function connect(): PDO;

    public function migrate(): void;
}

