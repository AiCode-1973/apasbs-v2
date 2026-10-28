-- APASBS v2 — CPF e carteirinha na autorização de exames
-- Execute:
--   mysql -u usuario -p apasbs < sql/amb_autorizacoes_paciente.sql
--
-- Script ADITIVO e reexecutável: os ALTER só rodam se a coluna ainda não
-- existir, então rodar duas vezes não dá erro.

SET NAMES utf8mb4;

-- ── CPF ─────────────────────────────────────────────────────────────────
-- Guardado só com os dígitos, como em `usuarios.cpf`: a pontuação é da
-- tela, não do dado. Assim procurar por "123.456" e por "123456" acha a
-- mesma paciente.
--
-- NULL quando não informado — e não string vazia. Vazio é um valor; NULL
-- é a ausência dele, que é o que realmente acontece quando a atendente não
-- tem o CPF em mãos na hora da ligação.
SET @sql := (
    SELECT IF(COUNT(*) = 0,
        'ALTER TABLE amb_autorizacoes
            ADD COLUMN cpf CHAR(11) NULL AFTER paciente,
            ADD KEY idx_aut_cpf (cpf)',
        'DO 0')
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'amb_autorizacoes'
      AND COLUMN_NAME  = 'cpf'
);
PREPARE p FROM @sql; EXECUTE p; DEALLOCATE PREPARE p;

-- ── Carteirinha ─────────────────────────────────────────────────────────
-- Como veio: cada convênio tem seu formato, alguns com letras e traços.
-- Normalizar seria inventar uma regra que o convênio não segue.
SET @sql := (
    SELECT IF(COUNT(*) = 0,
        'ALTER TABLE amb_autorizacoes
            ADD COLUMN carteirinha VARCHAR(40) NULL AFTER cpf',
        'DO 0')
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'amb_autorizacoes'
      AND COLUMN_NAME  = 'carteirinha'
);
PREPARE p FROM @sql; EXECUTE p; DEALLOCATE PREPARE p;

SELECT COUNT(*)                       AS autorizacoes,
       SUM(cpf IS NOT NULL)           AS com_cpf,
       SUM(carteirinha IS NOT NULL)   AS com_carteirinha
  FROM amb_autorizacoes;
