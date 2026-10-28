-- APASBS v2 — Data da autorização pelo convênio
-- Execute:
--   mysql -u usuario -p apasbs < sql/amb_autorizacoes_data_autorizacao.sql
--
-- Script ADITIVO e reexecutável: o ALTER só roda se a coluna ainda não
-- existir, então rodar duas vezes não dá erro.

SET NAMES utf8mb4;

-- ── Data da autorização ─────────────────────────────────────────────────
-- O dia em que o convênio liberou o exame — não o dia em que a paciente
-- pediu (data_solicitacao), nem o dia em que o exame foi marcado
-- (data_agendada). São três momentos diferentes, e só com os três é
-- possível dizer quanto tempo a operadora leva para responder e quanto
-- tempo a autorização fica parada antes de virar exame marcado.
--
-- NULL quando ainda não há autorização, e também quando ela foi concedida
-- mas a data não veio junto — é digitada à mão depois que o convênio
-- responde, e nem sempre quem registra tem o dia exato na hora.
--
-- Fica logo depois de data_pedido: na tela e na tabela, as datas seguem a
-- ordem em que acontecem — solicitação, pedido médico, autorização,
-- agendamento.
SET @sql := (
    SELECT IF(COUNT(*) = 0,
        'ALTER TABLE amb_autorizacoes
            ADD COLUMN data_autorizacao DATE NULL AFTER data_pedido,
            ADD KEY idx_aut_autorizacao (data_autorizacao)',
        'DO 0')
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'amb_autorizacoes'
      AND COLUMN_NAME  = 'data_autorizacao'
);
PREPARE p FROM @sql; EXECUTE p; DEALLOCATE PREPARE p;

-- O que já estava gravado continua sem data: inventar uma seria pior que
-- deixar em branco. Quem precisar da data de uma autorização antiga abre
-- o registro e preenche.
SELECT COUNT(*)                                        AS autorizacoes,
       SUM(status IN ('autorizado','agendado'))         AS autorizadas,
       SUM(data_autorizacao IS NOT NULL)                AS com_data_autorizacao
  FROM amb_autorizacoes;
