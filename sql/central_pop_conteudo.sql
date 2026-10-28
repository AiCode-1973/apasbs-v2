-- APASBS v2 — Conteúdo dos POPs da Central de Agendamento
-- GERADO por tools/gerar_pop_conteudo.py — não edite à mão.
-- Para mudar o texto, edite o script e gere de novo.
--
-- Execute DEPOIS de sql/central_pop.sql:
--   mysql -u usuario -p apasbs < sql/central_pop_conteudo.sql
--
-- Rodar de novo reescreve os POPs por completo (apaga e recria os blocos),
-- então qualquer edição feita pela tela nestes POPs é perdida. POPs criados
-- por você, com outro código, não são tocados.

SET NAMES utf8mb4;

-- ────────────────────────────────────────────────────────────────────
-- POP-CA-001 · Endoscopia — sem internação (manhã)
INSERT INTO central_pops (codigo, titulo, categoria, resumo, versao, revisado_em, ordem)
VALUES ('POP-CA-001', 'Endoscopia — sem internação (manhã)', 'Roteiros de agendamento', 'Roteiro de agendamento e mensagem de confirmação para endoscopia sem internação, no período da manhã.', '2.0', '2026-08-20', 10)
ON DUPLICATE KEY UPDATE titulo = VALUES(titulo), categoria = VALUES(categoria),
    resumo = VALUES(resumo), versao = VALUES(versao),
    revisado_em = VALUES(revisado_em), ordem = VALUES(ordem);

