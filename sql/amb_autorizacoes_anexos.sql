-- APASBS v2 — Autorizações: guia autorizada e pedido médico
--
-- VOCÊ NÃO PRECISA RODAR ESTE SCRIPT.
--
-- O módulo cria esta tabela sozinho, na primeira vez que alguém abre as
-- Autorizações — e, numa instalação que venha de antes, acrescenta sozinho a
-- coluna `conteudo`. Subir o código basta.
--
-- Ele continua aqui por um motivo só: se o usuário do banco não tiver
-- permissão para criar tabela, coisa que acontece em hospedagem compartilhada,
-- o sistema mostra uma página dizendo que falta esta tabela e apontando para
-- este arquivo. Aí alguém com permissão roda:
--
--     mysql -u usuario -p apasbs < sql/amb_autorizacoes_anexos.sql
--
-- É aditivo e pode rodar mais de uma vez.
--
-- ── Por que uma tabela, e não duas colunas ──────────────────────────────
-- "A imagem da guia" e "a imagem do pedido" parecem dois campos, mas na
-- prática quase nunca são um arquivo só: o pedido tem frente e verso, a guia
-- do convênio volta em duas páginas, e às vezes chega a foto tremida de
-- manhã e o PDF legível à tarde. Com duas colunas, a segunda foto apagaria
-- a primeira — e ninguém manda apagar documento de propósito.
--
-- Então cada arquivo é uma linha, marcada com o que ela é. O tipo fica num
-- ENUM porque são exatamente estes dois papéis: um texto livre acabaria com
-- "guia", "Guia" e "guia autorizada" significando a mesma coisa.
--
-- ── Por que o arquivo mora aqui dentro ──────────────────────────────────
-- Até outubro de 2026 o banco guardava a ficha do documento e a pasta
-- uploads/autorizacoes guardava o arquivo. São dois lugares, e só um deles
-- entra no backup da hospedagem: a pasta do site foi apagada, o banco
-- continuou inteiro, e as guias e os pedidos médicos de todas as pacientes se
-- perderam — com as fichas ainda aqui, apontando para arquivos que não
-- existiam mais.
--
-- Documento de paciente não se refaz: é preciso pedir de novo ao convênio ou
-- à pessoa. Então ele passa a viver onde o backup já alcança. O banco engorda
-- — um PDF de 2 MB ocupa 2 MB aqui e mais 2 MB em cada cópia —, e é barato
-- perto de uma tarde ligando para convênio atrás de guia perdida.
--
-- Nenhuma consulta de lista lê a coluna `conteudo`: ela sai do banco uma vez
-- só, quando alguém abre aquele documento.

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS amb_autorizacao_anexos (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    autorizacao_id INT UNSIGNED NOT NULL,

    -- guia   = a autorização que o convênio devolveu
    -- pedido = o pedido do médico que originou a solicitação
    tipo ENUM('guia','pedido') NOT NULL,

    nome_orig VARCHAR(255) NOT NULL,   -- como a pessoa chamava o arquivo
    nome_arq  VARCHAR(80)  NOT NULL,   -- nome aleatório, para quando o documento sair daqui
    mime      VARCHAR(100) NOT NULL,
    tamanho   INT UNSIGNED NOT NULL DEFAULT 0,

    -- O documento. NULL é estado legítimo, não falta de dado: são as linhas
    -- antigas, cujo arquivo estava na pasta. É assim que o sistema sabe que
    -- aquele documento se perdeu, e é isso que a tela mostra em âmbar.
    conteudo  MEDIUMBLOB   NULL,

    -- Quem anexou, por escrito além do id: se o usuário sair do sistema um
    -- dia, o registro continua dizendo quem mandou o documento.
    usuario_id   INT          NULL,
    usuario_nome VARCHAR(150) NULL,

    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    KEY idx_aut_anexo (autorizacao_id, tipo, created_at),

    CONSTRAINT fk_aut_anexo_autorizacao FOREIGN KEY (autorizacao_id)
        REFERENCES amb_autorizacoes(id) ON DELETE CASCADE,
    CONSTRAINT fk_aut_anexo_usuario FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Instalação que já tinha a tabela de antes: a coluna só é criada se faltar.
SET @sql := (
    SELECT IF(COUNT(*) = 0,
        'ALTER TABLE amb_autorizacao_anexos ADD COLUMN conteudo MEDIUMBLOB NULL AFTER tamanho',
        'DO 0')
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'amb_autorizacao_anexos'
      AND COLUMN_NAME  = 'conteudo'
);
PREPARE p FROM @sql; EXECUTE p; DEALLOCATE PREPARE p;

SELECT COUNT(*)                                 AS anexos,
       SUM(conteudo IS NOT NULL)                 AS no_banco,
       SUM(conteudo IS NULL)                     AS sem_arquivo,
       ROUND(SUM(LENGTH(conteudo)) / 1048576, 1) AS mb_no_banco
  FROM amb_autorizacao_anexos;
