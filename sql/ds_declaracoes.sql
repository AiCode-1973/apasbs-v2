-- APASBS v2 — Declaração de Saúde
-- Execute:
--   mysql -u usuario -p apasbs < sql/ds_declaracoes.sql
--
-- Depende de sql/pl_cadastro.sql: a declaração é sempre de um contrato.
-- Script ADITIVO. Pode ser executado mais de uma vez sem efeito colateral.
--
-- ── O que este módulo guarda ────────────────────────────────────────────
-- A declaração de saúde registra doenças e lesões preexistentes (DLP) no
-- momento da adesão. É o documento que decide se o beneficiário entra com
-- Cobertura Parcial Temporária — suspensão de procedimentos de alta
-- complexidade ligados àquela doença, por até 24 meses.
--
-- Três consequências no desenho:
--
--   1. É uma declaração por CONTRATO, não por pessoa. O papel tem uma
--      coluna para o titular e seis para dependentes, e é assinado uma vez
--      pelo contratante. Aqui é a mesma coisa, sem o limite de seis.
--
--   2. Resposta e descrição andam juntas. "Sim" sem dizer qual doença não
--      serve para nada — nem para a operadora, nem para o beneficiário, que
--      é quem fica protegido pelo que declarou.
--
--   3. Nada é apagado em cascata. Omissão comprovada pode cancelar
--      contrato, e o que protege as duas partes é justamente o que ficou
--      escrito e assinado. Excluir uma pessoa do cadastro não pode levar
--      junto a declaração dela.

SET NAMES utf8mb4;

-- ── Declarações ─────────────────────────────────────────────────────────
-- `orientacao` é a escolha do cabeçalho do formulário: a ANS exige que o
-- proponente possa ser orientado por médico, e que o registro dessa
-- escolha faça parte do documento.
--
-- `data_contratacao` é o marco da CPT — os 24 meses contam dela. Vem da
-- adesão do contrato, mas fica guardada aqui: se a adesão for corrigida
-- depois, a contagem da CPT que já estava valendo não muda sozinha.
CREATE TABLE IF NOT EXISTS ds_declaracoes (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    contrato_id INT UNSIGNED NOT NULL,

    data_preenchimento DATE NOT NULL,
    data_contratacao   DATE NULL,

    orientacao  ENUM('apas','proprio','dispensa') NOT NULL DEFAULT 'dispensa',
    medico_nome VARCHAR(150) NULL,
    medico_crm  VARCHAR(30)  NULL,

    -- Quem assina o documento. Nem sempre é o titular: menor de idade ou
    -- incapaz é representado, e o formulário diz isso com todas as letras.
    assinante_nome  VARCHAR(150) NULL,
    assinante_cpf   CHAR(11)     NULL,
    data_assinatura DATE         NULL,

    status     ENUM('em_preenchimento','concluida') NOT NULL DEFAULT 'em_preenchimento',
    observacao VARCHAR(400) NULL,

    usuario_id   INT          NULL,
    usuario_nome VARCHAR(150) NULL,
    created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    KEY idx_ds_contrato (contrato_id, data_preenchimento),
    KEY idx_ds_status (status),

    CONSTRAINT fk_ds_contrato FOREIGN KEY (contrato_id)
        REFERENCES pl_contratos(id),
    CONSTRAINT fk_ds_usuario FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Respostas ───────────────────────────────────────────────────────────
-- Uma linha por pessoa × item. Os 22 itens do questionário vivem no PHP
-- (DeclaracaoModel::ITENS): são o texto de um formulário regulado, não
-- cadastro que alguém mantém pela tela.
--
-- `resposta` NULL é "ainda não respondido", e é diferente de 'N'. A
-- diferença importa: declaração com item em branco não está pronta para
-- ser assinada, e o sistema precisa saber disso.
--
-- `valor` atende os itens que não são sim/não — grau de miopia, peso,
-- altura e idade.
CREATE TABLE IF NOT EXISTS ds_respostas (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    declaracao_id  INT UNSIGNED NOT NULL,
    beneficiario_id INT UNSIGNED NOT NULL,

    item      TINYINT UNSIGNED NOT NULL,      -- 1..22
    resposta  ENUM('S','N') NULL,
    valor     VARCHAR(60)  NULL,
    descricao VARCHAR(600) NULL,

    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uk_ds_resposta (declaracao_id, beneficiario_id, item),
    KEY idx_ds_resp_benef (beneficiario_id),
    KEY idx_ds_resp_sim (declaracao_id, resposta),

    -- CASCADE aqui, e só aqui: as respostas não existem sem a declaração
    -- que as contém — são o corpo dela, não documento separado.
    CONSTRAINT fk_ds_resp_declaracao FOREIGN KEY (declaracao_id)
        REFERENCES ds_declaracoes(id) ON DELETE CASCADE,
    CONSTRAINT fk_ds_resp_benef FOREIGN KEY (beneficiario_id)
        REFERENCES pl_beneficiarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Registro do módulo ──────────────────────────────────────────────────
INSERT INTO modulos (nome, slug, icone) VALUES
    ('Declaração de Saúde', 'declaracoes', 'declaracoes')
ON DUPLICATE KEY UPDATE nome = VALUES(nome), icone = VALUES(icone);

SELECT
    (SELECT COUNT(*) FROM ds_declaracoes) AS declaracoes,
    (SELECT COUNT(*) FROM ds_respostas)   AS respostas;
