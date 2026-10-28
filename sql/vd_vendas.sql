-- APASBS v2 — Vendas: propostas de plano por vendedor
-- Execute DEPOIS de sql/pl_cadastro.sql:
--   mysql -u usuario -p apasbs < sql/vd_vendas.sql
--
-- Script ADITIVO. Pode ser executado mais de uma vez sem efeito colateral.
--
-- ── Como este módulo conversa com o Cadastro ────────────────────────────
-- A proposta é a venda enquanto ela ainda é promessa. Fechada, ela VIRA o
-- contrato do Cadastro: `vd_propostas.contrato_id` aponta para
-- `pl_contratos`, e daí em diante quem manda no cadastro da família é o
-- módulo Cadastro. Não há cópia dos dados da família aqui — dois lugares
-- guardando o mesmo titular divergem na primeira correção.
--
-- O que fica em Vendas é o que o Cadastro não tem: quem vendeu, quando,
-- por qual empresa parceira e quanto de comissão aquilo gera.

SET NAMES utf8mb4;

-- ── Empresas parceiras ──────────────────────────────────────────────────
-- A corretora ou agência que vende os planos. Vira cadastro porque o
-- relatório por empresa com o nome escrito de três jeitos não é relatório.
CREATE TABLE IF NOT EXISTS vd_empresas (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome       VARCHAR(150) NOT NULL,
    cnpj       VARCHAR(18)  NULL,
    contato    VARCHAR(20)  NULL,       -- só dígitos; a máscara é da tela
    email      VARCHAR(150) NULL,
    observacao VARCHAR(400) NULL,
    ativo      TINYINT(1)   NOT NULL DEFAULT 1,

    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uk_vd_empresa_nome (nome),
    KEY idx_vd_empresa_ativo (ativo, nome)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Vendedores ──────────────────────────────────────────────────────────
-- Gente de fora, que normalmente não tem login no APASBS — por isso é
-- cadastro próprio, e não um usuário do sistema. `usuario_id` existe para
-- quem tiver login: é por ele que o sistema sabe mostrar a um vendedor
-- apenas as vendas dele.
--
-- `comissao_percent` fica no vendedor porque é o combinado com ele. Na hora
-- de fechar a venda o percentual é COPIADO para a proposta: renegociar a
-- comissão não pode mexer no que já foi vendido.
CREATE TABLE IF NOT EXISTS vd_vendedores (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT UNSIGNED NOT NULL,

    nome       VARCHAR(150) NOT NULL,
    cpf        CHAR(11)     NULL,
    contato    VARCHAR(20)  NULL,
    email      VARCHAR(150) NULL,

    comissao_percent DECIMAL(5,2) NOT NULL DEFAULT 0.00,

    usuario_id INT        NULL,         -- login no APASBS, quando existir
    ativo      TINYINT(1) NOT NULL DEFAULT 1,
    observacao VARCHAR(400) NULL,

    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uk_vd_vendedor_cpf (empresa_id, cpf),
    UNIQUE KEY uk_vd_vendedor_usuario (usuario_id),
    KEY idx_vd_vendedor_ativo (ativo, nome),

    CONSTRAINT fk_vd_vendedor_empresa FOREIGN KEY (empresa_id)
        REFERENCES vd_empresas(id),
    CONSTRAINT fk_vd_vendedor_usuario FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Propostas ───────────────────────────────────────────────────────────
-- A venda. Enquanto está em negociação, os dados do titular moram aqui;
-- fechada, viram o contrato e o titular do Cadastro, e `contrato_id` passa
-- a ser a ligação. Perdida, fica com o motivo — é o que ensina a corrigir
-- o discurso de venda.
CREATE TABLE IF NOT EXISTS vd_propostas (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    -- Numeração do próprio módulo, por ano: é o que o vendedor fala ao
    -- telefone antes de existir contrato.
    ano         SMALLINT UNSIGNED NOT NULL,
    sequencia   INT UNSIGNED      NOT NULL,
    numero      VARCHAR(20)       NOT NULL,

    vendedor_id INT UNSIGNED NOT NULL,

    -- O titular pretendido. CPF sem pontuação, como no resto do sistema.
    titular_nome       VARCHAR(150) NOT NULL,
    titular_cpf        CHAR(11)     NULL,
    titular_nascimento DATE         NULL,
    titular_categoria  ENUM('pm_ativo','pm_inativo','pensionista') NULL,
    contato            VARCHAR(20)  NULL,
    email              VARCHAR(150) NULL,

    tipo   ENUM('com_copart','sem_copart') NOT NULL DEFAULT 'com_copart',
    vidas  SMALLINT UNSIGNED NOT NULL DEFAULT 1,   -- quantas pessoas a família prevê

    status ENUM('em_negociacao','fechada','perdida') NOT NULL DEFAULT 'em_negociacao',

    data_proposta   DATE NOT NULL,
    data_fechamento DATE NULL,
    motivo_perda    VARCHAR(200) NULL,

    -- Preenchido quando a proposta é fechada e o contrato nasce.
    contrato_id INT UNSIGNED NULL,

    -- Congelado no fechamento: o combinado valia naquele dia.
    comissao_percent DECIMAL(5,2) NULL,

    observacao VARCHAR(400) NULL,

    usuario_id   INT          NULL,
    usuario_nome VARCHAR(150) NULL,
    created_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uk_vd_proposta_numero (numero),
    UNIQUE KEY uk_vd_proposta_ano_seq (ano, sequencia),
    KEY idx_vd_proposta_vendedor (vendedor_id, status),
    KEY idx_vd_proposta_data (data_proposta),
    KEY idx_vd_proposta_contrato (contrato_id),

    -- RESTRICT no vendedor: proposta é histórico de venda e de comissão, e
    -- não some porque alguém saiu da corretora — desative o vendedor.
    CONSTRAINT fk_vd_proposta_vendedor FOREIGN KEY (vendedor_id)
        REFERENCES vd_vendedores(id),
    -- SET NULL no contrato: se o contrato for excluído no Cadastro, a venda
    -- continua existindo, só perde o vínculo.
    CONSTRAINT fk_vd_proposta_contrato FOREIGN KEY (contrato_id)
        REFERENCES pl_contratos(id) ON DELETE SET NULL,
    CONSTRAINT fk_vd_proposta_usuario FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO modulos (nome, slug, icone) VALUES ('Vendas', 'vendas', 'vendas')
ON DUPLICATE KEY UPDATE nome = VALUES(nome), icone = VALUES(icone);

SELECT (SELECT COUNT(*) FROM vd_empresas)   AS empresas,
       (SELECT COUNT(*) FROM vd_vendedores) AS vendedores,
       (SELECT COUNT(*) FROM vd_propostas)  AS propostas;
