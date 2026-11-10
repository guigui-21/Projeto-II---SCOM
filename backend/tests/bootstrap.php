<?php
/**
 * Bootstrap dos testes: carrega o autoloader da aplicação, o autoloader do
 * Composer (se existir, para o PHPUnit) e os models "falsos" em memória.
 */
require_once __DIR__ . '/../src/autoload.php';

$composerAutoload = __DIR__ . '/../vendor/autoload.php';
if (is_file($composerAutoload)) {
    require_once $composerAutoload;
}

require_once __DIR__ . '/Support/FakeModels.php';

date_default_timezone_set('America/Sao_Paulo');