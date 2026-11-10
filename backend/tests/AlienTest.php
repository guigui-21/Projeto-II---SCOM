<?php
namespace Tests;

use App\Controllers\AlienController;
use App\Controllers\AuthController;
use App\Core\Csrf;
use App\Core\HttpException;
use App\Core\Logger;
use App\Core\RateLimiter;
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Core\Session;
use App\Core\Validator;
use App\Middleware\AuthMiddleware;
use PHPUnit\Framework\TestCase;
use Tests\Support\FakeAlienModel;
use Tests\Support\FakeEraModel;
use Tests\Support\FakeUsuarioModel;

/**
 * Testes automatizados da lógica de negócio (login, perfis, cadastro,
 * validação, CSRF, sanitização). Cobrem casos VÁLIDOS e INVÁLIDOS.
 * Rodam sem MySQL: os models são substituídos por versões em memória.
 */
class AlienTest extends TestCase
{
    private $logFile;
    private $rateFile;
    private $rateFileCadastro;
    private $session;
    private $usuarios;
    private $eras;
    private $aliens;
    private $authController;
    private $alienController;

    protected function setUp(): void
    {
        $base = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'aliens_test_' . uniqid();
        $this->logFile = $base . '.log';
        $this->rateFile = $base . '_rate.json';
        $this->rateFileCadastro = $base . '_rate_cadastro.json';

        $this->session = new Session(false);

        $this->usuarios = new FakeUsuarioModel();
        $this->usuarios->adicionar(1, 'Administrador Teste', 'administrador@teste.com', 'Administrador@123', 'administrador');
        $this->usuarios->adicionar(2, 'Visitante Teste', 'visitante@teste.com', 'Visitante@123', 'visitante');
        // (no sistema real existe apenas UMA conta administradora: a do database.sql)

        $this->eras = new FakeEraModel();
        $this->aliens = new FakeAlienModel();

        $logger = new Logger($this->logFile);
        $limiter = new RateLimiter($this->rateFile, 5, 600);
        $limiterCadastro = new RateLimiter($this->rateFileCadastro, 10, 600);
        $auth = new AuthMiddleware($this->session, $this->usuarios, $logger);

        $this->authController = new AuthController($this->usuarios, $this->session, $logger, $limiter, $auth, $limiterCadastro);
        $this->alienController = new AlienController($this->aliens, $this->eras, $auth, $logger);
    }

    protected function tearDown(): void
    {
        @unlink($this->logFile);
        @unlink($this->rateFile);
        @unlink($this->rateFileCadastro);
    }

    // ---------------------------------------------------------------- auxiliares

    private function request($method, $path, $body = array(), $comCsrf = true, $query = array())
    {
        $headers = array();
        if ($comCsrf) {
            $headers['X-CSRF-Token'] = Csrf::token($this->session);
        }
        return new Request($method, $path, $query, $body, $headers);
    }

    /** Executa um método de controller como o front controller faria (HttpException -> Response). */
    private function executar($controller, $metodo, Request $request, $params = array())
    {
        try {
            return $controller->$metodo($request, $params);
        } catch (HttpException $e) {
            return Response::error($e->getMessage(), $e->getStatus(), $e->getErrors());
        }
    }

    private function login($email, $senha)
    {
        return $this->executar($this->authController, 'login', $this->request('POST', '/api/login', array('email' => $email, 'senha' => $senha)));
    }

    private function cadastrar($dados)
    {
        return $this->executar($this->authController, 'register', $this->request('POST', '/api/register', $dados));
    }

    private function alienValido()
    {
        return array(
            'nome' => 'Chama',
            'especie' => 'Pyronita',
            'planeta_origem' => 'Pyros',
            'descricao' => 'Foi o primeiro alienígena no qual Ben se transformou.',
            'habilidades' => array('Pirocinese', 'Voo'),
            'era_id' => 1,
            'imagem_url' => 'assets/aliens/ChamaCL.png',
        );
    }

    private function logConteudo()
    {
        return is_file($this->logFile) ? file_get_contents($this->logFile) : '';
    }

    // ------------------------------------------------- os 5 testes da proposta

    public function testLoginComCredenciaisValidas()
    {
        $resp = $this->login('administrador@teste.com', 'Administrador@123');

        $this->assertSame(200, $resp->status);
        $this->assertSame('success', $resp->body['status']);
        $this->assertSame('administrador', $resp->body['data']['user']['perfil']);
        $this->assertSame(1, $this->session->get('user_id'));
        $this->assertStringContainsString('[INFO] Login bem-sucedido: administrador@teste.com', $this->logConteudo());
    }

