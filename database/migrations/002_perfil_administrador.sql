-- =============================================================================
-- Migração para quem JÁ importou a versão anterior do banco (sem perder dados).
--  * renomeia o perfil 'curador' para 'administrador'
--  * deixa UMA única conta administradora: admnistrador@omnitrix.com / @admin123
--  * remove o visitante de demonstração da versão anterior
-- Execute no phpMyAdmin (aba "Importar") ou:  mysql -u root < database/migrations/002_perfil_administrador.sql
-- =============================================================================
SET NAMES utf8mb4;
USE enciclopedia_aliens;

ALTER TABLE usuarios MODIFY perfil ENUM('curador', 'administrador', 'visitante') NOT NULL DEFAULT 'visitante';
UPDATE usuarios SET perfil = 'administrador' WHERE perfil = 'curador';
ALTER TABLE usuarios MODIFY perfil ENUM('administrador', 'visitante') NOT NULL DEFAULT 'visitante';

UPDATE usuarios
   SET nome = 'Administrador Omnitrix',
       email = 'admnistrador@omnitrix.com',
       senha = '$2y$12$hm0nXqzwWLhS63NhRkV9heV.82bsEYgd.BpDpwn5fWNN/2RNUMOJG'
 WHERE id = 1;

DELETE FROM usuarios WHERE id = 2 AND email = 'visitante@omnitrix.com';
