-- APASBS v2 — Controle de consultório do ambulatório (mapa de sala)
-- Execute uma vez no banco:
--   mysql -u usuario -p apasbs < sql/amb_consultorios.sql
--
-- Script ADITIVO: não altera nenhuma tabela existente do sistema e pode ser
-- executado mais de uma vez sem efeito colateral. Ele também migra quem já
-- rodou a versão anterior, em que profissional e especialidade eram texto
-- digitado direto na ocupação.

SET NAMES utf8mb4;

-- ── Salas ───────────────────────────────────────────────────────────────
-- As oito salas do mapa. Ficam em tabela própria porque o mapa muda de
-- forma quando uma sala entra em reforma ou uma nova é aberta: basta
-- desativar ou cadastrar, sem mexer em código.
CREATE TABLE IF NOT EXISTS amb_salas (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome       VARCHAR(60)  NOT NULL,
    observacao VARCHAR(200) NULL,
    ordem      SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    ativo      TINYINT(1)   NOT NULL DEFAULT 1,
    created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uk_amb_sala_nome (nome),
    KEY idx_amb_sala_ordem (ativo, ordem)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Especialidades do ambulatório ───────────────────────────────────────
-- Cadastro curto e próprio, separado das especialidades do CFM usadas em
-- Cirurgias: no mapa o que serve é a etiqueta curta que cabe na célula
-- ("Cardio", "Ortop. Joelho"), não o nome oficial por extenso.
CREATE TABLE IF NOT EXISTS amb_especialidades (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome       VARCHAR(80)  NOT NULL,
    observacao VARCHAR(200) NULL,
    ativo      TINYINT(1)   NOT NULL DEFAULT 1,
    created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uk_amb_esp_nome (nome),
    KEY idx_amb_esp_ativo (ativo, nome)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Profissionais ───────────────────────────────────────────────────────
-- Quem ocupa sala. Cadastro próprio, e não a lista de Médicos do
-- ambulatório: aquela é dos médicos que fazem endoscopia e colonoscopia na
-- agenda, com cor fixa; esta tem toda a casa — 56 nomes, de várias
-- especialidades — e ainda as atividades que não são pessoa.
--
--   atividade → acupuntura, anestesista, prova ventilatória. Ocupam sala
--               como qualquer médico, mas podem acontecer em duas salas ao
--               mesmo tempo: ficam fora do aviso de "mesma pessoa em dois
--               lugares".
--   especialidade_id → a especialidade habitual, que vem preenchida quando
--               a pessoa é escolhida no mapa. A ocupação pode ter outra:
--               quem atende clínica num dia e gastro no outro é comum.
CREATE TABLE IF NOT EXISTS amb_profissionais (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome             VARCHAR(120) NOT NULL,
    especialidade_id INT UNSIGNED NULL,
    atividade        TINYINT(1)   NOT NULL DEFAULT 0,
    observacao       VARCHAR(200) NULL,
    ativo            TINYINT(1)   NOT NULL DEFAULT 1,
    created_at       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uk_amb_prof_nome (nome),
    KEY idx_amb_prof_ativo (ativo, nome),

    CONSTRAINT fk_prof_especialidade FOREIGN KEY (especialidade_id)
        REFERENCES amb_especialidades(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Ocupações ───────────────────────────────────────────────────────────
-- Uma ocupação = uma sala, num dia da semana, num turno, das X às Y.
-- É uma grade semanal fixa, não uma agenda de datas: o mapa vale enquanto
-- ninguém mudar. Por isso não há coluna de data.
--
--   dia_semana    → 1 = segunda … 6 = sábado (mesma convenção de date('w'))
--   periodicidade → semanal ou quinzenal (o "15/15 DIAS" da planilha)
--   observacao    → o que a planilha escrevia entre parênteses
--                   ("a partir de outubro", "sala compartilhada", …)
--
-- Sala livre não vira registro: livre é a ausência de ocupação, e é assim
-- que o mapa mostra o buraco na grade.
CREATE TABLE IF NOT EXISTS amb_ocupacoes (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sala_id          INT UNSIGNED NOT NULL,
    profissional_id  INT UNSIGNED NULL,
    especialidade_id INT UNSIGNED NULL,
    dia_semana       TINYINT UNSIGNED NOT NULL,
    turno            ENUM('manha','tarde') NOT NULL,
    hora_inicio      TIME         NOT NULL,
    hora_fim         TIME         NOT NULL,
    periodicidade    ENUM('semanal','quinzenal') NOT NULL DEFAULT 'semanal',
    observacao       VARCHAR(200) NULL,
    ativo            TINYINT(1)   NOT NULL DEFAULT 1,
    usuario_id       INT          NULL,
    created_at       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- O mesmo profissional não entra duas vezes na mesma sala, dia e horário
    -- de início. Também é o que torna o script do mapa reexecutável.
    UNIQUE KEY uk_ocupacao (sala_id, dia_semana, turno, hora_inicio, profissional_id),
    KEY idx_ocupacao_grade (dia_semana, turno, sala_id, hora_inicio),

    CONSTRAINT fk_ocupacao_sala FOREIGN KEY (sala_id)
        REFERENCES amb_salas(id) ON DELETE CASCADE,
    CONSTRAINT fk_ocupacao_profissional FOREIGN KEY (profissional_id)
        REFERENCES amb_profissionais(id) ON DELETE CASCADE,
    CONSTRAINT fk_ocupacao_especialidade FOREIGN KEY (especialidade_id)
        REFERENCES amb_especialidades(id) ON DELETE SET NULL,
    CONSTRAINT fk_ocupacao_usuario FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ════════════════════════════════════════════════════════════════════════
-- MIGRAÇÃO — só faz efeito em quem rodou a versão anterior
-- ════════════════════════════════════════════════════════════════════════
-- Antes, profissional e especialidade eram texto digitado dentro da própria
-- ocupação. Cada bloco abaixo confere o estado do banco antes de agir, então
-- rodar o script de novo (ou pela primeira vez) não faz nada de errado.
--
-- CREATE TABLE IF NOT EXISTS não altera tabela existente: as colunas novas
-- precisam ser adicionadas à mão, com guarda.

-- 1. Colunas de ligação na ocupação
SET @tem := (SELECT COUNT(*) FROM information_schema.COLUMNS
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'amb_ocupacoes'
                AND COLUMN_NAME = 'profissional_id');
SET @sql := IF(@tem = 0,
    'ALTER TABLE amb_ocupacoes ADD COLUMN profissional_id INT UNSIGNED NULL AFTER sala_id',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @tem := (SELECT COUNT(*) FROM information_schema.COLUMNS
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'amb_ocupacoes'
                AND COLUMN_NAME = 'especialidade_id');
SET @sql := IF(@tem = 0,
    'ALTER TABLE amb_ocupacoes ADD COLUMN especialidade_id INT UNSIGNED NULL AFTER profissional_id',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 2. Especialidades que existiam como texto viram cadastro
SET @tem := (SELECT COUNT(*) FROM information_schema.COLUMNS
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'amb_ocupacoes'
                AND COLUMN_NAME = 'especialidade');
SET @sql := IF(@tem = 1,
    'INSERT IGNORE INTO amb_especialidades (nome)
       SELECT DISTINCT especialidade FROM amb_ocupacoes
        WHERE especialidade IS NOT NULL AND especialidade <> ''''',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 3. Profissionais que existiam como texto viram cadastro.
--    A especialidade habitual é a que aparece em mais ocupações da pessoa.
SET @tem := (SELECT COUNT(*) FROM information_schema.COLUMNS
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'amb_ocupacoes'
                AND COLUMN_NAME = 'profissional');
SET @sql := IF(@tem = 1,
    'INSERT IGNORE INTO amb_profissionais (nome, atividade, especialidade_id)
       SELECT o.profissional, MAX(o.atividade),
              (SELECT e.id FROM amb_ocupacoes o2
                 JOIN amb_especialidades e ON e.nome = o2.especialidade
                WHERE o2.profissional = o.profissional
                GROUP BY e.id ORDER BY COUNT(*) DESC, e.id LIMIT 1)
         FROM amb_ocupacoes o
        WHERE o.profissional IS NOT NULL AND o.profissional <> ''''
        GROUP BY o.profissional',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 4. Cada ocupação passa a apontar para os cadastros
SET @sql := IF(@tem = 1,
    'UPDATE amb_ocupacoes o
       JOIN amb_profissionais p ON p.nome = o.profissional
        SET o.profissional_id = p.id
      WHERE o.profissional_id IS NULL',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(@tem = 1,
    'UPDATE amb_ocupacoes o
       JOIN amb_especialidades e ON e.nome = o.especialidade
        SET o.especialidade_id = e.id
      WHERE o.especialidade_id IS NULL',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 5. A chave única passa a ser por profissional_id.
--    A chave velha começa em sala_id, e é ela que sustenta a FK da sala:
--    derrubar direto dá "needed in a foreign key constraint". Por isso um
--    índice provisório entra antes e sai depois.
SET @velha := (SELECT COUNT(*) FROM information_schema.STATISTICS
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'amb_ocupacoes'
                  AND INDEX_NAME = 'uk_ocupacao' AND COLUMN_NAME = 'profissional');

SET @sql := IF(@velha = 1, 'ALTER TABLE amb_ocupacoes ADD KEY idx_fk_sala_tmp (sala_id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(@velha = 1, 'ALTER TABLE amb_ocupacoes DROP INDEX uk_ocupacao', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @nova := (SELECT COUNT(*) FROM information_schema.STATISTICS
               WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'amb_ocupacoes'
                 AND INDEX_NAME = 'uk_ocupacao');
SET @sql := IF(@nova = 0,
    'ALTER TABLE amb_ocupacoes
       ADD UNIQUE KEY uk_ocupacao (sala_id, dia_semana, turno, hora_inicio, profissional_id)',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- A nova chave também começa em sala_id, então o índice provisório já cumpriu
-- o papel dele e sai — o banco termina igual a uma instalação nova.
SET @tmp := (SELECT COUNT(*) FROM information_schema.STATISTICS
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'amb_ocupacoes'
                AND INDEX_NAME = 'idx_fk_sala_tmp');
SET @sql := IF(@tmp > 0, 'ALTER TABLE amb_ocupacoes DROP INDEX idx_fk_sala_tmp', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 6. Chaves estrangeiras novas
SET @tem := (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'amb_ocupacoes'
                AND CONSTRAINT_NAME = 'fk_ocupacao_profissional');
SET @sql := IF(@tem = 0,
    'ALTER TABLE amb_ocupacoes ADD CONSTRAINT fk_ocupacao_profissional
       FOREIGN KEY (profissional_id) REFERENCES amb_profissionais(id) ON DELETE CASCADE',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @tem := (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'amb_ocupacoes'
                AND CONSTRAINT_NAME = 'fk_ocupacao_especialidade');
SET @sql := IF(@tem = 0,
    'ALTER TABLE amb_ocupacoes ADD CONSTRAINT fk_ocupacao_especialidade
       FOREIGN KEY (especialidade_id) REFERENCES amb_especialidades(id) ON DELETE SET NULL',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 7. As colunas de texto saem de cena — o dado agora mora nos cadastros.
--    Só caem depois que os passos acima copiaram tudo.
SET @tem := (SELECT COUNT(*) FROM information_schema.COLUMNS
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'amb_ocupacoes'
                AND COLUMN_NAME = 'profissional');
SET @orfas := (SELECT COUNT(*) FROM amb_ocupacoes WHERE profissional_id IS NULL);
SET @sql := IF(@tem = 1 AND @orfas = 0,
    'ALTER TABLE amb_ocupacoes
       DROP COLUMN profissional, DROP COLUMN especialidade, DROP COLUMN atividade',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ── Registro do módulo ──────────────────────────────────────────────────
-- Necessário para ele aparecer na matriz de permissões. O sistema também
-- faz isso sozinho no primeiro acesso (Modules::sync).
INSERT INTO modulos (nome, slug, icone) VALUES
    ('Consultórios', 'consultorios', 'consultorios')
ON DUPLICATE KEY UPDATE nome = VALUES(nome), icone = VALUES(icone);

SELECT
    (SELECT COUNT(*) FROM amb_salas)          AS salas,
    (SELECT COUNT(*) FROM amb_especialidades) AS especialidades,
    (SELECT COUNT(*) FROM amb_profissionais)  AS profissionais,
    (SELECT COUNT(*) FROM amb_ocupacoes)      AS ocupacoes;
