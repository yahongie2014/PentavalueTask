<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/Connectivity/DB.php';
require_once __DIR__ . '/Connectivity/DatabaseConnection.php';
require_once __DIR__ . '/Connectivity/MySQLConnection.php';
require_once __DIR__ . '/MindMap/Router.php';
require_once __DIR__ . '/App/SalesController.php';

use Dotenv\Dotenv;
use Connectivity\DB;
use App\SalesController;
use MindMap\Router;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

DB::init();
$app = new SalesController(DB::class);
$router = new Router();

$router->register('POST', '/create_order', fn() => $app->handleNewOrder());
$router->register('GET', '/analytics', fn() => $app->getAnalytics());
$router->register('GET', '/recommendations', fn() => $app->getAIRecommendations());
$router->register('GET', '/seed', fn() => $app->seedDatabase());
$router->register('GET', '/products', fn() => $app->getAllProducts());
$router->register('GET', '/', fn() => $app->getFrontPage());

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];
$router->dispatch($method, $path);
