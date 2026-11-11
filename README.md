# Enciclopédia Intergaláctica (Omnitrix Codex) — Trabalho Individual II · SCOM

Evolução do **Omnitrix Codex** (Trabalho I, só front-end) para uma aplicação **cliente-servidor**:
o frontend continua em HTML/CSS/JavaScript puro, mas agora consome uma **API REST em PHP**
com **MySQL**, autenticação por sessão, dois perfis de acesso (administrador / visitante) e cadastro de novas contas,
proteções contra SQL Injection, XSS e CSRF, logs e testes automatizados.

## Estrutura
```
enciclopedia-aliens/
├── backend/                 PHP (API REST, padrão MVC)
│   ├── config/              Database.php (PDO) e config.php (lê o .env)
│   ├── src/
│   │   ├── Controllers/     AuthController, AlienController, EraController
│   │   ├── Models/          UsuarioModel, AlienModel, EraModel (PDO + prepared statements)
│   │   ├── Middleware/      AuthMiddleware (401/403/CSRF)
│   │   └── Core/            Router, Request, Response, Session, Csrf, Validator, Logger, RateLimiter, Env
│   ├── logs/app.log         gerado automaticamente
│   ├── tests/               AlienTest.php (PHPUnit), run.php (executor sem dependências)
│   ├── .env / .env.example  credenciais do banco (fora do código; .env está no .gitignore)
│   ├── .htaccess            reescrita de rotas + bloqueio de arquivos sensíveis
│   ├── composer.json, phpunit.xml
│   └── index.php            front controller
├── frontend/                interface (View): index.html, css/, js/, assets/
├── docs/                    endpoints.md, arquitetura.md, seguranca-testes.md, checklist-requisitos.md, GIT.md,
│                            evidencias/
├── database/migrations/     scripts SQL numerados para bancos já existentes
├── database.sql             script de criação + dados iniciais
└── README.md
```

## Como executar (XAMPP)
1. **Copie** a pasta `enciclopedia-aliens` para `C:\xampp\htdocs\`.
2. No *XAMPP Control Panel*, inicie **Apache** e **MySQL**.
3. **Banco de dados:** abra `http://localhost/phpmyadmin` → aba **Importar** → escolha `database.sql` → **Executar**
   (ou, no terminal: `mysql -u root < database.sql`).
4. **Configuração:** o arquivo `backend/.env` já vem com os padrões do XAMPP (`root`, sem senha).
   Se o seu MySQL tiver senha, edite `DB_PASS`. (Modelo: `backend/.env.example`.)
5. Abra: **http://localhost/enciclopedia-aliens/frontend/index.html**
   (verifique a API em `http://localhost/enciclopedia-aliens/backend/api/eras`).

> O projeto precisa ser aberto pelo Apache (`http://localhost/...`), não por `file://`, porque o frontend usa `fetch()` e cookies de sessão.
> Se o Apache estiver sem `mod_rewrite`, troque em `frontend/js/config.js` o valor para `"../backend/index.php"`.

### Contas e perfis
| Perfil | Como obter | E-mail | Senha |
|---|---|---|---|
| **administrador** (conta única, criada pelo `database.sql`) | já existe | `admnistrador@omnitrix.com` | `@admin123` |
| **visitante** | botão **Cadastrar-se** no site (nome, e-mail e senha) | o que a pessoa cadastrar | a que a pessoa escolher |

- O **cadastro** sempre cria um **visitante** (o perfil nunca vem da requisição) e já faz o login automaticamente.
- Sem login ou como **visitante** o site é somente leitura. Só o **administrador** vê o botão
  **"+ Adicionar novo alien"** e, no pop-up de cada alien, os botões **Editar** e **Excluir**.
- Na tela de login e na de cadastro há um botão **Mostrar/Ocultar** para ver a senha digitada.
- Regras da senha no cadastro: 8 a 72 caracteres, com ao menos uma letra e um número.

### Já importei a versão anterior do banco. E agora?
Opção 1 (mais simples): importe `database.sql` de novo (ele recria as tabelas — os aliens que você
adicionou antes serão perdidos).
Opção 2 (mantém os dados): importe `database/migrations/002_perfil_administrador.sql` no phpMyAdmin (renomeia o perfil
`curador` para `administrador`, troca e-mail/senha da conta administradora e remove o visitante de demonstração).

## Testes automatizados
```bash
cd backend
php tests/run.php                  # executor sem dependências (19 testes)
# ou, com PHPUnit:
composer install
vendor/bin/phpunit --testdox
```
Os testes rodam **sem MySQL** (models em memória) e cobrem login válido/inválido, perfis (403),
cadastro de contas (201/400/409), perfis, cadastro de aliens, validação (400), CSRF, sanitização/XSS, rate limiting, logout e escape de rotas.

## Logs
`backend/logs/app.log` registra, com timestamp: login bem-sucedido, falha de autenticação, criação/edição/exclusão
de alien, acesso negado, CSRF inválido e erros internos. Para gerar um *erro interno* para o relatório,
pare o MySQL no XAMPP e abra a página: aparecerá `[ERROR] ... Falha de conexão PDO`.

## Documentação
- API: `docs/endpoints.md` · Arquitetura: `docs/arquitetura.md`
- Roteiro para gerar as evidências de segurança: `docs/seguranca-testes.md`
- Mapeamento requisito → código: `docs/checklist-requisitos.md` · Plano de Git: `docs/GIT.md`

## Observação sobre este pacote
O código PHP foi escrito sem acesso a um interpretador PHP durante a geração. Antes de entregar,
rode `php -l` nos arquivos (`for f in $(find backend -name "*.php"); do php -l $f; done`), execute os testes
e percorra o roteiro de `docs/seguranca-testes.md` para gerar as evidências do relatório.
