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

    /** POST /api/aliens */
    public function store(Request $request, $params = array())
    {
        $user = $this->auth->requireRole(array('administrador'));
        $this->auth->requireCsrf($request);

        list($data, $errors) = Validator::alien($request->body);
        $this->validarEra($data, $errors);
        if (count($errors) > 0) {
            throw new HttpException(400, 'Dados inválidos. Corrija os campos indicados.', $errors);
        }

        $id = $this->aliens->create($data, $user['id']);
        $this->logger->info("Alien '" . $data['nome'] . "' cadastrado com ID #" . $id . ' pelo usuário #' . $user['id']);

        return Response::success($this->aliens->find($id), 201);
    }

    /** PUT /api/aliens/{id} */
    public function update(Request $request, $params = array())
    {
        $user = $this->auth->requireRole(array('administrador'));
        $this->auth->requireCsrf($request);

        $id = $this->idParam($params);
        if ($this->aliens->find($id) === null) {
            throw new HttpException(404, 'Alien não encontrado.');
        }

        list($data, $errors) = Validator::alien($request->body);
        $this->validarEra($data, $errors);
        if (count($errors) > 0) {
            throw new HttpException(400, 'Dados inválidos. Corrija os campos indicados.', $errors);
        }

        $this->aliens->update($id, $data);
        $this->logger->info("Alien #" . $id . " ('" . $data['nome'] . "') atualizado pelo usuário #" . $user['id']);

        return Response::success($this->aliens->find($id));
    }

    /** DELETE /api/aliens/{id} */
    public function destroy(Request $request, $params = array())
    {
        $user = $this->auth->requireRole(array('administrador'));
        $this->auth->requireCsrf($request);

        $id = $this->idParam($params);
        $alien = $this->aliens->find($id);
        if ($alien === null) {
            throw new HttpException(404, 'Alien não encontrado.');
        }

        $this->aliens->delete($id);
        $this->logger->info("Alien #" . $id . " ('" . $alien['nome'] . "') excluído pelo usuário #" . $user['id']);

        return Response::success(array('id' => $id, 'mensagem' => 'Alien excluído com sucesso.'));
    }

    // ----- auxiliares ------------------------------------------------------

    private function validarEra($data, &$errors)
    {
        if (isset($data['era_id']) && $this->eras->find($data['era_id']) === null) {
            $errors['era_id'] = 'Era não encontrada.';
        }
    }

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