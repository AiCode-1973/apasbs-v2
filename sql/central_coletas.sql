-- APASBS v2 — Coleta automática do ReciclaLead
-- Execute uma vez no banco:
--   mysql -u usuario -p apasbs < sql/central_coletas.sql
--
-- Script ADITIVO: não altera nenhuma tabela existente e pode ser executado
-- mais de uma vez sem efeito colateral.
--
-- O que o robô traz NÃO entra direto no fechamento. Cai aqui, numa área de
-- espera, e alguém confere e confirma. Coletor de sistema de terceiro erra:
-- o fornecedor muda um rótulo, renomeia um motivo, e o número entra torto.
-- Número errado no fechamento é pior que número nenhum.

SET NAMES utf8mb4;

-- ── A coleta ────────────────────────────────────────────────────────────
-- Uma linha por execução do robô para uma competência.
--
--   status  pendente   → esperando conferência
--           confirmada → já virou fechamento
--           descartada → alguém olhou e recusou
--           erro       → o robô não conseguiu (login, rede, soma que não bate)
--
-- resposta_bruta guarda o JSON como veio. Quando um número parecer estranho
-- seis meses depois, é ele que diz se o problema foi do robô ou da origem.
CREATE TABLE IF NOT EXISTS central_coletas (
    id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ano                SMALLINT UNSIGNED NOT NULL,
    mes                TINYINT UNSIGNED  NOT NULL,
    origem             VARCHAR(40)   NOT NULL DEFAULT 'reciclalead',
    status             ENUM('pendente','confirmada','descartada','erro') NOT NULL DEFAULT 'pendente',

    total_atendimentos INT UNSIGNED  NOT NULL DEFAULT 0,
    soma_motivos       INT UNSIGNED  NOT NULL DEFAULT 0,
    pendentes          INT UNSIGNED  NULL,
    demanda_ativa      INT UNSIGNED  NULL,
    demanda_receptiva  INT UNSIGNED  NULL,
    contatos_total     INT UNSIGNED  NULL,
    contatos_novos     INT UNSIGNED  NULL,
    tma_minutos        INT UNSIGNED  NULL,
    tme_minutos        INT UNSIGNED  NULL,

    mensagem           VARCHAR(400)  NULL,
    resposta_bruta     MEDIUMTEXT    NULL,

    coletado_em        TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    confirmado_em      TIMESTAMP     NULL,
    usuario_id         INT           NULL,
    fechamento_id      INT UNSIGNED  NULL,

    KEY idx_coleta_competencia (ano, mes, status),
    KEY idx_coleta_status (status, coletado_em),

    CONSTRAINT fk_coleta_usuario FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id) ON DELETE SET NULL,
    CONSTRAINT fk_coleta_fechamento FOREIGN KEY (fechamento_id)
        REFERENCES central_fechamentos(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Os números de cada coleta ───────────────────────────────────────────
-- Uma tabela só para os três recortes, separados por `tipo`. São a mesma
-- forma — rótulo e quantidade — e guardá-los juntos evita três tabelas
-- iguais e três telas iguais.
--
--   motivo    → motivo de fechamento do atendimento (vira fechamento)
--   fila      → Consulta, Exames, Procedimentos, Autorização
--   atendente → quantos cada pessoa fechou no período
CREATE TABLE IF NOT EXISTS central_coleta_itens (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    coleta_id  INT UNSIGNED NOT NULL,
    tipo       ENUM('motivo','fila','atendente') NOT NULL,
    rotulo     VARCHAR(150) NOT NULL,
    quantidade INT UNSIGNED NOT NULL DEFAULT 0,
    percentual DECIMAL(6,2) NULL,

    -- Para o tipo `motivo`: com qual motivo cadastrado o rótulo casou.
    -- NULL quer dizer "ainda não casou" — a tela de conferência pergunta.
    motivo_id  INT UNSIGNED NULL,

    UNIQUE KEY uk_coleta_item (coleta_id, tipo, rotulo),
    KEY idx_coleta_item_tipo (coleta_id, tipo, quantidade),

    CONSTRAINT fk_coleta_item_coleta FOREIGN KEY (coleta_id)
        REFERENCES central_coletas(id) ON DELETE CASCADE,
    CONSTRAINT fk_coleta_item_motivo FOREIGN KEY (motivo_id)
        REFERENCES central_motivos(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── De/para dos motivos ─────────────────────────────────────────────────
-- O rótulo que vem do ReciclaLead nem sempre é igual ao nome cadastrado
-- aqui: "Dúvidas " vem com espaço no fim, "Agendamento Concluido" sem
-- acento. Quando alguém casa os dois na tela de conferência, o casamento
-- fica guardado e a próxima coleta já vem resolvida.
CREATE TABLE IF NOT EXISTS central_coleta_mapa (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    origem     VARCHAR(40)  NOT NULL DEFAULT 'reciclalead',
    rotulo     VARCHAR(150) NOT NULL,
    motivo_id  INT UNSIGNED NOT NULL,
    created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY uk_coleta_mapa (origem, rotulo),

    CONSTRAINT fk_coleta_mapa_motivo FOREIGN KEY (motivo_id)
        REFERENCES central_motivos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Registro do módulo ──────────────────────────────────────────────────
INSERT INTO modulos (nome, slug, icone) VALUES
    ('Coletas', 'coletas', 'coletas')
ON DUPLICATE KEY UPDATE nome = VALUES(nome), icone = VALUES(icone);

SELECT
    (SELECT COUNT(*) FROM central_coletas)      AS coletas,
    (SELECT COUNT(*) FROM central_coleta_itens) AS itens,
    (SELECT COUNT(*) FROM central_coleta_mapa)  AS de_para;
