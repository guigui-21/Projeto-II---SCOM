# Documentação da API REST — Enciclopédia Intergaláctica

Base URL (XAMPP): `http://localhost/enciclopedia-aliens/backend`
(se o `mod_rewrite` estiver desativado: `http://localhost/enciclopedia-aliens/backend/index.php`)

Todas as respostas são JSON (`Content-Type: application/json; charset=utf-8`).

## Convenções

**Sucesso**
```json
{ "status": "success", "data": { }, "meta": { } }
```
**Erro** (mensagem legível + erros por campo quando houver; `detail` só aparece com `APP_DEBUG=true`)
```json
{ "status": "error", "message": "Dados inválidos. Corrija os campos indicados.", "errors": { "nome": "Nome é obrigatório." } }
```

**Autenticação:** sessão PHP em cookie `OMNITRIXSESSID` (HttpOnly, SameSite=Strict).
**CSRF:** toda requisição `POST`, `PUT` e `DELETE` deve enviar o cabeçalho `X-CSRF-Token`
com o token obtido em `GET /api/me` (ou devolvido pelo login/logout).

**Perfis:** `visitante` (e público sem login) = somente leitura · `administrador` (conta única, criada no `database.sql`) = leitura + adicionar, editar e excluir aliens.
Novas contas criadas por `POST /api/register` são **sempre** `visitante`.

| Código | Significado no projeto |
|---|---|
| 200 / 201 | Sucesso / recurso criado |
| 400 | Dados inválidos (tipo, tamanho, formato, JSON malformado) |
| 401 | Não autenticado ou credenciais incorretas |
| 403 | Perfil sem permissão ou token CSRF inválido/ausente |
| 404 | Rota ou recurso inexistente |
| 409 | Conflito: e-mail já cadastrado |
| 405 | Método não permitido na rota |
| 413 | Corpo da requisição grande demais |
| 429 | Muitas tentativas de login (rate limiting) |
| 500 | Erro interno (detalhes apenas no log) |

---

## Resumo

| Método | Endpoint | Perfil mínimo | Sucesso | Erros |
|---|---|---|---|---|
| GET | `/api/me` | Público | 200 | 500 |
| POST | `/api/login` | Público (+CSRF) | 200 | 400, 401, 403, 429, 500 |
| POST | `/api/register` | Público (+CSRF) | 201 | 400, 403, 409, 429, 500 |
| POST | `/api/logout` | Autenticado (+CSRF) | 200 | 401, 403 |
| GET | `/api/eras` | Público | 200 | 500 |
| GET | `/api/aliens` | Público | 200 | 400, 500 |
| GET | `/api/aliens/{id}` | Público | 200 | 400, 404, 500 |
| POST | `/api/aliens` | Administrador (+CSRF) | 201 | 400, 401, 403 |
| PUT | `/api/aliens/{id}` | Administrador (+CSRF) | 200 | 400, 401, 403, 404 |
| DELETE | `/api/aliens/{id}` | Administrador (+CSRF) | 200 | 400, 401, 403, 404 |

### Matriz de permissões

| Ação | Público / Visitante | Administrador |
|---|:-:|:-:|
| Listar eras e aliens, ver detalhes | ✅ | ✅ |
| Criar uma conta (vira visitante) | ✅ | — |
| Adicionar alien | ❌ 403 (401 sem login) | ✅ |
| Editar alien | ❌ | ✅ |
| Excluir alien | ❌ | ✅ |

---

## GET `/api/me`
Retorna o usuário da sessão (ou `null`) e o token CSRF. Deve ser a primeira chamada do cliente.

**Resposta 200**
```json
{ "status": "success", "data": { "user": null, "csrf_token": "9f2c...e1" } }
```

## POST `/api/login`
Cabeçalhos: `Content-Type: application/json`, `X-CSRF-Token: <token>`

| Campo | Tipo | Regras |
|---|---|---|
| `email` | string | obrigatório, e-mail válido, ≤ 150 |
| `senha` | string | obrigatório, ≤ 128 |

**Requisição**
```json
{ "email": "admnistrador@omnitrix.com", "senha": "@admin123" }
```
**Resposta 200** (o ID da sessão é regenerado e o token CSRF é rotacionado)
```json
{ "status": "success",
  "data": { "user": { "id": 1, "nome": "Administrador Omnitrix", "email": "admnistrador@omnitrix.com", "perfil": "administrador" },
            "csrf_token": "b7d1...42" } }
```
**Erros:** `400` campos inválidos · `401` `E-mail ou senha incorretos.` · `403` CSRF · `429` após 5 falhas em 10 min.

## POST `/api/register`
Cria uma conta de **visitante** e já inicia a sessão. Cabeçalhos: `Content-Type: application/json`, `X-CSRF-Token`.

| Campo | Tipo | Regras |
|---|---|---|
| `nome` | string | obrigatório, 2–100 (tags HTML removidas) |
| `email` | string | obrigatório, e-mail válido, ≤ 150, único (gravado em minúsculas) |
| `senha` | string | obrigatória, 8–72 caracteres, com ao menos uma letra e um número |

