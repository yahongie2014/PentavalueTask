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
        $this->handleCors();
    }

    /**
     * Handle CORS preflight and headers
     */
    protected function handleCors(): void
    {
        $allowedOrigins = explode(',', $_ENV['CORS_ALLOWED_ORIGINS'] ?? '*');
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

        if (in_array('*', $allowedOrigins) || in_array($origin, $allowedOrigins)) {
            header("Access-Control-Allow-Origin: " . ($origin ?: '*'));
            header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
            header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
            header("Access-Control-Allow-Credentials: true");
            header("Access-Control-Max-Age: 3600");
        }

        // Handle preflight requests
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
            exit;
        }
    }

    /**
     * Return JSON response
     */
    protected function json($data, int $status = 200)
    {
        return ResponseHelper::json($data, $status);
    }

    /**
     * Get JSON input from request body
     */
    protected function input(): ?array
    {
        $input = json_decode(file_get_contents("php://input"), true);
        return json_last_error() === JSON_ERROR_NONE ? $input : null;
    }

    /**
     * Return 404 Not Found response
     */
    protected function notFound(string $message = 'Not Found')
    {
        return $this->json(['error' => $message], 404);
    }

    /**
     * Return 500 Server Error response
     */
    protected function serverError(string $message = 'Internal Server Error', ?\Throwable $e = null)
    {
        // Determine debug mode reliably (supports "true", "1", boolean, etc.)
        $envDebug = getenv('APP_DEBUG') !== false ? getenv('APP_DEBUG') : ($_ENV['APP_DEBUG'] ?? false);
        $debug = filter_var($envDebug, FILTER_VALIDATE_BOOLEAN);

        // Build server-side log message (always log)
        $logMsg = '[Server Error] ' . $message;
        if ($e instanceof \Throwable) {
            $logMsg .= ' | Exception: ' . $e->getMessage()
                . ' in ' . $e->getFile() . ':' . $e->getLine()
                . PHP_EOL . $e->getTraceAsString();
        } else {
            // include a short backtrace when no exception provided and debug on (helpful for dev)
            if ($debug) {
                $bt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5);
                $logMsg .= PHP_EOL . 'Backtrace: ' . print_r($bt, true);
            }
        }
        error_log($logMsg);

        // Prepare JSON response
        $response = [
            'error' => $message,
            'timestamp' => date('c'),
            'status' => 500,
        ];

        // Include debug information only when APP_DEBUG is true
        if ($debug) {
            if ($e instanceof \Throwable) {
                $response['exception'] = [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => explode(PHP_EOL, $e->getTraceAsString()), // array of lines
                ];
            } else {
                $response['debug_backtrace'] = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);
            }
        }

        return $this->json($response, 500);
    }

    /**
     * Redirect to URL
     */
    protected function redirect(string $url): void
    {
        header("Location: $url");
        exit;
    }

    /**
     * Ensure request uses specific HTTP method
     */
    protected function only(string $method): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== strtoupper($method)) {
            http_response_code(405);
            header('Allow: ' . strtoupper($method));
            exit($this->json(['error' => 'Method Not Allowed'], 405));
        }
    }

    /**
     * Make cURL request with error handling
     */
    protected function curlRequest(string $url, array $options = []): ?string
    {
        $ch = curl_init();

        $defaultOptions = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ];

        curl_setopt_array($ch, $options + $defaultOptions);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if ($error) {
            error_log("cURL error for {$url}: " . $error);
            return null;
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            error_log("cURL HTTP error {$httpCode} for {$url}");
            return null;
        }

        return $response ?: null;
    }

    /**
     * Validate required fields in request
     */
    protected function validateRequired(array $data, array $required): array
    {
        $missing = [];

        foreach ($required as $field) {
            if (!isset($data[$field]) || $data[$field] === '' || $data[$field] === null) {
                $missing[] = $field;
            }
        }

        return $missing;
    }

    /**
     * Sanitize string input
     */
    protected function sanitize(string $input): string
    {
        return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Check rate limit
     */
    protected function checkRateLimit(string $identifier, int $maxRequests = 100, int $windowSeconds = 3600): bool
    {
        $rateLimit = (int) ($_ENV['API_RATE_LIMIT'] ?? 100);

        // In production, use Redis for distributed rate limiting
        // For now, simple file-based implementation

        return true; // Placeholder - implement full rate limiting with Redis
    }
}