<?php
namespace App\Core;

/**
 * Resposta JSON padronizada.
 *   sucesso: {"status":"success","data":...,"meta":...}
 *   erro:    {"status":"error","message":"...","errors":{campo:msg},"detail":{...só em debug}}
 */
class Response
{
    public $status;
    public $body;
    public $headers = array();

    public function __construct($status, $body)
    {
        $this->status = (int) $status;
        $this->body = $body;
    }

    public static function success($data = null, $status = 200, $meta = null)
    {
        $body = array('status' => 'success', 'data' => $data);
        if ($meta !== null) {
            $body['meta'] = $meta;
        }
        return new self($status, $body);
    }

    public static function error($message, $status = 400, $errors = null, $detail = null)
    {
        $body = array('status' => 'error', 'message' => $message);
        if (!empty($errors)) {
            $body['errors'] = $errors;
        }
        if ($detail !== null) {
            $body['detail'] = $detail;
        }
        return new self($status, $body);
    }

    /**
     * Serializa em JSON escapando <, >, &, ' e " (JSON_HEX_*): mesmo que a
     * resposta fosse interpretada como HTML, nenhuma tag poderia ser formada
     * (defesa em profundidade contra XSS refletido).
     */
    public function encode()
    {
        $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
        $json = json_encode($this->body, $flags);
        if ($json === false) {
            $json = '{"status":"error","message":"Falha ao serializar a resposta."}';
        }
        return $json;
    }

    public function send()
    {
        http_response_code($this->status);
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Referrer-Policy: no-referrer');
        header('Cache-Control: no-store');
        foreach ($this->headers as $name => $value) {
            header($name . ': ' . $value);
        }
        echo $this->encode();
    }
}