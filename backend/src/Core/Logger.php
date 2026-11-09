<?php
namespace App\Core;

/**
 * Log de aplicação em arquivo, uma linha por evento:
 *   [2026-10-13 10:30:00] [INFO] Login bem-sucedido: administrador@omnitrix.com
 */
class Logger
{
    private $file;

    public function __construct($file)
    {
        $this->file = $file;
    }

    public function info($message)
    {
        $this->write('INFO', $message);
    }

    public function warning($message)
    {
        $this->write('WARNING', $message);
    }

    public function error($message)
    {
        $this->write('ERROR', $message);
    }

    private function write($level, $message)
    {
        // Evita "log injection": quebras de linha vindas do usuário viram espaço.
        $message = preg_replace('/[\r\n]+/', ' ', (string) $message);
        $line = '[' . date('Y-m-d H:i:s') . '] [' . $level . '] ' . $message . PHP_EOL;
        $dir = dirname($this->file);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        @file_put_contents($this->file, $line, FILE_APPEND | LOCK_EX);
    }
}