    public function testLoginComSenhaIncorreta()
    {
        $resp = $this->login('administrador@teste.com', 'senha-errada');

        $this->assertSame(401, $resp->status);
        $this->assertSame('error', $resp->body['status']);
        $this->assertNull($this->session->get('user_id'));
        $this->assertStringContainsString('[WARNING] Tentativa de login inválida: administrador@teste.com', $this->logConteudo());
    }

    public function testAdministradorPodeCadastrarAlien()
    {
        $this->login('administrador@teste.com', 'Administrador@123');

        $resp = $this->executar($this->alienController, 'store', $this->request('POST', '/api/aliens', $this->alienValido()));

        $this->assertSame(201, $resp->status);
        $this->assertSame('Chama', $resp->body['data']['nome']);
        $this->assertCount(1, $this->aliens->rows);
        $this->assertStringContainsString("Alien 'Chama' cadastrado com ID #1 pelo usuário #1", $this->logConteudo());
    }

    public function testVisitanteNaoPodeCadastrarAlien()
    {
        $this->login('visitante@teste.com', 'Visitante@123');

        $resp = $this->executar($this->alienController, 'store', $this->request('POST', '/api/aliens', $this->alienValido()));

        $this->assertSame(403, $resp->status);
        $this->assertCount(0, $this->aliens->rows);
    }

    public function testValidacaoCamposObrigatoriosAlien()
    {
        $this->login('administrador@teste.com', 'Administrador@123');

        $resp = $this->executar($this->alienController, 'store', $this->request('POST', '/api/aliens', array()));

        $this->assertSame(400, $resp->status);
        $this->assertArrayHasKey('nome', $resp->body['errors']);
        $this->assertArrayHasKey('especie', $resp->body['errors']);
        $this->assertArrayHasKey('planeta_origem', $resp->body['errors']);
        $this->assertArrayHasKey('descricao', $resp->body['errors']);
        $this->assertArrayHasKey('habilidades', $resp->body['errors']);
        $this->assertArrayHasKey('era_id', $resp->body['errors']);
        $this->assertCount(0, $this->aliens->rows);
    }

    // ------------------------------------------------------- testes adicionais

    public function testAdicionarAlienSemLoginRetorna401()
    {
        $resp = $this->executar($this->alienController, 'store', $this->request('POST', '/api/aliens', $this->alienValido()));

        $this->assertSame(401, $resp->status);
        $this->assertCount(0, $this->aliens->rows);
    }

    public function testRequisicaoSemTokenCsrfRetorna403()
    {
        $this->login('administrador@teste.com', 'Administrador@123');

        $resp = $this->executar($this->alienController, 'store', $this->request('POST', '/api/aliens', $this->alienValido(), false));

        $this->assertSame(403, $resp->status);
        $this->assertStringContainsString('CSRF', $resp->body['message']);
        $this->assertCount(0, $this->aliens->rows);
    }

    public function testSanitizacaoRemoveTagsHtmlDosCampos()
    {
        $this->login('administrador@teste.com', 'Administrador@123');
        $dados = $this->alienValido();
        $dados['nome'] = '<script>alert(1)</script>Chama';

        $resp = $this->executar($this->alienController, 'store', $this->request('POST', '/api/aliens', $dados));

        $this->assertSame(201, $resp->status);
        $this->assertStringNotContainsString('<script>', $resp->body['data']['nome']);
        $this->assertSame('alert(1)Chama', $resp->body['data']['nome']);
    }

    public function testLimiteDeTentativasBloqueiaLoginAposVariasFalhas()
    {
        for ($i = 0; $i < 5; $i++) {
            $this->login('administrador@teste.com', 'errada' . $i);
        }

        // Mesmo com a senha correta, o login fica bloqueado temporariamente.
        $resp = $this->login('administrador@teste.com', 'Administrador@123');

        $this->assertSame(429, $resp->status);
        $this->assertNull($this->session->get('user_id'));
    }

    public function testListagemPublicaFuncionaSemLogin()
    {
        $this->aliens->create($this->alienValido(), 1);

        $resp = $this->executar($this->alienController, 'index', $this->request('GET', '/api/aliens', array(), false));

        $this->assertSame(200, $resp->status);
        $this->assertCount(1, $resp->body['data']);
        $this->assertSame(1, $resp->body['meta']['total']);
    }

