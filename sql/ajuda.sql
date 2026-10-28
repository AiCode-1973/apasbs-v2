-- APASBS v2 — Módulo Ajuda (biblioteca de manuais)
-- Execute:
--   mysql -u usuario -p apasbs < sql/ajuda.sql
--
-- Script ADITIVO. Pode ser executado mais de uma vez sem efeito colateral.

SET NAMES utf8mb4;

-- ── Manuais ─────────────────────────────────────────────────────────────
-- O arquivo em si fica em uploads/ajuda/, com nome aleatório e extensão
-- derivada do conteúdo — nunca do nome enviado pelo navegador. Aqui ficam
-- só os dados que a tela precisa mostrar.
--
-- usuario_nome guarda o nome por escrito além do id: se o usuário sair do
-- sistema um dia, o registro continua dizendo quem publicou.
CREATE TABLE IF NOT EXISTS ajuda_manuais (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    titulo       VARCHAR(150) NOT NULL,
    descricao    VARCHAR(400) NULL,
    categoria    VARCHAR(80)  NOT NULL DEFAULT 'Geral',

    nome_arq     VARCHAR(80)  NOT NULL,
    nome_orig    VARCHAR(255) NOT NULL,
    mime         VARCHAR(100) NOT NULL,
    tamanho      INT UNSIGNED NOT NULL DEFAULT 0,

    ordem        SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    ativo        TINYINT(1)   NOT NULL DEFAULT 1,

    usuario_id   INT          NULL,
    usuario_nome VARCHAR(150) NULL,

    created_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uk_ajuda_arquivo (nome_arq),
    KEY idx_ajuda_ordem (ativo, categoria, ordem),

    CONSTRAINT fk_ajuda_usuario FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Registro do módulo ──────────────────────────────────────────────────
INSERT INTO modulos (nome, slug, icone) VALUES
    ('Manuais', 'ajuda', 'ajuda')
ON DUPLICATE KEY UPDATE nome = VALUES(nome), icone = VALUES(icone);

-- ── Os manuais publicados ──────────────────────────────────────────
-- Os arquivos correspondentes vão junto na entrega, em uploads/ajuda/.
-- São manuais separados de propósito: quem está na agenda não quer
-- folhear o mapa de sala para achar como cancelar uma marcação.
-- Manual atualizado troca de arquivo: o nome vem do md5 do PDF, e a chave
-- única da tabela é justamente o nome do arquivo. Sem esta limpeza, a versão
-- velha continuaria publicada ao lado da nova, e a pessoa baixaria a errada.
-- Só alcança os manuais que vêm com o sistema; o que foi enviado pela tela
-- não é tocado.
DELETE FROM ajuda_manuais
 WHERE titulo IN (
        'Manual da Agenda do Ambulatório',
        'Manual do Mapa de Sala',
        'Manual do POP',
        'Manual das Autorizações de Exame',
        'Manual dos Códigos de Exame',
        'Manual de Vendas',
        'Manual do Comercial',
        'Manual do Cadastro de Beneficiários',
        'Manual da Declaração de Saúde',
        'Manual da Saúde Ocupacional',
        'Manual do OPME',
        'Manual do Acesso e da Senha',
        'Manual das Coletas do ReciclaLead',
        'Manual da Auditoria Hospitalar'
 )
   AND nome_arq NOT IN (
        '7c09751cf3a50d0412e75b40636b2e24.pdf',
        '81c835c58d0ae939391c067179bb8e94.pdf',
        'b18aa5ee6c4d13a4ec0f864efa194e71.pdf',
        '32a987c72f7e7d70f047bae43dfe1410.pdf',
        '9226fb6c456f4f5685d38a9602c64a52.pdf',
        'b77b624a63d1a4b9ae22b1f4a0f76099.pdf',
        '4856435b5e4e13db7f023f758c6e6f92.pdf',
        '3bd9a9583d98adb4238129bc881a8c5c.pdf',
        'cb371a3c716e9b37b8b3f4230c2dfb56.pdf',
        '8d1a6c5684387fc82fa7b02a4c9a9bb2.pdf',
        '11516d01c8057969ec67a8a69315bf5b.pdf',
        '9e65fb05bb9eeb147d7b0b802a1a3c56.pdf',
        '6e58085f786e67f10c49fff333fc6192.pdf',
        'fcbe071de5370a3f3d962563fe4653f8.pdf'
 );

INSERT INTO ajuda_manuais
    (titulo, descricao, categoria, nome_arq, nome_orig, mime, tamanho, ordem)
VALUES
(
    'Manual da Agenda do Ambulatório',
    'Marcar, cancelar e montar a escala do mês. Com as telas do sistema, o que fazer em cada situação e as perguntas mais comuns.',
    'Ambulatório',
    '7c09751cf3a50d0412e75b40636b2e24.pdf',
    'Manual-Agenda-Ambulatorio-APASBS.pdf',
    'application/pdf',
    3393897,
    10
),
(
    'Manual do Mapa de Sala',
    'Quem ocupa cada consultório, em cada dia da semana. Ler o mapa, achar sala livre, lançar ocupações, entender os avisos de choque de horário e imprimir a folha do corredor.',
    'Ambulatório',
    '81c835c58d0ae939391c067179bb8e94.pdf',
    'Manual-Mapa-de-Sala-APASBS.pdf',
    'application/pdf',
    1559266,
    20
),
(
    'Manual do POP',
    'O que fazer e o que falar em cada situação de agendamento: achar o roteiro certo, ler os passos e os alertas, copiar a mensagem para a paciente e manter os procedimentos.',
    'Central de Agendamento',
    'b18aa5ee6c4d13a4ec0f864efa194e71.pdf',
    'Manual-POP-Central-Agendamento-APASBS.pdf',
    'application/pdf',
    1665649,
    30
),
(
    'Manual das Autorizações de Exame',
    'Pedidos de exame que dependem de liberação do convênio: registrar já com a guia e o pedido médico anexados — foto, PDF ou a página salva pelo portal —, responder ao convênio, anotar o que está pendente, datar a autorização, marcar a data, imprimir os documentos e manter os cadastros de convênios e procedimentos.',
    'Ambulatório',
    '32a987c72f7e7d70f047bae43dfe1410.pdf',
    'Manual-Autorizacoes-Exame-APASBS.pdf',
    'application/pdf',
    1577178,
    25
),
(
    'Manual dos Códigos de Exame',
    'Com que código cada convênio fatura cada exame: vários códigos por exame, busca na Tabela 22 da ANS e a consulta de onde o mesmo código aparece em outras operadoras.',
    'Ambulatório',
    '9226fb6c456f4f5685d38a9602c64a52.pdf',
    'Manual-Codigos-Exame-APASBS.pdf',
    'application/pdf',
    838508,
    27
),
(
    'Manual de Vendas',
    'Propostas de plano por vendedor: registrar a negociação, montar as vidas da família, fechar a venda criando o contrato no Cadastro, marcar perda com o motivo e acompanhar comissão — inclusive aplicando o mesmo percentual a todos.',
    'Cadastros',
    'b77b624a63d1a4b9ae22b1f4a0f76099.pdf',
    'Manual-Vendas-APASBS.pdf',
    'application/pdf',
    962911,
    24
),
(
    'Manual do Comercial',
    'Tabelas de valores dos convênios: achar quanto o convênio paga por procedimento, pacote, OPME, material, diária ou taxa, ver o que o edital traz além do valor e carregar um edital novo sem perder o anterior.',
    'Cadastros',
    '4856435b5e4e13db7f023f758c6e6f92.pdf',
    'Manual-Comercial-APASBS.pdf',
    'application/pdf',
    888539,
    25
),
(
    'Manual do Cadastro de Beneficiários',
    'Titulares, dependentes e mensalidades do plano de saúde: quem pode ser titular, como a coparticipação define o plano de cada pessoa, a faixa etária que muda no aniversário e o reajuste por vigência.',
    'Cadastros',
    '3bd9a9583d98adb4238129bc881a8c5c.pdf',
    'Manual-Cadastro-Beneficiarios-APASBS.pdf',
    'application/pdf',
    842603,
    26
),
(
    'Manual da Declaração de Saúde',
    'Doenças e lesões preexistentes declaradas na adesão: o questionário de 22 itens por pessoa, a cobertura parcial temporária de 24 meses e a folha assinada que integra o contrato.',
    'Cadastros',
    'cb371a3c716e9b37b8b3f4230c2dfb56.pdf',
    'Manual-Declaracao-Saude-APASBS.pdf',
    'application/pdf',
    1067935,
    27
),
(
    'Manual da Saúde Ocupacional',
    'Exames de NR-7 e PCMSO: cadastrar empresas e trabalhadores, preencher a ficha do admissional ao demissional, acompanhar quem está vencendo, registrar a entrega de atestado médico com protocolo e imprimir as folhas assinadas.',
    'Cadastros',
    '8d1a6c5684387fc82fa7b02a4c9a9bb2.pdf',
    'Manual-Saude-Ocupacional-APASBS.pdf',
    'application/pdf',
    2175749,
    28
),
(
    'Manual do OPME',
    'Notas de órteses, próteses e materiais especiais: lançar a nota com o parcelamento, dar baixa nas parcelas, acompanhar os vencimentos do mês e manter o cadastro de fornecedores.',
    'OPME',
    '11516d01c8057969ec67a8a69315bf5b.pdf',
    'Manual-OPME-APASBS.pdf',
    'application/pdf',
    1610517,
    40
),
(
    'Manual do Acesso e da Senha',
    'Entrar no sistema com CPF e senha, trocar a própria senha em Minha conta, o que o sistema exige de uma senha e o que é senha provisória.',
    'Ajuda',
    '9e65fb05bb9eeb147d7b0b802a1a3c56.pdf',
    'Manual-Acesso-e-Senha-APASBS.pdf',
    'application/pdf',
    666659,
    5
),
(
    'Manual da Auditoria Hospitalar',
    'Contas de internação e de PA/Ambulatório: o recorte por competência e convênio, os sete valores da auditoria, os motivos de cada glosa e o consolidado do mês, somado na hora.',
    'Faturamento',
    'fcbe071de5370a3f3d962563fe4653f8.pdf',
    'Manual-Auditoria-Hospitalar-APASBS.pdf',
    'application/pdf',
    1699403,
    26
),
(
    'Manual das Coletas do ReciclaLead',
    'O robô que busca os números da central toda semana: ler a fila, conferir o que veio, dizer em qual motivo do APASBS cada rótulo entra e gravar o fechamento da competência.',
    'Central de Agendamento',
    '6e58085f786e67f10c49fff333fc6192.pdf',
    'Manual-Coletas-ReciclaLead-APASBS.pdf',
    'application/pdf',
    1092803,
    35
)
-- ativo = 1: estes catorze são os manuais que vêm com o sistema, e reexecutar
-- o script republica os catorze. Se um deles tiver sido despublicado de
-- propósito, despublique de novo pela tela depois de rodar.
ON DUPLICATE KEY UPDATE titulo = VALUES(titulo), descricao = VALUES(descricao),
                        categoria = VALUES(categoria), ordem = VALUES(ordem),
                        tamanho = VALUES(tamanho), ativo = 1, updated_at = NOW();

SELECT id, titulo, categoria, ROUND(tamanho/1048576, 1) AS mb FROM ajuda_manuais;
