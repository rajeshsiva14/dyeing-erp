<?php
use Core\Support\Env;

require_once __DIR__ . '/../core/Support/Env.php';
Env::load(dirname(__DIR__) . '/.env');

date_default_timezone_set(Env::get('TIMEZONE', 'Asia/Kolkata'));

return [
    'name'     => Env::get('APP_NAME', 'KARCEM Textile ERP'),
    'env'      => Env::get('APP_ENV', 'local'),
    'debug'    => (bool)Env::get('APP_DEBUG', true),
    'url'      => rtrim(Env::get('APP_URL', 'http://localhost/dyeing'), '/'),
    'timezone' => Env::get('TIMEZONE', 'Asia/Kolkata'),
    'company'  => [
        'name'     => Env::get('COMPANY_NAME', 'KARCEM Textiles & Processors Pvt Ltd'),
        'gstin'    => Env::get('COMPANY_GSTIN', '33AAACK1234F1Z5'),
        'currency' => Env::get('BASE_CURRENCY', 'INR'),
    ]
];
