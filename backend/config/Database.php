<?php
/**
 * Conexão com o MySQL via PDO. As credenciais vêm do arquivo .env
 * (nunca ficam no código-fonte).
 */
class Database
{
    public static function connect($config)
    {
        if (empty($config['user'])) {
            throw new RuntimeException('Configuração do banco ausente: defina DB_USER e DB_PASS no arquivo backend/.env (copie de .env.example).');
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            $config['host'],
            (int) $config['port'],
            $config['name']
        );

        try {
            return new PDO($dsn, $config['user'], $config['pass'], array(
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ));
        } catch (PDOException $e) {
            throw new RuntimeException('Falha de conexão PDO: ' . $e->getMessage(), 0, $e);
        }
    }
}