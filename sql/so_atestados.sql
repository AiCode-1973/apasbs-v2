-- APASBS v2 — Saúde Ocupacional: entrega de atestado médico
-- Execute:
--   mysql -u usuario -p apasbs < sql/so_atestados.sql
--
-- Script ADITIVO. Pode ser executado mais de uma vez sem efeito colateral.
--
-- ── O que esta tabela é, e o que ela não é ──────────────────────────────
-- Ela registra a ENTREGA do atestado no balcão da Saúde Ocupacional: quem
-- entregou, quando, de que forma e quem recebeu. Não é a avaliação do
-- atestado, nem abono, nem afastamento concedido — é o comprovante de que o
-- papel chegou, que é exatamente o que o modelo impresso diz no rodapé.
--
-- Por isso o CID não é guardado: para comprovar a entrega ele não é
-- necessário, e diagnóstico é o dado mais sensível que existe na LGPD.

SET NAMES utf8mb4;

-- ── Matrícula do trabalhador ────────────────────────────────────────────
-- O formulário de entrega pede matrícula. Ela é do vínculo, como função e
-- setor, então mora no cadastro do trabalhador e é digitada uma vez só.
SET @tem := (
    SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = DATABASE()
       AND table_name   = 'so_trabalhadores'
       AND column_name  = 'matricula'
);

SET @sql := IF(@tem = 0,
    'ALTER TABLE so_trabalhadores ADD COLUMN matricula VARCHAR(30) NULL AFTER cpf',
    'DO 0'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ── Entregas de atestado ────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS so_atestados (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    -- O protocolo é o que a pessoa leva escrito no comprovante dela. Ano e
    -- sequência ficam em colunas próprias porque é assim que o próximo
    -- número é calculado; `protocolo` é a forma legível, 2026/0001.
    ano            SMALLINT UNSIGNED NOT NULL,
    sequencia      INT UNSIGNED      NOT NULL,
    protocolo      VARCHAR(20)       NOT NULL,

    trabalhador_id INT UNSIGNED NOT NULL,

    -- Dados do atestado
    emissao        DATE         NOT NULL,
    inicio         DATE         NULL,
    dias           SMALLINT UNSIGNED NULL,
    retorno        DATE         NULL,   -- sugerido de início + dias, editável
    medico_nome    VARCHAR(150) NULL,
    medico_registro VARCHAR(40) NULL,   -- CRM ou outro conselho, como veio

    -- Entrega
    entrega_data   DATE         NOT NULL,
    entrega_hora   TIME         NULL,
    forma          ENUM('presencial', 'sistema', 'email', 'outro')
                                NOT NULL DEFAULT 'presencial',
    forma_outro    VARCHAR(120) NULL,   -- só quando forma = 'outro'

    -- Recebimento pela Saúde Ocupacional
    recebido_por   VARCHAR(150) NULL,
    recebido_data  DATE         NULL,
    recebido_hora  TIME         NULL,

    observacao     VARCHAR(400) NULL,

    usuario_id     INT          NULL,
    usuario_nome   VARCHAR(150) NULL,
    created_at     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Duas travas, e elas fazem coisas diferentes: a primeira impede dois
    -- protocolos iguais na mão do trabalhador; a segunda é a que o cálculo
    -- do próximo número usa para não repetir sequência dentro do ano.
    UNIQUE KEY uk_so_atest_protocolo (protocolo),
    UNIQUE KEY uk_so_atest_ano_seq (ano, sequencia),

    KEY idx_so_atest_trabalhador (trabalhador_id, entrega_data),
    KEY idx_so_atest_entrega (entrega_data),
    KEY idx_so_atest_inicio (inicio),

    -- RESTRICT, como no resto do módulo: o comprovante de entrega é
    -- documento administrativo e não pode sumir junto com um cadastro
    -- removido por engano.
    CONSTRAINT fk_so_atest_trab FOREIGN KEY (trabalhador_id)
        REFERENCES so_trabalhadores(id),
    CONSTRAINT fk_so_atest_usuario FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SELECT COUNT(*) AS atestados_registrados FROM so_atestados;
