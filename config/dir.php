<?php

/**
 * Directory paths configuration
 * Resolves paths relative to project root if they start with /
 */
$project_root = dirname(__DIR__);

$resolvePath = function ($path) use ($project_root) {
    if (empty($path)) {
        return null;
    }
    
    // If path starts with / but doesn't exist as absolute, treat as relative to project root
    if ($path[0] === '/' && !is_dir($path)) {
        return rtrim($project_root . $path, '/') . '/';
    }
    
    // If relative path, resolve from project root
    if ($path[0] !== '/') {
        return rtrim($project_root . '/' . $path, '/') . '/';
    }
    
    // Absolute path that exists
    return rtrim($path, '/') . '/';
};

$PATH = [
    'css' => $resolvePath(getenv('CSS_DIR') ?: 'public/assets/css/'),
    'js' => $resolvePath(getenv('JS_DIR') ?: 'public/assets/js/'),
    'img' => $resolvePath(getenv('IMG_DIR') ?: 'public/assets/img/'),
    'view' => $resolvePath(getenv('VIEW_DIR') ?: 'app/views/'),
    'cache' => $resolvePath(getenv('CACHE_DIR') ?: 'storage/cache/'),
    'upload' => $resolvePath(getenv('UPLOAD_DIR') ?: 'storage/userfiles/'),
    'error_log' => $resolvePath(getenv('ERROR_LOG_DIR') ?: 'storage/logs/error/'),
    'event_log' => $resolvePath(getenv('EVENT_LOG_DIR') ?: 'storage/logs/event/')
];

define('PATH', $PATH);
