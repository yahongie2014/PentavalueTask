<?php
$requested = $_SERVER['REQUEST_URI'];
$file = __DIR__ . '/public' . $requested;

if (file_exists($file) && !is_dir($file)) {
    return false;
}
require_once __DIR__ . '/public/index.php';
