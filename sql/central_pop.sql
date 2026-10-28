-- APASBS v2 — POP da Central de Agendamento
-- Execute:
--   mysql -u usuario -p apasbs < sql/central_pop.sql
--   mysql -u usuario -p apasbs < sql/central_pop_conteudo.sql
--
-- Script ADITIVO. Pode ser executado mais de uma vez sem efeito colateral.

SET NAMES utf8mb4;

-- ── Procedimento operacional padrão ─────────────────────────────────────
CREATE TABLE IF NOT EXISTS central_pops (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    codigo       VARCHAR(30)  NOT NULL,
    titulo       VARCHAR(180) NOT NULL,
    categoria    VARCHAR(80)  NOT NULL DEFAULT 'Geral',
    resumo       VARCHAR(400) NULL,

    versao       VARCHAR(10)  NOT NULL DEFAULT '1.0',
    revisado_em  DATE         NULL,

    ordem        SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    ativo        TINYINT(1)   NOT NULL DEFAULT 1,

    usuario_id   INT          NULL,
    usuario_nome VARCHAR(150) NULL,

    created_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uk_pop_codigo (codigo),
    KEY idx_pop_ordem (ativo, categoria, ordem),

    CONSTRAINT fk_pop_usuario FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Blocos que formam o corpo do POP ────────────────────────────────────
-- Quatro tipos, cada um com uma função de leitura diferente:
--   texto     → parágrafo comum
--   passos    → lista numerada (uma linha do conteúdo = um passo)
--   alerta    → caixa de atenção, para o que não pode ser esquecido
--   mensagem  → modelo pronto para copiar e colar no WhatsApp
CREATE TABLE IF NOT EXISTS central_pop_blocos (
    id       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pop_id   INT UNSIGNED NOT NULL,
    tipo     ENUM('texto','passos','alerta','mensagem') NOT NULL DEFAULT 'texto',
    titulo   VARCHAR(120) NULL,
    conteudo TEXT         NOT NULL,
    ordem    SMALLINT UNSIGNED NOT NULL DEFAULT 0,

    KEY idx_bloco_pop (pop_id, ordem),

    CONSTRAINT fk_popbloco_pop FOREIGN KEY (pop_id)
        REFERENCES central_pops(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO modulos (nome, slug, icone) VALUES ('POP', 'pop', 'pop')
ON DUPLICATE KEY UPDATE nome = VALUES(nome), icone = VALUES(icone);
