-- APASBS v2 — Informações pendentes nas Autorizações de exame
-- Execute:
--   mysql -u usuario -p apasbs < sql/amb_autorizacoes_pendencia.sql
--
-- Script ADITIVO e reexecutável: cada ALTER só roda se ainda fizer falta,
-- então rodar duas vezes não dá erro.

SET NAMES utf8mb4;

-- ── A situação nova ─────────────────────────────────────────────────────
-- `status` é ENUM, então a situação nova precisa existir no banco antes de
-- existir na tela — senão o MySQL grava string vazia e o registro some dos
-- filtros sem avisar ninguém.
--
-- Entra depois de 'aguardando' porque é ali que ela acontece: o pedido foi
-- feito, e alguma informação falta para seguir.
SET @sql := (
    SELECT IF(LOCATE('pendente', COLUMN_TYPE) = 0,
        "ALTER TABLE amb_autorizacoes
            MODIFY COLUMN status
            ENUM('aguardando','pendente','autorizado','negado','agendado')
            NOT NULL DEFAULT 'aguardando'",
        'DO 0')
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'amb_autorizacoes'
      AND COLUMN_NAME  = 'status'
);
PREPARE p FROM @sql; EXECUTE p; DEALLOCATE PREPARE p;

-- ── O que está pendente ─────────────────────────────────────────────────
-- Campo próprio, separado de `observacao`.
--
-- A observação é recado ("a paciente prefere pela manhã") e não muda
-- situação nenhuma. Esta coluna é outra coisa: é o que falta para o pedido
-- andar. Misturar as duas faria todo recado antigo virar pendência na
-- primeira vez que alguém salvasse o registro — reescrevendo o passado por
-- causa de um campo que mudou de significado.
--
-- NULL quando não há pendência, que é o caso da esmagadora maioria.
SET @sql := (
    SELECT IF(COUNT(*) = 0,
        'ALTER TABLE amb_autorizacoes
            ADD COLUMN info_pendente VARCHAR(400) NULL AFTER motivo_negativa',
        'DO 0')
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'amb_autorizacoes'
      AND COLUMN_NAME  = 'info_pendente'
);
PREPARE p FROM @sql; EXECUTE p; DEALLOCATE PREPARE p;

-- Nada é convertido: nenhum registro existente vira pendência sozinho. As
-- observações que já estão lá continuam sendo observações.
SELECT COUNT(*)                          AS autorizacoes,
       SUM(status = 'pendente')          AS com_pendencia,
       SUM(observacao IS NOT NULL
           AND observacao <> '')         AS com_observacao
  FROM amb_autorizacoes;
