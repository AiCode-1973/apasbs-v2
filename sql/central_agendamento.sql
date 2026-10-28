-- APASBS v2 — Central de Agendamento
-- Execute uma vez no banco:
--   mysql -u usuario -p apasbs < sql/central_agendamento.sql
--
-- Script aditivo: não altera nenhuma tabela existente e pode ser executado
-- mais de uma vez sem efeito colateral.

-- ── Motivos de fechamento de atendimento ────────────────────────────────
CREATE TABLE IF NOT EXISTS central_motivos (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    codigo     VARCHAR(30)  NOT NULL,
    nome       VARCHAR(150) NOT NULL,
    descricao  VARCHAR(500) NULL,
    ativo      TINYINT(1)   NOT NULL DEFAULT 1,
    created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uk_central_motivo_codigo (codigo),
    UNIQUE KEY uk_central_motivo_nome   (nome)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Fechamento mensal da central ────────────────────────────────────────
-- Uma linha por competência (mês/ano). A chave única impede lançar o mesmo
-- mês duas vezes.
CREATE TABLE IF NOT EXISTS central_fechamentos (
    id                 INT UNSIGNED     AUTO_INCREMENT PRIMARY KEY,
    ano                SMALLINT UNSIGNED NOT NULL,
    mes                TINYINT UNSIGNED  NOT NULL,
    total_atendimentos INT UNSIGNED      NOT NULL DEFAULT 0,
    observacao         VARCHAR(500)      NULL,
    usuario_id         INT               NULL,
    created_at         TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at         TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uk_central_competencia (ano, mes),
    KEY idx_central_ano (ano),

    CONSTRAINT fk_central_usuario FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Quantidade de atendimentos por motivo, em cada competência ──────────
-- Só existe linha para motivo com quantidade lançada. A soma destas linhas
-- é conferida contra o total do fechamento, mas não o substitui.
CREATE TABLE IF NOT EXISTS central_fechamento_motivos (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    fechamento_id INT UNSIGNED NOT NULL,
    motivo_id     INT UNSIGNED NOT NULL,
    quantidade    INT UNSIGNED NOT NULL DEFAULT 0,

    UNIQUE KEY uk_fech_motivo (fechamento_id, motivo_id),
    KEY idx_fech_motivo_motivo (motivo_id),

    CONSTRAINT fk_fm_fechamento FOREIGN KEY (fechamento_id)
        REFERENCES central_fechamentos(id) ON DELETE CASCADE,
    CONSTRAINT fk_fm_motivo FOREIGN KEY (motivo_id)
        REFERENCES central_motivos(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Registro dos módulos ────────────────────────────────────────────────
-- Necessário para eles aparecerem na matriz de permissões. O sistema também
-- faz isso sozinho no primeiro acesso (Modules::sync).
INSERT INTO modulos (nome, slug, icone) VALUES
    ('Fechamentos', 'fechamentos', 'fechamentos'),
    ('Motivos',     'motivos',     'motivos')
ON DUPLICATE KEY UPDATE nome = VALUES(nome), icone = VALUES(icone);
