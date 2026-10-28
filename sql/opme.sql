-- APASBS v2 — OPME (órteses, próteses e materiais especiais)
-- Execute uma vez no banco:
--   mysql -u usuario -p apasbs < sql/opme.sql
--
-- Script ADITIVO: não altera nenhuma tabela existente e pode ser executado
-- mais de uma vez sem efeito colateral.

SET NAMES utf8mb4;

-- ── Fornecedores ────────────────────────────────────────────────────────
-- Na planilha o fornecedor era texto digitado a cada linha, e o mesmo
-- fornecedor aparecia como "E. Tamussino" e "E.tamussino". Cadastro próprio
-- resolve isso e ainda permite somar quanto se deve a cada um.
CREATE TABLE IF NOT EXISTS opme_fornecedores (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome       VARCHAR(120) NOT NULL,
    cnpj       VARCHAR(20)  NULL,
    contato    VARCHAR(120) NULL,
    telefone   VARCHAR(30)  NULL,
    email      VARCHAR(150) NULL,
    observacao VARCHAR(300) NULL,
    ativo      TINYINT(1)   NOT NULL DEFAULT 1,
    created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uk_opme_forn_nome (nome),
    KEY idx_opme_forn_ativo (ativo, nome)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Notas fiscais ───────────────────────────────────────────────────────
-- Uma nota = um material usado numa cirurgia, de um fornecedor, para uma
-- paciente. É o registro que a planilha repetia inteiro em cada mês em que
-- caía uma parcela: 630 linhas para 263 notas.
--
--   situacao → normal, cancelada (NF cancelada pelo fornecedor) ou doacao
--              (material doado, sem cobrança). As duas últimas ficam fora
--              das somas de vencimento.
--   encaminhado_hse → a coluna "Encaminhado NF ao HSE" da planilha.
CREATE TABLE IF NOT EXISTS opme_notas (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    paciente        VARCHAR(150) NOT NULL,
    data_cirurgia   DATE         NULL,
    hospital        VARCHAR(80)  NULL,
    medico          VARCHAR(120) NULL,
    fornecedor_id   INT UNSIGNED NOT NULL,
    numero_nf       VARCHAR(40)  NOT NULL,
    valor_total     DECIMAL(12,2) NOT NULL DEFAULT 0,
    data_nf         DATE         NULL,
    total_parcelas  TINYINT UNSIGNED NOT NULL DEFAULT 1,
    situacao        ENUM('normal','cancelada','doacao') NOT NULL DEFAULT 'normal',
    encaminhado_hse TINYINT(1)   NOT NULL DEFAULT 0,
    observacao      VARCHAR(300) NULL,
    usuario_id      INT          NULL,
    created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- O mesmo fornecedor não emite a mesma nota duas vezes para a mesma
    -- paciente. Também é o que torna o script de carga reexecutável.
    UNIQUE KEY uk_opme_nota (fornecedor_id, numero_nf, paciente),
    KEY idx_opme_nota_paciente (paciente),
    KEY idx_opme_nota_cirurgia (data_cirurgia),
    KEY idx_opme_nota_situacao (situacao),

    CONSTRAINT fk_opme_nota_fornecedor FOREIGN KEY (fornecedor_id)
        REFERENCES opme_fornecedores(id) ON DELETE RESTRICT,
    CONSTRAINT fk_opme_nota_usuario FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Parcelas ────────────────────────────────────────────────────────────
-- Cada linha das abas mensais da planilha vira uma parcela. O que define a
-- parcela é o VENCIMENTO, não o rótulo "3/4": no arquivo original o rótulo
-- às vezes vinha repetido de um mês para o outro, porque a linha era
-- copiada e o número esquecido. Por isso a chave única é por vencimento.
CREATE TABLE IF NOT EXISTS opme_parcelas (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nota_id        INT UNSIGNED NOT NULL,
    numero         TINYINT UNSIGNED NOT NULL DEFAULT 1,
    vencimento     DATE         NOT NULL,
    valor          DECIMAL(12,2) NOT NULL DEFAULT 0,
    pago           TINYINT(1)   NOT NULL DEFAULT 0,
    data_pagamento DATE         NULL,
    observacao     VARCHAR(200) NULL,
    usuario_id     INT          NULL,
    created_at     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uk_opme_parcela (nota_id, vencimento),
    KEY idx_opme_parcela_venc (vencimento, pago),

    CONSTRAINT fk_opme_parcela_nota FOREIGN KEY (nota_id)
        REFERENCES opme_notas(id) ON DELETE CASCADE,
    CONSTRAINT fk_opme_parcela_usuario FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Registro do módulo ──────────────────────────────────────────────────
-- Necessário para ele aparecer na matriz de permissões. O sistema também
-- faz isso sozinho no primeiro acesso (Modules::sync).
INSERT INTO modulos (nome, slug, icone) VALUES
    ('OPME', 'opme', 'opme')
ON DUPLICATE KEY UPDATE nome = VALUES(nome), icone = VALUES(icone);

SELECT
    (SELECT COUNT(*) FROM opme_fornecedores) AS fornecedores,
    (SELECT COUNT(*) FROM opme_notas)        AS notas,
    (SELECT COUNT(*) FROM opme_parcelas)     AS parcelas;
