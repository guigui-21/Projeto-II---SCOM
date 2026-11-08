<?php
namespace App\Core;

/**
 * Representa a requisição HTTP recebida (método, rota, query, corpo, cabeçalhos).
 * Em produção é criada por Request::fromGlobals(); nos testes é criada à mão.
 */
class Request
{
    public $method;
    public $path;
    public $query;
    public $body;
    public $headers;
    public $ip;
    public $host;

    public function __construct($method, $path, $query = array(), $body = array(), $headers = array(), $ip = '127.0.0.1', $host = 'localhost')
    {
        $this->method = strtoupper($method);
        $this->path = $path;
        $this->query = $query;
        $this->body = $body;
        $this->headers = array();
        foreach ($headers as $name => $value) {
            $this->headers[strtolower($name)] = $value;
        }
        $this->ip = $ip;
        $this->host = $host;
    }

    public function header($name)
    {
        $key = strtolower($name);
        return isset($this->headers[$key]) ? $this->headers[$key] : null;
    }

    public static function fromGlobals()
    {
        $method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';
        $uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';
        $path = parse_url($uri, PHP_URL_PATH);
        if (!is_string($path)) {
            $path = '/';
        }
        $path = rawurldecode($path);

        // Remove o diretório do backend (ex.: /enciclopedia-aliens/backend) e "/index.php".
        $script = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '/index.php';
        $base = str_replace('\\', '/', dirname($script));
        if ($base === '/' || $base === '.') {
            $base = '';
        }
        if ($base !== '' && strpos($path, $base) === 0) {
            $path = substr($path, strlen($base));
        }
        if (strpos($path, '/index.php') === 0) {
            $path = substr($path, strlen('/index.php'));
        }
        $path = '/' . trim($path, '/');

        $headers = array();
        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $headers[strtolower(str_replace('_', '-', substr($key, 5)))] = $value;
            }
        }
        if (isset($_SERVER['CONTENT_TYPE'])) {
            $headers['content-type'] = $_SERVER['CONTENT_TYPE'];
        }

        $body = array();
        $contentType = isset($headers['content-type']) ? strtolower($headers['content-type']) : '';
        if (strpos($contentType, 'application/json') !== false) {
            $raw = file_get_contents('php://input');
            if ($raw !== false && strlen($raw) > 200000) {
                throw new HttpException(413, 'Corpo da requisição muito grande.');
            }
            if ($raw !== false && trim($raw) !== '') {
                $decoded = json_decode($raw, true);
                if (!is_array($decoded)) {
                    throw new HttpException(400, 'JSON inválido no corpo da requisição.');
                }
                $body = $decoded;
            }
        } elseif (is_array($_POST)) {
            $body = $_POST;
        }

        $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
        $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';

        return new self($method, $path, $_GET, $body, $headers, $ip, $host);
    }
}