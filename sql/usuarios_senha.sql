-- APASBS v2 — troca de senha pelo próprio usuário
-- Execute:
--   mysql -u usuario -p apasbs < sql/usuarios_senha.sql
--
-- Script ADITIVO e reexecutável: os ALTER só rodam se a coluna ainda não
-- existir, então rodar duas vezes não dá erro.

SET NAMES utf8mb4;

-- ── senha_provisoria ────────────────────────────────────────────────────
-- Liga quando o administrador cria ou redefine a senha de alguém. Enquanto
-- estiver ligada, o sistema leva a pessoa para a tela de troca e não deixa
-- usar mais nada — a senha que outra pessoa digitou não pode virar a senha
-- definitiva de ninguém.
SET @sql := (
    SELECT IF(COUNT(*) = 0,
        'ALTER TABLE usuarios
            ADD COLUMN senha_provisoria TINYINT(1) NOT NULL DEFAULT 0 AFTER senha',
        'DO 0')
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'usuarios'
      AND COLUMN_NAME  = 'senha_provisoria'
);
PREPARE p FROM @sql; EXECUTE p; DEALLOCATE PREPARE p;

-- ── senha_alterada_em ───────────────────────────────────────────────────
-- Quando a senha mudou pela última vez. Serve para a tela de usuários
-- mostrar quem ainda está com a senha de fábrica.
SET @sql := (
    SELECT IF(COUNT(*) = 0,
        'ALTER TABLE usuarios
            ADD COLUMN senha_alterada_em DATETIME NULL AFTER senha_provisoria',
        'DO 0')
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'usuarios'
      AND COLUMN_NAME  = 'senha_alterada_em'
);
PREPARE p FROM @sql; EXECUTE p; DEALLOCATE PREPARE p;

-- Quem já existia não é obrigado a trocar nada: o sistema entrou em
-- operação com essas senhas e forçar a troca de todo mundo de uma vez só
-- geraria uma fila no suporte sem ganho de segurança real. A obrigação vale
-- daqui para a frente, para senha que o administrador criar ou redefinir.
UPDATE usuarios SET senha_provisoria = 0 WHERE senha_provisoria IS NULL;

SELECT COUNT(*) AS usuarios,
       SUM(senha_provisoria = 1) AS com_senha_provisoria
  FROM usuarios;
