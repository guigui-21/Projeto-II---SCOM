# Checklist de requisitos — Trabalho Individual II (SCOM)

Legenda: ✅ implementado e presente no pacote · 📎 depende de uma evidência que **você** precisa gerar/anexar ·
🧑‍💻 ação sua (não dá para entregar pronta dentro do zip).

## Requisitos obrigatórios

| # | Requisito do enunciado | Status | Onde está |
|---|---|:-:|---|
| 1 | Separação interface / lógica / acesso a dados | ✅ | `frontend/` · `backend/src/Controllers`, `Middleware`, `Core` · `backend/src/Models` |
| 1 | Arquitetura documentada | ✅ | `docs/arquitetura.md` (e capítulo 4 do relatório) |
| 2 | Entidade principal persistente | ✅ | tabela `aliens` — `database.sql` |
| 2 | CRUD compatível com o domínio | ✅ | `AlienController` + `AlienModel` (`GET/POST/PUT/DELETE /api/aliens`) |
| 2 | Consultas parametrizadas | ✅ | `backend/src/Models/*.php` (PDO `prepare` + `bindValue`, `ATTR_EMULATE_PREPARES=false`) |
| 2 | Constraints PK, FK, UNIQUE, NOT NULL | ✅ | `database.sql` |
| 2 | Script de criação do banco | ✅ | `database.sql` (+ `database/migrations/`) |
| 3 | Sessão segura | ✅ | `Core/Session.php` (HttpOnly, SameSite=Strict, `use_strict_mode`, `session_regenerate_id`) |
| 3 | Hash seguro de senha | ✅ | `password_hash`/`password_verify` — `AuthController` |
| 3 | Logout funcional | ✅ | `POST /api/logout` (destrói a sessão e troca o ID) |
| 3 | Proteção de rotas autenticadas | ✅ | `Middleware/AuthMiddleware.php` (401) |
| 4 | Pelo menos dois perfis | ✅ | `administrador` (conta única) e `visitante` (contas cadastradas) |
| 4 | Matriz de permissões no relatório | ✅ | `docs/endpoints.md` e capítulo 6 do relatório |
| 5 | Validação de tipos e tamanhos, sanitização, erros estruturados | ✅ | `Core/Validator.php`, `Core/Response.php` |
| 6 | SQL Injection | ✅ | prepared statements + validação de tipo |
| 6 | XSS refletido | ✅ | `htmlspecialchars` nas mensagens que refletem entrada, `JSON_HEX_*`, `nosniff`, `escaparHtml()` no frontend |
| 6 | CSRF | ✅ | `Core/Csrf.php` + verificação de `Origin` |
| 6 | Acesso não autorizado | ✅ | 401/403 — `AuthMiddleware` |
| 6 | Evidências dos testes de segurança | 📎 | seguir `docs/seguranca-testes.md` e salvar os prints em `docs/evidencias/` |
| 7 | Respostas padronizadas (status, mensagem, detalhe só em dev) | ✅ | `Response.php`, `index.php`, `.env` → `APP_DEBUG` |
| 8 | Documentação de todos os endpoints | ✅ | `docs/endpoints.md` |
| 9 | ≥ 5 testes automatizados (válidos e inválidos) | ✅ | `backend/tests/AlienTest.php` — 19 testes |
| 10 | Logs: login ok, falha de login, criação, erro interno (com timestamp) | ✅ | `Core/Logger.php` → `backend/logs/app.log` |

## Métricas de avaliação (autoavaliação)

| Critério | Itens atendidos |
|---|---|
| **A. Arquitetura e organização** | separação front/back ✅ · diretórios organizados ✅ · acesso a dados isolado ✅ · configuração separada ✅ · variáveis sensíveis fora do código (`.env`) ✅ · script SQL ✅ · README ✅ · documentação da arquitetura ✅ · nomenclatura consistente ✅ · sem arquivos temporários ✅ · sem código morto evidente ✅ · namespaces (`App\...`) ✅ · tratamento centralizado de erros (`index.php`) ✅ · reutilização de componentes (Core) ✅ · estrutura compatível com o padrão descrito ✅ |
| **B. Banco de dados** | PK em todas as tabelas ✅ · FKs (`aliens.era_id`, `aliens.usuario_id`) ✅ · UNIQUE/NOT NULL/ENUM ✅ · tipos adequados ✅ · índices (`idx_aliens_era_id`, `idx_aliens_usuario_id`, `idx_aliens_nome`) ✅ · script executável sem erros 📎 (rode e anexe o print) |
| **C. Segurança** | prepared statements ✅ · escaping de saída ✅ · CSRF ✅ · controle de acesso ✅ · 5 testes de segurança documentados 📎 |
| **D. Autenticação e autorização** | hash ✅ · login ✅ · logout ✅ · proteção de rotas ✅ · perfis diferenciados ✅ |
| **E. Qualidade da API** | métodos HTTP corretos ✅ · status adequados ✅ · JSON consistente ✅ · documentação completa ✅ |
| **F. Testes automatizados** | ≥ 5 executáveis ✅ · casos inválidos ✅ · evidência de execução 📎 (`php tests/run.php > docs/evidencias/testes-automatizados.txt`) |
| **G. Git e processo** | commits significativos, mensagens padronizadas, evolução incremental, branches/tags 🧑‍💻 (plano pronto em `docs/GIT.md`) |

Opcionais: `.env` ✅ · paginação ✅ · rate limiting simples (login e cadastro) ✅ · migrações de banco ✅ (`database/migrations/`) · testes de integração automatizados ❌ (não implementado: os testes usam models em memória).

## O que ainda precisa ser entregue (fora do código)

| Item | Penalidade se faltar | Como fazer |
|---|:-:|---|
| Relatório em PDF (14 tópicos) | −20 | compile `docs/relatorio/relatorio-projeto-ii.tex` no Overleaf |
| Vídeo | −10 | siga `docs/roteiro-video.md` |
| Repositório Git + link | −15 | siga `docs/GIT.md` |
| Evidências (testes, capturas de segurança, logs, `git log --oneline --graph`, dump do banco) | zera o critério | siga `docs/evidencias/LEIAME.md` |
| Credenciais fora do repositório | −10 | `backend/.env` está no `.gitignore` (versione só o `.env.example`) |
| Sistema executa localmente | −15 | siga o `README.md` e teste num XAMPP limpo |
