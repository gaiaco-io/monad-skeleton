<?php

/**
 * Bootstrap configuration
 * Application-wide settings loaded at startup
 */
$APP = [
    'name' => getenv('APP_NAME') ?: 'No-Name',
    'timezone' => getenv('TIMEZONE') ?: 'UTC',
    'base_url' => getenv('BASE_URL') ?: '',
    'session_timeout' => (int)(getenv('SESSION_TIMEOUT') ?: 1800),
    'env_mode' => getenv('ENV_MODE') ?: (getenv('ENV_PRODUCTION') === '0' ? 'development' : 'production')
];

// Set timezone
date_default_timezone_set($APP['timezone']);

// Set environment mode constant for backward compatibility
if (!defined('ENV_MODE')) {
    define('ENV_MODE', $APP['env_mode']);
}

define('APP', $APP);
