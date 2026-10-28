-- APASBS v2 — Autorização de exames
-- Execute:
--   mysql -u usuario -p apasbs < sql/amb_autorizacoes.sql
--
-- Script ADITIVO e reexecutável. Substitui a planilha em que cada dia
-- começava um bloco novo com o cabeçalho repetido no meio da folha.

SET NAMES utf8mb4;

-- ── Convênios ───────────────────────────────────────────────────────────
-- Cadastro em vez de texto digitado: na planilha, BLUEMED, Blue Med e
-- BLUE MED virariam três convênios na hora de contar.
CREATE TABLE IF NOT EXISTS amb_convenios (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome       VARCHAR(120) NOT NULL,
    telefone   VARCHAR(40)  NULL,
    observacao VARCHAR(300) NULL,
    ativo      TINYINT(1)   NOT NULL DEFAULT 1,
    created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uk_amb_convenio_nome (nome),
    KEY idx_amb_convenio_ativo (ativo, nome)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Procedimentos ───────────────────────────────────────────────────────
-- Os exames que passam por autorização. Separado de `central_exames`
-- de propósito: aquele controla vagas na agenda do ambulatório (Colono,
-- Combo, EDA) e este é a lista do que o convênio precisa liberar.
CREATE TABLE IF NOT EXISTS amb_procedimentos (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome       VARCHAR(120) NOT NULL,
    codigo     VARCHAR(20)  NULL,           -- TUSS, quando o convênio exige
    observacao VARCHAR(300) NULL,
    ativo      TINYINT(1)   NOT NULL DEFAULT 1,
    created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uk_amb_procedimento_nome (nome),
    KEY idx_amb_procedimento_ativo (ativo, nome)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Autorizações ────────────────────────────────────────────────────────
-- Uma linha por pedido de autorização.
--
-- data_pedido é NULL de propósito: na planilha havia "SEM DATA", porque o
-- pedido médico às vezes chega sem data escrita. Guardar o texto "SEM DATA"
-- numa coluna de data seria mentir sobre o tipo do dado — vazio é vazio.
--
-- data_agendada só existe depois que o convênio autoriza; é ela que fecha
-- o ciclo do que estava pendente.
CREATE TABLE IF NOT EXISTS amb_autorizacoes (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    data_solicitacao DATE        NOT NULL,
    paciente         VARCHAR(150) NOT NULL,
    contato          VARCHAR(20)  NULL,      -- só dígitos; a máscara é da tela
    convenio_id      INT UNSIGNED NOT NULL,
    procedimento_id  INT UNSIGNED NOT NULL,
    data_pedido      DATE         NULL,      -- data do pedido médico
    data_agendada    DATE         NULL,

    status       ENUM('aguardando','autorizado','negado','agendado')
                 NOT NULL DEFAULT 'aguardando',
    senha        VARCHAR(60)  NULL,          -- número da autorização do convênio
    motivo_negativa VARCHAR(300) NULL,
    observacao   VARCHAR(400) NULL,

    usuario_id   INT          NULL,
    usuario_nome VARCHAR(150) NULL,
    created_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    KEY idx_aut_status    (status, data_solicitacao),
    KEY idx_aut_data      (data_solicitacao),
    KEY idx_aut_paciente  (paciente),
    KEY idx_aut_agendada  (data_agendada),
    KEY idx_aut_convenio  (convenio_id),
    KEY idx_aut_proc      (procedimento_id),

    CONSTRAINT fk_aut_convenio FOREIGN KEY (convenio_id)
        REFERENCES amb_convenios(id),
    CONSTRAINT fk_aut_procedimento FOREIGN KEY (procedimento_id)
        REFERENCES amb_procedimentos(id),
    CONSTRAINT fk_aut_usuario FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Registro do módulo ──────────────────────────────────────────────────
INSERT INTO modulos (nome, slug, icone) VALUES
    ('Autorizações', 'autorizacoes', 'autorizacoes')
ON DUPLICATE KEY UPDATE nome = VALUES(nome), icone = VALUES(icone);

-- ── Semente ─────────────────────────────────────────────────────────────
-- O que já estava na planilha, para a tela não abrir vazia.
INSERT INTO amb_convenios (nome) VALUES ('BLUEMED')
ON DUPLICATE KEY UPDATE nome = VALUES(nome);

INSERT INTO amb_procedimentos (nome) VALUES
    ('Ecocardiograma'),
    ('Teste ergométrico')
ON DUPLICATE KEY UPDATE nome = VALUES(nome);

SELECT
    (SELECT COUNT(*) FROM amb_convenios)     AS convenios,
    (SELECT COUNT(*) FROM amb_procedimentos) AS procedimentos,
    (SELECT COUNT(*) FROM amb_autorizacoes)  AS autorizacoes;
