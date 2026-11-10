<?php
/**
 * Front controller da API REST (todas as rotas passam por aqui).
 * Responsabilidades: carregar configuração, montar as dependências,
 * despachar a rota e converter qualquer erro em resposta JSON padronizada.
 */

require __DIR__ . '/src/autoload.php';
require __DIR__ . '/config/Database.php';

use App\Controllers\AlienController;
use App\Controllers\AuthController;
use App\Controllers\EraController;
use App\Core\Env;
use App\Core\HttpException;
use App\Core\Logger;
use App\Core\RateLimiter;
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Core\Session;
use App\Middleware\AuthMiddleware;
use App\Models\AlienModel;
use App\Models\EraModel;
use App\Models\UsuarioModel;

Env::load(__DIR__ . '/.env');
$config = require __DIR__ . '/config/config.php';

date_default_timezone_set($config['app']['timezone']);
error_reporting(E_ALL);
ini_set('display_errors', '0');   // erros nunca vazam para o cliente; vão para o log

$logger = new Logger($config['paths']['log']);

try {
    $request = Request::fromGlobals();

    $session = new Session(true);
    $session->start();

    $pdo = Database::connect($config['db']);

    // --- Models (acesso a dados) ---
    $usuarios = new UsuarioModel($pdo);
    $eras = new EraModel($pdo);
    $aliens = new AlienModel($pdo);

    // --- Middleware e Controllers ---
    $limiter = new RateLimiter(
        $config['paths']['rate_limit'],
        $config['security']['login_max_attempts'],
        $config['security']['login_window']
    );
    $registerLimiter = new RateLimiter($config['paths']['rate_limit_register'], 10, 600);
    $auth = new AuthMiddleware($session, $usuarios, $logger);
    $authController = new AuthController($usuarios, $session, $logger, $limiter, $auth, $registerLimiter);
    $alienController = new AlienController($aliens, $eras, $auth, $logger);
    $eraController = new EraController($eras);

    // --- Rotas ---
    $router = new Router();
    $router->add('GET', '/', function ($req, $params) {
        return Response::success(array(
            'nome' => 'Enciclopédia Intergaláctica — API REST',
            'documentacao' => 'docs/endpoints.md',
        ));
    });
    $router->add('GET', '/api/me', array($authController, 'me'));
    $router->add('POST', '/api/login', array($authController, 'login'));
    $router->add('POST', '/api/register', array($authController, 'register'));
    $router->add('POST', '/api/logout', array($authController, 'logout'));
    $router->add('GET', '/api/eras', array($eraController, 'index'));
    $router->add('GET', '/api/aliens', array($alienController, 'index'));
    $router->add('GET', '/api/aliens/{id}', array($alienController, 'show'));
    $router->add('POST', '/api/aliens', array($alienController, 'store'));
    $router->add('PUT', '/api/aliens/{id}', array($alienController, 'update'));
    $router->add('DELETE', '/api/aliens/{id}', array($alienController, 'destroy'));

    $response = $router->dispatch($request);
} catch (HttpException $e) {
    $response = Response::error($e->getMessage(), $e->getStatus(), $e->getErrors());
} catch (\Throwable $e) {
    // Erro interno: detalhes técnicos só no log (e na resposta apenas em modo debug).
    $logger->error(get_class($e) . ': ' . $e->getMessage() . ' em ' . basename($e->getFile()) . ':' . $e->getLine());
    $detail = null;
    if ($config['app']['debug']) {
        $detail = array(
            'tipo' => get_class($e),
            'mensagem' => $e->getMessage(),
            'arquivo' => basename($e->getFile()) . ':' . $e->getLine(),
        );
    }
    $response = Response::error('Erro interno do servidor. Tente novamente mais tarde.', 500, null, $detail);
}

$response->send();