Qualquer campo `perfil` enviado é **ignorado**: não existe forma de criar um administrador pela API.

**Requisição**
```json
{ "nome": "Maria Silva", "email": "maria@exemplo.com", "senha": "minhasenha1" }
```
**Resposta 201** (ID de sessão regenerado, token CSRF rotacionado; a senha nunca é devolvida)
```json
{ "status": "success",
  "data": { "user": { "id": 2, "nome": "Maria Silva", "email": "maria@exemplo.com", "perfil": "visitante" },
            "csrf_token": "d41e...7c" } }
```
**Erros:** `400` campos inválidos (com `errors` por campo) · `403` CSRF · `409` `Este e-mail já está cadastrado.` · `429` mais de 10 cadastros em 10 min por IP.

## POST `/api/logout`
Exige login e `X-CSRF-Token`. Destrói a sessão.
```json
{ "status": "success", "data": { "mensagem": "Sessão encerrada.", "csrf_token": "c3a0...88" } }
```
**Erros:** `401` sem login · `403` CSRF.

## GET `/api/eras`
```json
{ "status": "success", "data": [
  { "id": 1, "slug": "classico", "nome": "Clássico (2005)", "descricao": "O primeiro Omnitrix..." } ] }
```

## GET `/api/aliens`
| Parâmetro (query) | Tipo | Padrão | Descrição |
|---|---|---|---|
| `pagina` | int ≥ 1 | 1 | página |
| `por_pagina` | int 1–100 | 100 | itens por página |
| `era` | string | — | filtra pelo `slug` da era (ex.: `classico`) |

Exemplo: `GET /api/aliens?era=classico&pagina=1&por_pagina=10`
```json
{ "status": "success",
  "data": [ { "id": 1, "nome": "Chama", "especie": "Pyronita", "planeta_origem": "Pyros",
              "descricao": "Foi o primeiro alienígena no qual Ben se transformou na história.",
              "habilidades": ["Pirocinese", "Voo"],
              "imagem_url": "assets/aliens/ChamaCL.png", "imagem_alt": "Ilustração do alienígena Chama",
              "era_id": 1, "era_slug": "classico", "era_nome": "Clássico (2005)",
              "usuario_id": 1, "criado_em": "2026-10-13 10:00:00" } ],
  "meta": { "pagina": 1, "por_pagina": 10, "total": 19, "total_paginas": 2 } }
```
**Erros:** `400` parâmetro inválido ou era inexistente.

## GET `/api/aliens/{id}`
`200` com o objeto do alien (mesmo formato acima) · `400` id não numérico · `404` `Alien não encontrado.`

## POST `/api/aliens` — somente administrador
Cabeçalhos: `Content-Type: application/json`, `X-CSRF-Token`.

| Campo | Tipo | Regras |
|---|---|---|
| `nome` | string | obrigatório, 2–100 |
| `especie` | string | obrigatório, 2–100 |
| `planeta_origem` | string | obrigatório, 2–100 |
| `descricao` | string | obrigatório, 10–2000 |
| `habilidades` | array de string | 1–12 itens, cada um 2–200 |
| `era_id` | int | obrigatório, deve existir |
| `imagem_url` | string | opcional; `assets/...(png/jpg/gif/webp/svg)` ou URL http(s), ≤ 255 |
| `imagem_alt` | string | opcional, ≤ 200 |

Todo texto é sanitizado (UTF-8 válido, tags HTML removidas, espaços normalizados).

**Requisição**
```json
{ "nome": "Cipó Selvagem", "especie": "Floraliano", "planeta_origem": "Flors",
  "descricao": "Prendeu uma nave inimiga usando raízes gigantes.",
  "habilidades": ["Controle de plantas", "Regeneração"], "era_id": 2 }
```
**Resposta 201:** objeto do alien criado (com `id`, `usuario_id`, `criado_em`).
**Erros:** `400` (com `errors` por campo) · `401` · `403` (visitante ou CSRF).

## PUT `/api/aliens/{id}` — somente administrador
Mesmo corpo do POST (substituição completa). `200` com o alien atualizado ·
`400` · `401` · `403` · `404`.

## DELETE `/api/aliens/{id}` — somente administrador
```json
{ "status": "success", "data": { "id": 61, "mensagem": "Alien excluído com sucesso." } }
```
`401` · `403` · `404`.

---

## Exemplo completo com curl

```bash
BASE=http://localhost/enciclopedia-aliens/backend

# 1) token CSRF + cookie de sessão
TOKEN=$(curl -s -c cj.txt $BASE/api/me | sed 's/.*"csrf_token":"\([^"]*\)".*/\1/')

# 2) login
curl -s -b cj.txt -c cj.txt -X POST $BASE/api/login \
  -H "Content-Type: application/json" -H "X-CSRF-Token: $TOKEN" \
  -d '{"email":"admnistrador@omnitrix.com","senha":"@admin123"}'
# (o login devolve um NOVO csrf_token: use-o nas próximas chamadas)
```