SET @pop := (SELECT id FROM central_pops WHERE codigo = 'POP-CA-001');
DELETE FROM central_pop_blocos WHERE pop_id = @pop;
INSERT INTO central_pop_blocos (pop_id, tipo, titulo, conteudo, ordem) VALUES
    (@pop, 'passos', 'O que fazer', 'Confirme com a paciente que ela não tem comorbidade cardiológica, pulmonar ou renal (ver POP-CA-013).
Agende o exame respeitando o limite de vagas do médico.
Passe para a paciente a data e o horário em que o exame será realizado.
Envie a mensagem de confirmação (modelo abaixo).
Envie em seguida o preparo do exame.', 10),
    (@pop, 'mensagem', 'Mensagem para a paciente', 'Hospital Santo Expedito
🩺 Exame marcado com sucesso!

📅 Data: [DATA DO EXAME]
⏰ Horário: [HORÁRIO]
👨‍⚕️ Médico(a): Dr(a).: [NOME DO MÉDICO]
🩺 Especialidade: ENDOSCOPIA

📍 Endereço: Rua Carvalho de Mendonça, 335 – Santos – 1º Andar.

Aguarde mais um pouco, iremos enviar agora o preparo para o exame.

Favor trazer o pedido médico original + guia autorizada.', 20);

-- ────────────────────────────────────────────────────────────────────
-- POP-CA-002 · Endoscopia — com internação (manhã)
INSERT INTO central_pops (codigo, titulo, categoria, resumo, versao, revisado_em, ordem)
VALUES ('POP-CA-002', 'Endoscopia — com internação (manhã)', 'Roteiros de agendamento', 'Roteiro de agendamento e mensagem de confirmação para endoscopia com internação, no período da manhã.', '2.0', '2026-08-20', 20)
ON DUPLICATE KEY UPDATE titulo = VALUES(titulo), categoria = VALUES(categoria),
    resumo = VALUES(resumo), versao = VALUES(versao),
    revisado_em = VALUES(revisado_em), ordem = VALUES(ordem);

SET @pop := (SELECT id FROM central_pops WHERE codigo = 'POP-CA-002');
DELETE FROM central_pop_blocos WHERE pop_id = @pop;
INSERT INTO central_pop_blocos (pop_id, tipo, titulo, conteudo, ordem) VALUES
    (@pop, 'passos', 'O que fazer', 'Confirme com a paciente que ela não tem comorbidade cardiológica, pulmonar ou renal (ver POP-CA-013).
Agende o exame respeitando o limite de vagas do médico.
Passe para a paciente a data e o horário em que o exame será realizado.
Passe também a data e o horário de comparecimento na internação: um dia antes do exame, às 16:00, no setor de internação.
Envie a mensagem de confirmação (modelo abaixo).
Envie em seguida o preparo do exame.', 10),
    (@pop, 'alerta', 'Convênios que não podem ser agendados', 'Não agendar Trasmontano e CAPEP neste roteiro.', 20),
    (@pop, 'mensagem', 'Mensagem para a paciente', 'Hospital Santo Expedito
🩺 Exame marcado com sucesso!

📅 Data: [DATA DO EXAME]
⏰ Horário: [HORÁRIO]
👨‍⚕️ Médico(a): Dr(a).: [NOME DO MÉDICO]
🩺 Especialidade: ENDOSCOPIA COM INTERNAÇÃO

📍 Endereço: Rua Carvalho de Mendonça, 335 – Santos – 1º Andar.

Aguarde mais um pouco, iremos enviar agora o preparo para o exame.

Favor trazer o pedido médico original + guia autorizada.

⚠️ É necessário entregar o *pedido médico original* com pelo menos 3 dias de antecedência no setor de exames. Esse prazo é necessário para que seja feita a reserva do quarto para a internação.

⚠️ Observação: Como o exame será realizado com internação, será necessário comparecer ao hospital no dia [DATA DA VÉSPERA], às 16:00, no setor de internação do hospital (térreo).', 30);

-- ────────────────────────────────────────────────────────────────────
-- POP-CA-003 · Endoscopia — com internação (manhã) + anestesista
INSERT INTO central_pops (codigo, titulo, categoria, resumo, versao, revisado_em, ordem)
VALUES ('POP-CA-003', 'Endoscopia — com internação (manhã) + anestesista', 'Roteiros de agendamento', 'Roteiro de agendamento e mensagem de confirmação para endoscopia com internação, no período da manhã, com consulta prévia do anestesista.', '2.0', '2026-08-20', 30)
ON DUPLICATE KEY UPDATE titulo = VALUES(titulo), categoria = VALUES(categoria),
    resumo = VALUES(resumo), versao = VALUES(versao),
    revisado_em = VALUES(revisado_em), ordem = VALUES(ordem);

SET @pop := (SELECT id FROM central_pops WHERE codigo = 'POP-CA-003');
DELETE FROM central_pop_blocos WHERE pop_id = @pop;
INSERT INTO central_pop_blocos (pop_id, tipo, titulo, conteudo, ordem) VALUES
    (@pop, 'passos', 'O que fazer', 'Confirme com a paciente que ela não tem comorbidade cardiológica, pulmonar ou renal (ver POP-CA-013).
Agende o exame respeitando o limite de vagas do médico.
Passe para a paciente a data e o horário em que o exame será realizado.
Passe também a data e o horário de comparecimento na internação: um dia antes do exame, às 16:00, no setor de internação.
Aproveite a presença na consulta com o anestesista para solicitar a entrega do pedido médico original e da carta de liberação do anestesista no setor de exames.
Envie a mensagem de confirmação (modelo abaixo).
Envie em seguida o preparo do exame.', 10),
    (@pop, 'alerta', 'Convênios que não podem ser agendados', 'Não agendar Trasmontano e CAPEP neste roteiro.', 20),
    (@pop, 'alerta', 'Distância entre a consulta e o exame', 'Evite marcar o anestesista muito perto da data do exame. O setor de exames ainda precisa descer a pasta da paciente para o setor de internação.', 30),
    (@pop, 'mensagem', 'Mensagem para a paciente', 'Hospital Santo Expedito
🩺 Exame marcado com sucesso!

📅 Data: [DATA DO EXAME]
⏰ Horário: [HORÁRIO]
👨‍⚕️ Médico(a): Dr(a).: [NOME DO MÉDICO]
🩺 Especialidade: ENDOSCOPIA COM INTERNAÇÃO

📍 Endereço: Rua Carvalho de Mendonça, 335 – Santos – 1º Andar.

Aguarde mais um pouco, iremos enviar agora o preparo para o exame.

Favor trazer o pedido médico original + guia autorizada.

⚠️ Trazer o *pedido médico original* no dia da consulta com o anestesista. Após a consulta, entregue o pedido médico original e a carta de liberação do anestesista na recepção de exames, localizada no 1º andar, para que seja realizada a reserva do quarto para a internação.

⚠️ Observação: Como o exame será realizado com internação, será necessário comparecer ao hospital no dia [DATA DA VÉSPERA], às 16:00, no setor de internação do hospital (térreo).', 40);

-- ────────────────────────────────────────────────────────────────────
-- POP-CA-004 · Endoscopia — sem internação (tarde)
INSERT INTO central_pops (codigo, titulo, categoria, resumo, versao, revisado_em, ordem)
VALUES ('POP-CA-004', 'Endoscopia — sem internação (tarde)', 'Roteiros de agendamento', 'Roteiro de agendamento e mensagem de confirmação para endoscopia sem internação, no período da tarde.', '2.0', '2026-08-20', 40)
ON DUPLICATE KEY UPDATE titulo = VALUES(titulo), categoria = VALUES(categoria),
    resumo = VALUES(resumo), versao = VALUES(versao),
    revisado_em = VALUES(revisado_em), ordem = VALUES(ordem);

SET @pop := (SELECT id FROM central_pops WHERE codigo = 'POP-CA-004');
DELETE FROM central_pop_blocos WHERE pop_id = @pop;
INSERT INTO central_pop_blocos (pop_id, tipo, titulo, conteudo, ordem) VALUES
    (@pop, 'passos', 'O que fazer', 'Confirme com a paciente que ela não tem comorbidade cardiológica, pulmonar ou renal (ver POP-CA-013).
Agende o exame respeitando o limite de vagas do médico.
Passe para a paciente a data e o horário em que o exame será realizado.
Envie a mensagem de confirmação (modelo abaixo).
Envie em seguida o preparo do exame.', 10),
    (@pop, 'mensagem', 'Mensagem para a paciente', 'Hospital Santo Expedito
🩺 Exame marcado com sucesso!

📅 Data: [DATA DO EXAME]
⏰ Horário: [HORÁRIO]
👨‍⚕️ Médico(a): Dr(a).: [NOME DO MÉDICO]
🩺 Especialidade: ENDOSCOPIA

📍 Endereço: Rua Carvalho de Mendonça, 335 – Santos – 1º Andar.

Aguarde mais um pouco, iremos enviar agora o preparo para o exame.

Favor trazer o pedido médico original + guia autorizada.', 20);

-- ────────────────────────────────────────────────────────────────────
-- POP-CA-005 · Endoscopia — com internação (tarde)
INSERT INTO central_pops (codigo, titulo, categoria, resumo, versao, revisado_em, ordem)
VALUES ('POP-CA-005', 'Endoscopia — com internação (tarde)', 'Roteiros de agendamento', 'Roteiro de agendamento e mensagem de confirmação para endoscopia com internação, no período da tarde.', '2.0', '2026-08-20', 50)
ON DUPLICATE KEY UPDATE titulo = VALUES(titulo), categoria = VALUES(categoria),
    resumo = VALUES(resumo), versao = VALUES(versao),
    revisado_em = VALUES(revisado_em), ordem = VALUES(ordem);

SET @pop := (SELECT id FROM central_pops WHERE codigo = 'POP-CA-005');
DELETE FROM central_pop_blocos WHERE pop_id = @pop;
INSERT INTO central_pop_blocos (pop_id, tipo, titulo, conteudo, ordem) VALUES
    (@pop, 'passos', 'O que fazer', 'Confirme com a paciente que ela não tem comorbidade cardiológica, pulmonar ou renal (ver POP-CA-013).
Agende o exame respeitando o limite de vagas do médico.
Passe para a paciente a data e o horário em que o exame será realizado.
Passe também a data e o horário de comparecimento na internação: no dia do exame, às 05:30, no setor de internação.
Envie a mensagem de confirmação (modelo abaixo).
Envie em seguida o preparo do exame.', 10),
    (@pop, 'mensagem', 'Mensagem para a paciente', 'Hospital Santo Expedito
🩺 Exame marcado com sucesso!

📅 Data: [DATA DO EXAME]
⏰ Horário: [HORÁRIO]
👨‍⚕️ Médico(a): Dr(a).: [NOME DO MÉDICO]
🩺 Especialidade: ENDOSCOPIA COM INTERNAÇÃO

📍 Endereço: Rua Carvalho de Mendonça, 335 – Santos – 1º Andar.

Aguarde mais um pouco, iremos enviar agora o preparo para o exame.

Favor trazer o pedido médico original + guia autorizada.

⚠️ É necessário entregar o *pedido médico original* com pelo menos 3 dias de antecedência no setor de exames. Esse prazo é necessário para que seja feita a reserva do quarto para a internação.

⚠️ Observação: Como o exame será realizado com internação, será necessário comparecer ao hospital no dia [DATA DO EXAME], às 05:30, no setor de internação do hospital (térreo).', 20);

-- ────────────────────────────────────────────────────────────────────
-- POP-CA-006 · Endoscopia — com internação (tarde) + anestesista
INSERT INTO central_pops (codigo, titulo, categoria, resumo, versao, revisado_em, ordem)
VALUES ('POP-CA-006', 'Endoscopia — com internação (tarde) + anestesista', 'Roteiros de agendamento', 'Roteiro de agendamento e mensagem de confirmação para endoscopia com internação, no período da tarde, com consulta prévia do anestesista.', '2.0', '2026-08-20', 60)
ON DUPLICATE KEY UPDATE titulo = VALUES(titulo), categoria = VALUES(categoria),
    resumo = VALUES(resumo), versao = VALUES(versao),
    revisado_em = VALUES(revisado_em), ordem = VALUES(ordem);

SET @pop := (SELECT id FROM central_pops WHERE codigo = 'POP-CA-006');
DELETE FROM central_pop_blocos WHERE pop_id = @pop;
INSERT INTO central_pop_blocos (pop_id, tipo, titulo, conteudo, ordem) VALUES
    (@pop, 'passos', 'O que fazer', 'Confirme com a paciente que ela não tem comorbidade cardiológica, pulmonar ou renal (ver POP-CA-013).
Agende o exame respeitando o limite de vagas do médico.
Passe para a paciente a data e o horário em que o exame será realizado.
Passe também a data e o horário de comparecimento na internação: no dia do exame, às 05:30, no setor de internação.
Aproveite a presença na consulta com o anestesista para solicitar a entrega do pedido médico original e da carta de liberação do anestesista no setor de exames.
Envie a mensagem de confirmação (modelo abaixo).
Envie em seguida o preparo do exame.', 10),
    (@pop, 'alerta', 'Distância entre a consulta e o exame', 'Evite marcar o anestesista muito perto da data do exame. O setor de exames ainda precisa descer a pasta da paciente para o setor de internação.', 20),
    (@pop, 'mensagem', 'Mensagem para a paciente', 'Hospital Santo Expedito
🩺 Exame marcado com sucesso!

📅 Data: [DATA DO EXAME]
⏰ Horário: [HORÁRIO]
👨‍⚕️ Médico(a): Dr(a).: [NOME DO MÉDICO]
🩺 Especialidade: ENDOSCOPIA COM INTERNAÇÃO

📍 Endereço: Rua Carvalho de Mendonça, 335 – Santos – 1º Andar.

Aguarde mais um pouco, iremos enviar agora o preparo para o exame.

Favor trazer o pedido médico original + guia autorizada.

⚠️ Trazer o *pedido médico original* no dia da consulta com o anestesista. Após a consulta, entregue o pedido médico original e a carta de liberação do anestesista na recepção de exames, localizada no 1º andar, para que seja realizada a reserva do quarto para a internação.

⚠️ Observação: Como o exame será realizado com internação, será necessário comparecer ao hospital no dia [DATA DO EXAME], às 05:30, no setor de internação do hospital (térreo).', 30);

-- ────────────────────────────────────────────────────────────────────
-- POP-CA-007 · Colonoscopia / Combo — sem internação (manhã)
INSERT INTO central_pops (codigo, titulo, categoria, resumo, versao, revisado_em, ordem)
VALUES ('POP-CA-007', 'Colonoscopia / Combo — sem internação (manhã)', 'Roteiros de agendamento', 'Roteiro de agendamento e mensagem de confirmação para colonoscopia / combo sem internação, no período da manhã.', '2.0', '2026-08-20', 70)
ON DUPLICATE KEY UPDATE titulo = VALUES(titulo), categoria = VALUES(categoria),
    resumo = VALUES(resumo), versao = VALUES(versao),
    revisado_em = VALUES(revisado_em), ordem = VALUES(ordem);

SET @pop := (SELECT id FROM central_pops WHERE codigo = 'POP-CA-007');
DELETE FROM central_pop_blocos WHERE pop_id = @pop;
INSERT INTO central_pop_blocos (pop_id, tipo, titulo, conteudo, ordem) VALUES
    (@pop, 'passos', 'O que fazer', 'Confirme com a paciente que ela não tem comorbidade cardiológica, pulmonar ou renal (ver POP-CA-013).
Agende o exame respeitando o limite de vagas do médico.
Passe para a paciente a data e o horário em que o exame será realizado.
Envie a mensagem de confirmação (modelo abaixo).
Envie em seguida o preparo do exame.', 10),
    (@pop, 'mensagem', 'Mensagem para a paciente', 'Hospital Santo Expedito
🩺 Exame marcado com sucesso!

📅 Data: [DATA DO EXAME]
⏰ Horário: [HORÁRIO]
👨‍⚕️ Médico(a): Dr(a).: [NOME DO MÉDICO]
🩺 Especialidade: COLONOSCOPIA

📍 Endereço: Rua Carvalho de Mendonça, 335 – Santos – 1º Andar.

Aguarde mais um pouco, iremos enviar agora o preparo para o exame.

Favor trazer o pedido médico original + guia autorizada.', 20);

-- ────────────────────────────────────────────────────────────────────
-- POP-CA-008 · Colonoscopia / Combo — com internação (manhã)
INSERT INTO central_pops (codigo, titulo, categoria, resumo, versao, revisado_em, ordem)
VALUES ('POP-CA-008', 'Colonoscopia / Combo — com internação (manhã)', 'Roteiros de agendamento', 'Roteiro de agendamento e mensagem de confirmação para colonoscopia / combo com internação, no período da manhã.', '2.0', '2026-08-20', 80)
ON DUPLICATE KEY UPDATE titulo = VALUES(titulo), categoria = VALUES(categoria),
    resumo = VALUES(resumo), versao = VALUES(versao),
    revisado_em = VALUES(revisado_em), ordem = VALUES(ordem);

SET @pop := (SELECT id FROM central_pops WHERE codigo = 'POP-CA-008');
DELETE FROM central_pop_blocos WHERE pop_id = @pop;
INSERT INTO central_pop_blocos (pop_id, tipo, titulo, conteudo, ordem) VALUES
    (@pop, 'passos', 'O que fazer', 'Confirme com a paciente que ela não tem comorbidade cardiológica, pulmonar ou renal (ver POP-CA-013).
Agende o exame respeitando o limite de vagas do médico.
Passe para a paciente a data e o horário em que o exame será realizado.
Passe também a data e o horário de comparecimento na internação: um dia antes do exame, às 16:00, no setor de internação.
Envie a mensagem de confirmação (modelo abaixo).
Envie em seguida o preparo do exame.', 10),
    (@pop, 'alerta', 'Convênios que não podem ser agendados', 'Não agendar Trasmontano e CAPEP neste roteiro.', 20),
    (@pop, 'mensagem', 'Mensagem para a paciente', 'Hospital Santo Expedito
🩺 Exame marcado com sucesso!

📅 Data: [DATA DO EXAME]
⏰ Horário: [HORÁRIO]
👨‍⚕️ Médico(a): Dr(a).: [NOME DO MÉDICO]
🩺 Especialidade: COLONOSCOPIA COM INTERNAÇÃO

📍 Endereço: Rua Carvalho de Mendonça, 335 – Santos – 1º Andar.

Aguarde mais um pouco, iremos enviar agora o preparo para o exame.

Favor trazer o pedido médico original + guia autorizada.

⚠️ É necessário entregar o *pedido médico original* com pelo menos 3 dias de antecedência no setor de exames. Esse prazo é necessário para que seja feita a reserva do quarto para a internação.

⚠️ Observação: Como o exame será realizado com internação, será necessário comparecer ao hospital no dia [DATA DA VÉSPERA], às 16:00, no setor de internação do hospital (térreo).', 30);

-- ────────────────────────────────────────────────────────────────────
-- POP-CA-009 · Colonoscopia / Combo — com internação (manhã) + anestesista
INSERT INTO central_pops (codigo, titulo, categoria, resumo, versao, revisado_em, ordem)
VALUES ('POP-CA-009', 'Colonoscopia / Combo — com internação (manhã) + anestesista', 'Roteiros de agendamento', 'Roteiro de agendamento e mensagem de confirmação para colonoscopia / combo com internação, no período da manhã, com consulta prévia do anestesista.', '2.0', '2026-08-20', 90)
ON DUPLICATE KEY UPDATE titulo = VALUES(titulo), categoria = VALUES(categoria),
    resumo = VALUES(resumo), versao = VALUES(versao),
    revisado_em = VALUES(revisado_em), ordem = VALUES(ordem);

SET @pop := (SELECT id FROM central_pops WHERE codigo = 'POP-CA-009');
DELETE FROM central_pop_blocos WHERE pop_id = @pop;
INSERT INTO central_pop_blocos (pop_id, tipo, titulo, conteudo, ordem) VALUES
    (@pop, 'passos', 'O que fazer', 'Confirme com a paciente que ela não tem comorbidade cardiológica, pulmonar ou renal (ver POP-CA-013).
Agende o exame respeitando o limite de vagas do médico.
Passe para a paciente a data e o horário em que o exame será realizado.
Passe também a data e o horário de comparecimento na internação: um dia antes do exame, às 16:00, no setor de internação.
Aproveite a presença na consulta com o anestesista para solicitar a entrega do pedido médico original e da carta de liberação do anestesista no setor de exames.
Envie a mensagem de confirmação (modelo abaixo).
Envie em seguida o preparo do exame.', 10),
    (@pop, 'alerta', 'Convênios que não podem ser agendados', 'Não agendar Trasmontano e CAPEP neste roteiro.', 20),
    (@pop, 'alerta', 'Distância entre a consulta e o exame', 'Evite marcar o anestesista muito perto da data do exame. O setor de exames ainda precisa descer a pasta da paciente para o setor de internação.', 30),
    (@pop, 'mensagem', 'Mensagem para a paciente', 'Hospital Santo Expedito
🩺 Exame marcado com sucesso!

📅 Data: [DATA DO EXAME]
⏰ Horário: [HORÁRIO]
👨‍⚕️ Médico(a): Dr(a).: [NOME DO MÉDICO]
🩺 Especialidade: COLONOSCOPIA COM INTERNAÇÃO

📍 Endereço: Rua Carvalho de Mendonça, 335 – Santos – 1º Andar.

Aguarde mais um pouco, iremos enviar agora o preparo para o exame.

Favor trazer o pedido médico original + guia autorizada.

⚠️ Trazer o *pedido médico original* no dia da consulta com o anestesista. Após a consulta, entregue o pedido médico original e a carta de liberação do anestesista na recepção de exames, localizada no 1º andar, para que seja realizada a reserva do quarto para a internação.

⚠️ Observação: Como o exame será realizado com internação, será necessário comparecer ao hospital no dia [DATA DA VÉSPERA], às 16:00, no setor de internação do hospital (térreo).', 40);

-- ────────────────────────────────────────────────────────────────────
-- POP-CA-010 · Colonoscopia / Combo — sem internação (tarde)
INSERT INTO central_pops (codigo, titulo, categoria, resumo, versao, revisado_em, ordem)
VALUES ('POP-CA-010', 'Colonoscopia / Combo — sem internação (tarde)', 'Roteiros de agendamento', 'Roteiro de agendamento e mensagem de confirmação para colonoscopia / combo sem internação, no período da tarde.', '2.0', '2026-08-20', 100)
ON DUPLICATE KEY UPDATE titulo = VALUES(titulo), categoria = VALUES(categoria),
    resumo = VALUES(resumo), versao = VALUES(versao),
    revisado_em = VALUES(revisado_em), ordem = VALUES(ordem);

SET @pop := (SELECT id FROM central_pops WHERE codigo = 'POP-CA-010');
DELETE FROM central_pop_blocos WHERE pop_id = @pop;
INSERT INTO central_pop_blocos (pop_id, tipo, titulo, conteudo, ordem) VALUES
    (@pop, 'passos', 'O que fazer', 'Confirme com a paciente que ela não tem comorbidade cardiológica, pulmonar ou renal (ver POP-CA-013).
Agende o exame respeitando o limite de vagas do médico.
Passe para a paciente a data e o horário em que o exame será realizado.
Envie a mensagem de confirmação (modelo abaixo).
Envie em seguida o preparo do exame.', 10),
    (@pop, 'mensagem', 'Mensagem para a paciente', 'Hospital Santo Expedito
🩺 Exame marcado com sucesso!

📅 Data: [DATA DO EXAME]
⏰ Horário: [HORÁRIO]
👨‍⚕️ Médico(a): Dr(a).: [NOME DO MÉDICO]
🩺 Especialidade: COLONOSCOPIA

📍 Endereço: Rua Carvalho de Mendonça, 335 – Santos – 1º Andar.

Aguarde mais um pouco, iremos enviar agora o preparo para o exame.

Favor trazer o pedido médico original + guia autorizada.', 20);

-- ────────────────────────────────────────────────────────────────────
-- POP-CA-011 · Colonoscopia / Combo — com internação (tarde)
INSERT INTO central_pops (codigo, titulo, categoria, resumo, versao, revisado_em, ordem)
VALUES ('POP-CA-011', 'Colonoscopia / Combo — com internação (tarde)', 'Roteiros de agendamento', 'Roteiro de agendamento e mensagem de confirmação para colonoscopia / combo com internação, no período da tarde.', '2.0', '2026-08-20', 110)
ON DUPLICATE KEY UPDATE titulo = VALUES(titulo), categoria = VALUES(categoria),
    resumo = VALUES(resumo), versao = VALUES(versao),
    revisado_em = VALUES(revisado_em), ordem = VALUES(ordem);

SET @pop := (SELECT id FROM central_pops WHERE codigo = 'POP-CA-011');
DELETE FROM central_pop_blocos WHERE pop_id = @pop;
INSERT INTO central_pop_blocos (pop_id, tipo, titulo, conteudo, ordem) VALUES
    (@pop, 'passos', 'O que fazer', 'Confirme com a paciente que ela não tem comorbidade cardiológica, pulmonar ou renal (ver POP-CA-013).
Agende o exame respeitando o limite de vagas do médico.
Passe para a paciente a data e o horário em que o exame será realizado.
Passe também a data e o horário de comparecimento na internação: no dia do exame, às 05:30, no setor de internação.
Envie a mensagem de confirmação (modelo abaixo).
Envie em seguida o preparo do exame.', 10),
    (@pop, 'mensagem', 'Mensagem para a paciente', 'Hospital Santo Expedito
🩺 Exame marcado com sucesso!

📅 Data: [DATA DO EXAME]
⏰ Horário: [HORÁRIO]
👨‍⚕️ Médico(a): Dr(a).: [NOME DO MÉDICO]
🩺 Especialidade: COLONOSCOPIA COM INTERNAÇÃO

📍 Endereço: Rua Carvalho de Mendonça, 335 – Santos – 1º Andar.

Aguarde mais um pouco, iremos enviar agora o preparo para o exame.

Favor trazer o pedido médico original + guia autorizada.

⚠️ É necessário entregar o *pedido médico original* com pelo menos 3 dias de antecedência no setor de exames. Esse prazo é necessário para que seja feita a reserva do quarto para a internação.

⚠️ Observação: Como o exame será realizado com internação, será necessário comparecer ao hospital no dia [DATA DO EXAME], às 05:30, no setor de internação do hospital (térreo).', 20);

-- ────────────────────────────────────────────────────────────────────
-- POP-CA-012 · Colonoscopia / Combo — com internação (tarde) + anestesista
INSERT INTO central_pops (codigo, titulo, categoria, resumo, versao, revisado_em, ordem)
VALUES ('POP-CA-012', 'Colonoscopia / Combo — com internação (tarde) + anestesista', 'Roteiros de agendamento', 'Roteiro de agendamento e mensagem de confirmação para colonoscopia / combo com internação, no período da tarde, com consulta prévia do anestesista.', '2.0', '2026-08-20', 120)
ON DUPLICATE KEY UPDATE titulo = VALUES(titulo), categoria = VALUES(categoria),
    resumo = VALUES(resumo), versao = VALUES(versao),
    revisado_em = VALUES(revisado_em), ordem = VALUES(ordem);

SET @pop := (SELECT id FROM central_pops WHERE codigo = 'POP-CA-012');
DELETE FROM central_pop_blocos WHERE pop_id = @pop;
INSERT INTO central_pop_blocos (pop_id, tipo, titulo, conteudo, ordem) VALUES
    (@pop, 'passos', 'O que fazer', 'Confirme com a paciente que ela não tem comorbidade cardiológica, pulmonar ou renal (ver POP-CA-013).
Agende o exame respeitando o limite de vagas do médico.
Passe para a paciente a data e o horário em que o exame será realizado.
Passe também a data e o horário de comparecimento na internação: no dia do exame, às 05:30, no setor de internação.
Aproveite a presença na consulta com o anestesista para solicitar a entrega do pedido médico original e da carta de liberação do anestesista no setor de exames.
Envie a mensagem de confirmação (modelo abaixo).
Envie em seguida o preparo do exame.', 10),
    (@pop, 'alerta', 'Distância entre a consulta e o exame', 'Evite marcar o anestesista muito perto da data do exame. O setor de exames ainda precisa descer a pasta da paciente para o setor de internação.', 20),
    (@pop, 'mensagem', 'Mensagem para a paciente', 'Hospital Santo Expedito
🩺 Exame marcado com sucesso!

📅 Data: [DATA DO EXAME]
⏰ Horário: [HORÁRIO]
👨‍⚕️ Médico(a): Dr(a).: [NOME DO MÉDICO]
🩺 Especialidade: COLONOSCOPIA COM INTERNAÇÃO

📍 Endereço: Rua Carvalho de Mendonça, 335 – Santos – 1º Andar.

Aguarde mais um pouco, iremos enviar agora o preparo para o exame.

Favor trazer o pedido médico original + guia autorizada.

⚠️ Trazer o *pedido médico original* no dia da consulta com o anestesista. Após a consulta, entregue o pedido médico original e a carta de liberação do anestesista na recepção de exames, localizada no 1º andar, para que seja realizada a reserva do quarto para a internação.

⚠️ Observação: Como o exame será realizado com internação, será necessário comparecer ao hospital no dia [DATA DO EXAME], às 05:30, no setor de internação do hospital (térreo).', 30);

-- ────────────────────────────────────────────────────────────────────
-- POP-CA-013 · Regras gerais de agendamento de EDA e Colonoscopia
INSERT INTO central_pops (codigo, titulo, categoria, resumo, versao, revisado_em, ordem)
VALUES ('POP-CA-013', 'Regras gerais de agendamento de EDA e Colonoscopia', 'Regras gerais', 'As nove regras que valem para todos os roteiros: convênios, comorbidades, Mounjaro, limites por médico, encaixe e exames que passam pela supervisão.', '2.0', '2026-08-20', 5)
ON DUPLICATE KEY UPDATE titulo = VALUES(titulo), categoria = VALUES(categoria),
    resumo = VALUES(resumo), versao = VALUES(versao),
    revisado_em = VALUES(revisado_em), ordem = VALUES(ordem);

SET @pop := (SELECT id FROM central_pops WHERE codigo = 'POP-CA-013');
DELETE FROM central_pop_blocos WHERE pop_id = @pop;
INSERT INTO central_pop_blocos (pop_id, tipo, titulo, conteudo, ordem) VALUES
    (@pop, 'texto', NULL, 'Regras que valem para todos os agendamentos de endoscopia e colonoscopia, independentemente do roteiro. Na dúvida, estas prevalecem.', 10),
    (@pop, 'alerta', '1. CAPEP com internação', 'Não agendar no período da manhã. O agendamento só acontece depois de autorizado. Solicite que a paciente compareça ao hospital, no setor de internação, portando o pedido médico original, a carteirinha da CAPEP e um documento oficial de identidade — é ali que se pede a autorização da CAPEP para a internação.', 20),
    (@pop, 'alerta', '2. Consulta com o anestesista', 'Evite marcar o anestesista muito perto da data do exame, principalmente em exames com internação: o setor de exames ainda vai precisar descer a pasta da paciente para o setor de internação.', 30),
    (@pop, 'alerta', '3. Exames que passam pela supervisão', 'Colono com argônio, endoscopia por cápsula, CPRE, broncoscopia, dilatação de esôfago, passagem de SNE por EDA, abertura de GTT e troca de sonda de GTT não são agendados pela central. Encaminhe à supervisão: o agendamento requer outros equipamentos ou materiais.', 40),
    (@pop, 'alerta', '4. Encaixe', 'Caso precise de encaixe, falar com a Enf. Sandra.', 50),
    (@pop, 'alerta', '5. Remarcação de exame com internação', 'Sempre que uma paciente com internação tiver a data do exame remarcada, informe o setor de exames. Eles precisam saber da nova data para encaminhar a pasta ao setor de internação no prazo adequado.', 60),
    (@pop, 'alerta', '6. Comorbidades', 'Antes de agendar qualquer exame, verifique com a paciente se possui alguma comorbidade cardiológica, pulmonar ou renal. Se possuir, ela precisa primeiro passar em consulta e obter a liberação para a realização do exame.', 70),
    (@pop, 'alerta', '7. Uso de Mounjaro', 'Verifique se a paciente faz uso de Mounjaro. Em caso positivo, o exame só pode ser realizado após 21 dias sem o uso.', 80),
    (@pop, 'alerta', '8. Limite de agendamentos por médico', 'Respeite a quantidade limite de agendamentos de cada médico, enviada pela enfermagem todo mês por e-mail — por exemplo, "Dr. X atende 4 colonoscopias + 2 combos". No sistema, esse limite é o que está lançado em Ambulatório › Agenda: quando o bloco lota, a tela não deixa marcar mais.', 90),
    (@pop, 'alerta', '9. CAPEP e biópsia', 'Para a CAPEP, é imprescindível que o pedido médico de endoscopia e/ou colonoscopia contenha a solicitação de biópsia.', 100);

SELECT categoria, COUNT(*) AS pops FROM central_pops GROUP BY categoria;
