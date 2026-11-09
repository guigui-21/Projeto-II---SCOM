<?php
namespace App\Controllers;

use App\Core\Csrf;
use App\Core\HttpException;
use App\Core\Logger;
use App\Core\RateLimiter;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Middleware\AuthMiddleware;
use App\Models\UsuarioModel;

/**
 * Controller de autenticação: sessão atual, login e logout.
 */
class AuthController
{
    private $usuarios;
    private $session;
    private $logger;
    private $limiter;
    private $auth;

    public function __construct(UsuarioModel $usuarios, Session $session, Logger $logger, RateLimiter $limiter, AuthMiddleware $auth)
    {
        $this->usuarios = $usuarios;
        $this->session = $session;
        $this->logger = $logger;
        $this->limiter = $limiter;
        $this->auth = $auth;
    }

    /** GET /api/me — usuário logado (ou null) + token CSRF da sessão. */
    public function me(Request $request, $params = array())
    {
        return Response::success(array(
            'user' => $this->auth->currentUser(),
            'csrf_token' => Csrf::token($this->session),
        ));
    }

    /** POST /api/login */
    public function login(Request $request, $params = array())
    {
        $this->auth->requireCsrf($request);

        $body = is_array($request->body) ? $request->body : array();
        $email = (isset($body['email']) && is_string($body['email'])) ? strtolower(trim($body['email'])) : '';
        $senha = (isset($body['senha']) && is_string($body['senha'])) ? $body['senha'] : '';

        $errors = array();
        if ($email === '') {
            $errors['email'] = 'Informe o e-mail.';
        } elseif (strlen($email) > 150 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'E-mail inválido.';
        }
        if ($senha === '') {
            $errors['senha'] = 'Informe a senha.';
        } elseif (strlen($senha) > 128) {
            $errors['senha'] = 'Senha muito longa.';
        }
        if (count($errors) > 0) {
            throw new HttpException(400, 'Dados de login inválidos.', $errors);
        }

        $chave = $request->ip . '|' . $email;
        if ($this->limiter->tooManyAttempts($chave)) {
            $this->logger->warning('Login bloqueado por excesso de tentativas: ' . $email . ' (IP ' . $request->ip . ')');
            throw new HttpException(429, 'Muitas tentativas de login. Aguarde alguns minutos e tente novamente.');
        }

        $user = $this->usuarios->findByEmail($email);
        // Mesmo quando o e-mail não existe, um hash é verificado (tempo de resposta similar).
        $hash = $user ? $user['senha'] : password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT);
        $senhaConfere = password_verify($senha, $hash);

        if (!$user || !$senhaConfere) {
            $this->limiter->hit($chave);
            $this->logger->warning('Tentativa de login inválida: ' . $email);
            throw new HttpException(401, 'E-mail ou senha incorretos.');
        }

        $this->limiter->clear($chave);
        $this->session->regenerate();
        $this->session->set('user_id', (int) $user['id']);
        $token = Csrf::rotate($this->session);

        $this->logger->info('Login bem-sucedido: ' . $email);

        return Response::success(array(
            'user' => AuthMiddleware::publicUser($user),
            'csrf_token' => $token,
        ));
    }

    /** POST /api/logout */
    public function logout(Request $request, $params = array())
    {
        $user = $this->auth->requireLogin();
        $this->auth->requireCsrf($request);

        $this->session->destroy();
        $token = Csrf::rotate($this->session);

        $this->logger->info('Logout: ' . $user['email']);

        return Response::success(array('mensagem' => 'Sessão encerrada.', 'csrf_token' => $token));
    }
}