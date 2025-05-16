<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/Connectivity/DB.php';
require_once __DIR__ . '/Connectivity/DBConnectionInterface.php';
require_once __DIR__ . '/Connectivity/MySQLConnection.php';
require_once __DIR__ . '/MindMap/Router.php';
require_once __DIR__ . '/App/SalesController.php';
require_once __DIR__ . '/Helpers/ResponseHelper.php';

use Dotenv\Dotenv;
use Connectivity\DB;
use Connectivity\MySQLConnection;
use App\SalesController;
use MindMap\Router;


$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$staticFile = __DIR__ . '/public' . $requestUri;
if (file_exists($staticFile) && pathinfo($staticFile, PATHINFO_EXTENSION) === 'html') {
    readfile($staticFile);
    exit;
}

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

DB::init(new MySQLConnection());
$controller = new SalesController(DB::class);
$router = new Router();

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