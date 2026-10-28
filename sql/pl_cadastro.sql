-- APASBS v2 — Cadastro de beneficiários do plano de saúde
-- Execute:
--   mysql -u usuario -p apasbs < sql/pl_cadastro.sql
--
-- Script ADITIVO. Pode ser executado mais de uma vez sem efeito colateral.
--
-- ── O que este módulo modela ────────────────────────────────────────────
-- O plano da APAS Baixada Santista atende Policiais Militares (ativos e
-- inativos), pensionistas e seus familiares. Cada família é um CONTRATO,
-- com um titular e seus dependentes, e cada pessoa fica num dos planos
-- STANDARD conforme as regras de elegibilidade.
--
-- Três decisões estruturais que valem explicar:
--
--   1. A tabela de preços é versionada por VIGÊNCIA. O reajuste anual da
--      ANS entra como um conjunto novo de linhas, não como edição das
--      antigas — assim a mensalidade de março continua sendo a de março
--      depois que o reajuste de novembro entrar, e o histórico fica de pé.
--
--   2. A FAIXA ETÁRIA não é coluna. É calculada da data de nascimento na
--      hora: a faixa muda no aniversário, e guardar o número significaria
--      ter beneficiário na faixa errada até alguém recadastrar.
--
--   3. O PLANO fica gravado no beneficiário. O sistema sugere e explica,
--      mas quem confirma é quem cadastra: as regras do documento têm zonas
--      cinzentas, e o plano escolhido muda o valor que a família paga.

SET NAMES utf8mb4;

