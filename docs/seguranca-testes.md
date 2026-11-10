# Roteiro de testes de segurança (para gerar as evidências do relatório)

Execute cada teste com o sistema rodando no XAMPP e **tire um print** do resultado
(ou salve a saída em `docs/evidencias/`). O resultado esperado está descrito em cada item.

Preparação (PowerShell/Git Bash/terminal com curl):
```bash
BASE=http://localhost/enciclopedia-aliens/backend
TOKEN=$(curl -s -c cj.txt $BASE/api/me | sed 's/.*"csrf_token":"\([^"]*\)".*/\1/')
```

## 1. Acesso a rota protegida sem login
```bash
curl -i -b cj.txt -X POST $BASE/api/aliens -H "Content-Type: application/json" \
  -H "X-CSRF-Token: $TOKEN" -d '{"nome":"X"}'
```
**Esperado:** `HTTP 401` — `Autenticação necessária. Faça login para continuar.`

## 2. Acesso com perfil insuficiente (visitante)
Crie uma conta de visitante pelo cadastro (ela já fica logada) e tente adicionar um alien:
```bash
curl -s -b cj.txt -c cj.txt -X POST $BASE/api/register -H "Content-Type: application/json" \
  -H "X-CSRF-Token: $TOKEN" -d '{"nome":"Visitante Teste","email":"visitante.teste@exemplo.com","senha":"teste1234"}'
# use o csrf_token devolvido como TOKEN2
curl -i -b cj.txt -X POST $BASE/api/aliens -H "Content-Type: application/json" \
  -H "X-CSRF-Token: $TOKEN2" -d '{"nome":"X"}'
```
**Esperado:** cadastro `HTTP 201` com `"perfil":"visitante"`; depois `HTTP 403` — `Você não tem permissão para realizar esta ação.`
+ linha `[WARNING] Acesso negado` no `logs/app.log`. (Na interface: o visitante não vê os botões de adicionar/editar/excluir.)

## 3. SQL Injection
Login com payload clássico:
```bash
curl -i -b cj.txt -X POST $BASE/api/login -H "Content-Type: application/json" \
  -H "X-CSRF-Token: $TOKEN" -d '{"email":"admin@x.com'"'"' OR 1=1 -- ","senha":"qualquer"}'
```
**Esperado:** `HTTP 400` (e-mail inválido) — o texto nunca chega ao banco.
Na listagem: `curl -i "$BASE/api/aliens/1%20OR%201=1"` → `HTTP 400` (`Identificador inválido`).
E `curl -i "$BASE/api/aliens?era=x'%20OR%20'1'='1"` → `HTTP 400` (`Era não encontrada`).
**Por quê:** validação de tipo + `PDO::prepare` com parâmetros (`ATTR_EMULATE_PREPARES=false`).

## 4. XSS (armazenado e refletido)
**4a. Armazenado** — logado como administrador, cadastre (pela interface ou curl) um alien com
`nome` = `<img src=x onerror=alert(1)>Teste`.
**Esperado:** o nome é salvo como `Teste` (tags removidas por `Validator::cleanString`) e,
mesmo que algum texto com `<` chegasse ao frontend, o `app.js` o exibiria escapado (`escaparHtml`).
**4b. Refletido** — `curl -i "$BASE/api/<script>alert(1)</script>"`
**Esperado:** `HTTP 404` e a mensagem mostra `&lt;script&gt;` (nunca a tag literal).
Além disso, o JSON usa `JSON_HEX_TAG|AMP|APOS|QUOT` e o cabeçalho `X-Content-Type-Options: nosniff`.

## 5. CSRF
Logado como administrador, **sem** o cabeçalho `X-CSRF-Token`:
```bash
curl -i -b cj.txt -X POST $BASE/api/aliens -H "Content-Type: application/json" -d '{"nome":"X"}'
```
**Esperado:** `HTTP 403` — `Token CSRF inválido ou ausente.` (+ `[WARNING]` no log).
Com `-H "Origin: http://site-malicioso.com"` e token válido → `HTTP 403` (`Origem da requisição não permitida`).

## 6. Reutilização de sessão inválida / logout
```bash
curl -i -b cj.txt -X POST $BASE/api/logout -H "X-CSRF-Token: $TOKEN3"   # 200
curl -i -b cj.txt -X POST $BASE/api/aliens -H "Content-Type: application/json" \
  -H "X-CSRF-Token: $TOKEN3" -d '{"nome":"X"}'                          # usando o cookie ANTIGO
```
**Esperado:** logout `200`; a requisição seguinte com o cookie antigo → `HTTP 401` (a sessão foi invalidada
e o ID antigo descartado — `session.use_strict_mode`).

## 7. Força bruta no login
Repita 6 vezes o login com senha errada. **Esperado:** da 6ª tentativa em diante `HTTP 429`
(`Muitas tentativas de login...`) e `[WARNING] Login bloqueado...` no log.

## 8. Arquivos sensíveis
`curl -i $BASE/.env` e `curl -i $BASE/logs/app.log` → `HTTP 403` (`.htaccess`).

## 9. Tentar criar uma conta de administrador pelo cadastro
```bash
curl -i -b cj.txt -X POST $BASE/api/register -H "Content-Type: application/json" \
  -H "X-CSRF-Token: $TOKEN" -d '{"nome":"Espertinho","email":"esperto@exemplo.com","senha":"teste1234","perfil":"administrador"}'
```
**Esperado:** `HTTP 201`, porém com `"perfil":"visitante"` (o campo `perfil` é ignorado). E repetir o cadastro com
`admnistrador@omnitrix.com` → `HTTP 409` (e-mail já cadastrado).
