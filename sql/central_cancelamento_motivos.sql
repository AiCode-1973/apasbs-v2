-- APASBS v2 — Cadastro dos motivos de cancelamento
-- Execute DEPOIS de sql/central_agenda_marcacoes.sql:
--   mysql -u usuario -p apasbs < sql/central_cancelamento_motivos.sql
--
-- Só é necessário para quem já rodou o script de marcações antes desta
-- versão: a tabela existia sem `ordem` e `ativo`. Numa instalação nova ela
-- já nasce completa e este script não faz nada.
--
-- Pode ser executado mais de uma vez sem efeito colateral.

SET NAMES utf8mb4;

-- ── Colunas novas, adicionadas só se ainda não existirem ────────────────
-- ordem → controla a sequência em que as mensagens aparecem para a atendente
-- ativo → tira a mensagem da lista sem apagar o histórico que já a usou

SET @sql := (
    SELECT IF(COUNT(*) = 0,
        'ALTER TABLE central_cancelamento_motivos
            ADD COLUMN ordem SMALLINT UNSIGNED NOT NULL DEFAULT 0 AFTER nome',
        'DO 0')
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'central_cancelamento_motivos'
      AND COLUMN_NAME  = 'ordem'
);
PREPARE p FROM @sql; EXECUTE p; DEALLOCATE PREPARE p;

SET @sql := (
    SELECT IF(COUNT(*) = 0,
        'ALTER TABLE central_cancelamento_motivos
            ADD COLUMN ativo TINYINT(1) NOT NULL DEFAULT 1 AFTER ordem',
        'DO 0')
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'central_cancelamento_motivos'
      AND COLUMN_NAME  = 'ativo'
);
PREPARE p FROM @sql; EXECUTE p; DEALLOCATE PREPARE p;

SET @sql := (
    SELECT IF(COUNT(*) = 0,
        'ALTER TABLE central_cancelamento_motivos
            ADD COLUMN updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
                ON UPDATE CURRENT_TIMESTAMP',
        'DO 0')
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'central_cancelamento_motivos'
      AND COLUMN_NAME  = 'updated_at'
);
PREPARE p FROM @sql; EXECUTE p; DEALLOCATE PREPARE p;

SET @sql := (
    SELECT IF(COUNT(*) = 0,
        'ALTER TABLE central_cancelamento_motivos
            ADD KEY idx_cancelamento_motivo_ordem (ativo, ordem)',
        'DO 0')
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'central_cancelamento_motivos'
      AND INDEX_NAME   = 'idx_cancelamento_motivo_ordem'
);
PREPARE p FROM @sql; EXECUTE p; DEALLOCATE PREPARE p;

-- ── Ordem inicial para o que já estava cadastrado ───────────────────────
-- Quem já tinha mensagens configuradas fica com todas em ordem 0, o que faz
-- a lista cair na ordem alfabética. Isto numera na ordem alfabética atual
-- (10, 20, 30…) para dar espaço de encaixar mensagens novas no meio depois.
SET @n := 0;
UPDATE central_cancelamento_motivos
   SET ordem = (@n := @n + 10)
 WHERE ordem = 0
 ORDER BY nome;

-- ── Registro do módulo ──────────────────────────────────────────────────
INSERT INTO modulos (nome, slug, icone) VALUES
    ('Cancelamentos', 'cancelamentos', 'cancelamentos')
ON DUPLICATE KEY UPDATE nome = VALUES(nome), icone = VALUES(icone);

-- ── Conferência ─────────────────────────────────────────────────────────
SELECT id, nome, ordem, ativo FROM central_cancelamento_motivos ORDER BY ordem, nome;
