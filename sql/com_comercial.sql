-- APASBS v2 — Comercial: tabelas de valores dos convênios
-- Execute:
--   mysql -u usuario -p apasbs < sql/com_comercial.sql
--   mysql -u usuario -p apasbs < sql/com_capep_2026.sql
--
-- Script ADITIVO. Pode ser executado mais de uma vez sem efeito colateral.
--
-- ── O que isto guarda ───────────────────────────────────────────────────
-- O que o convênio paga por cada coisa: diária, taxa de sala, procedimento,
-- honorário, pacote, OPME, material e dieta. A fonte é o edital de
-- credenciamento, e um edital inteiro é UMA tabela com vigência — o
-- reajuste do ano que vem entra como outra, e a antiga continua ali para
-- explicar uma conta de seis meses atrás.
--
-- Por isso nada é sobrescrito: `com_tabelas` é a capa (convênio, edital,
-- vigência) e `com_itens` são as linhas. Trocar de edital não apaga o
-- anterior.

SET NAMES utf8mb4;

-- ── A capa: um edital de um convênio, com a data em que passou a valer ──
CREATE TABLE IF NOT EXISTS com_tabelas (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    convenio   VARCHAR(80)  NOT NULL,
    referencia VARCHAR(180) NOT NULL,   -- "Edital 011/2022 — 29º retificado"
    vigencia   DATE         NOT NULL,

    observacao VARCHAR(400) NULL,
    ativo      TINYINT(1)   NOT NULL DEFAULT 1,

    usuario_id   INT          NULL,
    usuario_nome VARCHAR(150) NULL,
    created_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- A chave é a trinca: o mesmo edital pode ser recarregado sem duplicar,
    -- e duas vigências do mesmo edital convivem.
    UNIQUE KEY uk_com_tabela (convenio, referencia, vigencia),
    KEY idx_com_tabela_vig (ativo, convenio, vigencia),

    CONSTRAINT fk_com_tabela_usuario FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── As linhas da tabela de valores ──────────────────────────────────────
-- Uma linha por item cobrável. `tipo` é o que mais se filtra na tela;
-- `grupo` guarda a seção do edital, que é como o pessoal do faturamento
-- fala ("tá na tabela de OPME de ortopedia").
--
-- `variante` existe porque o mesmo item tem preços diferentes por recorte
-- do hospital: diária de enfermaria vale um valor no PORTE I e outro no
-- PORTE II. Cada combinação é uma linha, e não uma coluna por porte — assim
-- um edital que amanhã criar o PORTE IV não pede mudança de tabela.
--
-- `extras` é JSON em texto: porte, UCO, valor HM, fabricante, registro
-- Anvisa, página do edital. São campos que só existem em alguns tipos, e
-- uma coluna por campo deixaria a tabela com metade das colunas vazias.
CREATE TABLE IF NOT EXISTS com_itens (
    id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tabela_id INT UNSIGNED NOT NULL,

    tipo      ENUM('procedimento','honorario','pacote','opme','material',
                   'dieta','diaria','taxa','porte') NOT NULL DEFAULT 'procedimento',
    grupo     VARCHAR(120) NULL,
    codigo    VARCHAR(30)  NULL,        -- TUSS, CBHPM ou o código do próprio edital
    descricao VARCHAR(400) NOT NULL,
    variante  VARCHAR(60)  NULL,        -- PORTE I/II/III, Honorários/SADT…

    valor     DECIMAL(12,2) NULL,
    extras    TEXT          NULL,       -- JSON com o que é específico do tipo

    -- 'edital' é carga automática e é reescrita a cada geração; 'manual' é
    -- o que foi incluído pela tela, e sobrevive à recarga.
    origem    ENUM('edital','manual') NOT NULL DEFAULT 'manual',

    usuario_id   INT          NULL,
    usuario_nome VARCHAR(150) NULL,
    created_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    KEY idx_com_item_tabela (tabela_id, tipo, grupo),
    KEY idx_com_item_codigo (codigo),
    KEY idx_com_item_desc (descricao(100)),

    -- CASCADE aqui, ao contrário do resto do sistema: a linha só existe
    -- dentro da tabela de valores. Apagar a tabela e deixar 4 mil linhas
    -- órfãs seria pior do que apagar junto — e isto não é prontuário.
    CONSTRAINT fk_com_item_tabela FOREIGN KEY (tabela_id)
        REFERENCES com_tabelas(id) ON DELETE CASCADE,
    CONSTRAINT fk_com_item_usuario FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO modulos (nome, slug, icone) VALUES ('Comercial', 'comercial', 'comercial')
ON DUPLICATE KEY UPDATE nome = VALUES(nome), icone = VALUES(icone);

SELECT COUNT(*) AS tabelas FROM com_tabelas;
