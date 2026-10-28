-- APASBS v2 — Saúde Ocupacional (NR-7 / PCMSO)
-- Execute:
--   mysql -u usuario -p apasbs < sql/so_ocupacional.sql
--
-- Script ADITIVO. Pode ser executado mais de uma vez sem efeito colateral.
--
-- ── Sobre o dado guardado aqui ──────────────────────────────────────────
-- Isto é prontuário: dado de saúde, o grau mais sensível da LGPD, e a NR-7
-- manda guardar por 20 anos depois do desligamento. Duas consequências que
-- estão no desenho das tabelas:
--
--   1. Nada é apagado em cascata a partir de empresa ou trabalhador. A ficha
--      só sai por exclusão explícita de quem tem permissão para isso — um
--      cadastro removido por engano não pode levar 20 anos de prontuário
--      junto.
--   2. Todo registro carrega usuario_nome por escrito, além do id. Se o
--      profissional sair do sistema daqui a dez anos, a ficha continua
--      dizendo quem a preencheu.

SET NAMES utf8mb4;

-- ── Empresas ────────────────────────────────────────────────────────────
-- A empregadora do trabalhador examinado. Vira cadastro porque se repete
-- muito e porque relatório por empresa com o nome escrito de três jeitos
-- não é relatório.
CREATE TABLE IF NOT EXISTS so_empresas (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome       VARCHAR(150) NOT NULL,
    cnpj       VARCHAR(18)  NULL,       -- como veio, com ou sem pontuação
    contato    VARCHAR(20)  NULL,       -- só dígitos; a máscara é da tela
    observacao VARCHAR(400) NULL,
    ativo      TINYINT(1)   NOT NULL DEFAULT 1,

    created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uk_so_empresa_nome (nome),
    KEY idx_so_empresa_ativo (ativo, nome)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Trabalhadores ───────────────────────────────────────────────────────
-- Uma linha por vínculo: a mesma pessoa contratada por duas empresas são
-- dois registros, porque função, setor e admissão são do vínculo, não da
-- pessoa. Por isso a chave única é (empresa, CPF) e não o CPF sozinho.
--
-- O CPF aceita NULL: acontece de o exame admissional ser feito antes de a
-- papelada chegar. Guardado, vai só com os dígitos, como em `usuarios.cpf`
-- — a pontuação é da tela. Em MySQL, índice único convive com vários NULL,
-- então isso não atrapalha a trava de duplicidade.
CREATE TABLE IF NOT EXISTS so_trabalhadores (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT UNSIGNED NOT NULL,

    nome       VARCHAR(150) NOT NULL,
    cpf        CHAR(11)     NULL,
    nascimento DATE         NULL,
    funcao     VARCHAR(120) NULL,
    setor      VARCHAR(120) NULL,
    admissao   DATE         NULL,

    ativo      TINYINT(1)   NOT NULL DEFAULT 1,   -- 0 = desligado
    observacao VARCHAR(400) NULL,

    usuario_id   INT          NULL,
    usuario_nome VARCHAR(150) NULL,
    created_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uk_so_trab_empresa_cpf (empresa_id, cpf),
    KEY idx_so_trab_nome (nome),
    KEY idx_so_trab_cpf (cpf),
    KEY idx_so_trab_ativo (ativo, nome),

    -- RESTRICT: empresa com trabalhador não se apaga. O cadastro é a porta
    -- de entrada do prontuário, e prontuário aqui tem prazo legal.
    CONSTRAINT fk_so_trab_empresa FOREIGN KEY (empresa_id)
        REFERENCES so_empresas(id),
    CONSTRAINT fk_so_trab_usuario FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Prontuários ─────────────────────────────────────────────────────────
-- Uma linha por exame: admissional, cada periódico, retorno ao trabalho,
-- mudança de risco e o demissional. É o histórico que a NR-7 pede.
--
-- O IMC não é coluna: peso e altura são o dado, o IMC é conta. Guardar a
-- conta é guardar duas verdades que podem divergir depois de uma correção
-- de digitação.
CREATE TABLE IF NOT EXISTS so_prontuarios (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    trabalhador_id INT UNSIGNED NOT NULL,

    -- 2. Tipo de exame
    tipo       ENUM('admissional','periodico','retorno','mudanca_risco','demissional')
               NOT NULL DEFAULT 'admissional',
    data_exame DATE NOT NULL,

    -- 3. Anamnese ocupacional / riscos (PGR)
    hist_profissional VARCHAR(600) NULL,
    risco_ruido       TINYINT(1) NOT NULL DEFAULT 0,
    risco_quimico     TINYINT(1) NOT NULL DEFAULT 0,
    risco_biologico   TINYINT(1) NOT NULL DEFAULT 0,
    risco_ergonomico  TINYINT(1) NOT NULL DEFAULT 0,
    risco_psicossocial TINYINT(1) NOT NULL DEFAULT 0,
    -- NULL = não perguntado. Diferente de "não usa", que é 0.
    epi               TINYINT(1) NULL,
    antecedentes      VARCHAR(600) NULL,

    -- 4. Exame físico (triagem de enfermagem)
    pa_sistolica  SMALLINT UNSIGNED NULL,
    pa_diastolica SMALLINT UNSIGNED NULL,
    fc            SMALLINT UNSIGNED NULL,
    peso          DECIMAL(5,2) NULL,        -- kg
    altura        DECIMAL(3,2) NULL,        -- m
    acuidade_od   VARCHAR(10) NULL,
    acuidade_oe   VARCHAR(10) NULL,
    queixas       VARCHAR(600) NULL,

    -- 5. Exames complementares anexados
    audiometria_em  DATE NULL,
    espirometria_em DATE NULL,
    laboratorial_em DATE NULL,
    outros_exames   VARCHAR(200) NULL,
    obs_enfermagem  VARCHAR(600) NULL,

    -- 6. Encaminhamento e conclusão médica
    encaminhado_em DATE NULL,
    -- 'pendente' é o estado real de quase toda ficha recém-preenchida: a
    -- enfermagem faz a triagem, o médico do trabalho conclui depois.
    conclusao   ENUM('pendente','apto','apto_restricao','inapto')
                NOT NULL DEFAULT 'pendente',
    restricoes  VARCHAR(400) NULL,
    validade    DATE NULL,

    -- Quem assina
    enfermeiro_nome  VARCHAR(150) NULL,
    enfermeiro_coren VARCHAR(30)  NULL,
    medico_nome      VARCHAR(150) NULL,
    medico_crm       VARCHAR(30)  NULL,
    ciencia_em       DATE NULL,      -- quando o trabalhador tomou ciência

    usuario_id   INT          NULL,
    usuario_nome VARCHAR(150) NULL,
    created_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    KEY idx_so_pront_trab (trabalhador_id, data_exame),
    KEY idx_so_pront_data (data_exame),
    KEY idx_so_pront_conclusao (conclusao, data_exame),
    KEY idx_so_pront_validade (validade),
    KEY idx_so_pront_tipo (tipo),

    -- Sem ON DELETE CASCADE, de propósito: apagar um trabalhador não pode
    -- levar junto 20 anos de prontuário. O sistema recusa a exclusão
    -- enquanto houver ficha, e diz quantas há.
    CONSTRAINT fk_so_pront_trab FOREIGN KEY (trabalhador_id)
        REFERENCES so_trabalhadores(id),
    CONSTRAINT fk_so_pront_usuario FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Registro do módulo ──────────────────────────────────────────────────
INSERT INTO modulos (nome, slug, icone) VALUES
    ('Saúde Ocupacional', 'ocupacional', 'ocupacional')
ON DUPLICATE KEY UPDATE nome = VALUES(nome), icone = VALUES(icone);

SELECT
    (SELECT COUNT(*) FROM so_empresas)      AS empresas,
    (SELECT COUNT(*) FROM so_trabalhadores) AS trabalhadores,
    (SELECT COUNT(*) FROM so_prontuarios)   AS prontuarios;
