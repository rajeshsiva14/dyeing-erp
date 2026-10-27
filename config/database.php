<?php
use Core\Support\Env;

require_once __DIR__ . '/../core/Support/Env.php';
Env::load(dirname(__DIR__) . '/.env');

return [
    'driver'    => 'mysql',
    'host'      => Env::get('DB_HOST', '127.0.0.1'),
    'port'      => (int)Env::get('DB_PORT', 3306),
    'database'  => Env::get('DB_DATABASE', 'textile_erp'),
    'username'  => Env::get('DB_USERNAME', 'root'),
    'password'  => Env::get('DB_PASSWORD', ''),
    'charset'   => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'options'   => [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]
];
