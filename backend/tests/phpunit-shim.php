<?php
namespace PHPUnit\Framework;

/**
 * Versão MÍNIMA do TestCase do PHPUnit, usada apenas por tests/run.php quando
 * o PHPUnit (Composer) não está instalado. Com o PHPUnit instalado este
 * arquivo não é carregado.
 */
class AssertionFailedError extends \Exception
{
}

abstract class TestCase
{
    protected function setUp(): void
    {
    }

    protected function tearDown(): void
    {
    }

    final public function runSetUp()
    {
        $this->setUp();
    }

    final public function runTearDown()
    {
        $this->tearDown();
    }

    public static function fail($message = '')
    {
        throw new AssertionFailedError($message);
    }

    public static function assertTrue($condition, $message = '')
    {
        if ($condition !== true) {
            self::fail($message !== '' ? $message : 'Esperado true.');
        }
    }

    public static function assertFalse($condition, $message = '')
    {
        if ($condition !== false) {
            self::fail($message !== '' ? $message : 'Esperado false.');
        }
    }

    public static function assertSame($expected, $actual, $message = '')
    {
        if ($expected !== $actual) {
            self::fail(($message !== '' ? $message . ' - ' : '') . 'Esperado ' . var_export($expected, true) . ', obtido ' . var_export($actual, true) . '.');
        }
    }

    public static function assertEquals($expected, $actual, $message = '')
    {
        if ($expected != $actual) {
            self::fail(($message !== '' ? $message . ' - ' : '') . 'Esperado ' . var_export($expected, true) . ', obtido ' . var_export($actual, true) . '.');
        }
    }

    public static function assertNull($actual, $message = '')
    {
        if ($actual !== null) {
            self::fail(($message !== '' ? $message . ' - ' : '') . 'Esperado null, obtido ' . var_export($actual, true) . '.');
        }
    }

    public static function assertNotNull($actual, $message = '')
    {
        if ($actual === null) {
            self::fail($message !== '' ? $message : 'Esperado valor diferente de null.');
        }
    }

    public static function assertCount($expectedCount, $haystack, $message = '')
    {
        if (count($haystack) !== $expectedCount) {
            self::fail(($message !== '' ? $message . ' - ' : '') . 'Esperado ' . $expectedCount . ' itens, obtido ' . count($haystack) . '.');
        }
    }

    public static function assertArrayHasKey($key, $array, $message = '')
    {
        if (!is_array($array) || !array_key_exists($key, $array)) {
            self::fail(($message !== '' ? $message . ' - ' : '') . "Chave '" . $key . "' não encontrada.");
        }
    }

    public static function assertStringContainsString($needle, $haystack, $message = '')
    {
        if (strpos((string) $haystack, (string) $needle) === false) {
            self::fail(($message !== '' ? $message . ' - ' : '') . "Texto '" . $needle . "' não encontrado em '" . $haystack . "'.");
        }
    }

    public static function assertStringNotContainsString($needle, $haystack, $message = '')
    {
        if (strpos((string) $haystack, (string) $needle) !== false) {
            self::fail(($message !== '' ? $message . ' - ' : '') . "Texto '" . $needle . "' não deveria estar em '" . $haystack . "'.");
        }
    }
}