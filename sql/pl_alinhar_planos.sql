-- APASBS v2 — Cadastro: alinhar os dependentes ao plano do titular
-- Execute:
--   mysql -u usuario -p apasbs < sql/pl_alinhar_planos.sql
--
-- Ajuste de UMA VEZ, para os contratos que já existiam antes da regra nova.
-- Rodar de novo não faz mal: quem já está alinhado não é tocado.
--
-- ── A regra ─────────────────────────────────────────────────────────────
-- O plano é do contrato, não da pessoa: o dependente fica no plano do
-- titular, sem exceção — inclusive quem a elegibilidade indicaria noutro
-- plano. A mensalidade de cada um passa a ser a da faixa etária dele no
-- plano novo; isso é conta do sistema, nada a fazer aqui.
--
-- `plano_sugerido` NÃO é mexido de propósito: é ele que registra o que a
-- regra indicava, e é assim que a conferência posterior enxerga onde a
-- escolha da casa divergiu do regulamento.

SET NAMES utf8mb4;

-- ── 1. O que vai mudar (rode e confira antes de seguir) ─────────────────
SELECT c.id                AS contrato,
       c.numero_proposta,
       t.nome              AS titular,
       pt.nome             AS plano_do_titular,
       b.nome              AS dependente,
       COALESCE(pb.nome, '— sem plano —') AS plano_atual,
       b.status
  FROM pl_beneficiarios b
  JOIN pl_contratos     c  ON c.id = b.contrato_id
  JOIN pl_beneficiarios t  ON t.contrato_id = b.contrato_id AND t.tipo = 'titular'
  LEFT JOIN pl_planos   pt ON pt.id = t.plano_id
  LEFT JOIN pl_planos   pb ON pb.id = b.plano_id
 WHERE b.tipo = 'dependente'
   AND t.plano_id IS NOT NULL
   AND (b.plano_id IS NULL OR b.plano_id <> t.plano_id)
 ORDER BY c.id, b.nome;

-- ── 2. O ajuste ─────────────────────────────────────────────────────────
-- Contrato cujo titular está sem plano fica de fora: não há plano para
-- copiar, e chutar um mexeria no valor cobrado da família.
UPDATE pl_beneficiarios b
  JOIN pl_beneficiarios t
    ON t.contrato_id = b.contrato_id AND t.tipo = 'titular'
   SET b.plano_id = t.plano_id
 WHERE b.tipo = 'dependente'
   AND t.plano_id IS NOT NULL
   AND (b.plano_id IS NULL OR b.plano_id <> t.plano_id);

-- ── 3. O que sobrou fora ────────────────────────────────────────────────
-- Devem aparecer só contratos cujo titular ainda não tem plano definido.
SELECT c.id AS contrato, c.numero_proposta, t.nome AS titular_sem_plano,
       COUNT(b.id) AS dependentes
  FROM pl_contratos c
  JOIN pl_beneficiarios t ON t.contrato_id = c.id AND t.tipo = 'titular'
  LEFT JOIN pl_beneficiarios b ON b.contrato_id = c.id AND b.tipo = 'dependente'
 WHERE t.plano_id IS NULL
 GROUP BY c.id, c.numero_proposta, t.nome;
