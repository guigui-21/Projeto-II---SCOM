# Arquitetura do sistema

Padrão adotado: **MVC em camadas**, com frontend e backend separados e comunicação
exclusivamente por **API REST (JSON)**.

```
 Navegador (frontend/)                     Apache + PHP (backend/)                   MySQL
┌───────────────────────────┐   fetch()   ┌─────────────────────────────┐  PDO     ┌──────────┐
│ VIEW                      │  JSON/HTTP  │ index.php  (front controller)│ prepared │ usuarios │
│ index.html + css          │ ──────────► │   │ Router                   │ ───────► │ eras     │
│ js/app.js  (renderização) │             │   ▼                          │          │ aliens   │
│ js/auth.js, administrador.js    │ ◄────────── │ CONTROLLER (src/Controllers) │ ◄─────── │          │
│ js/api.js  (cliente HTTP) │             │   │ AuthMiddleware           │          └──────────┘
└───────────────────────────┘             │   ▼                          │
                                          │ MODEL (src/Models, PDO)      │
                                          └─────────────────────────────┘
```

| Camada | Responsabilidade | Onde |
|---|---|---|
| **View** | Interface, acessibilidade, renderização dos dados recebidos | `frontend/` |
| **Controller** | Recebe a requisição, aplica autenticação/perfil/CSRF/validação, devolve a resposta | `backend/src/Controllers/` |
| **Middleware** | Proteção de rotas: 401 (login), 403 (perfil e CSRF) | `backend/src/Middleware/AuthMiddleware.php` |
| **Model** | Único ponto de acesso ao banco (PDO + prepared statements) | `backend/src/Models/` |
| **Core** | Infraestrutura: Router, Request/Response, Session, Csrf, Validator, Logger, RateLimiter, Env | `backend/src/Core/` |
| **Config** | Leitura do `.env`, conexão PDO | `backend/config/` |

## Fluxo de uma requisição (POST /api/aliens)
1. `index.php` carrega o `.env`, abre a sessão e a conexão PDO e monta as dependências.
2. O **Router** casa método + rota e chama `AlienController::store`.
3. O **AuthMiddleware** exige login (401) e perfil `administrador` (403); depois valida o token CSRF (403).
4. O **Validator** valida tipos/tamanhos e sanitiza os campos (400 com erros por campo).
5. O **AlienModel** grava com `INSERT` parametrizado; o **Logger** registra o evento.
6. A resposta JSON padronizada (201) volta ao frontend, que recarrega a lista.
7. Qualquer exceção é capturada no `index.php`: `HttpException` → status correspondente;
   erro inesperado → 500 genérico + detalhe técnico no `logs/app.log`.

## Decisões de projeto
- **Dados fora do frontend:** `aliensData.js` foi removido; `erasVisual.js` guarda só as cores/imagens de cada era.
- **Sem dependências em produção:** autoloader próprio; o Composer é necessário apenas para o PHPUnit.
- **Testabilidade:** controllers recebem os models por construtor; nos testes usam-se models em memória (`tests/Support/FakeModels.php`).
- **Entidade principal:** `aliens` (equivale à "tarefas" do enunciado genérico), com FK para `usuarios` (quem cadastrou) e para `eras`.
