-- APASBS v2 — Marca "Agenda criada no TechSallus" no bloco
-- Execute DEPOIS de sql/central_agenda.sql:
--   mysql -u usuario -p apasbs < sql/central_agenda_techsallus.sql
--
-- Acrescenta uma coluna à tabela de blocos. Pode ser executado mais de uma
-- vez sem efeito colateral.

SET NAMES utf8mb4;

-- Todos os blocos que já existem entram como "ainda não criada" (0). É o
-- padrão honesto: o sistema não tem como saber o que já foi feito lá antes.
SET @sql := (
    SELECT IF(COUNT(*) = 0,
        'ALTER TABLE central_agenda_blocos
            ADD COLUMN techsallus TINYINT(1) NOT NULL DEFAULT 0 AFTER turno',
        'DO 0')
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'central_agenda_blocos'
      AND COLUMN_NAME  = 'techsallus'
);
PREPARE p FROM @sql; EXECUTE p; DEALLOCATE PREPARE p;

-- Conferência
SELECT COUNT(*) AS blocos, SUM(techsallus) AS ja_criados_no_techsallus
  FROM central_agenda_blocos;
