# Migrações de banco

Scripts SQL numerados, aplicados em ordem sobre um banco que já foi criado com uma versão anterior do `database.sql`.

| Arquivo | O que faz |
|---|---|
| `001_esquema_inicial.md` | descreve o esquema inicial (criado pelo `database.sql`) |
| `002_perfil_administrador.sql` | renomeia o perfil `curador` para `administrador`, deixa uma única conta administradora e remove o visitante de demonstração |

Como aplicar: phpMyAdmin → aba **Importar** → escolher o arquivo → **Executar**
(ou `mysql -u root < database/migrations/002_perfil_administrador.sql`).
Quem está começando do zero não precisa de migrações: basta importar o `database.sql`.
