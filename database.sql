-- =============================================================================
-- Enciclopédia Intergaláctica (Omnitrix Codex) — Trabalho Individual II (SCOM)
-- Script de criação do banco de dados (MySQL/MariaDB — XAMPP)
--
-- Como executar (phpMyAdmin): aba "Importar" -> escolher este arquivo -> Executar.
-- Como executar (terminal):   mysql -u root < database.sql
--
-- O script pode ser executado várias vezes: ele recria as tabelas do zero
-- (DROP TABLE IF EXISTS) e repovoa os dados de exemplo.
--
-- Conta do ADMINISTRADOR (única; senha gravada apenas como hash bcrypt):
--   admnistrador@omnitrix.com  /  @admin123   (perfil: administrador)
-- Qualquer outra conta é criada pelo cadastro do site e tem perfil 'visitante'.
-- =============================================================================

SET NAMES utf8mb4;

CREATE DATABASE IF NOT EXISTS enciclopedia_aliens
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE enciclopedia_aliens;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS aliens;
DROP TABLE IF EXISTS eras;
DROP TABLE IF EXISTS usuarios;
SET FOREIGN_KEY_CHECKS = 1;

-- -----------------------------------------------------------------------------
-- 1. usuarios: autenticação e perfis de acesso
-- -----------------------------------------------------------------------------
CREATE TABLE usuarios (
  id        INT AUTO_INCREMENT PRIMARY KEY,
  nome      VARCHAR(100) NOT NULL,
  email     VARCHAR(150) NOT NULL UNIQUE,
  senha     VARCHAR(255) NOT NULL COMMENT 'Hash gerado por password_hash() (bcrypt)',
  perfil    ENUM('administrador', 'visitante') NOT NULL DEFAULT 'visitante',
  criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 2. eras: categorização temporal do catálogo (as 4 eras do Omnitrix)
--    slug = identificador estável usado pelo frontend para escolher o visual
-- -----------------------------------------------------------------------------
CREATE TABLE eras (
  id        INT AUTO_INCREMENT PRIMARY KEY,
  slug      VARCHAR(50)  NOT NULL UNIQUE,
  nome      VARCHAR(100) NOT NULL UNIQUE,
  descricao TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 3. aliens: ENTIDADE PRINCIPAL (CRUD) — cada alien pertence a uma era e foi
--    cadastrado por um usuário (FK para usuarios).
--    habilidades = lista de textos em JSON (ex.: ["Pirocinese","Voo"])
--    descricao   = feito marcante / história do alien
-- -----------------------------------------------------------------------------
CREATE TABLE aliens (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  nome           VARCHAR(100) NOT NULL,
  especie        VARCHAR(100) NOT NULL,
  planeta_origem VARCHAR(100) NOT NULL,
  descricao      TEXT NOT NULL,
  habilidades    TEXT NOT NULL,
  imagem_url     VARCHAR(255) NULL,
  imagem_alt     VARCHAR(200) NULL,
  era_id         INT NOT NULL,
  usuario_id     INT NOT NULL,
  criado_em      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_aliens_eras
    FOREIGN KEY (era_id) REFERENCES eras(id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_aliens_usuarios
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  INDEX idx_aliens_era_id (era_id),
  INDEX idx_aliens_usuario_id (usuario_id),
  INDEX idx_aliens_nome (nome)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;