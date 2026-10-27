<?php
/**
 * KARCEM Textile ERP - Master Entrypoint & Front Controller
 * File: index.php
 */

declare(strict_types=1);

defined('BASE_PATH') || define('BASE_PATH', __DIR__);

// PSR-4 Autoloader for Core namespace
spl_autoload_register(function (string $class) {
    if (str_starts_with($class, 'Core\\')) {
        $relative = substr($class, 5);
        $file = __DIR__ . '/core/' . str_replace('\\', '/', $relative) . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    }
});

// Load App & Env configurations
require_once __DIR__ . '/config/app.php';

use Core\Routing\Router;

// Dispatch Request
Router::init();
Router::dispatch();
