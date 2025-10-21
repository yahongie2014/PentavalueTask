<?php

array_map(fn($file) => require_once $file, array_merge(
    [__DIR__ . '/vendor/autoload.php'],
    glob(__DIR__ . '/Connectivity/*.php'),
    glob(__DIR__ . '/MindMap/*.php'),
    glob(__DIR__ . '/App/Controllers/*.php'),
    glob(__DIR__ . '/App/Repositories/*.php'),
    glob(__DIR__ . '/App/Services/*.php'),
    glob(__DIR__ . '/App/Helpers/*.php')
));

use Dotenv\Dotenv;
use App\Controllers\SalesController;
use MindMap\Router;

$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$staticFile = __DIR__ . '/public' . $requestUri;
if (file_exists($staticFile) && pathinfo($staticFile, PATHINFO_EXTENSION) === 'html') {
    readfile($staticFile);
    exit;
}

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$router = new Router();
$controller = SalesController::class;

$router->register('POST', '/create_order', [$controller, 'handleNewOrder']);
$router->register('GET', '/analytics', [$controller, 'getAnalytics']);
$router->register('GET', '/recommendations', [$controller, 'getAIRecommendations']);
$router->register('GET', '/seed', [$controller, 'seedDatabase']);
$router->register('GET', '/products', [$controller, 'getAllProducts']);
$router->register('GET', '/', [$controller, 'getFrontPage']);
$router->register('GET', '/test-api', [$controller, 'getAPiRequest']);

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];
$router->dispatch($method, $path);
?>