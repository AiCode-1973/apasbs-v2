-- APASBS v2 — Vendas: as vidas da família na proposta
-- Execute DEPOIS de sql/vd_vendas.sql:
--   mysql -u usuario -p apasbs < sql/vd_pessoas.sql
--
-- Script ADITIVO. Pode ser executado mais de uma vez sem efeito colateral.
--
-- ── Por que a lista vive aqui, e não no Cadastro ────────────────────────
-- Enquanto a venda é promessa, a família também é: nomes que podem mudar,
-- CPF que ainda não chegou, dependente que desiste. Guardar isso em
-- `pl_beneficiarios` encheria o Cadastro de gente que talvez nunca entre no
-- plano — e de contratos que nunca existiram.
--
-- Quando a proposta fecha, estas linhas são COPIADAS para o contrato como
-- beneficiários de verdade. Daí em diante vale o Cadastro, e a lista daqui
-- fica só como registro do que foi vendido.
--
-- Por isso o CASCADE: a vida só existe dentro da proposta. Apagar a
-- proposta (coisa que só acontece enquanto ela está em negociação) leva a
-- lista junto, e não deixa órfão nenhum.

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS vd_proposta_pessoas (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    proposta_id INT UNSIGNED NOT NULL,

    nome       VARCHAR(150) NOT NULL,
    cpf        CHAR(11)     NULL,       -- só dígitos; a pontuação é da tela
    nascimento DATE         NULL,

    -- A chave do parentesco é a mesma de Elegibilidade::PARENTESCOS: é ela
    -- que o motor do Cadastro lê para dizer em qual plano a pessoa entra.
    parentesco VARCHAR(40)  NULL,

    -- As condições que mudam a classificação. Ficam aqui porque a resposta
    -- é dada na venda ("tem guarda judicial?"), e perguntar de novo no
    -- Cadastro seria fazer a família repetir o que já disse.
    guarda_judicial       TINYINT(1) NOT NULL DEFAULT 0,
    tutela_curatela       TINYINT(1) NOT NULL DEFAULT 0,
    dependencia_economica TINYINT(1) NOT NULL DEFAULT 0,
    uniao_estavel         TINYINT(1) NOT NULL DEFAULT 0,
    emancipado            TINYINT(1) NOT NULL DEFAULT 0,
    vinculo_comprovado    TINYINT(1) NOT NULL DEFAULT 0,

    observacao VARCHAR(300) NULL,

    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    KEY idx_vd_pessoa_proposta (proposta_id, nome),

    CONSTRAINT fk_vd_pessoa_proposta FOREIGN KEY (proposta_id)
        REFERENCES vd_propostas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SELECT COUNT(*) AS vidas_cadastradas FROM vd_proposta_pessoas;
