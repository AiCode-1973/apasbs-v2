-- APASBS v2 — log de auditoria
-- Execute uma vez no banco `apasbs`:
--   mysql -u root -p apasbs < sql/auditoria.sql
--
-- Script aditivo: não altera nenhuma tabela existente.

CREATE TABLE IF NOT EXISTS auditoria (
    id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id   INT          NULL,
    usuario_nome VARCHAR(150) NOT NULL,
    modulo       VARCHAR(50)  NOT NULL,
    acao         VARCHAR(20)  NOT NULL,
    registro_id  VARCHAR(50)  NULL,
    descricao    VARCHAR(255) NOT NULL,
    ip           VARCHAR(45)  NULL,
    user_agent   VARCHAR(255) NULL,
    created_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,

    KEY idx_aud_data    (created_at),
    KEY idx_aud_modulo  (modulo),
    KEY idx_aud_acao    (acao),
    KEY idx_aud_usuario (usuario_id),

    CONSTRAINT fk_aud_usuario FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Registra o módulo na tabela `modulos` para que ele apareça na matriz de
-- permissões. O sistema também faz isso sozinho no primeiro acesso.
INSERT INTO modulos (nome, slug, icone) VALUES ('Auditoria', 'auditoria', 'auditoria')
    ON DUPLICATE KEY UPDATE nome = VALUES(nome), icone = VALUES(icone);
