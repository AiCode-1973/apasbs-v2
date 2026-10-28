-- APASBS v2 — Marcações da agenda (quem marcou, para qual paciente)
-- Execute DEPOIS de sql/central_agenda.sql:
--   mysql -u usuario -p apasbs < sql/central_agenda_marcacoes.sql
--
-- Script ADITIVO em relação às tabelas de outros módulos. Pode ser executado
-- mais de uma vez sem efeito colateral.

-- Garante que os acentos cheguem certos, seja pelo terminal ou pelo phpMyAdmin.
SET NAMES utf8mb4;

-- ── Motivos de cancelamento ─────────────────────────────────────────────
-- Lista própria desta seção. NÃO tem relação com os "Motivos de fechamento
-- de atendimento" (central_motivos) — são coisas diferentes e ficam separadas.
--
-- A atendente digita o motivo na hora de cancelar; se for um motivo novo, ele
-- entra aqui sozinho e passa a ser sugerido nas próximas vezes. A colação
-- utf8mb4_unicode_ci é insensível a maiúsculas, então "Paciente desistiu" e
-- "paciente desistiu" contam como o mesmo motivo.
CREATE TABLE IF NOT EXISTS central_cancelamento_motivos (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome       VARCHAR(150) NOT NULL,
    ordem      SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    ativo      TINYINT(1)   NOT NULL DEFAULT 1,
    usuario_id INT          NULL,
    created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uk_cancelamento_motivo_nome (nome),
    KEY idx_cancelamento_motivo_ordem (ativo, ordem),

    CONSTRAINT fk_cancmotivo_usuario FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Marcações ───────────────────────────────────────────────────────────
-- Uma linha por exame marcado. É esta contagem que preenche o "marcadas" de
-- cada bloco — ninguém digita mais esse número à mão.
--
-- atendente_nome e cancelado_nome guardam o nome por escrito, além do id.
-- Assim, se o usuário for removido do sistema um dia, o registro continua
-- dizendo quem marcou e quem cancelou.
CREATE TABLE IF NOT EXISTS central_agenda_marcacoes (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    bloco_id       INT UNSIGNED NOT NULL,
    exame_id       INT UNSIGNED NOT NULL,

    paciente_cpf   CHAR(11)     NULL,
    paciente_nome  VARCHAR(150) NOT NULL,
    observacao     VARCHAR(300) NULL,

    status         ENUM('marcada','cancelada') NOT NULL DEFAULT 'marcada',

    atendente_id   INT          NULL,
    atendente_nome VARCHAR(150) NOT NULL,

    motivo_id      INT UNSIGNED NULL,
    cancelado_id   INT          NULL,
    cancelado_nome VARCHAR(150) NULL,
    cancelado_em   DATETIME     NULL,

    created_at     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    KEY idx_marcacao_bloco    (bloco_id, exame_id, status),
    KEY idx_marcacao_status   (status),
    KEY idx_marcacao_paciente (paciente_nome),
    KEY idx_marcacao_cpf      (paciente_cpf),

    CONSTRAINT fk_marcacao_bloco FOREIGN KEY (bloco_id)
        REFERENCES central_agenda_blocos(id) ON DELETE CASCADE,
    CONSTRAINT fk_marcacao_exame FOREIGN KEY (exame_id)
        REFERENCES central_exames(id) ON DELETE RESTRICT,
    CONSTRAINT fk_marcacao_motivo FOREIGN KEY (motivo_id)
        REFERENCES central_cancelamento_motivos(id) ON DELETE SET NULL,
    CONSTRAINT fk_marcacao_atendente FOREIGN KEY (atendente_id)
        REFERENCES usuarios(id) ON DELETE SET NULL,
    CONSTRAINT fk_marcacao_cancelado FOREIGN KEY (cancelado_id)
        REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Aposentadoria do contador digitado ──────────────────────────────────
-- A coluna central_agenda_vagas.ocupadas era digitada à mão. Agora o número
-- de marcadas é contado das marcações, então a coluna sai — deixá-la no banco
-- seria manter dois números que podem discordar.
--
-- Antes de remover, avisa se alguém já tinha digitado alguma coisa lá.
-- Se o número vier maior que zero, esses lançamentos precisam ser refeitos
-- como marcações (o contador não sabe o nome do paciente, então não dá para
-- convertê-los sozinho).
SET @conferir := (
    SELECT IF(
        COUNT(*) > 0,
        'SELECT COUNT(*) AS contadores_digitados_perdidos FROM central_agenda_vagas WHERE ocupadas > 0',
        'SELECT 0 AS contadores_digitados_perdidos'
    )
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'central_agenda_vagas'
      AND COLUMN_NAME  = 'ocupadas'
);
PREPARE conferencia FROM @conferir;
EXECUTE conferencia;
DEALLOCATE PREPARE conferencia;

SET @sql := (
    SELECT IF(
        COUNT(*) > 0,
        'ALTER TABLE central_agenda_vagas DROP COLUMN ocupadas',
        'DO 0'
    )
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'central_agenda_vagas'
      AND COLUMN_NAME  = 'ocupadas'
);
PREPARE apagar_coluna FROM @sql;
EXECUTE apagar_coluna;
DEALLOCATE PREPARE apagar_coluna;
