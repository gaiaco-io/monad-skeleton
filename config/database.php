<?php

/**
 * Database configuration
 * Provides defaults for missing environment variables and context-aware database selection
 */

/**
 * Helper to read environment values with fallbacks
 */
$env = static function (string $key, mixed $default = null): mixed {
    $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
    if ($value === false || $value === null) {
        return $default;
    }
    if (is_string($value)) {
        $value = trim($value);
    }
    return $value === '' ? $default : $value;
};

/**
 * Get database configuration based on context.
 * 
 * Supported contexts:
 * - 'app' or null: App-specific database based on request URI (default)
 * - 'kerberos' or 'session': Kerberos database (for sessions, auth)
 * - 'shared': Shared Core database (for CRM data)
 * - 'subscription': Subscription database (for plan entitlements)
 * 
 * @param string|null $context The database context
 * @return array Database configuration array
 */
function getDBConfig(?string $context = null): array
{
    $env = static function (string $key, mixed $default = null): mixed {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
        if ($value === false || $value === null) {
            return $default;
        }
        if (is_string($value)) {
            $value = trim($value);
        }
        return $value === '' ? $default : $value;
    };

    $database_name = '';
    $host_key = 'DB_HOST';
    $port_key = 'DB_PORT';
    $username_key = 'DB_USERNAME';
    $password_key = 'DB_PASSWORD';
    $charset_key = 'DB_CHARSET';
    $driver_key = 'DB_DRIVER';

    // Handle specific contexts
    if ($context === 'kerberos' || $context === 'session') {
        $database_name = $env('KERBEROS_DB_NAME', $env('DB_DATABASE', ''));
        $host_key = 'KERBEROS_DB_HOST';
        $port_key = 'KERBEROS_DB_PORT';
        $username_key = 'KERBEROS_DB_USERNAME';
        $password_key = 'KERBEROS_DB_PASSWORD';
        $charset_key = 'KERBEROS_DB_CHARSET';
        $driver_key = 'KERBEROS_DB_DRIVER';
    } elseif ($context === 'shared') {
        $database_name = $env('SHARED_DB_NAME', $env('DB_DATABASE', ''));
        $host_key = 'SHARED_DB_HOST';
        $port_key = 'SHARED_DB_PORT';
        $username_key = 'SHARED_DB_USERNAME';
        $password_key = 'SHARED_DB_PASSWORD';
        $charset_key = 'SHARED_DB_CHARSET';
        $driver_key = 'SHARED_DB_DRIVER';
    } elseif ($context === 'hello') {
        $database_name = $env('HELLO_DB_NAME', $env('DB_DATABASE', ''));
        $host_key = 'HELLO_DB_HOST';
        $port_key = 'HELLO_DB_PORT';
        $username_key = 'HELLO_DB_USERNAME';
        $password_key = 'HELLO_DB_PASSWORD';
        $charset_key = 'HELLO_DB_CHARSET';
        $driver_key = 'HELLO_DB_DRIVER';
    } elseif ($context === 'flow') {
        $database_name = $env('FLOW_DB_NAME', $env('DB_DATABASE', ''));
        $host_key = 'FLOW_DB_HOST';
        $port_key = 'FLOW_DB_PORT';
        $username_key = 'FLOW_DB_USERNAME';
        $password_key = 'FLOW_DB_PASSWORD';
        $charset_key = 'FLOW_DB_CHARSET';
        $driver_key = 'FLOW_DB_DRIVER';
    } elseif ($context === 'subscription') {
        $database_name = $env('SUBSCRIPTION_DB_NAME', $env('DB_DATABASE', ''));
        $host_key = 'SUBSCRIPTION_DB_HOST';
        $port_key = 'SUBSCRIPTION_DB_PORT';
        $username_key = 'SUBSCRIPTION_DB_USERNAME';
        $password_key = 'SUBSCRIPTION_DB_PASSWORD';
        $charset_key = 'SUBSCRIPTION_DB_CHARSET';
        $driver_key = 'SUBSCRIPTION_DB_DRIVER';
    } elseif ($context === 'session') {
        $database_name = $env('SESSION_DB_NAME', $env('DB_DATABASE', ''));
        $host_key = 'SESSION_DB_HOST';
        $port_key = 'SESSION_DB_PORT';
        $username_key = 'SESSION_DB_USERNAME';
        $password_key = 'SESSION_DB_PASSWORD';
        $charset_key = 'SESSION_DB_CHARSET';
        $driver_key = 'SESSION_DB_DRIVER';
    } else {
        // Default: app-specific database based on request URI
        $request_uri = $_SERVER['REQUEST_URI'] ?? '';
        $database_name = $env('DB_DATABASE', '');

        // Check for app-specific database names based on request URI
        if (strpos($request_uri, '/hello') === 0) {
            $database_name = $env('HELLO_DB_NAME', $database_name);
        } elseif (strpos($request_uri, '/flow') === 0) {
            $database_name = $env('FLOW_DB_NAME', $database_name);
        } elseif (strpos($request_uri, '/subscription') === 0) {
            $database_name = $env('SUBSCRIPTION_DB_NAME', $database_name);
        } elseif (strpos($request_uri, '/auth') === 0) {
            $database_name = $env('KERBEROS_DB_NAME', $database_name);
        }
    }

    return [
        'driver' => $env($driver_key, $env('DB_DRIVER', 'mysql')),
        'host' => $env($host_key, $env('DB_HOST', '127.0.0.1')),
        'port' => $env($port_key, $env('DB_PORT', '3306')),
        'database' => $database_name,
        'username' => $env($username_key, $env('DB_USERNAME', 'root')),
        'password' => $env($password_key, $env('DB_PASSWORD', '')),
        'charset' => $env($charset_key, $env('DB_CHARSET', 'utf8mb4'))
    ];
}

// Set default DB constant for backward compatibility (app context)
$DB = getDBConfig('app');

define('DB', $DB);
