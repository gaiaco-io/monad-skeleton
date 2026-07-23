<?php
/*
 * This is the entry point of the application. The following operations are performed:
 * 1. Load Composer autoloader.
 * 2. Load the necessary configuration files.
 * 3. Load application classes
 * 4. Load the router (either routes/web.php or routes/api.php).
 * 5. Load the UI
 */

use Dotenv\Dotenv;
use Dotenv\Repository\Adapter\PutenvAdapter;
use Dotenv\Repository\RepositoryBuilder;

// Auto-load packages from Composer
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

// Ensure .env values populate getenv()/putenv()
$dotenvRepository = RepositoryBuilder::createWithDefaultAdapters()
    ->addAdapter(PutenvAdapter::class)
    ->immutable()
    ->make();

Dotenv::create($dotenvRepository, dirname(__DIR__))->load();

// Bootstrap the application environment.
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/dir.php';
require_once __DIR__ . '/../config/locale.php';
require_once __DIR__ . '/../config/llm.php';
require_once __DIR__ . '/../config/mail.php';

// Autoload application classes
try {
    spl_autoload_register(function ($named_object) {
        $named_object = str_replace('\\', '/', $named_object);
        $class_filename = basename($named_object);

        // Extract namespace parts for Clarity classes
        $namespace_parts = explode('/', $named_object);
        $clarity_path = '';

        if (count($namespace_parts) >= 3 && $namespace_parts[0] === 'Gaia' && $namespace_parts[1] === 'Clarity') {
            $clarity_path = __DIR__ . '/../Clarity/' . implode('/', array_slice($namespace_parts, 2)) . '.php';
        }

        $paths = [
            __DIR__ . '/../app/models/' . $class_filename . '.php',
            __DIR__ . '/../app/controllers/' . $class_filename . '.php',
            __DIR__ . '/../app/services/' . $class_filename . '.php',

            !empty($clarity_path) ? $clarity_path : __DIR__ . '/../Clarity/' . $class_filename . '.php',
        ];

        $file_found = false;

        foreach ($paths as $path) {
            if (is_file($path)) {
                require_once $path;
                $file_found = true;
                break;
            }
        }

        if (!$file_found) {
            die('Named object not found: ' . (!empty($named_object) ? $named_object : 'Unknown object'));
        }
    });

    // Load routes per app
    require_once __DIR__ . '/../app/routes/web.php';
    require_once __DIR__ . '/../app/routes/api.php';
    require_once __DIR__ . '/../app/routes/cli.php';

    $request_uri = $_SERVER['REQUEST_URI'] ?? '/';

    if (\Gaia\Clarity\Services\Route::hasMatched()) {
        try {
            $view = new \Gaia\Clarity\Services\View();

            if (\Gaia\Clarity\Services\Session::hasSession()) {
                $session = new \Gaia\Clarity\Services\Session();
                $view->set('full_name', $session->read('full_name'));
            }

            $csrf = new \Gaia\Clarity\Services\CsrfService();
            $view->set('csrf_token', $csrf->getToken());
            $view->set('csrf_field', $csrf->getFieldName());
        } catch (\Throwable $e) {
            // Silently fail - views will use defaults
        }

        // Include layout which will call View::render() to inject content
        $main_path = __DIR__ . '/../app/views/Layouts/main.php';
        
        if (file_exists($main_path)) {
            require_once $main_path;
        }
    
        exit;
    } else {
        \Gaia\Clarity\Services\View::prepare('/Errors/404')->render();
    }
} catch (Throwable $throwable) {
    \Gaia\Clarity\Services\Mediator::handleException($throwable);
}