-- ── Planos ──────────────────────────────────────────────────────────────
-- Os três produtos, com o registro na ANS. `coparticipacao` é o que de
-- fato separa o STANDARD 4 dos outros dois.
CREATE TABLE IF NOT EXISTS pl_planos (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome           VARCHAR(60)  NOT NULL,
    registro_ans   VARCHAR(30)  NULL,
    coparticipacao TINYINT(1)   NOT NULL DEFAULT 1,
    descricao      VARCHAR(300) NULL,
    ordem          SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    ativo          TINYINT(1)   NOT NULL DEFAULT 1,

    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uk_pl_plano_nome (nome)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Tabela de preços ────────────────────────────────────────────────────
-- Uma linha por plano × vigência × faixa etária. As dez faixas são as da
-- ANS, guardadas pelo índice (0 = 00-18 … 9 = 59 ou mais) porque os
-- rótulos são fixos por norma e vivem no PHP.
--
-- Reajustar é inserir um conjunto novo com outra vigência. O sistema usa
-- sempre a vigência mais recente que já começou.
CREATE TABLE IF NOT EXISTS pl_precos (
    id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    plano_id  INT UNSIGNED NOT NULL,
    vigencia  DATE         NOT NULL,
    faixa     TINYINT UNSIGNED NOT NULL,   -- 0..9
    valor     DECIMAL(10,2) NOT NULL,

    usuario_id   INT          NULL,
    usuario_nome VARCHAR(150) NULL,
    created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uk_pl_preco (plano_id, vigencia, faixa),
    KEY idx_pl_preco_vig (plano_id, vigencia),

    CONSTRAINT fk_pl_preco_plano FOREIGN KEY (plano_id) REFERENCES pl_planos(id),
    CONSTRAINT fk_pl_preco_usuario FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Contratos ───────────────────────────────────────────────────────────
-- A família. `tipo` é a escolha que governa toda a classificação: sem
-- coparticipação joga o grupo inteiro no STANDARD 4; com coparticipação
-- separa o núcleo (STANDARD 1) dos demais parentes (STANDARD 2).
CREATE TABLE IF NOT EXISTS pl_contratos (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    numero_proposta VARCHAR(30) NULL,
    tipo            ENUM('com_copart','sem_copart') NOT NULL DEFAULT 'com_copart',
    data_adesao     DATE NULL,
    status          ENUM('em_analise','ativo','suspenso','cancelado')
                    NOT NULL DEFAULT 'em_analise',
    observacao      VARCHAR(400) NULL,

    usuario_id   INT          NULL,
    usuario_nome VARCHAR(150) NULL,
    created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uk_pl_contrato_proposta (numero_proposta),
    KEY idx_pl_contrato_status (status),
    KEY idx_pl_contrato_adesao (data_adesao),

    CONSTRAINT fk_pl_contrato_usuario FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Beneficiários ───────────────────────────────────────────────────────
-- Titular e dependentes na mesma tabela: são a mesma coisa do ponto de
-- vista do plano — uma pessoa, numa faixa etária, pagando uma mensalidade.
-- O que muda é o papel.
--
-- `categoria` só vale para o titular (PM ativo, PM inativo ou pensionista);
-- `parentesco` só vale para o dependente. As condições que a regra pede
-- (guarda, tutela, dependência econômica) são colunas próprias porque o
-- motor de elegibilidade decide com elas.
CREATE TABLE IF NOT EXISTS pl_beneficiarios (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    contrato_id INT UNSIGNED NOT NULL,
    tipo        ENUM('titular','dependente') NOT NULL DEFAULT 'dependente',

    nome       VARCHAR(150) NOT NULL,
    cpf        CHAR(11)     NULL,
    nascimento DATE         NULL,
    sexo       ENUM('F','M','outro') NULL,
    nome_mae   VARCHAR(150) NULL,
    cns        VARCHAR(20)  NULL,          -- Cartão Nacional de Saúde
    contato    VARCHAR(20)  NULL,          -- só dígitos; a máscara é da tela
    email      VARCHAR(150) NULL,

    -- Só titular
    categoria  ENUM('pm_ativo','pm_inativo','pensionista') NULL,
    matricula  VARCHAR(30) NULL,           -- RE / matrícula na PM

    -- Só dependente
    parentesco VARCHAR(40) NULL,           -- chave da lista em Elegibilidade.php

    -- As condições que as regras verificam. NULL não existe aqui: ou a
    -- condição foi comprovada, ou não foi — e "não foi" é o padrão.
    guarda_judicial        TINYINT(1) NOT NULL DEFAULT 0,
    tutela_curatela        TINYINT(1) NOT NULL DEFAULT 0,
    dependencia_economica  TINYINT(1) NOT NULL DEFAULT 0,
    uniao_estavel          TINYINT(1) NOT NULL DEFAULT 0,
    emancipado             TINYINT(1) NOT NULL DEFAULT 0,
    vinculo_comprovado     TINYINT(1) NOT NULL DEFAULT 0,

    plano_id   INT UNSIGNED NULL,
    -- O que o motor sugeriu na hora da gravação, por escrito. Guardado para
    -- a conferência: se alguém trocou o plano à mão, dá para ver de quê.
    plano_sugerido VARCHAR(60) NULL,

    data_inclusao   DATE NULL,
    data_exclusao   DATE NULL,
    motivo_exclusao VARCHAR(200) NULL,
    status          ENUM('ativo','inativo') NOT NULL DEFAULT 'ativo',
    observacao      VARCHAR(400) NULL,

    usuario_id   INT          NULL,
    usuario_nome VARCHAR(150) NULL,
    created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    KEY idx_pl_benef_contrato (contrato_id, tipo),
    KEY idx_pl_benef_nome (nome),
    KEY idx_pl_benef_cpf (cpf),
    KEY idx_pl_benef_status (status),
    KEY idx_pl_benef_plano (plano_id),

    -- RESTRICT no contrato: apagar a família não pode sumir com os
    -- beneficiários por tabela. A exclusão é uma de cada vez, consciente.
    CONSTRAINT fk_pl_benef_contrato FOREIGN KEY (contrato_id)
        REFERENCES pl_contratos(id),
    CONSTRAINT fk_pl_benef_plano FOREIGN KEY (plano_id)
        REFERENCES pl_planos(id),
    CONSTRAINT fk_pl_benef_usuario FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Os três planos ──────────────────────────────────────────────────────
INSERT INTO pl_planos (nome, registro_ans, coparticipacao, descricao, ordem) VALUES
    ('STANDARD 1', '434.313/00-1', 1,
     'Com coparticipação. Titular (PM ativo, inativo ou pensionista) e o núcleo familiar próximo: cônjuge, companheiro(a), filhos menores e enteados menores com guarda comprovada.', 10),
    ('STANDARD 2', '434.314/00-0', 1,
     'Com coparticipação. Demais graus de parentesco: filhos maiores, enteados sem guarda, ascendentes, netos, irmãos, colaterais e afins.', 20),
    ('STANDARD 4', '490.619/21-5', 0,
     'Sem coparticipação. Atende o titular e toda a família elegível num contrato só, e é o único que aceita menor sob guarda judicial.', 40)
ON DUPLICATE KEY UPDATE registro_ans = VALUES(registro_ans),
                        coparticipacao = VALUES(coparticipacao),
                        descricao = VALUES(descricao), ordem = VALUES(ordem);

-- ── Mensalidades de 2026 ────────────────────────────────────────────────
-- Da planilha "VALORES DE MENSALIDADES - 2026".
--
-- As vigências vêm da observação da planilha, e não das células de data:
-- as células foram digitadas como 01/03/2026 e 01/11/2025 e o Excel as
-- guardou como 3 de janeiro e 11 de janeiro, lendo mês/dia. A observação
-- escrita — "STD 1 E STD 2 — REAJUSTE 7,93% — A PARTIR DE 01/03/2026" e
-- "STD 4 — REAJUSTE 8,45% — A PARTIR DE 01/11/2025" — é o que vale.
--
-- Faixas: 0=00-18  1=19-23  2=24-28  3=29-33  4=34-38
--         5=39-43  6=44-48  7=49-53  8=54-58  9=59 ou mais
INSERT INTO pl_precos (plano_id, vigencia, faixa, valor)
SELECT p.id, v.vigencia, v.faixa, v.valor
  FROM pl_planos p
  JOIN (
        SELECT 'STANDARD 1' AS plano, '2026-03-01' AS vigencia, 0 AS faixa,  169.21 AS valor UNION ALL
        SELECT 'STANDARD 1', '2026-03-01', 1,  206.62 UNION ALL
        SELECT 'STANDARD 1', '2026-03-01', 2,  276.28 UNION ALL
        SELECT 'STANDARD 1', '2026-03-01', 3,  309.76 UNION ALL
        SELECT 'STANDARD 1', '2026-03-01', 4,  338.19 UNION ALL
        SELECT 'STANDARD 1', '2026-03-01', 5,  396.61 UNION ALL
        SELECT 'STANDARD 1', '2026-03-01', 6,  424.15 UNION ALL
        SELECT 'STANDARD 1', '2026-03-01', 7,  552.58 UNION ALL
        SELECT 'STANDARD 1', '2026-03-01', 8,  836.39 UNION ALL
        SELECT 'STANDARD 1', '2026-03-01', 9, 1001.13 UNION ALL

        SELECT 'STANDARD 2', '2026-03-01', 0,  216.16 UNION ALL
        SELECT 'STANDARD 2', '2026-03-01', 1,  259.68 UNION ALL
        SELECT 'STANDARD 2', '2026-03-01', 2,  341.30 UNION ALL
        SELECT 'STANDARD 2', '2026-03-01', 3,  378.67 UNION ALL
        SELECT 'STANDARD 2', '2026-03-01', 4,  414.60 UNION ALL
        SELECT 'STANDARD 2', '2026-03-01', 5,  484.16 UNION ALL
        SELECT 'STANDARD 2', '2026-03-01', 6,  517.08 UNION ALL
        SELECT 'STANDARD 2', '2026-03-01', 7,  663.97 UNION ALL
        SELECT 'STANDARD 2', '2026-03-01', 8,  985.77 UNION ALL
        SELECT 'STANDARD 2', '2026-03-01', 9, 1170.19 UNION ALL

        SELECT 'STANDARD 4', '2025-11-01', 0,  190.67 UNION ALL
        SELECT 'STANDARD 4', '2025-11-01', 1,  229.06 UNION ALL
        SELECT 'STANDARD 4', '2025-11-01', 2,  301.06 UNION ALL
        SELECT 'STANDARD 4', '2025-11-01', 3,  319.70 UNION ALL
        SELECT 'STANDARD 4', '2025-11-01', 4,  365.72 UNION ALL
        SELECT 'STANDARD 4', '2025-11-01', 5,  427.09 UNION ALL
        SELECT 'STANDARD 4', '2025-11-01', 6,  456.12 UNION ALL
        SELECT 'STANDARD 4', '2025-11-01', 7,  585.67 UNION ALL
        SELECT 'STANDARD 4', '2025-11-01', 8,  869.55 UNION ALL
        SELECT 'STANDARD 4', '2025-11-01', 9, 1032.21
       ) v ON v.plano = p.nome
ON DUPLICATE KEY UPDATE valor = VALUES(valor);

-- ── Registro do módulo ──────────────────────────────────────────────────
INSERT INTO modulos (nome, slug, icone) VALUES
    ('Cadastro', 'cadastro', 'cadastro')
ON DUPLICATE KEY UPDATE nome = VALUES(nome), icone = VALUES(icone);

SELECT p.nome, p.registro_ans,
       IF(p.coparticipacao, 'com coparticipação', 'sem coparticipação') AS tipo,
       COUNT(pr.id) AS faixas,
       MIN(pr.valor) AS menor, MAX(pr.valor) AS maior
  FROM pl_planos p
  LEFT JOIN pl_precos pr ON pr.plano_id = p.id
 GROUP BY p.id ORDER BY p.ordem;
