<?php
namespace App\Middleware;

use App\Core\Csrf;
use App\Core\HttpException;
use App\Core\Logger;
use App\Core\Request;
use App\Core\Session;
use App\Models\UsuarioModel;

/**
 * Proteção de rotas: exige usuário autenticado (401), perfil adequado (403)
 * e token CSRF válido em requisições que alteram estado (403).
 */
class AuthMiddleware
{
    private $session;
    private $usuarios;
    private $logger;

    public function __construct(Session $session, UsuarioModel $usuarios, Logger $logger)
    {
        $this->session = $session;
        $this->usuarios = $usuarios;
        $this->logger = $logger;
    }

    /** Usuário da sessão (sem o hash da senha) ou null se não autenticado. */
    public function currentUser()
    {
        $id = $this->session->get('user_id');
        if ($id === null) {
            return null;
        }
        $user = $this->usuarios->findById((int) $id);
        if ($user === null) {
            // usuário removido do banco: invalida a sessão
            $this->session->remove('user_id');
            return null;
        }
        return self::publicUser($user);
    }

    public static function publicUser($user)
    {
        return array(
            'id' => (int) $user['id'],
            'nome' => $user['nome'],
            'email' => $user['email'],
            'perfil' => $user['perfil'],
        );
    }

    public function requireLogin()
    {
        $user = $this->currentUser();
        if ($user === null) {
            throw new HttpException(401, 'Autenticação necessária. Faça login para continuar.');
        }
        return $user;
    }

    public function requireRole($perfis)
    {
        $user = $this->requireLogin();
        if (!in_array($user['perfil'], $perfis, true)) {
            $this->logger->warning('Acesso negado: usuário #' . $user['id'] . ' (' . $user['perfil'] . ') tentou uma ação restrita a ' . implode('/', $perfis));
            throw new HttpException(403, 'Você não tem permissão para realizar esta ação.');
        }
        return $user;
    }

    public function requireCsrf(Request $request)
    {
        $token = $request->header('X-CSRF-Token');
        if (!Csrf::validate($this->session, $token)) {
            $this->logger->warning('Token CSRF inválido ou ausente (IP ' . $request->ip . ', rota ' . $request->method . ' ' . $request->path . ')');
            throw new HttpException(403, 'Token CSRF inválido ou ausente.');
        }

        // Verificação adicional do cabeçalho Origin (quando enviado pelo navegador).
        $origin = $request->header('Origin');
        if ($origin !== null && $origin !== '') {
            $host = parse_url($origin, PHP_URL_HOST);
            $port = parse_url($origin, PHP_URL_PORT);
            $originHost = ($host === null || $host === false) ? '' : $host . ($port ? ':' . $port : '');
            if (strcasecmp($originHost, $request->host) !== 0) {
                $this->logger->warning('Origem não permitida: ' . $origin);
                throw new HttpException(403, 'Origem da requisição não permitida.');
            }
        }
    }
}