    public function testRespostaDeLoginNaoExpoeHashDaSenha()
    {
        $resp = $this->login('administrador@teste.com', 'Administrador@123');
        $json = $resp->encode();

        $this->assertStringNotContainsString('$2y$', $json);
        $this->assertStringNotContainsString('"senha"', $json);
    }

    public function testLogoutEncerraASessao()
    {
        $this->login('administrador@teste.com', 'Administrador@123');

        $resp = $this->executar($this->authController, 'logout', $this->request('POST', '/api/logout'));

        $this->assertSame(200, $resp->status);
        $this->assertNull($this->session->get('user_id'));
    }

    public function testRotaInexistenteEscapaHtmlNaMensagemDeErro()
    {
        $router = new Router();
        $mensagem = '';
        try {
            $router->dispatch(new Request('GET', '/<script>alert(1)</script>'));
        } catch (HttpException $e) {
            $mensagem = $e->getMessage();
            $this->assertSame(404, $e->getStatus());
        }

        $this->assertStringNotContainsString('<script>', $mensagem);
        $this->assertStringContainsString('&lt;script&gt;', $mensagem);
    }

    // ------------------------------------------------ cadastro de novas contas

    public function testCadastroCriaContaVisitanteEIniciaSessao()
    {
        $resp = $this->cadastrar(array('nome' => 'Maria Silva', 'email' => 'Maria@Teste.com', 'senha' => 'abc12345'));

        $this->assertSame(201, $resp->status);
        $this->assertSame('visitante', $resp->body['data']['user']['perfil']);
        $this->assertSame('maria@teste.com', $resp->body['data']['user']['email']);
        $this->assertSame($resp->body['data']['user']['id'], $this->session->get('user_id'));
        $this->assertStringContainsString('Novo usuário cadastrado: maria@teste.com (perfil visitante)', $this->logConteudo());
        // a senha é guardada como hash, nunca em texto puro
        $salvo = $this->usuarios->findByEmail('maria@teste.com');
        $this->assertTrue(password_verify('abc12345', $salvo['senha']));
        $this->assertStringNotContainsString('abc12345', $resp->encode());
    }

    public function testCadastroComDadosInvalidosRetorna400()
    {
        $resp = $this->cadastrar(array('nome' => '', 'email' => 'isso-nao-e-email', 'senha' => '123'));

        $this->assertSame(400, $resp->status);
        $this->assertArrayHasKey('nome', $resp->body['errors']);
        $this->assertArrayHasKey('email', $resp->body['errors']);
        $this->assertArrayHasKey('senha', $resp->body['errors']);
    }

    public function testCadastroComEmailJaExistenteRetorna409()
    {
        $resp = $this->cadastrar(array('nome' => 'Outro Admin', 'email' => 'administrador@teste.com', 'senha' => 'abc12345'));

        $this->assertSame(409, $resp->status);
        $this->assertArrayHasKey('email', $resp->body['errors']);
    }

    public function testCadastroNaoPermiteEscolherPerfilAdministrador()
    {
        $resp = $this->cadastrar(array(
            'nome' => 'Espertinho',
            'email' => 'esperto@teste.com',
            'senha' => 'abc12345',
            'perfil' => 'administrador',
        ));

        $this->assertSame(201, $resp->status);
        $this->assertSame('visitante', $resp->body['data']['user']['perfil']);

        // e, sendo visitante, não consegue cadastrar/editar aliens
        $store = $this->executar($this->alienController, 'store', $this->request('POST', '/api/aliens', $this->alienValido()));
        $this->assertSame(403, $store->status);
    }

    public function testCadastroSemTokenCsrfRetorna403()
    {
        $req = new Request('POST', '/api/register', array(), array('nome' => 'Ana', 'email' => 'ana@teste.com', 'senha' => 'abc12345'));
        $resp = $this->executar($this->authController, 'register', $req);

        $this->assertSame(403, $resp->status);
        $this->assertNull($this->usuarios->findByEmail('ana@teste.com'));
    }

    public function testValidatorRejeitaUrlDeImagemPerigosa()
    {
        $dados = $this->alienValido();
        $dados['imagem_url'] = 'javascript:alert(1)';

        $resultado = Validator::alien($dados);

        $this->assertArrayHasKey('imagem_url', $resultado[1]);
    }
}