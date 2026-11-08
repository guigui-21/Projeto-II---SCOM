<?php
namespace App\Core;

/**
 * Exceção que carrega um status HTTP e (opcionalmente) erros por campo.
 * É capturada no front controller e convertida em resposta JSON padronizada.
 */
class HttpException extends \Exception
{
    private $status;
    private $errors;

    public function __construct($status, $message, $errors = null)
    {
        parent::__construct($message);
        $this->status = (int) $status;
        $this->errors = $errors;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function getErrors()
    {
        return $this->errors;
    }
}