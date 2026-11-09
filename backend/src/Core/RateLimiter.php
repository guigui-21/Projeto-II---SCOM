<?php
namespace App\Core;

/**
 * Limitador de tentativas simples (usado no login para dificultar força bruta).
 * Guarda os horários das últimas falhas em um arquivo JSON.
 */
class RateLimiter
{
    private $file;
    private $max;
    private $window;

    public function __construct($file, $max = 5, $window = 600)
    {
        $this->file = $file;
        $this->max = (int) $max;
        $this->window = (int) $window;
    }

    public function tooManyAttempts($key)
    {
        $data = $this->prune($this->load());
        $k = sha1($key);
        return isset($data[$k]) && count($data[$k]) >= $this->max;
    }

    public function hit($key)
    {
        $data = $this->prune($this->load());
        $k = sha1($key);
        if (!isset($data[$k])) {
            $data[$k] = array();
        }
        $data[$k][] = time();
        $this->save($data);
    }

    public function clear($key)
    {
        $data = $this->prune($this->load());
        unset($data[sha1($key)]);
        $this->save($data);
    }

    private function load()
    {
        if (!is_file($this->file)) {
            return array();
        }
        $raw = @file_get_contents($this->file);
        $data = json_decode((string) $raw, true);
        return is_array($data) ? $data : array();
    }

    private function save($data)
    {
        $dir = dirname($this->file);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        @file_put_contents($this->file, json_encode($data), LOCK_EX);
    }

    private function prune($data)
    {
        $limit = time() - $this->window;
        $result = array();
        foreach ($data as $key => $times) {
            $recent = array();
            foreach ($times as $t) {
                if ($t > $limit) {
                    $recent[] = $t;
                }
            }
            if (count($recent) > 0) {
                $result[$key] = $recent;
            }
        }
        return $result;
    }
}