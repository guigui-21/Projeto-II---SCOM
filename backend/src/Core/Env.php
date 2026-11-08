<?php
namespace App\Core;

/**
 * Leitor mínimo de arquivo .env (CHAVE=valor). As credenciais do banco e
 * demais configurações sensíveis ficam fora do código-fonte.
 */
class Env
{
    private static $values = array();

    public static function load($path)
    {
        if (!is_file($path) || !is_readable($path)) {
            return false;
        }
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#') {
                continue;
            }
            $pos = strpos($line, '=');
            if ($pos === false) {
                continue;
            }
            $key = trim(substr($line, 0, $pos));
            $value = trim(substr($line, $pos + 1));
            $len = strlen($value);
            if ($len >= 2) {
                $first = $value[0];
                $last = $value[$len - 1];
                if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                    $value = substr($value, 1, -1);
                }
            }
            self::$values[$key] = $value;
        }
        return true;
    }

    public static function get($key, $default = null)
    {
        if (array_key_exists($key, self::$values)) {
            return self::$values[$key];
        }
        $env = getenv($key);
        return ($env !== false) ? $env : $default;
    }

    public static function bool($key, $default = false)
    {
        $value = self::get($key);
        if ($value === null) {
            return $default;
        }
        return in_array(strtolower((string) $value), array('1', 'true', 'yes', 'on'), true);
    }
}