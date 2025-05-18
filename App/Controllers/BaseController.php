<?php

namespace App\Controllers;

use PDO;
use Connectivity\DB;
use App\Helpers\ResponseHelper;

abstract class BaseController
{
    protected PDO $pdo;

    public function __construct(?DB $db = null)
    {
        $this->pdo = $db ? $db->connect() : (new DB())->connect();
    }

    protected function json($data, int $status = 200)
    {
        return ResponseHelper::json($data, $status);
    }

    protected function input(): ?array
    {
        $input = json_decode(file_get_contents("php://input"), true);
        return json_last_error() === JSON_ERROR_NONE ? $input : null;
    }

    protected function notFound(string $message = 'Not Found')
    {
        return $this->json(['error' => $message], 404);
    }

    protected function serverError(string $message = 'Internal Server Error')
    {
        return $this->json(['error' => $message], 500);
    }

    protected function redirect(string $url)
    {
        header("Location: $url");
        exit;
    }

    protected function only(string $method)
    {
        if ($_SERVER['REQUEST_METHOD'] !== strtoupper($method)) {
            http_response_code(405);
            exit($this->json(['error' => 'Method Not Allowed'], 405));
        }
    }

}
