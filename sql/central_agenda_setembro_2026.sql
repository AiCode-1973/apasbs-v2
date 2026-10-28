-- APASBS v2 — Agenda de Colono/Combo/EDA — SETEMBRO 2026
-- Execute DEPOIS de sql/central_agenda.sql:
--   mysql -u usuario -p apasbs < sql/central_agenda_setembro_2026.sql
--
-- Transcrição da escala enviada pela Central. Rodar de novo apenas
-- reescreve as mesmas capacidades — nunca duplica bloco nem médico.
--
-- Confere com a planilha original:
--   15 blocos · 11 dias · 34 Combos · 28 Colonos · 40 EDA · 102 vagas

-- Garante que os acentos cheguem certos, seja pelo terminal ou pelo phpMyAdmin.
SET NAMES utf8mb4;

-- ── Médicos da escala ───────────────────────────────────────────────────
-- cor 1..6 = índice na paleta categórica. Ordem fixa para o médico manter
-- sempre a mesma cor em todos os meses.
INSERT INTO central_medicos (nome, especialidade, cor, ativo) VALUES
    ('Dr. Ettore',          'Endoscopia', 1, 1),
    ('Dra. Fernanda Limoni','Endoscopia', 2, 1),
    ('Dr. Rafael',          'Endoscopia', 3, 1),
    ('Dra. Juliana',        'Endoscopia', 4, 1),
    ('Dr. Raimundo',        'Endoscopia', 5, 1)
ON DUPLICATE KEY UPDATE cor = VALUES(cor), ativo = 1;

-- ── Dias bloqueados ─────────────────────────────────────────────────────
INSERT INTO central_agenda_bloqueios (data, motivo) VALUES
    ('2026-09-07', 'Feriado — Independência do Brasil'),
    ('2026-09-08', 'Feriado — não abrir agenda (Dr. Ettore)')
ON DUPLICATE KEY UPDATE motivo = VALUES(motivo);

-- ── Escala em formato tabular ───────────────────────────────────────────
-- Uma linha por (médico, dia, turno, tipo de exame, capacidade).
DROP TEMPORARY TABLE IF EXISTS tmp_agenda_set2026;
CREATE TEMPORARY TABLE tmp_agenda_set2026 (
    medico     VARCHAR(150) NOT NULL,
    data       DATE         NOT NULL,
    turno      VARCHAR(10)  NOT NULL,
    exame      VARCHAR(20)  NOT NULL,
    capacidade SMALLINT UNSIGNED NOT NULL
) ENGINE=MEMORY DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO tmp_agenda_set2026 (medico, data, turno, exame, capacidade) VALUES
    -- Dr. Ettore — terças à tarde — até 5 combos por dia (dia 08 não abre)
    ('Dr. Ettore', '2026-09-01', 'tarde', 'COMBO', 5),
    ('Dr. Ettore', '2026-09-15', 'tarde', 'COMBO', 5),
    ('Dr. Ettore', '2026-09-22', 'tarde', 'COMBO', 5),
    ('Dr. Ettore', '2026-09-29', 'tarde', 'COMBO', 5),

    -- Dra. Fernanda Limoni — quintas de manhã — até 10 EDA por dia
    ('Dra. Fernanda Limoni', '2026-09-03', 'manha', 'EDA', 10),
    ('Dra. Fernanda Limoni', '2026-09-10', 'manha', 'EDA', 10),
    ('Dra. Fernanda Limoni', '2026-09-17', 'manha', 'EDA', 10),
    ('Dra. Fernanda Limoni', '2026-09-24', 'manha', 'EDA', 10),

    -- Dr. Rafael — segundas de manhã — 2 combos e 4 colonos
    ('Dr. Rafael', '2026-09-14', 'manha', 'COMBO',  2),
    ('Dr. Rafael', '2026-09-14', 'manha', 'COLONO', 4),
    ('Dr. Rafael', '2026-09-21', 'manha', 'COMBO',  2),
    ('Dr. Rafael', '2026-09-21', 'manha', 'COLONO', 4),
    ('Dr. Rafael', '2026-09-28', 'manha', 'COMBO',  2),
    ('Dr. Rafael', '2026-09-28', 'manha', 'COLONO', 4),

    -- Dra. Juliana — quinta à tarde — até 2 combos e 4 colonos
    ('Dra. Juliana', '2026-09-03', 'tarde', 'COMBO',  2),
    ('Dra. Juliana', '2026-09-03', 'tarde', 'COLONO', 4),

    -- Dr. Raimundo — quintas à tarde — até 2 combos e 4 colonos
    ('Dr. Raimundo', '2026-09-10', 'tarde', 'COMBO',  2),
    ('Dr. Raimundo', '2026-09-10', 'tarde', 'COLONO', 4),
    ('Dr. Raimundo', '2026-09-17', 'tarde', 'COMBO',  2),
    ('Dr. Raimundo', '2026-09-17', 'tarde', 'COLONO', 4),
    ('Dr. Raimundo', '2026-09-24', 'tarde', 'COMBO',  2),
    ('Dr. Raimundo', '2026-09-24', 'tarde', 'COLONO', 4);

-- ── Blocos ──────────────────────────────────────────────────────────────
INSERT INTO central_agenda_blocos (medico_id, data, turno)
SELECT DISTINCT m.id, s.data, s.turno
FROM tmp_agenda_set2026 s
JOIN central_medicos m ON m.nome = s.medico
ON DUPLICATE KEY UPDATE central_agenda_blocos.id = central_agenda_blocos.id;

-- ── Vagas por tipo de exame ─────────────────────────────────────────────
INSERT INTO central_agenda_vagas (bloco_id, exame_id, capacidade)
SELECT b.id, e.id, s.capacidade
FROM tmp_agenda_set2026 s
JOIN central_medicos m       ON m.nome = s.medico
JOIN central_agenda_blocos b ON b.medico_id = m.id AND b.data = s.data AND b.turno = s.turno
JOIN central_exames e        ON e.codigo = s.exame
ON DUPLICATE KEY UPDATE capacidade = VALUES(capacidade);

DROP TEMPORARY TABLE IF EXISTS tmp_agenda_set2026;

-- ── Conferência ─────────────────────────────────────────────────────────
SELECT
    (SELECT COUNT(*)      FROM central_agenda_blocos WHERE data BETWEEN '2026-09-01' AND '2026-09-30') AS blocos,
    (SELECT COUNT(DISTINCT data) FROM central_agenda_blocos WHERE data BETWEEN '2026-09-01' AND '2026-09-30') AS dias,
    (SELECT COALESCE(SUM(v.capacidade), 0)
       FROM central_agenda_vagas v
       JOIN central_agenda_blocos b ON b.id = v.bloco_id
      WHERE b.data BETWEEN '2026-09-01' AND '2026-09-30') AS vagas;
