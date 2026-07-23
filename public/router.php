<?php
// router.php - Router script for PHP built-in server

$requestUri = $_SERVER['REQUEST_URI'];
$requestPath = parse_url($requestUri, PHP_URL_PATH);

// Remove query string for file existence check
$filePath = __DIR__ . $requestPath;

// If the requested file exists and is not a directory, serve it directly
if ($requestPath !== '/' && file_exists($filePath) && !is_dir($filePath)) {
    return false; // Let PHP serve the file directly
}

// Otherwise, route everything through index.php
$_SERVER['SCRIPT_NAME'] = '/index.php';
require __DIR__ . '/index.php';

