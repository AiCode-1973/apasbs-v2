-- APASBS v2 — CPF da paciente na marcação
-- Execute DEPOIS de sql/central_agenda_marcacoes.sql:
--   mysql -u usuario -p apasbs < sql/central_agenda_cpf.sql
--
-- Pode ser executado mais de uma vez sem efeito colateral.

SET NAMES utf8mb4;

-- Guardado só com os 11 dígitos, sem ponto nem traço: a formatação é
-- coisa de tela. Marcações antigas ficam com o campo vazio — o sistema
-- não tem como adivinhar o CPF de quem já foi agendada.
SET @sql := (
    SELECT IF(COUNT(*) = 0,
        'ALTER TABLE central_agenda_marcacoes
            ADD COLUMN paciente_cpf CHAR(11) NULL AFTER exame_id',
        'DO 0')
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'central_agenda_marcacoes'
      AND COLUMN_NAME  = 'paciente_cpf'
);
PREPARE p FROM @sql; EXECUTE p; DEALLOCATE PREPARE p;

-- Índice para achar a paciente pelo CPF e para conferir duplicidade no bloco
SET @sql := (
    SELECT IF(COUNT(*) = 0,
        'ALTER TABLE central_agenda_marcacoes
            ADD KEY idx_marcacao_cpf (paciente_cpf)',
        'DO 0')
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'central_agenda_marcacoes'
      AND INDEX_NAME   = 'idx_marcacao_cpf'
);
PREPARE p FROM @sql; EXECUTE p; DEALLOCATE PREPARE p;

SELECT COUNT(*) AS marcacoes,
       SUM(paciente_cpf IS NULL OR paciente_cpf = '') AS sem_cpf_ainda
  FROM central_agenda_marcacoes;
