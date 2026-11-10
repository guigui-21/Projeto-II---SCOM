<?php
namespace App\Controllers;

use App\Core\HttpException;
use App\Core\Logger;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Middleware\AuthMiddleware;
use App\Models\AlienModel;
use App\Models\EraModel;

/**
 * Controller do CRUD de aliens.
 *   leitura:  pública (visitante / sem login)
 *   escrita:  somente perfil "administrador" + token CSRF
 */
class AlienController
{
    private $aliens;
    private $eras;
    private $auth;
    private $logger;

    public function __construct(AlienModel $aliens, EraModel $eras, AuthMiddleware $auth, Logger $logger)
    {
        $this->aliens = $aliens;
        $this->eras = $eras;
        $this->auth = $auth;
        $this->logger = $logger;
    }

    /** GET /api/aliens?pagina=1&por_pagina=100&era=classico */
    public function index(Request $request, $params = array())
    {
        $pagina = $this->intQuery($request->query, 'pagina', 1, 1, 100000);
        $porPagina = $this->intQuery($request->query, 'por_pagina', 100, 1, 100);

        $eraId = null;
        if (isset($request->query['era']) && $request->query['era'] !== '') {
            $slug = is_string($request->query['era']) ? $request->query['era'] : '';
            $era = $this->eras->findBySlug($slug);
            if ($era === null) {
                throw new HttpException(400, 'Filtro de era inválido.', array('era' => 'Era não encontrada.'));
            }
            $eraId = (int) $era['id'];
        }

        $total = $this->aliens->count($eraId);
        $lista = $this->aliens->paginate($pagina, $porPagina, $eraId);

        return Response::success($lista, 200, array(
            'pagina' => $pagina,
            'por_pagina' => $porPagina,
            'total' => $total,
            'total_paginas' => (int) ceil($total / $porPagina),
        ));
    }

    /** GET /api/aliens/{id} */
    public function show(Request $request, $params = array())
    {
        $id = $this->idParam($params);
        $alien = $this->aliens->find($id);
        if ($alien === null) {
            throw new HttpException(404, 'Alien não encontrado.');
        }
        return Response::success($alien);
    }

    // ----- auxiliares ------------------------------------------------------

    private function idParam($params)
    {
        $id = isset($params['id']) ? $params['id'] : '';
        if (!ctype_digit((string) $id) || (int) $id < 1) {
            throw new HttpException(400, 'Identificador inválido.', array('id' => 'O id deve ser um número inteiro positivo.'));
        }
        return (int) $id;
    }

    private function intQuery($query, $nome, $padrao, $min, $max)
    {
        if (!isset($query[$nome]) || $query[$nome] === '') {
            return $padrao;
        }
        $valor = $query[$nome];
        if (!is_string($valor) || !ctype_digit($valor)) {
            throw new HttpException(400, 'Parâmetro inválido.', array($nome => 'Deve ser um número inteiro.'));
        }
        $valor = (int) $valor;
        if ($valor < $min || $valor > $max) {
            throw new HttpException(400, 'Parâmetro fora do intervalo permitido.', array($nome => 'Deve estar entre ' . $min . ' e ' . $max . '.'));
        }
        return $valor;
    }
}