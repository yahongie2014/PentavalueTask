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

    protected function input()
    {
        $input = json_decode(file_get_contents("php://input"), true);
        return json_last_error() === JSON_ERROR_NONE ? $input : null;
    }

    protected function notFound($message = 'Not Found')
    {
        return $this->json(['error' => $message], 404);
    }

    protected function serverError($message = 'Internal Server Error')
    {
        return $this->json(['error' => $message], 500);
    }

    protected function redirect($url)
    {
        header("Location: $url");
        exit;
    }

    protected function only($method)
    {
        if ($_SERVER['REQUEST_METHOD'] !== strtoupper($method)) {
            http_response_code(405);
            exit($this->json(['error' => 'Method Not Allowed'], 405));
        }
    }

    protected function curlRequest(string $url, array $options = [])
    {
        $ch = curl_init();

        $defaultOptions = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
        ];

        curl_setopt_array($ch, $options + $defaultOptions);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error || !$response) {
            error_log("cURL error: " . $error);
            return null;
        }

        return $response;
    }


}
