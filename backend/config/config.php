<?php
use App\Core\Env;

/**
 * Configuração da aplicação. Valores sensíveis vêm do .env (via Env::get).
 */
$root = dirname(__DIR__);

return array(
    'app' => array(
        'debug' => Env::bool('APP_DEBUG', false),
        'timezone' => Env::get('APP_TIMEZONE', 'America/Sao_Paulo'),
    ),
    'db' => array(
        'host' => Env::get('DB_HOST', '127.0.0.1'),
        'port' => Env::get('DB_PORT', '3306'),
        'name' => Env::get('DB_NAME', 'enciclopedia_aliens'),
        'user' => Env::get('DB_USER'),
        'pass' => Env::get('DB_PASS', ''),
    ),
    'paths' => array(
        'log' => $root . '/logs/app.log',
        'rate_limit' => $root . '/storage/rate_limit.json',
        'rate_limit_register' => $root . '/storage/rate_limit_register.json',
    ),
    'security' => array(
        'login_max_attempts' => (int) Env::get('LOGIN_MAX_ATTEMPTS', 5),
        'login_window' => (int) Env::get('LOGIN_WINDOW_SECONDS', 600),
    ),
);