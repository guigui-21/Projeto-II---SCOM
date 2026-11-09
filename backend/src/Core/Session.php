<?php
namespace App\Core;

/**
 * Encapsula a sessão PHP. No modo "nativo" usa $_SESSION com cookie seguro
 * (HttpOnly + SameSite=Strict); no modo não nativo (testes) guarda em memória.
 */
class Session
{
    private $native;
    private $data = array();

    public function __construct($native = true)
    {
        $this->native = $native;
    }

    public function start()
    {
        if (!$this->native || session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        session_name('OMNITRIXSESSID');
        session_set_cookie_params(array(
            'lifetime' => 0,
            'path' => '/',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Strict',
        ));
        session_start();
    }

    public function get($key, $default = null)
    {
        if ($this->native) {
            return isset($_SESSION[$key]) ? $_SESSION[$key] : $default;
        }
        return isset($this->data[$key]) ? $this->data[$key] : $default;
    }

    public function set($key, $value)
    {
        if ($this->native) {
            $_SESSION[$key] = $value;
        } else {
            $this->data[$key] = $value;
        }
    }

    public function remove($key)
    {
        if ($this->native) {
            unset($_SESSION[$key]);
        } else {
            unset($this->data[$key]);
        }
    }

    /** Gera um novo ID de sessão (previne session fixation no login). */
    public function regenerate()
    {
        if ($this->native && session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    /** Encerra a sessão atual: apaga os dados e invalida o ID antigo. */
    public function destroy()
    {
        if ($this->native) {
            if (session_status() === PHP_SESSION_ACTIVE) {
                $_SESSION = array();
                session_regenerate_id(true);
            }
        } else {
            $this->data = array();
        }
    }
}