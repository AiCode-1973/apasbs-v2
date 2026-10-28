-- APASBS v2 — Códigos de exame por convênio
-- Execute:
--   mysql -u usuario -p apasbs < sql/amb_exame_codigos.sql
--
-- Depende de sql/amb_autorizacoes.sql, que cria `amb_convenios`. Os
-- convênios são os mesmos das autorizações — dois cadastros do mesmo
-- convênio divergiriam no primeiro que alguém renomeasse.
--
-- Script ADITIVO. Pode ser executado mais de uma vez sem efeito colateral.

SET NAMES utf8mb4;

-- ── O exame de um convênio ──────────────────────────────────────────────
-- Um registro por convênio + descrição. A trava é a chave única: se
-- "Bluemed / Ecocardiograma" já existe, o segundo cadastro é recusado e a
-- pessoa é mandada editar o que há — senão a mesma informação viveria em
-- duas linhas, e uma delas ficaria desatualizada sem ninguém notar.
CREATE TABLE IF NOT EXISTS amb_exame_codigos (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    convenio_id INT UNSIGNED NOT NULL,

    -- Texto livre, e não a lista de procedimentos: aqui vale a descrição do
    -- convênio ("ECO TRANSTORÁCICO C/ DOPPLER"), que quase nunca é o nome
    -- que a casa usa ("Ecocardiograma").
    descricao   VARCHAR(200) NOT NULL,
    observacao  VARCHAR(400) NULL,

    ativo       TINYINT(1)   NOT NULL DEFAULT 1,

    usuario_id   INT          NULL,
    usuario_nome VARCHAR(150) NULL,
    created_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uk_exame_convenio_desc (convenio_id, descricao),
    KEY idx_exame_descricao (descricao),
    KEY idx_exame_ativo (ativo),

    CONSTRAINT fk_exame_convenio FOREIGN KEY (convenio_id)
        REFERENCES amb_convenios(id),
    CONSTRAINT fk_exame_usuario FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Os códigos daquele exame ────────────────────────────────────────────
-- Tabela separada porque um exame tem 1..N códigos: o convênio às vezes
-- fatura o mesmo exame em vários lançamentos. Guardar "1,2,3" numa coluna
-- de texto pareceria mais simples, mas impediria procurar por um código.
--
-- `codigo` é VARCHAR e não bigint: nem todo código de convênio é TUSS, e
-- alguns têm zero à esquerda, que um número comeria.
--
-- `termo` guarda a descrição oficial no momento do cadastro. A ANS revisa
-- a Tabela 22, e o que foi combinado com o convênio precisa continuar
-- dizendo o que dizia quando foi combinado.
CREATE TABLE IF NOT EXISTS amb_exame_codigos_tuss (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    exame_id   INT UNSIGNED NOT NULL,
    codigo     VARCHAR(30)  NOT NULL,
    termo      VARCHAR(300) NULL,
    ordem      SMALLINT UNSIGNED NOT NULL DEFAULT 0,

    UNIQUE KEY uk_exame_codigo (exame_id, codigo),
    KEY idx_codigo (codigo),

    CONSTRAINT fk_codigo_exame FOREIGN KEY (exame_id)
        REFERENCES amb_exame_codigos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Registro do módulo ──────────────────────────────────────────────────
INSERT INTO modulos (nome, slug, icone) VALUES
    ('Códigos de exames', 'codigos', 'codigos')
ON DUPLICATE KEY UPDATE nome = VALUES(nome), icone = VALUES(icone);

SELECT
    (SELECT COUNT(*) FROM amb_exame_codigos)      AS exames,
    (SELECT COUNT(*) FROM amb_exame_codigos_tuss) AS codigos;
