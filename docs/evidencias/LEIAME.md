# Evidências (gerar e salvar aqui)

| Evidência | Como gerar |
|---|---|
| Resultado dos testes automatizados | `cd backend && php tests/run.php > ../docs/evidencias/testes-automatizados.txt` (ou `vendor/bin/phpunit --testdox`) |
| Capturas dos testes de segurança | seguir `docs/seguranca-testes.md` e salvar os prints |
| Amostra dos logs | copiar trechos de `backend/logs/app.log` após usar o sistema |
| Histórico Git | `git log --oneline --graph --all > docs/evidencias/git-log.txt` |
| Exportação do banco | `mysqldump -u root enciclopedia_aliens > docs/evidencias/dump.sql` |
