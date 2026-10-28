-- APASBS v2 — Agenda da Central (Colono / Combo / EDA)
-- Execute uma vez no banco:
--   mysql -u usuario -p apasbs < sql/central_agenda.sql
--
-- Script ADITIVO: não altera nenhuma tabela existente e pode ser executado
-- mais de uma vez sem efeito colateral.

-- Garante que os acentos cheguem certos, seja pelo terminal ou pelo phpMyAdmin.
SET NAMES utf8mb4;

-- ── Médicos que atendem na agenda da central ────────────────────────────
-- Cadastro próprio da central. Não substitui nem mexe no campo de médico
-- usado no módulo de Cirurgias.
--   cor → 1..6, índice da paleta categórica (validada para daltonismo).
--         A cor nunca identifica sozinha: o nome sempre aparece junto.
CREATE TABLE IF NOT EXISTS central_medicos (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome          VARCHAR(150) NOT NULL,
    crm           VARCHAR(20)  NULL,
    especialidade VARCHAR(120) NULL,
    cor           TINYINT UNSIGNED NOT NULL DEFAULT 1,
    ativo         TINYINT(1)   NOT NULL DEFAULT 1,
    created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uk_central_medico_nome (nome),
    KEY idx_central_medico_ativo (ativo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Tipos de exame agendáveis (Combo, Colono, EDA, …) ───────────────────
-- Cada tipo tem contagem independente dentro do mesmo bloco.
CREATE TABLE IF NOT EXISTS central_exames (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    codigo     VARCHAR(20)  NOT NULL,
    nome       VARCHAR(120) NOT NULL,
    descricao  VARCHAR(300) NULL,
    ordem      SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    ativo      TINYINT(1)   NOT NULL DEFAULT 1,
    created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uk_central_exame_codigo (codigo),
    UNIQUE KEY uk_central_exame_nome   (nome),
    KEY idx_central_exame_ordem (ordem)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Blocos de agenda ────────────────────────────────────────────────────
-- Um bloco = um médico, num dia, num turno. Lançado dia a dia — não há
-- regra semanal automática, porque a escala muda todo mês.
CREATE TABLE IF NOT EXISTS central_agenda_blocos (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    medico_id  INT UNSIGNED NOT NULL,
    data       DATE         NOT NULL,
    turno      ENUM('manha','tarde') NOT NULL,
    observacao VARCHAR(300) NULL,
    usuario_id INT          NULL,
    created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uk_bloco (medico_id, data, turno),
    KEY idx_bloco_data (data),

    CONSTRAINT fk_bloco_medico FOREIGN KEY (medico_id)
        REFERENCES central_medicos(id) ON DELETE RESTRICT,
    CONSTRAINT fk_bloco_usuario FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Vagas por tipo de exame dentro do bloco ─────────────────────────────
-- capacidade = quanto cabe   ·   ocupadas = quanto já foi marcado
CREATE TABLE IF NOT EXISTS central_agenda_vagas (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    bloco_id   INT UNSIGNED NOT NULL,
    exame_id   INT UNSIGNED NOT NULL,
    capacidade SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    ocupadas   SMALLINT UNSIGNED NOT NULL DEFAULT 0,

    UNIQUE KEY uk_vaga (bloco_id, exame_id),
    KEY idx_vaga_exame (exame_id),

    CONSTRAINT fk_vaga_bloco FOREIGN KEY (bloco_id)
        REFERENCES central_agenda_blocos(id) ON DELETE CASCADE,
    CONSTRAINT fk_vaga_exame FOREIGN KEY (exame_id)
        REFERENCES central_exames(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Dias bloqueados (feriado, recesso, manutenção) ──────────────────────
-- O dia continua visível no calendário, hachurado, com o motivo escrito.
CREATE TABLE IF NOT EXISTS central_agenda_bloqueios (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    data       DATE         NOT NULL,
    motivo     VARCHAR(150) NOT NULL,
    usuario_id INT          NULL,
    created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uk_bloqueio_data (data),

    CONSTRAINT fk_bloqueio_usuario FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Tipos de exame padrão ───────────────────────────────────────────────
INSERT INTO central_exames (codigo, nome, descricao, ordem) VALUES
    ('COMBO',  'Combo',        'Colonoscopia + endoscopia na mesma sessão', 1),
    ('COLONO', 'Colonoscopia', 'Colonoscopia isolada',                      2),
    ('EDA',    'EDA',          'Endoscopia digestiva alta',                 3)
ON DUPLICATE KEY UPDATE nome = VALUES(nome), ordem = VALUES(ordem);

-- ── Registro dos módulos ────────────────────────────────────────────────
-- Necessário para eles aparecerem na matriz de permissões. O sistema também
-- faz isso sozinho no primeiro acesso (Modules::sync).
INSERT INTO modulos (nome, slug, icone) VALUES
    ('Agenda',  'agenda',  'agenda'),
    ('Médicos', 'medicos', 'medicos'),
    ('Exames',  'exames',  'exames')
ON DUPLICATE KEY UPDATE nome = VALUES(nome), icone = VALUES(icone);
