<?php
/**
 * Autoloader simples (PSR-4): App\Core\Foo -> src/Core/Foo.php
 * Dispensa o Composer em tempo de execução (o Composer só é necessário
 * para instalar o PHPUnit, usado nos testes).
 */
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $file = __DIR__ . '/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($file)) {
        require $file;
    }
});