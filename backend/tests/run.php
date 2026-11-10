<?php
/**
 * Executor de testes SEM dependências (alternativa ao PHPUnit).
 *
 *   php tests/run.php
 *
 * Se o PHPUnit estiver instalado (composer install), prefira:
 *   vendor/bin/phpunit --testdox
 *
 * Dica para gerar a evidência do relatório:
 *   php tests/run.php > ../docs/evidencias/testes-automatizados.txt
 */
require __DIR__ . '/bootstrap.php';

if (class_exists('PHPUnit\\Framework\\TestCase')) {
    echo 'PHPUnit instalado: execute  vendor/bin/phpunit --testdox' . PHP_EOL;
    exit(0);
}
require __DIR__ . '/phpunit-shim.php';

require_once __DIR__ . '/AlienTest.php';

echo 'Testes automatizados — Enciclopédia Intergaláctica' . PHP_EOL;
echo 'PHP ' . PHP_VERSION . ' — ' . date('Y-m-d H:i:s') . PHP_EOL;
echo str_repeat('-', 64) . PHP_EOL;

$total = 0;
$falhas = 0;

foreach (get_declared_classes() as $classe) {
    $ref = new ReflectionClass($classe);
    if ($ref->isAbstract() || strpos($classe, 'Tests\\') !== 0 || !$ref->isSubclassOf('PHPUnit\\Framework\\TestCase')) {
        continue;
    }
    foreach ($ref->getMethods(ReflectionMethod::IS_PUBLIC) as $metodo) {
        if (strpos($metodo->getName(), 'test') !== 0) {
            continue;
        }
        $total++;
        $teste = new $classe();
        try {
            $teste->runSetUp();
            $metodo->invoke($teste);
            $teste->runTearDown();
            echo '[ OK ]  ' . $metodo->getName() . PHP_EOL;
        } catch (\Throwable $e) {
            $falhas++;
            echo '[FALHA] ' . $metodo->getName() . ' -> ' . $e->getMessage() . PHP_EOL;
            try {
                $teste->runTearDown();
            } catch (\Throwable $ignorado) {
            }
        }
    }
}

echo str_repeat('-', 64) . PHP_EOL;
echo ($falhas === 0 ? 'OK' : 'FALHOU') . ' — ' . $total . ' testes, ' . ($total - $falhas) . ' passaram, ' . $falhas . ' falharam.' . PHP_EOL;
exit($falhas === 0 ? 0 : 1);