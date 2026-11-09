<?php
namespace App\Core;

/**
 * Proteção CSRF por token (synchronizer token): o servidor guarda um token
 * na sessão e exige que toda requisição que altera estado o devolva no
 * cabeçalho X-CSRF-Token.
 */
class Csrf
{
    const KEY = '_csrf_token';

    public static function token(Session $session)
    {
        $token = $session->get(self::KEY);
        if (!is_string($token) || $token === '') {
            $token = bin2hex(random_bytes(32));
            $session->set(self::KEY, $token);
        }
        return $token;
    }

    public static function rotate(Session $session)
    {
        $token = bin2hex(random_bytes(32));
        $session->set(self::KEY, $token);
        return $token;
    }

    public static function validate(Session $session, $provided)
    {
        $token = $session->get(self::KEY);
        return is_string($token) && $token !== '' && is_string($provided) && hash_equals($token, $provided);
    }
}