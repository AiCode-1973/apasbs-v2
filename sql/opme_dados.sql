-- APASBS v2 — Carga inicial do OPME
-- GERADO por tools/gerar_opme_dados.py — não edite à mão.
-- Fonte: planilha "Opme 2026 Atual.xlsx".
--
-- Execute DEPOIS de sql/opme.sql:
--   mysql -u usuario -p apasbs < sql/opme_dados.sql
--
-- Reexecutar reescreve as notas que vieram da planilha (mesmo fornecedor,
-- número de NF e paciente) e as parcelas delas. O que for cadastrado pela
-- tela depois disso não é tocado — a não ser que repita essa mesma chave.

SET NAMES utf8mb4;

-- ── Fornecedores ────────────────────────────────────────────────────
INSERT INTO opme_fornecedores (nome) VALUES
    ('Bio2'),
    ('Bio2 New Possibilities'),
    ('Biomedicall'),
    ('Biotronik'),
    ('Biotronk'),
    ('Brsil'),
    ('Custom Medical'),
    ('Custon Medical'),
    ('E.tamussino'),
    ('Enva'),
    ('Gm Reis'),
    ('HDL Logistica Hospitalar'),
    ('Handle'),
    ('Invasive'),
    ('Isomedical'),
    ('Itamedical'),
    ('Lan Medical'),
    ('Leoni'),
    ('Lifesil'),
    ('Litoral Medic'),
    ('Max Spine'),
    ('Medic Solution'),
    ('Micromedical'),
    ('Millenium'),
    ('Mogami'),
    ('Mogani'),
    ('Motiva'),
    ('OTN Surgical'),
    ('Oteos MB'),
    ('Phisiomed'),
    ('Plantão Médico'),
    ('Promedon'),
    ('Promeho'),
    ('RCL Implantes'),
    ('RL Medical'),
    ('Richards Medical'),
    ('Russer'),
    ('SD Implant'),
    ('SD Implantes'),
    ('Saedmed'),
    ('Schiavão'),
    ('Slim'),
    ('Slim Produtos'),
    ('Storm'),
    ('Surgipro'),
    ('Vaz. Schiavão'),
    ('Wolford Medical')
ON DUPLICATE KEY UPDATE ativo = 1;

-- ── Notas fiscais ───────────────────────────────────────────────────
INSERT INTO opme_notas
    (fornecedor_id, numero_nf, paciente, data_cirurgia, hospital, medico,
     valor_total, data_nf, total_parcelas, situacao, encaminhado_hse, observacao)
VALUES
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Millenium'), '3824', 'Viviana Teles de Oliveira',
     '2025-08-28', 'HSE', 'Welber Meneses', 17800.00, '2025-09-10', 4, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Leoni'), '17.172', 'Luiz Carlos Alves Pereira',
     '2025-08-22', 'HSE', 'Luis Felipe', 19000.00, '2025-09-15', 3, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Litoral Medic'), '5385', 'Robenia Cavalcante Gonçalves Bassi de Lima',
     '2025-08-30', 'HSE', 'Welber Meneses', 50560.00, '2025-09-09', 5, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Millenium'), '3820', 'João Figueira de Faria Filho',
     '2025-08-30', 'HSE', 'Welber Meneses', 17800.00, '2025-09-09', 4, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Millenium'), '3844', 'Moacir Rodrigues de Souza',
     '2025-09-01', 'HSE', 'Welber Meneses', 17800.00, '2025-09-16', 4, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Mogami'), '18.832', 'Telma Rodrigues Pupo da Silva',
     '2025-08-29', 'HSE', 'Monica Mazzurana', 8039.35, '2025-09-12', 4, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Mogami'), '23.273', 'Lenice Vitar Manzoti Benfato',
     '2025-08-02', NULL, NULL, 1191.00, '2025-09-29', 3, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'SD Implant'), '5.567', 'Maria Lucia Simões de Oliveira',
     '2025-09-16', 'HSE', 'Breno Faria', 48900.00, '2025-09-24', 4, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Plantão Médico'), '294.156', 'Isaias Rodrigues Bezerra',
     '2025-10-11', 'São Lucas Santos', 'Rubens Palma', 4620.00, '2025-10-23', 2, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Medic Solution'), '263.999', 'João Baptista de Freitas',
     '2025-09-25', 'HSE', 'Alissom Zamara', 18595.00, '2025-10-06', 3, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Itamedical'), '8.768', 'Maria de Fátima Pereira Nicolucci',
     '2025-08-26', 'HSE', 'Carlo Alba', 12000.00, '2025-09-12', 3, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Phisiomed'), '326.944', 'Carlos Alberto Harwalys',
     '2025-10-01', 'HSE', 'Felipe Guilherme', 11300.00, '2025-10-10', 4, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Mogami'), '27.265', 'Carlos Alberto Harwalys',
     '2025-10-01', 'HSE', NULL, 504.00, '2025-10-13', 3, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Millenium'), '3985', 'Roseli dos Anjos Pereira Gonçalves',
     '2025-09-27', 'HSE', 'Welber Meneses', 17800.00, '2025-10-09', 4, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Millenium'), '3997', 'Maria Helena Mendes',
     '2025-10-06', 'HSE', 'Welber Meneses', 17800.00, '2025-10-10', 4, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Millenium'), '3989', 'Oscar Americo de Veras',
     '2025-09-26', 'HSE', 'Welber Meneses', 17800.00, '2025-10-09', 4, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Millenium'), '3995', 'Claudiomar Queiroz',
     '2025-10-02', 'HSE', 'Andre Luis D. Costa', 76080.02, '2025-10-09', 6, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Millenium'), '3986', 'Adriana de Araujo Rittes',
     '2025-09-29', 'HSE', 'Welber Meneses', 12000.00, '2025-10-09', 3, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Millenium'), '3991', 'Sthefanie de Morais',
     '2025-09-18', 'HSE', 'Welber Meneses', 17800.00, '2025-10-09', 3, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Millenium'), '3990', 'Maria de Lourdes dos Santos Ramos',
     '2025-09-27', 'HSE', 'Welber Meneses', 35000.00, '2025-10-09', 4, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Leoni'), '17.265', 'Cleonice Ribeiro de Oliveira',
     '2025-09-23', 'HSE', 'Breno Faria', 10100.00, '2025-10-07', 2, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Surgipro'), '1', 'Irene Gomes dos Santos',
     '2025-09-24', 'HSE', 'Luis Felipe', 8040.00, '2025-10-01', 3, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Biotronk'), '1151669', 'Cleyde de Albuquerque Carletti',
     '2025-10-15', 'HSE', 'Jefferson dos Santos', 8400.00, '2025-10-20', 3, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Bio2 New Possibilities'), '86406', 'Roberto Aureo Vila Boas',
     '2025-09-17', 'HSE', 'Jose Luis Santos', 9780.00, '2025-10-07', 3, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Millenium'), '4026', 'Anameure de Carvalho Seixas Santos',
     '2025-10-10', 'HSE', 'Welber Meneses', 16950.00, '2025-10-17', 4, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Millenium'), '4039', 'Vilma Menezes da Silva',
     '2025-10-14', 'HSE', 'Andre Luis D. Costa', 18000.02, '2025-10-20', 4, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Millenium'), '4040', 'Reinaldo Aurelio Pereira',
     '2026-09-26', 'HSE', 'Welber Meneses', 17800.00, '2025-10-20', 4, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Leoni'), '17.352', 'Jose Colombo Rodio',
     '2025-10-13', NULL, 'Erica de Gerard', 1000.00, '2025-10-21', 1, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Itamedical'), '8953', 'Maria Rosangela da Silva de Melo',
     '2025-07-14', 'HSE', 'Carlo Alba', 12000.00, '2025-10-22', 4, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Itamedical'), '8954', 'Jose Carlos Barriento',
     '2025-10-13', 'HSE', 'Carlo Alba', 12000.00, '2025-10-22', 4, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Medic Solution'), '265.876', 'Isaias Rodrigues Bezerra',
     '2025-10-11', NULL, 'Rubens Palma', 31445.00, '2025-10-23', 3, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Russer'), '103.973', 'Julio Cesar de Carlos',
     '2025-10-04', 'HSE', 'Itallo Braga', 2204.00, '2025-10-21', 1, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Itamedical'), '9013', 'Cintia Aparecida de Macedo da Silva',
     '2025-10-28', 'HSE', 'Daniel Paganini', 1500.00, '2025-10-29', 2, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Millenium'), '4066', 'Isaias Salviano',
     '2025-10-09', 'HSE', 'Welber Meneses', 16950.00, '2025-10-23', 4, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Litoral Medic'), '5514', 'Marina Severina Alves Vairo',
     '2025-10-16', 'HSE', 'Welber Meneses', 2000.00, '2025-10-23', 1, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Surgipro'), '4', 'Maria Eunice Lime Panhome',
     '2025-10-15', 'HSE', 'Alexandre Augusto', 8040.00, '2025-10-27', 2, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Russer'), '104.429', 'Ian Filipe Barbosa Ramos',
     '2025-10-31', 'HSE', NULL, 215.00, '2025-10-31', 1, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Plantão Médico'), '294.617', 'Luiz Alves',
     '2025-09-13', 'São Lucas Santos', 'Alissom Zamara', 23580.00, '2025-10-29', 3, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Itamedical'), '9.033', 'Giovanni Guedes Ribeiro',
     '2025-10-29', 'HSE', 'Carlo Alba', 5700.00, '2025-11-04', 2, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Russer'), '104.558', 'Marcos Antonio de Oliveira',
     '2025-09-17', 'HSE', 'Tarcisio Abib', 7990.00, '2025-11-04', 1, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Russer'), '104.431', 'Gabriel Oliveira Felix da Silva',
     '2025-09-27', 'HSE', 'Itallo Braga', 1415.00, '2025-10-31', 2, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Invasive'), '147228', 'Isaias Rodrigues Bezerra',
     '2025-10-11', 'São Lucas Santos', 'Rubens Palma', 8900.00, '2025-11-24', 3, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Medic Solution'), '266.710', 'Gilberto Pereira de Souza',
     '2025-10-16', NULL, 'Alissom Zamara', 7595.00, '2025-10-31', 3, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Itamedical'), '9.104', 'Aires de Carvalho de Oliveira',
     '2025-11-06', 'HSE', 'Carlo Alba', 12000.00, '2025-11-14', 5, 'normal', 0, 'NF 91.05 R$500,00 doação'),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Millenium'), '4175', 'Antonio Jose Almeida da Silva',
     '2025-11-10', 'HSE', 'Luis Felipe', 1700.00, '2025-11-12', 1, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Millenium'), '4188', 'Alexander Alves dos Santos',
     '2025-11-06', 'HSE', 'Welber Meneses', 29770.02, '2025-11-13', 4, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Russer'), '104.958', 'Thais Nogueira Raymundo',
     '2025-09-15', 'HSE', NULL, 1465.00, '2025-11-13', 1, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Itamedical'), '9.088', 'Cibele Cassilhas Mota Souza',
     '2025-11-03', 'HSE', 'Carlo Alba', 4500.00, '2025-11-11', 3, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Russer'), '104.869', 'Terezinha Silva Prieto',
     '2025-09-27', 'HSE', 'Itallo Braga', 2205.00, '2025-11-11', 2, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Vaz. Schiavão'), '8395', 'Alexandre Alves dos Santos',
     '2025-11-06', 'HSE', NULL, 6000.00, '2025-11-12', 1, 'normal', 0, 'Desconto para quitação.'),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Litoral Medic'), '5573', 'Erick Henrique Fuchs Rosário',
     '2025-11-08', 'HSE', 'Welber Meneses', 2000.00, '2025-11-12', 2, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Saedmed'), '73.726', 'Lenice Vitar Manzoti Benfato',
     '2025-10-21', 'HSE', NULL, 952.00, '2025-11-13', 1, 'normal', 0, 'Desconto para quitação.'),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Litoral Medic'), '5578', 'Marli de Lima',
     '2025-11-08', 'HSE', 'Welber Meneses', 2000.00, '2025-11-13', 2, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Millenium'), '4207', 'Alessandra Mendonça de Souza Alves',
     '2025-11-13', 'HSE', 'Welber Meneses', 1998.00, '2025-11-18', 2, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'SD Implant'), '5682', 'Antonio Carlos Zacchinni',
     '2025-11-04', 'HSE', 'Breno Faria', 48000.00, '2025-11-10', 5, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Custom Medical'), '7488', 'Mariana Santos Nogueira',
     '2025-10-22', 'HSE', 'Carlos Renato', 6800.00, '2025-11-17', 2, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Isomedical'), '88.225', 'Antonia Marques dos Santos',
     '2025-08-28', 'HSE', NULL, 1665.00, '2025-11-18', 1, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Saedmed'), '73.919', 'Valter Saladino Santi',
     '2025-09-19', 'HSE', NULL, 1344.00, '2025-11-24', 1, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Surgipro'), '13', 'Maria Ferreira de Souza',
     '2025-11-19', 'HSE', 'Luis Felipe', 6290.00, '2025-11-27', 3, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'E.tamussino'), '267164', 'Maria Izabel Francisca Correia',
     '2025-08-21', NULL, NULL, 2250.00, '2025-11-18', 2, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Saedmed'), '74.077', 'Celia Maria da Silva Menezes',
     '2025-09-04', NULL, NULL, 1344.00, '2025-12-01', 1, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Biomedicall'), '1.070', 'Debora Carneiro de Carvalho',
     '2025-11-28', 'HSE', 'Erica de Gerard', 800.00, '2025-12-02', 1, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Saedmed'), '74.100', 'Antonio Mores Costa',
     '2025-10-13', 'HSE', NULL, 1344.00, '2025-12-02', 1, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Litoral Medic'), '5574', 'Elida Soares de Oliveira Furtada',
     '2025-11-06', 'HSE', 'Welber Meneses', 1000.00, '2025-11-12', 2, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Enva'), '6.478', 'Luiz Alves',
     '2025-09-13', 'São Lucas Santos', 'Alissom Zamara', 10000.00, '2025-12-03', 4, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Leoni'), '17.524', 'Carlo Alberto Carvalho Ferreira',
     '2025-11-12', 'HSE', 'Alexandre', 800.00, '2025-12-01', 1, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'SD Implant'), '5.734', 'Sonia Nascimento da Mota',
     '2025-11-18', 'HSE', 'Breno Faria', 49280.00, '2025-11-28', 5, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Russer'), '105.710', 'Pedro Henrique Manzo',
     '2025-11-21', 'HSE', NULL, 2205.00, '2025-12-02', 2, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Russer'), '4287', 'Maria Lenice Bernardes Branco',
     '2025-11-25', 'HSE', 'Erica Gerard', 980.00, '2025-12-04', 1, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Isomedical'), '89.497', 'Luciana Cardoso dos Nascimento',
     '2025-10-21', NULL, NULL, 3230.00, '2025-12-09', 1, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Isomedical'), '89.496', 'Giovana Rabotzke Saramento',
     '2025-09-18', NULL, NULL, 3230.00, '2025-12-09', 1, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Leoni'), '17.503', 'Gabriel de Almeida Leite',
     '2025-11-19', 'HSE', 'Luis Felipe', 17000.00, '2025-11-26', 3, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Russer'), '105.494', 'Nilton Monte',
     '2025-09-24', NULL, NULL, 1480.00, '2025-11-27', 1, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Leoni'), '17.484', 'Edson Antonio dos Santos',
     '2025-11-18', 'HSE', 'Breno Faria', 9400.00, '2025-11-19', 2, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Leoni'), '17.497', 'Alexandre Magno Alcantara Costa',
     '2025-11-15', 'HSE', 'Luis Felipe', 3800.00, '2025-11-24', 1, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Lan Medical'), '26.203', 'Ivaneia Quintanilha Mariano de Souza',
     '2025-11-01', NULL, 'Alessandra de Moura', 71730.00, '2025-11-25', 5, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Millenium'), '4286', 'Nathalia Cristina Gomes Prado',
     '2026-11-28', 'HSE', 'Anderson Galdo', 16950.00, '2025-12-04', 4, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Millenium'), '4320', 'Valter dos Passos Jr.',
     '2025-11-27', 'HSE', 'Welber Meneses', 16950.00, '2025-12-12', 4, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Litoral Medic'), '5686', 'Manoel Edmilton da Silva',
     '2025-11-27', 'HSE', 'Welber Meneses', 2400.00, '2025-12-11', 3, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Litoral Medic'), '5685', 'Rhaisa Martins Franco',
     '2025-11-27', 'HSE', 'Welber Meneses', 2000.00, '2025-12-11', 3, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Litoral Medic'), '5688', 'Arleide Maria Simões dos Santos',
     '2025-12-04', 'HSE', 'Welber Meneses', 2000.00, '2025-12-11', 2, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Millenium'), '4350', 'Helena Aparecida Ghiraldelli',
     '2025-12-01', 'HSE', 'Luis Felipe', 1700.00, '2025-12-16', 1, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Russer'), '106.346', 'Marcelo da Silva',
     '2025-12-05', NULL, NULL, 2235.00, '2025-12-16', 2, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Promeho'), '1.365', 'Edgard Valentim Jr',
     '2025-11-25', NULL, NULL, 1450.00, '2025-12-29', 2, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Storm'), '301.774', 'João Camilo Filho',
     '2025-12-10', NULL, 'Sergio Slearuc', 2300.00, '2025-12-16', 2, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Millenium'), '4351', 'Cleide Aparecida Ramos Rosa',
     '2025-12-01', 'HSE', 'Welber Meneses', 16950.00, '2025-12-16', 4, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'SD Implant'), '5769', 'Neiri Souza de Farias',
     '2025-12-02', 'HSE', 'Breno Faria', 48000.00, '2025-12-05', 5, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Phisiomed'), '335.701', 'Sidnei Albano',
     '2025-11-20', 'HSE', 'Felipe Guilherme', 10100.00, '2025-11-28', 4, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Millenium'), '4389', 'Cacilda da Silva Rufino',
     '2025-12-18', 'HSE', 'Welber Meneses', 1998.00, '2025-12-22', 1, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Medic Solution'), '270.834', 'Lisete Flores Gomes Fernandes',
     '2025-11-27', NULL, 'Alissom Zamara', 7045.00, '2025-12-23', 2, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Isomedical'), '89.405', 'Alexandra Olivia da Silva Lopes',
     '2025-10-01', NULL, NULL, 4110.00, '2025-12-08', 1, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Millenium'), '4352', 'Samuel Ferreira da Silva',
     '2025-12-01', 'HSE', 'Welber Meneses', 16950.00, '2025-12-16', 4, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Itamedical'), '9159', 'Douglas Leite Pereira',
     '2025-11-24', 'HSE', 'Carlo Alba', 6200.00, '2025-11-25', 3, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Millenium'), '17.553', 'Willians Santos Baracho',
     '2025-12-02', NULL, NULL, 9400.00, '2025-12-05', 2, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Custon Medical'), '7.602', 'Mariana Santos Nogueira',
     '2025-12-08', NULL, NULL, 20700.00, '2025-12-08', 3, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Leoni'), '17.597', 'Edinaldo Jose da Silva',
     '2025-12-09', 'HSE', 'Breno Faria', 10100.00, '2025-12-12', 2, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Leoni'), '17.598', 'Vera Lucia dos Santos',
     '2025-12-09', 'HSE', 'Breno Faria', 10100.00, '2025-12-12', 2, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Storm'), '301.815', 'Anna Clara Marques Pereira',
     '2025-12-08', NULL, 'Jose Marcelo', 2300.00, '2025-12-18', 2, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Storm'), '301.791', 'Bruna Peres de Paiva',
     '2025-12-15', 'HSE', 'Daniel Reis', 2300.00, '2025-12-17', 2, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Promedon'), '517871', 'Maria Tereza Araujo Vasques',
     '2025-11-10', NULL, NULL, 3960.00, '2025-12-22', 1, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Plantão Médico'), '300.613', 'Celia Maria da Silva Menezes',
     '2025-12-23', NULL, 'Alissom Zamara', 9745.00, '2025-12-29', 3, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Leoni'), '17.657', 'Ilma Ribeiro da Silva Ferreira',
     '2025-12-29', NULL, NULL, 9400.00, '2026-01-05', 2, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'RL Medical'), '517', 'Celso Aparecido Campos',
     '2025-12-26', NULL, 'Matheus Bastos', 800.00, '2026-01-06', 1, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Leoni'), '17.677', 'Elza Alves Bergamasco',
     '2025-12-24', NULL, NULL, 9100.00, '2026-01-08', 2, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Russer'), '106.776', 'Rafael Rodrigues Froio',
     '2025-12-13', NULL, NULL, 2205.00, '2026-01-08', 2, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Itamedical'), '9.345', 'Hevellin de Andrade Alves',
     '2025-12-16', 'HSE', 'Carlo Alba', 3500.00, '2026-01-05', 2, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'E.tamussino'), '272930', 'Iara Palasson Garcia',
     '2025-10-14', NULL, NULL, 1400.00, '2025-12-19', 1, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Slim'), '34.859', 'Vivian Silva Cursino',
     '2025-12-19', 'HSE', 'Erica Gerard', 1155.00, '2025-12-29', 1, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Slim'), '34.863', 'David Mendes dos Santos Filho',
     '2025-12-18', 'HSE', 'Ederson Raimundo', 1155.00, '2025-12-30', 1, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Phisiomed'), '337.069', 'Lais Augusto Coelho Nantes Lima',
     '2025-12-25', 'HSE', 'Felipe Guilherme', 9500.00, '2025-12-05', 3, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Plantão Médico'), '303.178', 'Celia Maria da Silva Menezes',
     '2026-01-13', 'São Lucas Santos', 'Alissom Zamara', 9745.00, '2026-01-27', 3, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Wolford Medical'), '16.573', 'Lourdes Senna Goncalves',
     '2026-01-10', NULL, 'Matheus Bastos', 13550.00, '2026-01-28', 3, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'OTN Surgical'), '21830', 'Nilton Cota',
     '2025-08-25', 'HSE', 'Luis Felipe', 8900.00, '2026-01-28', 4, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Russer'), '107.532', 'Maria Lucia Rodrigues Santos',
     '2026-01-06', NULL, NULL, 3095.00, '2026-01-29', 2, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Millenium'), '4581', 'Leide Aparecida dos Santos Orti',
     '2026-01-17', 'HSE', 'Welber Meneses', 16950.00, '2026-01-30', 4, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Millenium'), '4557', 'Marina Severina Alves Vairo',
     '2026-01-19', 'HSE', 'Welber Meneses', 16950.00, '2026-01-29', 4, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Surgipro'), '326.944', 'Marcelo Rodrigues do Nascimento',
     '2026-01-05', 'HSE', 'Alexandre Vianna', 8040.00, '2026-01-29', 4, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Surgipro'), '33', 'Erick Alecsander Pereira Pandalpho',
     '2026-01-10', 'HSE', 'Jose Luis', 2000.00, '2026-01-29', 2, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Millenium'), '4593', 'Christhianne Arantes D. B. dos Santos',
     '2026-01-29', 'HSE', 'Welber Meneses', 16950.00, '2026-02-03', 4, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Russer'), '107.232', 'Charles Nunes de Oliveira',
     '2025-11-27', NULL, NULL, 3095.00, '2026-01-22', 2, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Russer'), '107.744', 'Julio Cesar de Carlos',
     '2025-08-15', NULL, NULL, 2305.00, '2026-02-05', 1, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Russer'), '107.748', 'Vanessa Baptista Valentim da Silva',
     '2026-01-16', NULL, NULL, 3240.00, '2026-02-05', 1, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Mogami'), '56.376', 'Monica Alves dos Santos Oliveira',
     '2025-10-31', NULL, NULL, 1390.00, '2026-02-06', 1, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Millenium'), '4613', 'Maria de Fatima Souza Silva',
     '2026-02-05', 'HSE', 'Welber Meneses', 16950.00, '2026-02-09', 4, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Mogami'), '56.673', 'Alessandre Jorge Chiapetta',
     '2025-11-04', NULL, NULL, 1900.00, '2026-02-09', 1, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Isomedical'), '92.591', 'Ricardo Ferreira da Silva',
     '2026-01-13', 'HSE', 'Wagner Riva', 1500.00, '2026-02-12', 1, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Isomedical'), '92.664', 'Luiz Antonio Martins',
     '2026-01-15', NULL, NULL, 267.00, '2026-02-13', 1, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Handle'), '1551.429', 'Carla Regina Novaes Arruda',
     '2026-01-16', 'HSE', 'Ittalo Henrique', 2020.00, '2026-01-30', 4, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Micromedical'), '69614', 'Dennys Willian Conceição Costa',
     '2025-08-27', NULL, 'Behramann Martins', 14841.00, '2026-01-30', 6, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Leoni'), '17.785', 'Helena Alambert',
     '2026-01-21', NULL, 'Luis Felipe', 7100.00, '2026-02-04', 2, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Biotronik'), '1163173', 'Raimundo Osvanir Queiroz do Nascimento',
     '2026-01-10', NULL, 'Jefferson do Santos', 8800.00, '2026-02-03', 3, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Leoni'), '17786', 'Lindinaldo Cordeiro de Andrade',
     '2026-01-27', NULL, NULL, 9400.00, '2026-02-04', 2, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Mogani'), '56450', 'Leticia Santos de Souza',
     '2025-12-10', NULL, NULL, 7460.00, '2026-02-09', 4, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Russer'), '107835', 'Neuza Gonçalves Lima',
     '2025-10-17', NULL, NULL, 2135.00, '2026-02-09', 1, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Russer'), '107842', 'Alda Fonseca Guimarães',
     '2026-01-06', NULL, NULL, 1245.00, '2026-02-09', 1, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Surgipro'), '36', 'João Figueira de Maria Filho',
     '2026-01-26', NULL, NULL, 1000.00, '2026-02-10', 1, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Biotronik'), '1164471', 'Marta Rodrigues Ferreira',
     '2026-02-06', NULL, NULL, 8800.00, '2026-02-12', 3, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'OTN Surgical'), '21917', 'Leonardo Brambilla Nicolaci Fincatti',
     '2025-09-15', NULL, NULL, 20000.00, '2026-02-11', 5, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Surgipro'), '35', 'Josefa Pereira da Silva dos Santos',
     '2026-01-07', NULL, NULL, 7090.00, '2026-02-10', 4, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Russer'), '108.344', 'Marcos Antonio de Oliveira',
     '2026-02-06', NULL, NULL, 1250.00, '2026-02-25', 1, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Leoni'), '17.908', 'Wagner Rodrigues Moraes',
     '2026-02-11', NULL, NULL, 12000.00, '2026-02-25', 3, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Leoni'), '17.909', 'Celia Costa Saldanha',
     '2026-02-18', NULL, NULL, 19000.00, '2026-02-25', 3, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Itamedical'), '9589', 'Juliana da Conceição Costa Pinheiro',
     '2026-02-14', NULL, NULL, 4500.00, '2026-02-25', 3, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Lifesil'), '2584', 'Nair Vilella Gaiva Victoriano',
     '2026-02-25', NULL, 'NF cancelada', 2890.00, '2026-02-19', 1, 'cancelada', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Brsil'), '3179', 'Nair Vilella Gaiva Victoriano',
     '2026-02-25', NULL, NULL, 2890.00, '2026-03-27', 2, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Mogami'), '62.567', 'Sidnei Albano',
     '2025-11-20', NULL, NULL, 109.00, '2026-03-02', 1, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Mogami'), '62.744', 'João Carlos Costa',
     '2026-01-29', NULL, NULL, 8334.00, '2026-03-02', 4, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'RL Medical'), '547', 'Diogo dos Santos Barbosa',
     '2026-02-15', NULL, NULL, 1600.00, '2026-02-27', 2, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Isomedical'), '94.759', 'Atila Gregório Pereira',
     '2026-01-14', NULL, NULL, 3230.00, '2026-03-23', 2, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Micromedical'), '70740', 'Rosilda Rodrigues de Carvalho',
     '2026-01-21', NULL, 'Behramann Martins', 37894.00, '2026-03-12', 4, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Gm Reis'), '40.565', 'Gabriel dos Santos da Silva',
     '2026-02-18', 'HSE', 'Igor Marijuschkim', 12890.00, '2026-03-19', 5, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Storm'), '302251', 'Amanda Soares da Silva',
     '2026-01-19', NULL, NULL, 1600.00, '2026-02-02', 2, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Leoni'), '17.838', 'Claudia Aparecida Fuchs Pereira Silva',
     '2026-02-10', 'HSE', 'Breno Faria', 10100.00, '2026-02-12', 2, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Leoni'), '17.839', 'Sergio Luiz Gugel',
     '2026-02-10', NULL, NULL, 10100.00, '2026-02-12', 2, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Millenium'), '4651', 'Ravelly Maria Siqueira Vera',
     '2026-02-11', NULL, NULL, 999.00, '2026-02-19', 1, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Leoni'), '17.924', 'Anna Karolina Pereira Oliveira',
     '2026-02-25', 'HSE', 'Luis Felipe', 9000.00, '2026-03-05', 2, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Surgipro'), '48', 'Josefa Rodrigues da Costa',
     '2026-02-23', 'HSE', 'Luis Felipe', 4000.00, '2026-03-04', 3, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Mogami'), '62.783', 'Thamara de Oliveira Santana do Carmo',
     '2026-02-23', NULL, NULL, 7460.00, '2026-03-02', 4, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Mogami'), '62.781', 'Rosangela Cortez Alves',
     '2026-01-05', NULL, NULL, 1350.00, '2026-03-02', 2, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Millenium'), '4722', 'Ojair Luiz',
     '2026-02-23', NULL, NULL, 16950.00, '2026-03-10', 4, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Millenium'), '4752', 'Wesley Gonçalves',
     '2026-02-26', 'HSE', 'Welber Meneses', 16950.00, '2026-03-16', 4, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Leoni'), '17.967', 'Anderson de Oliveira Carvalho',
     '2026-03-04', 'HSE', 'Luis Felipe', 19000.00, '2026-03-17', 3, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Surgipro'), '56', 'Inacia Fortunato Silva de Jesus',
     '2026-03-05', 'HSE', 'Welber Meneses', 2000.00, '2026-03-16', 2, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Surgipro'), '55', 'Rita de Cassia Castilha dos Santos',
     '2026-03-05', 'HSE', 'Welber Meneses', 2000.00, '2026-03-16', 2, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Micromedical'), '70979', 'Manoel Edmilton da Silva',
     '2026-03-11', 'São Lucas Santos', 'Acacio Fernandes', 5179.00, '2026-03-20', 3, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Itamedical'), '9.731', 'Carlos Alberto Alves Lourenço',
     '2026-03-09', 'HSE', 'Carlo Alba', 4500.00, '2026-03-24', 3, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Itamedical'), '9.730', 'Celeste Santos Paixão',
     '2026-03-09', 'HSE', 'Carlo Alba', 4500.00, '2026-03-24', 4, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Mogami'), '69.271', 'Maria de Fátima Vieira Sales Evaristo',
     '2026-03-09', NULL, NULL, 1589.00, '2026-03-24', 2, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Millenium'), '4814', 'Elaine Christine de Souza',
     '2026-03-05', 'HSE', 'Welber Meneses', 16950.00, '2026-03-25', 4, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Russer'), '109.505', 'Sandra Mota da Silva Souza',
     '2026-03-06', NULL, NULL, 3095.00, '2026-03-25', 1, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Mogami'), '70124', 'Elizabete Oliveira de Souza',
     '2026-02-06', NULL, NULL, 109.00, '2026-03-26', 3, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Russer'), '109.600', 'Raphael de Oliveira Lacalentola',
     '2026-03-06', NULL, NULL, 2205.00, '2026-03-27', 1, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Surgipro'), '60', 'Marlucy da Silva Fernandes',
     '2026-03-16', 'HSE', 'Luis Felipe', 5500.00, '2026-03-27', 3, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Itamedical'), '9.755', 'Marcio Acioly dos Santos',
     '2026-03-30', 'HSE', 'Carlo Alba', 4500.00, '2026-03-31', 4, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'RL Medical'), '557', 'Oscar Correia de Lima',
     '2026-03-27', NULL, 'Matheus Bastos', 4150.00, '2026-04-02', 3, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Max Spine'), '123', 'Guilherme Coelho de Sá',
     '2026-01-23', NULL, NULL, 3072.80, '2026-03-05', 3, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'RCL Implantes'), '413061', 'Edson Jose da Silva Lopes',
     '2026-02-26', NULL, 'Ricardo de Arruda', 15450.00, '2026-04-15', 3, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Bio2'), '87833', 'Reinaldo Almeida',
     '2026-04-07', NULL, 'Ricardo de Arruda', 14255.00, '2026-04-22', 3, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Leoni'), '17.827', 'Eduardo Lino de Jesus',
     '2026-01-26', 'HSE', 'Luis Felipe', 4200.00, '2026-02-11', 1, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Millenium'), '4828', 'Soelene de Castro Farat',
     '2026-03-12', 'HSE', 'Welber Meneses', 16950.00, '2026-03-27', 4, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Millenium'), '4829', 'Jose Brasil da Silva',
     '2026-03-12', 'HSE', 'Welber Meneses', 7500.00, '2026-03-27', 2, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Millenium'), '4953', 'Christhianne Arantes D. B. dos Santos',
     '2026-03-26', NULL, 'Welber Meneses', 41505.00, '2026-04-29', 4, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Leoni'), '17.975', 'Jeane Cardoso da Silva',
     '2026-06-09', 'HSE', 'Luis Felipe', 2390.00, '2026-03-18', 1, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Leoni'), '17923', 'Gabriel de Almeida Leite',
     '2026-02-25', NULL, 'Luis Felipe', 900.00, '2026-03-05', 1, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Leoni'), '17.968', 'Dilson Leal',
     '2026-03-05', NULL, 'Ederson', 2180.00, '2026-03-17', 1, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Leoni'), '18.009', 'Maria da Conceição Rodrigues de Amorim',
     '2026-03-18', NULL, 'Alexandre', 3900.00, '2026-03-24', 1, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Leoni'), '18.025', 'Joselice Caseiro Gomes Rodrigues',
     '2026-03-11', NULL, 'Alexandre', 4500.00, '2026-04-15', 1, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Leoni'), '18.047', 'Manoel Domingos Feitoza',
     '2026-03-28', 'HSE', 'Luis Felipe', 900.00, '2026-03-31', 1, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Leoni'), '18.046', 'Charles Soares Paulino Jr.',
     '2026-03-28', 'HSE', 'Luis Felipe', 4200.00, '2026-03-31', 1, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Mogami'), '72.615', 'Zulmira Cordeiro Souza Santos',
     '2026-03-26', NULL, NULL, 114.00, '2026-04-06', 1, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Max Spine'), '155', 'Zulmira Cordeiro Souza Santos',
     '2026-03-26', NULL, NULL, 8540.50, '2026-04-30', 5, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Isomedical'), '95.723', 'Vanessa Fancelli Zachini',
     '2026-02-19', NULL, NULL, 8285.00, '2026-04-09', 2, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Millenium'), '4882', 'Mario Medeiros',
     '2026-03-19', 'HSE', 'Welber Meneses', 16950.00, '2026-04-10', 4, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Russer'), '110.057', 'Maikon da Silva Menezes',
     '2026-03-06', NULL, NULL, 3490.00, '2026-04-09', 1, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Millenium'), '4881', 'Mariana Bispo dos Santos Martins',
     '2026-03-21', 'HSE', 'Welber Meneses', 31170.00, '2026-04-10', 4, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Schiavão'), '9392', 'Mariana Bispo dos Santos Martins',
     '2026-03-21', NULL, NULL, 6000.00, '2026-04-08', 1, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Richards Medical'), '7167', 'Noah Tomadacci Amaral',
     '2026-03-03', NULL, NULL, 280.00, '2026-04-17', 2, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Surgipro'), '62', 'Thamyres Victoria Quaresma Gonçalves',
     '2026-04-08', 'HSE', 'Luis Felipe', 2600.01, '2026-04-16', 2, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Schiavão'), '9486', 'Maria Izabel Machado Fonseca',
     '2026-04-13', NULL, NULL, 5631.00, '2026-04-17', 2, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Millenium'), '4962', 'Maria Izabel Machado Fonseca',
     '2026-04-13', 'HSE', 'Welber Meneses', 70920.02, '2026-04-29', 5, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Itamedical'), '9857', 'Luiz Antonio Cote',
     '2026-02-24', 'HSE', 'Carlo Alba', 4500.00, '2026-04-17', 3, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Itamedical'), '9858', 'Alessandra Aparecida Peixoto Leão',
     '2026-02-24', 'HSE', 'Carlo Alba', 4500.00, '2026-04-17', 3, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Millenium'), '4927', 'Lucia Cristina dos Santos',
     '2026-04-09', 'HSE', 'Andre Luiz D.Costa', 19535.01, '2026-04-22', 4, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Itamedical'), '9859', 'Sandro Fragoso Marcal Silva',
     '2026-04-06', 'HSE', 'Carlo Alba', 4500.00, '2026-04-17', 3, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Itamedical'), '9861', 'Gerson Roberto da Silva',
     '2026-03-30', 'HSE', 'Carlo Alba', 12000.00, '2026-04-17', 5, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Itamedical'), '9860', 'Gerson Roberto da Silva',
     '2026-03-30', 'HSE', 'Carlo Alba', 295.00, '2026-04-17', 1, 'doacao', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Slim'), '35826', 'Silvia Danielle de Souza Caxiado Santos',
     '2026-04-11', NULL, 'Igor Marijuschkim', 960.00, '2026-04-22', 2, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Millenium'), '4930', 'Edson Jose Alves',
     '2026-04-09', 'HSE', 'Welber Meneses', 33000.00, '2026-04-22', 4, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Russer'), '110.666', 'Bruno Guedes Monteiro de Freitas',
     '2026-04-11', NULL, NULL, 2290.00, '2026-04-24', 1, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Isomedical'), '96762', 'Antonio Carlos do Carmo',
     '2026-03-24', 'Santa Casa', 'Jefferson dos Santos', 7626.55, '2026-04-27', 2, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Isomedical'), '96.761', 'Vanderlei Gomes Ferreira',
     '2026-03-20', 'Santa Casa', 'Jefferson dos Santos', 6987.55, '2026-04-27', 2, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Itamedical'), '9926', 'Denise Alves de Mello Lopes',
     '2026-04-17', 'HSE', 'Carlo Alba', 4500.00, '2026-04-24', 3, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Slim Produtos'), '35659', 'Charles Alves dos Santos',
     '2026-03-20', NULL, 'Erica Gerard', 3780.00, '2026-03-30', 3, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Isomedical'), '96794', 'Reinaldo Aurelio Pereira',
     '2026-03-19', NULL, NULL, 1230.00, '2026-04-27', 2, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Russer'), '111.033', 'Doraci de Lourdes Ferreira Souza',
     '2026-04-24', NULL, NULL, 3240.00, '2026-05-05', 1, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Surgipro'), '73', 'Geraldo José de Oliveira',
     '2026-05-06', 'HSE', 'Luis Filipe Pellegrini', 2600.01, '2026-05-15', 2, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Handle'), '1616385', 'Silvio Batista dos Santos',
     '2026-03-24', 'HSE', 'Tarcisio Abib Neto', 1480.00, '2026-05-26', 2, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Handle'), '93434', 'Silvio Batista dos Santos',
     '2026-03-24', 'HSE', 'Tarcisio Abib Neto', 2500.00, '2026-05-26', 2, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Max Spine'), '166', 'Elizabete Oliveira de Souza',
     '2026-02-06', 'HSE', 'Felipe G.da S. Souza', 8540.00, '2026-05-15', 4, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Surgipro'), '68', 'Annameure de Carvalho Seixa Santos',
     NULL, 'HSE', 'Welber Meneses', 2000.00, '2026-05-12', 2, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Millenium'), '5028', 'Maria de Lourdes dos Santos Ramos',
     '2026-04-30', 'HSE', 'Welber Meneses', 35000.01, '2026-05-13', 5, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Leoni'), '18.077', 'Zelia Tavares Gonçalves',
     '2026-04-04', NULL, 'Luis Felipe', 6180.00, '2026-04-08', 1, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Leoni'), '18139', 'Matheus Martins Rodrigues',
     '2026-04-13', NULL, 'Alexandre', 6000.00, '2026-04-22', 1, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Leoni'), '18195', 'Dulcenete Motta de Melo',
     '2026-04-28', NULL, 'Breno Faria', 8000.00, '2026-05-04', 2, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Leoni'), '18197', 'Kleber Fernandes dos Santos',
     '2026-04-28', NULL, 'Breno Faria', 900.00, '2026-05-04', 1, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Leoni'), '18.198', 'Maria Pureza de Jesus Santos',
     '2026-04-28', NULL, 'Luis Felipe', 10200.00, '2026-05-04', 3, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Surgipro'), '77', 'Joel Gonçalves dos Santos',
     '2026-05-09', 'HSE', 'Luis Felipe', 10500.00, '2026-05-29', 4, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Millenium'), '5042', 'Wilma Alves Dias',
     '2026-04-16', 'HSE', 'Andre Luis Domingues', 27862.00, '2026-05-15', 4, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Leoni'), '18314', 'Ednaldo de Pontes Gouveia',
     '2026-05-18', 'HSE', 'Erica Gerard', 3990.00, '2026-07-10', 2, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Surgipro'), '75', 'Luiza Silva Pinto de Abreu',
     '2026-05-09', 'HSE', 'Luis Filipe Pellegrini', 9500.01, '2026-05-25', 3, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Russer'), '111.751', 'Claudio Luiz dos Santos',
     '2026-05-01', NULL, NULL, 3240.00, '2026-05-26', 1, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Micromedical'), '73340', 'Eli Hurtado Ferreira',
     '2026-04-29', 'São Lucas Santos', 'Acacio Fernandes', 32749.00, '2026-05-27', 4, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Micromedical'), '73341', 'Eli Hurtado Ferreira',
     '2026-04-29', 'São Lucas Santos', 'Acacio Fernandes', 861.98, NULL, 1, 'doacao', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Biotronik'), '1177349', 'Antonio de Oliveira Paixão',
     '2026-05-04', 'HSE', 'Jefferson dos Santos', 6000.00, '2026-05-26', 3, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Litoral Medic'), '6090', 'Eleide dos Santos Andrade',
     '2025-05-19', 'HSE', 'Rubens Francisco', 12260.00, '2026-05-26', 3, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Surgipro'), '74', 'Ana Maria de Oliveira Souza',
     '2026-04-22', 'HSE', 'Luis Filipe Pellegrini', 9000.00, '2026-05-25', 4, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Surgipro'), '79', 'Milene Pontes Belem dos Santos',
     '2026-04-27', 'HSE', 'Welber Meneses', 1000.00, '2026-06-09', 1, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Mogami'), '92.499', 'Wagner dos Santos Rodrigues',
     '2026-05-22', 'HSE', NULL, 4667.60, '2026-06-16', 3, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'HDL Logistica Hospitalar'), '164.079', 'Rogerio Silvano',
     '2026-06-22', 'HSE', NULL, 926.00, '2026-06-19', 1, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Oteos MB'), '42.335', 'Silvana Aparecida C. Silva',
     '2026-04-07', 'HSE', 'Ricardo de Arruda', 14000.00, '2026-06-09', 4, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Biotronik'), '1177564', 'Luiz Armando Moraes',
     '2026-05-15', 'HSE', 'Jefferson dos Santos', 8800.00, '2026-05-27', 3, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Gm Reis'), '40.565', 'Gabriel de Almeida Leite',
     '2026-02-18', NULL, NULL, 12890.00, '2026-03-19', 5, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'SD Implantes'), '6219', 'Luiz Tavares',
     '2026-05-23', 'HSE', 'Sergio Ribeiro', 29900.00, '2026-06-10', 4, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Millenium'), '5142', 'Ronaldo Lima Teixeira',
     '2026-04-14', 'HSE', 'André Luiz Domingues', 18924.00, '2026-06-10', 4, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Millenium'), '5126', 'Alexsander Alves do Santos',
     '2026-05-28', 'HSE', 'Welber Meneses', 16950.00, '2026-06-03', 4, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Leoni'), '18.326', 'Carlos Alberto Carvalho Ferreira',
     '2026-05-12', 'HSE', 'Alexandre', 115000.00, '2026-05-26', 4, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Leoni'), '18327', 'Ilda Damasceno Guimarães',
     '2026-05-13', 'HSE', 'Carmo', 48000.00, '2026-05-26', 3, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Leoni'), '18409', 'Mario Aparecido Agostinho',
     '2026-06-02', 'HSE', 'Breno Faria', 10100.00, '2026-06-02', 3, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Leoni'), '18440', 'Wanderley Sarkis Ligotte',
     '2026-05-27', NULL, 'Luis Filipe', 21000.00, '2026-06-16', 3, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Leoni'), '18441', 'Mario Otini',
     '2026-05-11', NULL, 'Luis Filipe', 8600.00, '2026-06-16', 3, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Leoni'), '18442', 'Wilson Osti',
     '2026-05-29', NULL, 'Luis Filipe', 19500.00, '2026-06-16', 3, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Surgipro'), '84', 'Maria Queiroz de Souza',
     '2026-06-01', 'HSE', 'Luis Filipe Pellegrini', 2100.00, '2026-06-17', 1, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Millenium'), '5250', 'Andrea Pekny Carvalho Gomes',
     '2026-06-22', 'HSE', 'Anderson Galdo', 2400.00, '2026-06-30', 2, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Surgipro'), '85', 'Maria Hosana Alves da Silva',
     '2026-06-13', 'HSE', 'Luis Filipe', 10499.98, '2026-06-24', 4, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Millenium'), '5290', 'Moacir Rodrigues de Souza',
     '2026-07-01', 'HSE', 'Welber Meneses', 16950.00, '2026-07-06', 4, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Millenium'), '5289', 'Valdirene do Valle Quaresma',
     '2026-07-06', 'HSE', 'Welber Meneses', 1998.00, '2026-07-06', 2, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Millenium'), '5345', 'Maria Pedrosa de Sousa Alcantara Costa',
     '2026-06-22', 'HSE', 'Anderson Galdo', 3400.00, '2026-07-17', 2, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Millenium'), '5364', 'Anameure de Carvalho Seixa Santos',
     '2026-07-09', 'HSE', 'Welber Meneses', 28800.01, '2026-07-24', 4, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Leoni'), '18441', 'Mario Otani',
     '2026-05-11', NULL, 'Luis Filipe', 8600.00, '2026-06-16', 2, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Leoni'), '18504', 'Kelci Rodrigues Ocanha Saraiva',
     '2026-06-23', 'HSE', 'Breno Faria', 10100.00, '2026-07-02', 3, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Millenium'), '5212', 'Lindinaldo Cordeiro de Andrade',
     '2026-06-15', 'HSE', 'Welber Meneses', 16950.00, '2026-06-25', 4, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Motiva'), '584272', 'Sonia Maria dos Santos Rodrigues',
     '2026-04-08', 'HSE', 'Ricardo Ramirez', 4869.00, '2026-05-12', 4, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Mogami'), '100.268', 'Ruana Santana',
     '2026-04-17', 'HSE', 'Monica Mazzurana', 7460.00, '2026-07-10', 5, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Mogami'), '100.267', 'Caroline Feliciano Baessa',
     '2026-04-17', 'HSE', 'Kleber Leandro', 7460.00, '2026-07-10', 5, 'normal', 0, NULL),
    ((SELECT id FROM opme_fornecedores WHERE nome = 'Surgipro'), '95', 'Solange Maria dos Santos Silva',
     '2026-07-20', 'HSE', 'Luis Felipe', 1500.00, '2026-07-22', 1, 'normal', 0, NULL)
ON DUPLICATE KEY UPDATE
    data_cirurgia   = VALUES(data_cirurgia),
    hospital        = VALUES(hospital),
    medico          = VALUES(medico),
    valor_total     = VALUES(valor_total),
    data_nf         = VALUES(data_nf),
    total_parcelas  = VALUES(total_parcelas),
    situacao        = VALUES(situacao),
    encaminhado_hse = VALUES(encaminhado_hse),
    observacao      = VALUES(observacao);

-- ── Parcelas ────────────────────────────────────────────────────────
INSERT INTO opme_parcelas (nota_id, numero, vencimento, valor)
VALUES
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '3824' AND n.paciente = 'Viviana Teles de Oliveira'),
     4, '2026-01-10', 4450.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Leoni' AND n.numero_nf = '17.172' AND n.paciente = 'Luiz Carlos Alves Pereira'),
     3, '2026-01-10', 6334.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Litoral Medic' AND n.numero_nf = '5385' AND n.paciente = 'Robenia Cavalcante Gonçalves Bassi de Lima'),
     4, '2026-01-10', 10112.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Litoral Medic' AND n.numero_nf = '5385' AND n.paciente = 'Robenia Cavalcante Gonçalves Bassi de Lima'),
     5, '2026-02-10', 10112.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '3820' AND n.paciente = 'João Figueira de Faria Filho'),
     4, '2026-01-10', 4450.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '3844' AND n.paciente = 'Moacir Rodrigues de Souza'),
     4, '2026-01-10', 4450.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Mogami' AND n.numero_nf = '18.832' AND n.paciente = 'Telma Rodrigues Pupo da Silva'),
     4, '2026-01-17', 2009.83),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Mogami' AND n.numero_nf = '23.273' AND n.paciente = 'Lenice Vitar Manzoti Benfato'),
     3, '2026-01-05', 497.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'SD Implant' AND n.numero_nf = '5.567' AND n.paciente = 'Maria Lucia Simões de Oliveira'),
     3, '2026-01-10', 12225.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'SD Implant' AND n.numero_nf = '5.567' AND n.paciente = 'Maria Lucia Simões de Oliveira'),
     3, '2026-02-10', 12225.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'SD Implant' AND n.numero_nf = '5.567' AND n.paciente = 'Maria Lucia Simões de Oliveira'),
     4, '2026-03-10', 12225.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'SD Implant' AND n.numero_nf = '5.567' AND n.paciente = 'Maria Lucia Simões de Oliveira'),
     4, '2026-04-10', 12225.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Plantão Médico' AND n.numero_nf = '294.156' AND n.paciente = 'Isaias Rodrigues Bezerra'),
     2, '2026-01-10', 2310.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Medic Solution' AND n.numero_nf = '263.999' AND n.paciente = 'João Baptista de Freitas'),
     3, '2026-01-10', 6198.46),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Itamedical' AND n.numero_nf = '8.768' AND n.paciente = 'Maria de Fátima Pereira Nicolucci'),
     3, '2026-01-10', 4000.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Phisiomed' AND n.numero_nf = '326.944' AND n.paciente = 'Carlos Alberto Harwalys'),
     2, '2026-01-10', 2825.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Phisiomed' AND n.numero_nf = '326.944' AND n.paciente = 'Carlos Alberto Harwalys'),
     3, '2026-02-10', 2825.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Phisiomed' AND n.numero_nf = '326.944' AND n.paciente = 'Carlos Alberto Harwalys'),
     4, '2026-03-10', 2825.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Phisiomed' AND n.numero_nf = '326.944' AND n.paciente = 'Carlos Alberto Harwalys'),
     4, '2026-04-10', 2825.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Mogami' AND n.numero_nf = '27.265' AND n.paciente = 'Carlos Alberto Harwalys'),
     3, '2026-01-19', 168.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '3985' AND n.paciente = 'Roseli dos Anjos Pereira Gonçalves'),
     3, '2026-01-10', 4450.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '3985' AND n.paciente = 'Roseli dos Anjos Pereira Gonçalves'),
     4, '2026-02-10', 4450.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '3997' AND n.paciente = 'Maria Helena Mendes'),
     3, '2026-01-10', 4450.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '3997' AND n.paciente = 'Maria Helena Mendes'),
     4, '2026-02-10', 4450.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '3989' AND n.paciente = 'Oscar Americo de Veras'),
     3, '2026-01-10', 4450.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '3989' AND n.paciente = 'Oscar Americo de Veras'),
     4, '2026-02-10', 4450.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '3995' AND n.paciente = 'Claudiomar Queiroz'),
     3, '2026-01-10', 12680.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '3995' AND n.paciente = 'Claudiomar Queiroz'),
     4, '2026-02-10', 12680.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '3995' AND n.paciente = 'Claudiomar Queiroz'),
     5, '2026-03-10', 12680.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '3995' AND n.paciente = 'Claudiomar Queiroz'),
     6, '2026-04-10', 12680.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '3986' AND n.paciente = 'Adriana de Araujo Rittes'),
     3, '2026-01-10', 4000.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '3991' AND n.paciente = 'Sthefanie de Morais'),
     3, '2026-01-10', 5933.43),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '3990' AND n.paciente = 'Maria de Lourdes dos Santos Ramos'),
     3, '2026-01-10', 8750.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '3990' AND n.paciente = 'Maria de Lourdes dos Santos Ramos'),
     4, '2026-02-10', 8750.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Leoni' AND n.numero_nf = '17.265' AND n.paciente = 'Cleonice Ribeiro de Oliveira'),
     2, '2026-01-10', 5050.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Surgipro' AND n.numero_nf = '1' AND n.paciente = 'Irene Gomes dos Santos'),
     3, '2026-01-10', 2680.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Biotronk' AND n.numero_nf = '1151669' AND n.paciente = 'Cleyde de Albuquerque Carletti'),
     2, '2026-01-10', 2799.97),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Biotronk' AND n.numero_nf = '1151669' AND n.paciente = 'Cleyde de Albuquerque Carletti'),
     3, '2026-02-10', 2800.06),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Bio2 New Possibilities' AND n.numero_nf = '86406' AND n.paciente = 'Roberto Aureo Vila Boas'),
     3, '2026-01-10', 3290.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '4026' AND n.paciente = 'Anameure de Carvalho Seixas Santos'),
     3, '2026-01-10', 4237.50),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '4026' AND n.paciente = 'Anameure de Carvalho Seixas Santos'),
     4, '2026-02-10', 4237.50),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '4039' AND n.paciente = 'Vilma Menezes da Silva'),
     3, '2026-01-10', 4499.99),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '4039' AND n.paciente = 'Vilma Menezes da Silva'),
     4, '2026-02-10', 4499.99),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '4040' AND n.paciente = 'Reinaldo Aurelio Pereira'),
     3, '2026-01-10', 4450.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '4040' AND n.paciente = 'Reinaldo Aurelio Pereira'),
     4, '2026-02-10', 4450.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Leoni' AND n.numero_nf = '17.352' AND n.paciente = 'Jose Colombo Rodio'),
     1, '2026-01-10', 1000.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Itamedical' AND n.numero_nf = '8953' AND n.paciente = 'Maria Rosangela da Silva de Melo'),
     2, '2026-01-10', 3000.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Itamedical' AND n.numero_nf = '8953' AND n.paciente = 'Maria Rosangela da Silva de Melo'),
     3, '2026-02-10', 3000.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Itamedical' AND n.numero_nf = '8953' AND n.paciente = 'Maria Rosangela da Silva de Melo'),
     4, '2026-03-10', 3000.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Itamedical' AND n.numero_nf = '8954' AND n.paciente = 'Jose Carlos Barriento'),
     2, '2026-01-10', 3000.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Itamedical' AND n.numero_nf = '8954' AND n.paciente = 'Jose Carlos Barriento'),
     3, '2026-02-10', 3000.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Itamedical' AND n.numero_nf = '8954' AND n.paciente = 'Jose Carlos Barriento'),
     4, '2026-03-10', 3000.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Medic Solution' AND n.numero_nf = '265.876' AND n.paciente = 'Isaias Rodrigues Bezerra'),
     3, '2026-01-21', 10481.88),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Russer' AND n.numero_nf = '103.973' AND n.paciente = 'Julio Cesar de Carlos'),
     1, '2026-01-19', 2204.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Itamedical' AND n.numero_nf = '9013' AND n.paciente = 'Cintia Aparecida de Macedo da Silva'),
     1, '2026-01-10', 750.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Itamedical' AND n.numero_nf = '9013' AND n.paciente = 'Cintia Aparecida de Macedo da Silva'),
     2, '2026-02-10', 750.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '4066' AND n.paciente = 'Isaias Salviano'),
     2, '2026-01-10', 4237.50),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '4066' AND n.paciente = 'Isaias Salviano'),
     3, '2026-02-10', 4237.50),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '4066' AND n.paciente = 'Isaias Salviano'),
     4, '2026-03-10', 4237.50),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Litoral Medic' AND n.numero_nf = '5514' AND n.paciente = 'Marina Severina Alves Vairo'),
     1, '2026-01-10', 2000.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Surgipro' AND n.numero_nf = '4' AND n.paciente = 'Maria Eunice Lime Panhome'),
     2, '2026-01-10', 4020.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Russer' AND n.numero_nf = '104.429' AND n.paciente = 'Ian Filipe Barbosa Ramos'),
     1, '2026-01-12', 215.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Plantão Médico' AND n.numero_nf = '294.617' AND n.paciente = 'Luiz Alves'),
     2, '2026-01-10', 7860.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Plantão Médico' AND n.numero_nf = '294.617' AND n.paciente = 'Luiz Alves'),
     3, '2026-02-10', 7860.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Itamedical' AND n.numero_nf = '9.033' AND n.paciente = 'Giovanni Guedes Ribeiro'),
     1, '2026-01-10', 2850.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Itamedical' AND n.numero_nf = '9.033' AND n.paciente = 'Giovanni Guedes Ribeiro'),
     2, '2026-02-10', 2850.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Russer' AND n.numero_nf = '104.558' AND n.paciente = 'Marcos Antonio de Oliveira'),
     1, '2026-01-12', 7990.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Russer' AND n.numero_nf = '104.431' AND n.paciente = 'Gabriel Oliveira Felix da Silva'),
     2, '2026-01-12', 707.50),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Russer' AND n.numero_nf = '104.431' AND n.paciente = 'Gabriel Oliveira Felix da Silva'),
     2, '2026-02-10', 707.50),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Invasive' AND n.numero_nf = '147228' AND n.paciente = 'Isaias Rodrigues Bezerra'),
     2, '2026-01-10', 2967.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Invasive' AND n.numero_nf = '147228' AND n.paciente = 'Isaias Rodrigues Bezerra'),
     3, '2026-02-10', 2967.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Medic Solution' AND n.numero_nf = '266.710' AND n.paciente = 'Gilberto Pereira de Souza'),
     2, '2026-01-10', 2531.64),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Medic Solution' AND n.numero_nf = '266.710' AND n.paciente = 'Gilberto Pereira de Souza'),
     3, '2026-02-10', 2531.72),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Itamedical' AND n.numero_nf = '9.104' AND n.paciente = 'Aires de Carvalho de Oliveira'),
     1, '2026-01-10', 2400.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Itamedical' AND n.numero_nf = '9.104' AND n.paciente = 'Aires de Carvalho de Oliveira'),
     2, '2026-02-10', 2400.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Itamedical' AND n.numero_nf = '9.104' AND n.paciente = 'Aires de Carvalho de Oliveira'),
     3, '2026-03-10', 2400.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Itamedical' AND n.numero_nf = '9.104' AND n.paciente = 'Aires de Carvalho de Oliveira'),
     4, '2026-04-10', 2400.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Itamedical' AND n.numero_nf = '9.104' AND n.paciente = 'Aires de Carvalho de Oliveira'),
     5, '2026-05-10', 2400.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '4175' AND n.paciente = 'Antonio Jose Almeida da Silva'),
     1, '2026-01-10', 1700.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '4188' AND n.paciente = 'Alexander Alves dos Santos'),
     1, '2026-01-10', 7442.51),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '4188' AND n.paciente = 'Alexander Alves dos Santos'),
     2, '2026-02-10', 7442.51),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '4188' AND n.paciente = 'Alexander Alves dos Santos'),
     3, '2026-03-10', 7442.51),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '4188' AND n.paciente = 'Alexander Alves dos Santos'),
     4, '2026-04-10', 7442.51),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Russer' AND n.numero_nf = '104.958' AND n.paciente = 'Thais Nogueira Raymundo'),
     1, '2026-01-12', 1465.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Itamedical' AND n.numero_nf = '9.088' AND n.paciente = 'Cibele Cassilhas Mota Souza'),
     1, '2026-01-10', 1500.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Itamedical' AND n.numero_nf = '9.088' AND n.paciente = 'Cibele Cassilhas Mota Souza'),
     2, '2026-02-10', 1500.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Itamedical' AND n.numero_nf = '9.088' AND n.paciente = 'Cibele Cassilhas Mota Souza'),
     3, '2026-03-10', 1500.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Russer' AND n.numero_nf = '104.869' AND n.paciente = 'Terezinha Silva Prieto'),
     1, '2026-01-12', 1102.50),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Russer' AND n.numero_nf = '104.869' AND n.paciente = 'Terezinha Silva Prieto'),
     2, '2026-02-12', 1102.50),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Vaz. Schiavão' AND n.numero_nf = '8395' AND n.paciente = 'Alexandre Alves dos Santos'),
     1, '2026-01-30', 5631.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Litoral Medic' AND n.numero_nf = '5573' AND n.paciente = 'Erick Henrique Fuchs Rosário'),
     1, '2026-01-10', 1000.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Litoral Medic' AND n.numero_nf = '5573' AND n.paciente = 'Erick Henrique Fuchs Rosário'),
     2, '2026-02-10', 1000.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Saedmed' AND n.numero_nf = '73.726' AND n.paciente = 'Lenice Vitar Manzoti Benfato'),
     1, '2026-01-12', 952.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Litoral Medic' AND n.numero_nf = '5578' AND n.paciente = 'Marli de Lima'),
     1, '2026-01-10', 1000.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Litoral Medic' AND n.numero_nf = '5578' AND n.paciente = 'Marli de Lima'),
     2, '2026-02-10', 1000.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '4207' AND n.paciente = 'Alessandra Mendonça de Souza Alves'),
     1, '2026-01-10', 1998.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'SD Implant' AND n.numero_nf = '5682' AND n.paciente = 'Antonio Carlos Zacchinni'),
     1, '2026-01-10', 9600.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'SD Implant' AND n.numero_nf = '5682' AND n.paciente = 'Antonio Carlos Zacchinni'),
     2, '2026-02-10', 9600.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'SD Implant' AND n.numero_nf = '5682' AND n.paciente = 'Antonio Carlos Zacchinni'),
     3, '2026-03-10', 9600.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'SD Implant' AND n.numero_nf = '5682' AND n.paciente = 'Antonio Carlos Zacchinni'),
     4, '2026-04-10', 9600.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'SD Implant' AND n.numero_nf = '5682' AND n.paciente = 'Antonio Carlos Zacchinni'),
     5, '2026-05-10', 9600.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Custom Medical' AND n.numero_nf = '7488' AND n.paciente = 'Mariana Santos Nogueira'),
     1, '2026-01-10', 3400.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Custom Medical' AND n.numero_nf = '7488' AND n.paciente = 'Mariana Santos Nogueira'),
     2, '2026-02-10', 3400.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Isomedical' AND n.numero_nf = '88.225' AND n.paciente = 'Antonia Marques dos Santos'),
     1, '2026-01-10', 1665.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Saedmed' AND n.numero_nf = '73.919' AND n.paciente = 'Valter Saladino Santi'),
     1, '2026-01-23', 1344.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Surgipro' AND n.numero_nf = '13' AND n.paciente = 'Maria Ferreira de Souza'),
     1, '2026-01-10', 2096.68),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Surgipro' AND n.numero_nf = '13' AND n.paciente = 'Maria Ferreira de Souza'),
     2, '2026-02-10', 2096.68),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Surgipro' AND n.numero_nf = '13' AND n.paciente = 'Maria Ferreira de Souza'),
     3, '2026-03-10', 2096.68),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'E.tamussino' AND n.numero_nf = '267164' AND n.paciente = 'Maria Izabel Francisca Correia'),
     1, '2026-01-12', 1275.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'E.tamussino' AND n.numero_nf = '267164' AND n.paciente = 'Maria Izabel Francisca Correia'),
     2, '2026-02-12', 1275.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Saedmed' AND n.numero_nf = '74.077' AND n.paciente = 'Celia Maria da Silva Menezes'),
     1, '2026-01-30', 1344.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Biomedicall' AND n.numero_nf = '1.070' AND n.paciente = 'Debora Carneiro de Carvalho'),
     1, '2026-01-02', 800.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Saedmed' AND n.numero_nf = '74.100' AND n.paciente = 'Antonio Mores Costa'),
     1, '2026-01-31', 1344.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Litoral Medic' AND n.numero_nf = '5574' AND n.paciente = 'Elida Soares de Oliveira Furtada'),
     1, '2026-01-10', 500.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Litoral Medic' AND n.numero_nf = '5574' AND n.paciente = 'Elida Soares de Oliveira Furtada'),
     2, '2026-02-10', 500.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Enva' AND n.numero_nf = '6.478' AND n.paciente = 'Luiz Alves'),
     1, '2026-02-10', 2500.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Enva' AND n.numero_nf = '6.478' AND n.paciente = 'Luiz Alves'),
     2, '2026-03-10', 2500.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Enva' AND n.numero_nf = '6.478' AND n.paciente = 'Luiz Alves'),
     3, '2026-04-10', 2500.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Enva' AND n.numero_nf = '6.478' AND n.paciente = 'Luiz Alves'),
     4, '2026-05-10', 2500.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Leoni' AND n.numero_nf = '17.524' AND n.paciente = 'Carlo Alberto Carvalho Ferreira'),
     1, '2026-02-10', 800.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'SD Implant' AND n.numero_nf = '5.734' AND n.paciente = 'Sonia Nascimento da Mota'),
     1, '2026-02-10', 9856.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'SD Implant' AND n.numero_nf = '5.734' AND n.paciente = 'Sonia Nascimento da Mota'),
     2, '2026-03-10', 9856.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'SD Implant' AND n.numero_nf = '5.734' AND n.paciente = 'Sonia Nascimento da Mota'),
     3, '2026-04-10', 9856.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'SD Implant' AND n.numero_nf = '5.734' AND n.paciente = 'Sonia Nascimento da Mota'),
     4, '2026-05-10', 9856.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'SD Implant' AND n.numero_nf = '5.734' AND n.paciente = 'Sonia Nascimento da Mota'),
     5, '2026-06-10', 9856.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Russer' AND n.numero_nf = '105.710' AND n.paciente = 'Pedro Henrique Manzo'),
     1, '2026-02-10', 1102.50),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Russer' AND n.numero_nf = '105.710' AND n.paciente = 'Pedro Henrique Manzo'),
     2, '2026-03-10', 1102.50),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Russer' AND n.numero_nf = '4287' AND n.paciente = 'Maria Lenice Bernardes Branco'),
     1, '2026-02-10', 980.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Isomedical' AND n.numero_nf = '89.497' AND n.paciente = 'Luciana Cardoso dos Nascimento'),
     1, '2026-02-10', 3230.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Isomedical' AND n.numero_nf = '89.496' AND n.paciente = 'Giovana Rabotzke Saramento'),
     1, '2026-02-10', 3230.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Leoni' AND n.numero_nf = '17.503' AND n.paciente = 'Gabriel de Almeida Leite'),
     1, '2026-02-10', 5667.80),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Leoni' AND n.numero_nf = '17.503' AND n.paciente = 'Gabriel de Almeida Leite'),
     2, '2026-03-10', 5667.80),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Leoni' AND n.numero_nf = '17.503' AND n.paciente = 'Gabriel de Almeida Leite'),
     3, '2026-04-10', 5667.80),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Russer' AND n.numero_nf = '105.494' AND n.paciente = 'Nilton Monte'),
     1, '2026-02-10', 1480.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Leoni' AND n.numero_nf = '17.484' AND n.paciente = 'Edson Antonio dos Santos'),
     1, '2026-02-10', 4700.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Leoni' AND n.numero_nf = '17.484' AND n.paciente = 'Edson Antonio dos Santos'),
     2, '2026-03-10', 4700.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Leoni' AND n.numero_nf = '17.497' AND n.paciente = 'Alexandre Magno Alcantara Costa'),
     1, '2026-02-10', 3800.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Lan Medical' AND n.numero_nf = '26.203' AND n.paciente = 'Ivaneia Quintanilha Mariano de Souza'),
     1, '2026-02-10', 14346.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Lan Medical' AND n.numero_nf = '26.203' AND n.paciente = 'Ivaneia Quintanilha Mariano de Souza'),
     2, '2026-03-10', 14346.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Lan Medical' AND n.numero_nf = '26.203' AND n.paciente = 'Ivaneia Quintanilha Mariano de Souza'),
     3, '2026-04-07', 14346.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Lan Medical' AND n.numero_nf = '26.203' AND n.paciente = 'Ivaneia Quintanilha Mariano de Souza'),
     4, '2026-05-05', 14346.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Lan Medical' AND n.numero_nf = '26.203' AND n.paciente = 'Ivaneia Quintanilha Mariano de Souza'),
     5, '2026-06-02', 14346.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '4286' AND n.paciente = 'Nathalia Cristina Gomes Prado'),
     1, '2026-02-10', 4237.50),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '4286' AND n.paciente = 'Nathalia Cristina Gomes Prado'),
     2, '2026-03-10', 4237.50),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '4286' AND n.paciente = 'Nathalia Cristina Gomes Prado'),
     3, '2026-04-10', 4237.50),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '4286' AND n.paciente = 'Nathalia Cristina Gomes Prado'),
     4, '2026-05-10', 4237.50),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '4320' AND n.paciente = 'Valter dos Passos Jr.'),
     1, '2026-02-10', 4237.50),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '4320' AND n.paciente = 'Valter dos Passos Jr.'),
     1, '2026-03-10', 4237.50),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '4320' AND n.paciente = 'Valter dos Passos Jr.'),
     3, '2026-04-10', 4237.50),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '4320' AND n.paciente = 'Valter dos Passos Jr.'),
     4, '2026-05-10', 4237.50),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Litoral Medic' AND n.numero_nf = '5686' AND n.paciente = 'Manoel Edmilton da Silva'),
     1, '2026-02-10', 800.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Litoral Medic' AND n.numero_nf = '5686' AND n.paciente = 'Manoel Edmilton da Silva'),
     2, '2026-03-10', 800.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Litoral Medic' AND n.numero_nf = '5686' AND n.paciente = 'Manoel Edmilton da Silva'),
     3, '2026-04-10', 800.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Litoral Medic' AND n.numero_nf = '5685' AND n.paciente = 'Rhaisa Martins Franco'),
     1, '2026-02-10', 666.67),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Litoral Medic' AND n.numero_nf = '5685' AND n.paciente = 'Rhaisa Martins Franco'),
     2, '2026-03-10', 666.67),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Litoral Medic' AND n.numero_nf = '5685' AND n.paciente = 'Rhaisa Martins Franco'),
     3, '2026-04-10', 666.67),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Litoral Medic' AND n.numero_nf = '5688' AND n.paciente = 'Arleide Maria Simões dos Santos'),
     1, '2026-02-10', 1000.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Litoral Medic' AND n.numero_nf = '5688' AND n.paciente = 'Arleide Maria Simões dos Santos'),
     2, '2026-03-10', 1000.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '4350' AND n.paciente = 'Helena Aparecida Ghiraldelli'),
     1, '2026-02-10', 1700.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Russer' AND n.numero_nf = '106.346' AND n.paciente = 'Marcelo da Silva'),
     1, '2026-02-10', 1117.50),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Russer' AND n.numero_nf = '106.346' AND n.paciente = 'Marcelo da Silva'),
     2, '2026-03-10', 1117.50),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Promeho' AND n.numero_nf = '1.365' AND n.paciente = 'Edgard Valentim Jr'),
     1, '2026-02-10', 725.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Promeho' AND n.numero_nf = '1.365' AND n.paciente = 'Edgard Valentim Jr'),
     2, '2026-03-10', 725.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Storm' AND n.numero_nf = '301.774' AND n.paciente = 'João Camilo Filho'),
     1, '2026-02-10', 2300.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Storm' AND n.numero_nf = '301.774' AND n.paciente = 'João Camilo Filho'),
     2, '2026-03-10', 1150.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '4351' AND n.paciente = 'Cleide Aparecida Ramos Rosa'),
     1, '2026-02-10', 4237.50),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '4351' AND n.paciente = 'Cleide Aparecida Ramos Rosa'),
     2, '2026-03-10', 4237.50),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '4351' AND n.paciente = 'Cleide Aparecida Ramos Rosa'),
     3, '2026-04-10', 4237.50),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '4351' AND n.paciente = 'Cleide Aparecida Ramos Rosa'),
     4, '2026-05-10', 4237.50),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'SD Implant' AND n.numero_nf = '5769' AND n.paciente = 'Neiri Souza de Farias'),
     1, '2026-02-10', 9600.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'SD Implant' AND n.numero_nf = '5769' AND n.paciente = 'Neiri Souza de Farias'),
     2, '2026-03-10', 9600.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'SD Implant' AND n.numero_nf = '5769' AND n.paciente = 'Neiri Souza de Farias'),
     3, '2026-04-10', 9600.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'SD Implant' AND n.numero_nf = '5769' AND n.paciente = 'Neiri Souza de Farias'),
     4, '2026-05-10', 9600.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'SD Implant' AND n.numero_nf = '5769' AND n.paciente = 'Neiri Souza de Farias'),
     5, '2026-06-10', 9600.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Phisiomed' AND n.numero_nf = '335.701' AND n.paciente = 'Sidnei Albano'),
     1, '2026-02-10', 2252.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Phisiomed' AND n.numero_nf = '335.701' AND n.paciente = 'Sidnei Albano'),
     2, '2026-03-10', 2252.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Phisiomed' AND n.numero_nf = '335.701' AND n.paciente = 'Sidnei Albano'),
     3, '2026-04-10', 2252.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Phisiomed' AND n.numero_nf = '335.701' AND n.paciente = 'Sidnei Albano'),
     4, '2026-05-10', 2252.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '4389' AND n.paciente = 'Cacilda da Silva Rufino'),
     1, '2026-02-10', 1998.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Medic Solution' AND n.numero_nf = '270.834' AND n.paciente = 'Lisete Flores Gomes Fernandes'),
     1, '2026-02-21', 3522.50),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Medic Solution' AND n.numero_nf = '270.834' AND n.paciente = 'Lisete Flores Gomes Fernandes'),
     2, '2026-03-23', 3522.50),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Isomedical' AND n.numero_nf = '89.405' AND n.paciente = 'Alexandra Olivia da Silva Lopes'),
     1, '2026-02-10', 4110.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '4352' AND n.paciente = 'Samuel Ferreira da Silva'),
     1, '2026-02-10', 4237.50),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '4352' AND n.paciente = 'Samuel Ferreira da Silva'),
     2, '2026-03-10', 4237.50),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '4352' AND n.paciente = 'Samuel Ferreira da Silva'),
     3, '2026-04-10', 4237.50),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '4352' AND n.paciente = 'Samuel Ferreira da Silva'),
     4, '2026-05-10', 4237.50),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Itamedical' AND n.numero_nf = '9159' AND n.paciente = 'Douglas Leite Pereira'),
     1, '2026-03-10', 2066.67),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Itamedical' AND n.numero_nf = '9159' AND n.paciente = 'Douglas Leite Pereira'),
     3, '2026-04-10', 2066.67),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '17.553' AND n.paciente = 'Willians Santos Baracho'),
     1, '2026-03-10', 4700.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '17.553' AND n.paciente = 'Willians Santos Baracho'),
     2, '2026-04-10', 4700.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Custon Medical' AND n.numero_nf = '7.602' AND n.paciente = 'Mariana Santos Nogueira'),
     1, '2026-03-10', 6900.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Custon Medical' AND n.numero_nf = '7.602' AND n.paciente = 'Mariana Santos Nogueira'),
     2, '2026-04-10', 6900.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Custon Medical' AND n.numero_nf = '7.602' AND n.paciente = 'Mariana Santos Nogueira'),
     3, '2026-05-10', 6900.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Leoni' AND n.numero_nf = '17.597' AND n.paciente = 'Edinaldo Jose da Silva'),
     1, '2026-03-10', 5050.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Leoni' AND n.numero_nf = '17.597' AND n.paciente = 'Edinaldo Jose da Silva'),
     2, '2026-04-10', 5050.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Leoni' AND n.numero_nf = '17.598' AND n.paciente = 'Vera Lucia dos Santos'),
     1, '2026-03-10', 5050.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Leoni' AND n.numero_nf = '17.598' AND n.paciente = 'Vera Lucia dos Santos'),
     2, '2026-04-10', 5050.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Storm' AND n.numero_nf = '301.815' AND n.paciente = 'Anna Clara Marques Pereira'),
     1, '2026-03-10', 1150.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Storm' AND n.numero_nf = '301.815' AND n.paciente = 'Anna Clara Marques Pereira'),
     2, '2026-04-10', 1150.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Storm' AND n.numero_nf = '301.791' AND n.paciente = 'Bruna Peres de Paiva'),
     1, '2026-03-10', 1150.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Storm' AND n.numero_nf = '301.791' AND n.paciente = 'Bruna Peres de Paiva'),
     2, '2026-04-10', 1150.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Promedon' AND n.numero_nf = '517871' AND n.paciente = 'Maria Tereza Araujo Vasques'),
     1, '2026-03-10', 3960.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Plantão Médico' AND n.numero_nf = '300.613' AND n.paciente = 'Celia Maria da Silva Menezes'),
     1, '2026-03-10', 3248.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Plantão Médico' AND n.numero_nf = '300.613' AND n.paciente = 'Celia Maria da Silva Menezes'),
     2, '2026-04-10', 3248.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Plantão Médico' AND n.numero_nf = '300.613' AND n.paciente = 'Celia Maria da Silva Menezes'),
     3, '2026-05-10', 3248.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Leoni' AND n.numero_nf = '17.657' AND n.paciente = 'Ilma Ribeiro da Silva Ferreira'),
     1, '2026-03-10', 4700.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Leoni' AND n.numero_nf = '17.657' AND n.paciente = 'Ilma Ribeiro da Silva Ferreira'),
     2, '2026-04-10', 4700.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'RL Medical' AND n.numero_nf = '517' AND n.paciente = 'Celso Aparecido Campos'),
     1, '2026-03-10', 800.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Leoni' AND n.numero_nf = '17.677' AND n.paciente = 'Elza Alves Bergamasco'),
     1, '2026-03-10', 4550.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Leoni' AND n.numero_nf = '17.677' AND n.paciente = 'Elza Alves Bergamasco'),
     2, '2026-04-10', 4550.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Russer' AND n.numero_nf = '106.776' AND n.paciente = 'Rafael Rodrigues Froio'),
     1, '2026-03-10', 1102.50),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Russer' AND n.numero_nf = '106.776' AND n.paciente = 'Rafael Rodrigues Froio'),
     2, '2026-04-10', 1102.50),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Itamedical' AND n.numero_nf = '9.345' AND n.paciente = 'Hevellin de Andrade Alves'),
     1, '2026-03-10', 1750.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Itamedical' AND n.numero_nf = '9.345' AND n.paciente = 'Hevellin de Andrade Alves'),
     2, '2026-04-10', 1750.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'E.tamussino' AND n.numero_nf = '272930' AND n.paciente = 'Iara Palasson Garcia'),
     1, '2026-03-10', 1400.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Slim' AND n.numero_nf = '34.859' AND n.paciente = 'Vivian Silva Cursino'),
     1, '2026-03-10', 1155.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Slim' AND n.numero_nf = '34.863' AND n.paciente = 'David Mendes dos Santos Filho'),
     1, '2026-03-10', 1155.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Phisiomed' AND n.numero_nf = '337.069' AND n.paciente = 'Lais Augusto Coelho Nantes Lima'),
     1, '2026-03-10', 316664.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Phisiomed' AND n.numero_nf = '337.069' AND n.paciente = 'Lais Augusto Coelho Nantes Lima'),
     2, '2026-04-10', 316664.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Phisiomed' AND n.numero_nf = '337.069' AND n.paciente = 'Lais Augusto Coelho Nantes Lima'),
     3, '2026-05-10', 316664.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Plantão Médico' AND n.numero_nf = '303.178' AND n.paciente = 'Celia Maria da Silva Menezes'),
     1, '2026-03-10', 3248.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Plantão Médico' AND n.numero_nf = '303.178' AND n.paciente = 'Celia Maria da Silva Menezes'),
     2, '2026-04-10', 3248.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Plantão Médico' AND n.numero_nf = '303.178' AND n.paciente = 'Celia Maria da Silva Menezes'),
     3, '2026-05-10', 3249.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Wolford Medical' AND n.numero_nf = '16.573' AND n.paciente = 'Lourdes Senna Goncalves'),
     1, '2026-03-20', 4516.62),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Wolford Medical' AND n.numero_nf = '16.573' AND n.paciente = 'Lourdes Senna Goncalves'),
     2, '2026-04-20', 4516.62),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Wolford Medical' AND n.numero_nf = '16.573' AND n.paciente = 'Lourdes Senna Goncalves'),
     3, '2026-05-20', 4516.62),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'OTN Surgical' AND n.numero_nf = '21830' AND n.paciente = 'Nilton Cota'),
     1, '2026-03-10', 2225.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'OTN Surgical' AND n.numero_nf = '21830' AND n.paciente = 'Nilton Cota'),
     2, '2026-04-10', 2225.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'OTN Surgical' AND n.numero_nf = '21830' AND n.paciente = 'Nilton Cota'),
     3, '2026-05-10', 2225.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'OTN Surgical' AND n.numero_nf = '21830' AND n.paciente = 'Nilton Cota'),
     4, '2026-06-10', 2225.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Russer' AND n.numero_nf = '107.532' AND n.paciente = 'Maria Lucia Rodrigues Santos'),
     1, '2026-03-30', 1547.50),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Russer' AND n.numero_nf = '107.532' AND n.paciente = 'Maria Lucia Rodrigues Santos'),
     2, '2026-04-30', 1547.50),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '4581' AND n.paciente = 'Leide Aparecida dos Santos Orti'),
     1, '2026-03-10', 4237.50),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '4581' AND n.paciente = 'Leide Aparecida dos Santos Orti'),
     2, '2026-04-10', 4237.50),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '4581' AND n.paciente = 'Leide Aparecida dos Santos Orti'),
     3, '2026-05-10', 4237.50),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '4581' AND n.paciente = 'Leide Aparecida dos Santos Orti'),
     4, '2026-06-10', 4237.50),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '4557' AND n.paciente = 'Marina Severina Alves Vairo'),
     1, '2026-03-10', 4237.50),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '4557' AND n.paciente = 'Marina Severina Alves Vairo'),
     2, '2026-04-10', 4237.50),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '4557' AND n.paciente = 'Marina Severina Alves Vairo'),
     3, '2026-05-10', 4237.50),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '4557' AND n.paciente = 'Marina Severina Alves Vairo'),
     4, '2026-06-10', 4237.50),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Surgipro' AND n.numero_nf = '326.944' AND n.paciente = 'Marcelo Rodrigues do Nascimento'),
     1, '2026-03-10', 2010.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Surgipro' AND n.numero_nf = '326.944' AND n.paciente = 'Marcelo Rodrigues do Nascimento'),
     2, '2026-04-10', 2010.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Surgipro' AND n.numero_nf = '326.944' AND n.paciente = 'Marcelo Rodrigues do Nascimento'),
     3, '2026-05-10', 2010.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Surgipro' AND n.numero_nf = '326.944' AND n.paciente = 'Marcelo Rodrigues do Nascimento'),
     4, '2026-06-10', 2010.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Surgipro' AND n.numero_nf = '33' AND n.paciente = 'Erick Alecsander Pereira Pandalpho'),
     1, '2026-03-10', 1000.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Surgipro' AND n.numero_nf = '33' AND n.paciente = 'Erick Alecsander Pereira Pandalpho'),
     2, '2026-04-10', 1000.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '4593' AND n.paciente = 'Christhianne Arantes D. B. dos Santos'),
     1, '2026-03-10', 4237.50),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '4593' AND n.paciente = 'Christhianne Arantes D. B. dos Santos'),
     2, '2026-04-10', 4237.50),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '4593' AND n.paciente = 'Christhianne Arantes D. B. dos Santos'),
     3, '2026-05-10', 4237.50),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '4593' AND n.paciente = 'Christhianne Arantes D. B. dos Santos'),
     4, '2026-06-10', 4237.50),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Russer' AND n.numero_nf = '107.232' AND n.paciente = 'Charles Nunes de Oliveira'),
     1, '2026-03-10', 1547.50),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Russer' AND n.numero_nf = '107.232' AND n.paciente = 'Charles Nunes de Oliveira'),
     2, '2026-04-10', 1547.50),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Russer' AND n.numero_nf = '107.744' AND n.paciente = 'Julio Cesar de Carlos'),
     1, '2026-06-23', 2305.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Russer' AND n.numero_nf = '107.748' AND n.paciente = 'Vanessa Baptista Valentim da Silva'),
     1, '2026-03-23', 3240.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Mogami' AND n.numero_nf = '56.376' AND n.paciente = 'Monica Alves dos Santos Oliveira'),
     1, '2026-03-06', 1390.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '4613' AND n.paciente = 'Maria de Fatima Souza Silva'),
     1, '2026-03-10', 4237.50),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '4613' AND n.paciente = 'Maria de Fatima Souza Silva'),
     2, '2026-04-10', 4237.50),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '4613' AND n.paciente = 'Maria de Fatima Souza Silva'),
     3, '2026-05-10', 4237.50),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '4613' AND n.paciente = 'Maria de Fatima Souza Silva'),
     4, '2026-06-10', 4237.50),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Mogami' AND n.numero_nf = '56.673' AND n.paciente = 'Alessandre Jorge Chiapetta'),
     1, '2026-03-11', 1900.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Isomedical' AND n.numero_nf = '92.591' AND n.paciente = 'Ricardo Ferreira da Silva'),
     1, '2026-03-30', 1500.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Isomedical' AND n.numero_nf = '92.664' AND n.paciente = 'Luiz Antonio Martins'),
     1, '2026-03-30', 267.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Handle' AND n.numero_nf = '1551.429' AND n.paciente = 'Carla Regina Novaes Arruda'),
     1, '2026-04-10', 505.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Handle' AND n.numero_nf = '1551.429' AND n.paciente = 'Carla Regina Novaes Arruda'),
     2, '2026-05-10', 505.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Handle' AND n.numero_nf = '1551.429' AND n.paciente = 'Carla Regina Novaes Arruda'),
     3, '2026-06-10', 505.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Handle' AND n.numero_nf = '1551.429' AND n.paciente = 'Carla Regina Novaes Arruda'),
     4, '2026-07-10', 505.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Micromedical' AND n.numero_nf = '69614' AND n.paciente = 'Dennys Willian Conceição Costa'),
     1, '2026-04-10', 2473.50),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Micromedical' AND n.numero_nf = '69614' AND n.paciente = 'Dennys Willian Conceição Costa'),
     2, '2026-05-10', 2473.50),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Micromedical' AND n.numero_nf = '69614' AND n.paciente = 'Dennys Willian Conceição Costa'),
     3, '2026-06-10', 2473.50),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Micromedical' AND n.numero_nf = '69614' AND n.paciente = 'Dennys Willian Conceição Costa'),
     4, '2026-07-10', 2473.50),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Micromedical' AND n.numero_nf = '69614' AND n.paciente = 'Dennys Willian Conceição Costa'),
     6, '2026-08-10', 2473.50),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Micromedical' AND n.numero_nf = '69614' AND n.paciente = 'Dennys Willian Conceição Costa'),
     6, '2026-09-10', 2473.50),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Leoni' AND n.numero_nf = '17.785' AND n.paciente = 'Helena Alambert'),
     1, '2026-04-10', 3550.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Leoni' AND n.numero_nf = '17.785' AND n.paciente = 'Helena Alambert'),
     2, '2026-05-10', 3550.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Biotronik' AND n.numero_nf = '1163173' AND n.paciente = 'Raimundo Osvanir Queiroz do Nascimento'),
     1, '2026-04-10', 293330.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Biotronik' AND n.numero_nf = '1163173' AND n.paciente = 'Raimundo Osvanir Queiroz do Nascimento'),
     1, '2026-05-10', 2933.30),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Biotronik' AND n.numero_nf = '1163173' AND n.paciente = 'Raimundo Osvanir Queiroz do Nascimento'),
     2, '2026-06-10', 2933.30),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Biotronik' AND n.numero_nf = '1163173' AND n.paciente = 'Raimundo Osvanir Queiroz do Nascimento'),
     3, '2026-07-10', 2933.30),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Leoni' AND n.numero_nf = '17786' AND n.paciente = 'Lindinaldo Cordeiro de Andrade'),
     1, '2026-04-10', 4700.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Leoni' AND n.numero_nf = '17786' AND n.paciente = 'Lindinaldo Cordeiro de Andrade'),
     1, '2026-05-10', 4700.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Leoni' AND n.numero_nf = '17786' AND n.paciente = 'Lindinaldo Cordeiro de Andrade'),
     2, '2026-06-10', 4700.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Mogani' AND n.numero_nf = '56450' AND n.paciente = 'Leticia Santos de Souza'),
     1, '2026-04-10', 1865.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Mogani' AND n.numero_nf = '56450' AND n.paciente = 'Leticia Santos de Souza'),
     2, '2026-05-10', 1865.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Mogani' AND n.numero_nf = '56450' AND n.paciente = 'Leticia Santos de Souza'),
     3, '2026-06-10', 1865.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Mogani' AND n.numero_nf = '56450' AND n.paciente = 'Leticia Santos de Souza'),
     4, '2026-07-10', 1865.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Russer' AND n.numero_nf = '107835' AND n.paciente = 'Neuza Gonçalves Lima'),
     1, '2026-04-10', 2135.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Russer' AND n.numero_nf = '107842' AND n.paciente = 'Alda Fonseca Guimarães'),
     1, '2026-04-10', 1245.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Surgipro' AND n.numero_nf = '36' AND n.paciente = 'João Figueira de Maria Filho'),
     1, '2026-04-10', 1000.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Biotronik' AND n.numero_nf = '1164471' AND n.paciente = 'Marta Rodrigues Ferreira'),
     1, '2026-04-10', 2933.30),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Biotronik' AND n.numero_nf = '1164471' AND n.paciente = 'Marta Rodrigues Ferreira'),
     2, '2026-05-10', 2933.30),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Biotronik' AND n.numero_nf = '1164471' AND n.paciente = 'Marta Rodrigues Ferreira'),
     3, '2026-06-10', 2933.40),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'OTN Surgical' AND n.numero_nf = '21917' AND n.paciente = 'Leonardo Brambilla Nicolaci Fincatti'),
     1, '2026-04-10', 4000.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'OTN Surgical' AND n.numero_nf = '21917' AND n.paciente = 'Leonardo Brambilla Nicolaci Fincatti'),
     2, '2026-05-10', 4000.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'OTN Surgical' AND n.numero_nf = '21917' AND n.paciente = 'Leonardo Brambilla Nicolaci Fincatti'),
     3, '2026-06-10', 4000.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'OTN Surgical' AND n.numero_nf = '21917' AND n.paciente = 'Leonardo Brambilla Nicolaci Fincatti'),
     4, '2026-07-10', 4000.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'OTN Surgical' AND n.numero_nf = '21917' AND n.paciente = 'Leonardo Brambilla Nicolaci Fincatti'),
     5, '2026-08-10', 4000.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Surgipro' AND n.numero_nf = '35' AND n.paciente = 'Josefa Pereira da Silva dos Santos'),
     1, '2026-04-10', 1772.50),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Surgipro' AND n.numero_nf = '35' AND n.paciente = 'Josefa Pereira da Silva dos Santos'),
     2, '2026-05-10', 1772.50),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Surgipro' AND n.numero_nf = '35' AND n.paciente = 'Josefa Pereira da Silva dos Santos'),
     3, '2026-06-10', 1772.50),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Surgipro' AND n.numero_nf = '35' AND n.paciente = 'Josefa Pereira da Silva dos Santos'),
     4, '2026-07-10', 1772.50),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Russer' AND n.numero_nf = '108.344' AND n.paciente = 'Marcos Antonio de Oliveira'),
     1, '2026-04-10', 1250.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Leoni' AND n.numero_nf = '17.908' AND n.paciente = 'Wagner Rodrigues Moraes'),
     1, '2026-04-10', 4000.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Leoni' AND n.numero_nf = '17.908' AND n.paciente = 'Wagner Rodrigues Moraes'),
     2, '2026-05-10', 4000.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Leoni' AND n.numero_nf = '17.908' AND n.paciente = 'Wagner Rodrigues Moraes'),
     3, '2026-06-10', 4000.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Leoni' AND n.numero_nf = '17.909' AND n.paciente = 'Celia Costa Saldanha'),
     1, '2026-04-10', 6334.60),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Leoni' AND n.numero_nf = '17.909' AND n.paciente = 'Celia Costa Saldanha'),
     2, '2026-05-10', 6332.70),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Leoni' AND n.numero_nf = '17.909' AND n.paciente = 'Celia Costa Saldanha'),
     3, '2026-06-10', 6332.70),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Itamedical' AND n.numero_nf = '9589' AND n.paciente = 'Juliana da Conceição Costa Pinheiro'),
     1, '2026-04-10', 1500.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Itamedical' AND n.numero_nf = '9589' AND n.paciente = 'Juliana da Conceição Costa Pinheiro'),
     2, '2026-05-10', 1500.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Itamedical' AND n.numero_nf = '9589' AND n.paciente = 'Juliana da Conceição Costa Pinheiro'),
     3, '2026-06-10', 1500.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Brsil' AND n.numero_nf = '3179' AND n.paciente = 'Nair Vilella Gaiva Victoriano'),
     2, '2026-04-26', 1445.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Brsil' AND n.numero_nf = '3179' AND n.paciente = 'Nair Vilella Gaiva Victoriano'),
     2, '2026-05-26', 1445.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Mogami' AND n.numero_nf = '62.567' AND n.paciente = 'Sidnei Albano'),
     1, '2026-04-30', 109.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Mogami' AND n.numero_nf = '62.744' AND n.paciente = 'João Carlos Costa'),
     1, '2026-04-01', 2083.50),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Mogami' AND n.numero_nf = '62.744' AND n.paciente = 'João Carlos Costa'),
     2, '2026-05-01', 2083.50),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Mogami' AND n.numero_nf = '62.744' AND n.paciente = 'João Carlos Costa'),
     3, '2026-06-01', 2083.50),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Mogami' AND n.numero_nf = '62.744' AND n.paciente = 'João Carlos Costa'),
     4, '2026-06-30', 2083.50),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'RL Medical' AND n.numero_nf = '547' AND n.paciente = 'Diogo dos Santos Barbosa'),
     1, '2026-04-10', 800.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'RL Medical' AND n.numero_nf = '547' AND n.paciente = 'Diogo dos Santos Barbosa'),
     2, '2026-05-11', 800.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Isomedical' AND n.numero_nf = '94.759' AND n.paciente = 'Atila Gregório Pereira'),
     1, '2026-04-10', 1615.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Isomedical' AND n.numero_nf = '94.759' AND n.paciente = 'Atila Gregório Pereira'),
     2, '2026-05-10', 1615.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Micromedical' AND n.numero_nf = '70740' AND n.paciente = 'Rosilda Rodrigues de Carvalho'),
     1, '2026-04-11', 9743.50),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Micromedical' AND n.numero_nf = '70740' AND n.paciente = 'Rosilda Rodrigues de Carvalho'),
     2, '2026-05-11', 9743.50),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Micromedical' AND n.numero_nf = '70740' AND n.paciente = 'Rosilda Rodrigues de Carvalho'),
     3, '2026-06-10', 9743.50),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Micromedical' AND n.numero_nf = '70740' AND n.paciente = 'Rosilda Rodrigues de Carvalho'),
     4, '2026-07-10', 9743.50),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Gm Reis' AND n.numero_nf = '40.565' AND n.paciente = 'Gabriel dos Santos da Silva'),
     1, '2026-04-18', 2596.16),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Gm Reis' AND n.numero_nf = '40.565' AND n.paciente = 'Gabriel dos Santos da Silva'),
     2, '2026-05-18', 2596.16),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Gm Reis' AND n.numero_nf = '40.565' AND n.paciente = 'Gabriel dos Santos da Silva'),
     3, '2026-06-17', 2596.16),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Gm Reis' AND n.numero_nf = '40.565' AND n.paciente = 'Gabriel dos Santos da Silva'),
     4, '2026-07-17', 2596.16),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Gm Reis' AND n.numero_nf = '40.565' AND n.paciente = 'Gabriel dos Santos da Silva'),
     5, '2026-08-16', 2596.16),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Storm' AND n.numero_nf = '302251' AND n.paciente = 'Amanda Soares da Silva'),
     1, '2026-04-20', 800.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Storm' AND n.numero_nf = '302251' AND n.paciente = 'Amanda Soares da Silva'),
     2, '2026-05-10', 800.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Leoni' AND n.numero_nf = '17.838' AND n.paciente = 'Claudia Aparecida Fuchs Pereira Silva'),
     1, '2026-05-10', 5050.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Leoni' AND n.numero_nf = '17.838' AND n.paciente = 'Claudia Aparecida Fuchs Pereira Silva'),
     2, '2026-06-10', 5050.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Leoni' AND n.numero_nf = '17.839' AND n.paciente = 'Sergio Luiz Gugel'),
     1, '2026-05-10', 5050.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Leoni' AND n.numero_nf = '17.839' AND n.paciente = 'Sergio Luiz Gugel'),
     2, '2026-06-10', 5050.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '4651' AND n.paciente = 'Ravelly Maria Siqueira Vera'),
     1, '2026-05-10', 999.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Leoni' AND n.numero_nf = '17.924' AND n.paciente = 'Anna Karolina Pereira Oliveira'),
     1, '2026-05-10', 4500.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Leoni' AND n.numero_nf = '17.924' AND n.paciente = 'Anna Karolina Pereira Oliveira'),
     2, '2026-06-10', 4500.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Surgipro' AND n.numero_nf = '48' AND n.paciente = 'Josefa Rodrigues da Costa'),
     1, '2026-05-10', 1333.34),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Surgipro' AND n.numero_nf = '48' AND n.paciente = 'Josefa Rodrigues da Costa'),
     2, '2026-06-10', 1333.34),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Surgipro' AND n.numero_nf = '48' AND n.paciente = 'Josefa Rodrigues da Costa'),
     3, '2026-07-10', 1333.34),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Mogami' AND n.numero_nf = '62.783' AND n.paciente = 'Thamara de Oliveira Santana do Carmo'),
     1, '2026-05-10', 1865.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Mogami' AND n.numero_nf = '62.783' AND n.paciente = 'Thamara de Oliveira Santana do Carmo'),
     2, '2026-06-10', 1865.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Mogami' AND n.numero_nf = '62.783' AND n.paciente = 'Thamara de Oliveira Santana do Carmo'),
     3, '2026-07-10', 1865.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Mogami' AND n.numero_nf = '62.783' AND n.paciente = 'Thamara de Oliveira Santana do Carmo'),
     4, '2026-08-10', 1865.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Mogami' AND n.numero_nf = '62.781' AND n.paciente = 'Rosangela Cortez Alves'),
     1, '2026-05-11', 675.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Mogami' AND n.numero_nf = '62.781' AND n.paciente = 'Rosangela Cortez Alves'),
     2, '2026-06-10', 675.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '4722' AND n.paciente = 'Ojair Luiz'),
     1, '2026-05-10', 4237.50),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '4722' AND n.paciente = 'Ojair Luiz'),
     2, '2026-06-10', 4237.50),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '4722' AND n.paciente = 'Ojair Luiz'),
     3, '2026-07-10', 4237.50),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '4722' AND n.paciente = 'Ojair Luiz'),
     4, '2026-08-10', 4237.50),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '4752' AND n.paciente = 'Wesley Gonçalves'),
     1, '2026-05-10', 4237.50),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '4752' AND n.paciente = 'Wesley Gonçalves'),
     2, '2026-06-10', 4237.50),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '4752' AND n.paciente = 'Wesley Gonçalves'),
     3, '2026-07-10', 4237.50),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '4752' AND n.paciente = 'Wesley Gonçalves'),
     4, '2026-08-10', 4237.50),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Leoni' AND n.numero_nf = '17.967' AND n.paciente = 'Anderson de Oliveira Carvalho'),
     1, '2026-05-10', 6332.70),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Leoni' AND n.numero_nf = '17.967' AND n.paciente = 'Anderson de Oliveira Carvalho'),
     2, '2026-06-10', 6332.70),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Leoni' AND n.numero_nf = '17.967' AND n.paciente = 'Anderson de Oliveira Carvalho'),
     3, '2026-07-10', 6332.70),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Surgipro' AND n.numero_nf = '56' AND n.paciente = 'Inacia Fortunato Silva de Jesus'),
     1, '2026-05-10', 1000.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Surgipro' AND n.numero_nf = '56' AND n.paciente = 'Inacia Fortunato Silva de Jesus'),
     2, '2026-06-10', 1000.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Surgipro' AND n.numero_nf = '55' AND n.paciente = 'Rita de Cassia Castilha dos Santos'),
     1, '2026-05-10', 1000.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Surgipro' AND n.numero_nf = '55' AND n.paciente = 'Rita de Cassia Castilha dos Santos'),
     1, '2026-06-10', 1000.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Micromedical' AND n.numero_nf = '70979' AND n.paciente = 'Manoel Edmilton da Silva'),
     1, '2026-05-10', 1726.33),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Micromedical' AND n.numero_nf = '70979' AND n.paciente = 'Manoel Edmilton da Silva'),
     2, '2026-06-10', 1726.33),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Micromedical' AND n.numero_nf = '70979' AND n.paciente = 'Manoel Edmilton da Silva'),
     3, '2026-07-10', 1726.33),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Itamedical' AND n.numero_nf = '9.731' AND n.paciente = 'Carlos Alberto Alves Lourenço'),
     1, '2026-05-10', 1500.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Itamedical' AND n.numero_nf = '9.731' AND n.paciente = 'Carlos Alberto Alves Lourenço'),
     2, '2026-06-10', 1500.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Itamedical' AND n.numero_nf = '9.731' AND n.paciente = 'Carlos Alberto Alves Lourenço'),
     3, '2026-07-10', 1500.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Itamedical' AND n.numero_nf = '9.730' AND n.paciente = 'Celeste Santos Paixão'),
     1, '2026-05-10', 1500.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Itamedical' AND n.numero_nf = '9.730' AND n.paciente = 'Celeste Santos Paixão'),
     2, '2026-06-10', 1500.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Itamedical' AND n.numero_nf = '9.730' AND n.paciente = 'Celeste Santos Paixão'),
     3, '2026-07-10', 1500.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Mogami' AND n.numero_nf = '69.271' AND n.paciente = 'Maria de Fátima Vieira Sales Evaristo'),
     1, '2026-05-10', 794.50),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Mogami' AND n.numero_nf = '69.271' AND n.paciente = 'Maria de Fátima Vieira Sales Evaristo'),
     2, '2026-06-10', 794.50),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '4814' AND n.paciente = 'Elaine Christine de Souza'),
     1, '2026-05-10', 4237.50),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '4814' AND n.paciente = 'Elaine Christine de Souza'),
     2, '2026-06-10', 4237.50),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '4814' AND n.paciente = 'Elaine Christine de Souza'),
     3, '2026-07-10', 4237.50),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '4814' AND n.paciente = 'Elaine Christine de Souza'),
     4, '2026-08-10', 4237.50),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Russer' AND n.numero_nf = '109.505' AND n.paciente = 'Sandra Mota da Silva Souza'),
     1, '2026-05-11', 3095.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Mogami' AND n.numero_nf = '70124' AND n.paciente = 'Elizabete Oliveira de Souza'),
     1, '2026-05-04', 36.33),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Mogami' AND n.numero_nf = '70124' AND n.paciente = 'Elizabete Oliveira de Souza'),
     2, '2026-06-10', 36.33),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Mogami' AND n.numero_nf = '70124' AND n.paciente = 'Elizabete Oliveira de Souza'),
     3, '2026-07-01', 36.33),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Russer' AND n.numero_nf = '109.600' AND n.paciente = 'Raphael de Oliveira Lacalentola'),
     1, '2026-05-11', 2205.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Surgipro' AND n.numero_nf = '60' AND n.paciente = 'Marlucy da Silva Fernandes'),
     1, '2026-05-10', 1833.34),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Surgipro' AND n.numero_nf = '60' AND n.paciente = 'Marlucy da Silva Fernandes'),
     2, '2026-06-10', 1833.34),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Surgipro' AND n.numero_nf = '60' AND n.paciente = 'Marlucy da Silva Fernandes'),
     3, '2026-07-10', 1833.34),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Itamedical' AND n.numero_nf = '9.755' AND n.paciente = 'Marcio Acioly dos Santos'),
     1, '2026-05-10', 1125.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Itamedical' AND n.numero_nf = '9.755' AND n.paciente = 'Marcio Acioly dos Santos'),
     2, '2026-06-10', 1125.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Itamedical' AND n.numero_nf = '9.755' AND n.paciente = 'Marcio Acioly dos Santos'),
     3, '2026-07-10', 1125.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Itamedical' AND n.numero_nf = '9.755' AND n.paciente = 'Marcio Acioly dos Santos'),
     4, '2026-08-10', 1125.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'RL Medical' AND n.numero_nf = '557' AND n.paciente = 'Oscar Correia de Lima'),
     1, '2026-05-10', 1383.32),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'RL Medical' AND n.numero_nf = '557' AND n.paciente = 'Oscar Correia de Lima'),
     2, '2026-06-10', 1383.32),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'RL Medical' AND n.numero_nf = '557' AND n.paciente = 'Oscar Correia de Lima'),
     3, '2026-07-10', 1383.32),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Max Spine' AND n.numero_nf = '123' AND n.paciente = 'Guilherme Coelho de Sá'),
     1, '2026-05-10', 1024.26),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Max Spine' AND n.numero_nf = '123' AND n.paciente = 'Guilherme Coelho de Sá'),
     2, '2026-06-10', 1024.26),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Max Spine' AND n.numero_nf = '123' AND n.paciente = 'Guilherme Coelho de Sá'),
     3, '2026-07-10', 1024.26),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'RCL Implantes' AND n.numero_nf = '413061' AND n.paciente = 'Edson Jose da Silva Lopes'),
     1, '2026-05-10', 5149.95),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'RCL Implantes' AND n.numero_nf = '413061' AND n.paciente = 'Edson Jose da Silva Lopes'),
     2, '2026-06-10', 5149.95),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'RCL Implantes' AND n.numero_nf = '413061' AND n.paciente = 'Edson Jose da Silva Lopes'),
     3, '2026-07-10', 5149.95),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Bio2' AND n.numero_nf = '87833' AND n.paciente = 'Reinaldo Almeida'),
     1, '2026-05-21', 4751.67),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Bio2' AND n.numero_nf = '87833' AND n.paciente = 'Reinaldo Almeida'),
     2, '2026-06-05', 4751.67),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Bio2' AND n.numero_nf = '87833' AND n.paciente = 'Reinaldo Almeida'),
     3, '2026-06-06', 4751.66),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Leoni' AND n.numero_nf = '17.827' AND n.paciente = 'Eduardo Lino de Jesus'),
     1, '2026-05-10', 4200.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '4828' AND n.paciente = 'Soelene de Castro Farat'),
     1, '2026-05-10', 423750.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '4828' AND n.paciente = 'Soelene de Castro Farat'),
     2, '2026-06-10', 423750.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '4828' AND n.paciente = 'Soelene de Castro Farat'),
     3, '2026-07-10', 423750.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '4828' AND n.paciente = 'Soelene de Castro Farat'),
     4, '2026-08-10', 423750.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '4829' AND n.paciente = 'Jose Brasil da Silva'),
     1, '2026-05-10', 3750.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '4829' AND n.paciente = 'Jose Brasil da Silva'),
     2, '2026-06-10', 3750.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '4953' AND n.paciente = 'Christhianne Arantes D. B. dos Santos'),
     1, '2026-06-10', 10376.25),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '4953' AND n.paciente = 'Christhianne Arantes D. B. dos Santos'),
     2, '2026-07-10', 10376.25),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '4953' AND n.paciente = 'Christhianne Arantes D. B. dos Santos'),
     3, '2026-08-10', 10376.25),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '4953' AND n.paciente = 'Christhianne Arantes D. B. dos Santos'),
     4, '2026-09-10', 10376.25),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Leoni' AND n.numero_nf = '17.975' AND n.paciente = 'Jeane Cardoso da Silva'),
     1, '2026-06-10', 2390.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Leoni' AND n.numero_nf = '17923' AND n.paciente = 'Gabriel de Almeida Leite'),
     1, '2026-06-10', 900.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Leoni' AND n.numero_nf = '17.968' AND n.paciente = 'Dilson Leal'),
     1, '2026-06-10', 2180.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Leoni' AND n.numero_nf = '18.009' AND n.paciente = 'Maria da Conceição Rodrigues de Amorim'),
     1, '2026-06-10', 3900.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Leoni' AND n.numero_nf = '18.025' AND n.paciente = 'Joselice Caseiro Gomes Rodrigues'),
     1, '2026-06-10', 4500.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Leoni' AND n.numero_nf = '18.047' AND n.paciente = 'Manoel Domingos Feitoza'),
     1, '2026-06-10', 900.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Leoni' AND n.numero_nf = '18.046' AND n.paciente = 'Charles Soares Paulino Jr.'),
     1, '2026-06-10', 4200.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Mogami' AND n.numero_nf = '72.615' AND n.paciente = 'Zulmira Cordeiro Souza Santos'),
     1, '2026-06-10', 114.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Max Spine' AND n.numero_nf = '155' AND n.paciente = 'Zulmira Cordeiro Souza Santos'),
     1, '2026-06-10', 1708.10),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Max Spine' AND n.numero_nf = '155' AND n.paciente = 'Zulmira Cordeiro Souza Santos'),
     2, '2026-07-10', 1708.10),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Max Spine' AND n.numero_nf = '155' AND n.paciente = 'Zulmira Cordeiro Souza Santos'),
     3, '2026-08-10', 1708.10),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Max Spine' AND n.numero_nf = '155' AND n.paciente = 'Zulmira Cordeiro Souza Santos'),
     4, '2026-09-10', 1708.10),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Max Spine' AND n.numero_nf = '155' AND n.paciente = 'Zulmira Cordeiro Souza Santos'),
     5, '2026-10-10', 1708.10),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Isomedical' AND n.numero_nf = '95.723' AND n.paciente = 'Vanessa Fancelli Zachini'),
     1, '2026-06-10', 4142.50),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Isomedical' AND n.numero_nf = '95.723' AND n.paciente = 'Vanessa Fancelli Zachini'),
     2, '2026-07-10', 4142.50),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '4882' AND n.paciente = 'Mario Medeiros'),
     1, '2026-06-10', 4237.50),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '4882' AND n.paciente = 'Mario Medeiros'),
     2, '2026-07-10', 4237.50),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '4882' AND n.paciente = 'Mario Medeiros'),
     3, '2026-08-10', 4237.50),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '4882' AND n.paciente = 'Mario Medeiros'),
     4, '2026-09-10', 4237.50),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Russer' AND n.numero_nf = '110.057' AND n.paciente = 'Maikon da Silva Menezes'),
     1, '2026-06-08', 3490.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '4881' AND n.paciente = 'Mariana Bispo dos Santos Martins'),
     1, '2026-06-10', 7792.50),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '4881' AND n.paciente = 'Mariana Bispo dos Santos Martins'),
     2, '2026-07-10', 7792.50),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '4881' AND n.paciente = 'Mariana Bispo dos Santos Martins'),
     3, '2026-08-10', 7792.50),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '4881' AND n.paciente = 'Mariana Bispo dos Santos Martins'),
     4, '2026-09-10', 7792.50),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Schiavão' AND n.numero_nf = '9392' AND n.paciente = 'Mariana Bispo dos Santos Martins'),
     1, '2026-06-10', 5631.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Richards Medical' AND n.numero_nf = '7167' AND n.paciente = 'Noah Tomadacci Amaral'),
     1, '2026-06-10', 140.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Richards Medical' AND n.numero_nf = '7167' AND n.paciente = 'Noah Tomadacci Amaral'),
     2, '2026-07-10', 140.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Surgipro' AND n.numero_nf = '62' AND n.paciente = 'Thamyres Victoria Quaresma Gonçalves'),
     1, '2026-06-10', 1300.01),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Surgipro' AND n.numero_nf = '62' AND n.paciente = 'Thamyres Victoria Quaresma Gonçalves'),
     2, '2026-07-10', 1300.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Schiavão' AND n.numero_nf = '9486' AND n.paciente = 'Maria Izabel Machado Fonseca'),
     1, '2026-06-10', 2815.50),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Schiavão' AND n.numero_nf = '9486' AND n.paciente = 'Maria Izabel Machado Fonseca'),
     2, '2026-07-10', 2815.50),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '4962' AND n.paciente = 'Maria Izabel Machado Fonseca'),
     1, '2026-06-10', 14184.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '4962' AND n.paciente = 'Maria Izabel Machado Fonseca'),
     2, '2026-07-10', 14184.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '4962' AND n.paciente = 'Maria Izabel Machado Fonseca'),
     3, '2026-08-10', 14184.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '4962' AND n.paciente = 'Maria Izabel Machado Fonseca'),
     4, '2026-09-10', 14184.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '4962' AND n.paciente = 'Maria Izabel Machado Fonseca'),
     5, '2026-10-10', 14184.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Itamedical' AND n.numero_nf = '9857' AND n.paciente = 'Luiz Antonio Cote'),
     1, '2026-06-10', 1500.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Itamedical' AND n.numero_nf = '9857' AND n.paciente = 'Luiz Antonio Cote'),
     2, '2026-07-10', 1500.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Itamedical' AND n.numero_nf = '9857' AND n.paciente = 'Luiz Antonio Cote'),
     3, '2026-08-10', 1500.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Itamedical' AND n.numero_nf = '9858' AND n.paciente = 'Alessandra Aparecida Peixoto Leão'),
     1, '2026-06-10', 1500.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Itamedical' AND n.numero_nf = '9858' AND n.paciente = 'Alessandra Aparecida Peixoto Leão'),
     2, '2026-07-10', 1500.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Itamedical' AND n.numero_nf = '9858' AND n.paciente = 'Alessandra Aparecida Peixoto Leão'),
     3, '2026-08-10', 1500.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '4927' AND n.paciente = 'Lucia Cristina dos Santos'),
     1, '2026-06-10', 4883.75),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '4927' AND n.paciente = 'Lucia Cristina dos Santos'),
     2, '2026-07-10', 4883.75),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '4927' AND n.paciente = 'Lucia Cristina dos Santos'),
     3, '2026-08-10', 4883.75),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '4927' AND n.paciente = 'Lucia Cristina dos Santos'),
     4, '2026-09-10', 4883.75),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Itamedical' AND n.numero_nf = '9859' AND n.paciente = 'Sandro Fragoso Marcal Silva'),
     1, '2026-06-10', 1500.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Itamedical' AND n.numero_nf = '9859' AND n.paciente = 'Sandro Fragoso Marcal Silva'),
     2, '2026-07-10', 1500.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Itamedical' AND n.numero_nf = '9859' AND n.paciente = 'Sandro Fragoso Marcal Silva'),
     3, '2026-08-10', 1500.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Itamedical' AND n.numero_nf = '9861' AND n.paciente = 'Gerson Roberto da Silva'),
     1, '2026-06-10', 2400.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Itamedical' AND n.numero_nf = '9861' AND n.paciente = 'Gerson Roberto da Silva'),
     2, '2026-07-10', 2400.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Itamedical' AND n.numero_nf = '9861' AND n.paciente = 'Gerson Roberto da Silva'),
     3, '2026-08-10', 2400.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Itamedical' AND n.numero_nf = '9861' AND n.paciente = 'Gerson Roberto da Silva'),
     4, '2026-09-10', 2400.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Itamedical' AND n.numero_nf = '9861' AND n.paciente = 'Gerson Roberto da Silva'),
     5, '2026-10-10', 2400.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Slim' AND n.numero_nf = '35826' AND n.paciente = 'Silvia Danielle de Souza Caxiado Santos'),
     1, '2026-06-10', 480.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Slim' AND n.numero_nf = '35826' AND n.paciente = 'Silvia Danielle de Souza Caxiado Santos'),
     2, '2026-07-10', 480.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '4930' AND n.paciente = 'Edson Jose Alves'),
     1, '2026-06-10', 8250.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '4930' AND n.paciente = 'Edson Jose Alves'),
     2, '2026-07-10', 8250.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '4930' AND n.paciente = 'Edson Jose Alves'),
     3, '2026-08-10', 8250.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '4930' AND n.paciente = 'Edson Jose Alves'),
     4, '2026-09-10', 8250.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Russer' AND n.numero_nf = '110.666' AND n.paciente = 'Bruno Guedes Monteiro de Freitas'),
     1, '2026-06-10', 2290.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Isomedical' AND n.numero_nf = '96762' AND n.paciente = 'Antonio Carlos do Carmo'),
     1, '2026-06-10', 3631.27),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Isomedical' AND n.numero_nf = '96762' AND n.paciente = 'Antonio Carlos do Carmo'),
     2, '2026-07-10', 3631.27),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Isomedical' AND n.numero_nf = '96.761' AND n.paciente = 'Vanderlei Gomes Ferreira'),
     1, '2026-06-10', 3493.77),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Isomedical' AND n.numero_nf = '96.761' AND n.paciente = 'Vanderlei Gomes Ferreira'),
     2, '2026-07-10', 3493.77),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Itamedical' AND n.numero_nf = '9926' AND n.paciente = 'Denise Alves de Mello Lopes'),
     1, '2026-06-10', 1500.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Itamedical' AND n.numero_nf = '9926' AND n.paciente = 'Denise Alves de Mello Lopes'),
     2, '2026-07-10', 1500.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Itamedical' AND n.numero_nf = '9926' AND n.paciente = 'Denise Alves de Mello Lopes'),
     3, '2026-08-10', 1500.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Slim Produtos' AND n.numero_nf = '35659' AND n.paciente = 'Charles Alves dos Santos'),
     1, '2026-06-10', 1260.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Slim Produtos' AND n.numero_nf = '35659' AND n.paciente = 'Charles Alves dos Santos'),
     2, '2026-07-10', 1260.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Slim Produtos' AND n.numero_nf = '35659' AND n.paciente = 'Charles Alves dos Santos'),
     3, '2026-08-10', 1260.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Isomedical' AND n.numero_nf = '96794' AND n.paciente = 'Reinaldo Aurelio Pereira'),
     1, '2026-06-10', 615.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Isomedical' AND n.numero_nf = '96794' AND n.paciente = 'Reinaldo Aurelio Pereira'),
     2, '2026-07-10', 615.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Russer' AND n.numero_nf = '111.033' AND n.paciente = 'Doraci de Lourdes Ferreira Souza'),
     1, '2026-06-19', 3240.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Surgipro' AND n.numero_nf = '73' AND n.paciente = 'Geraldo José de Oliveira'),
     1, '2026-06-10', 1000.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Handle' AND n.numero_nf = '1616385' AND n.paciente = 'Silvio Batista dos Santos'),
     1, '2026-06-25', 740.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Handle' AND n.numero_nf = '1616385' AND n.paciente = 'Silvio Batista dos Santos'),
     2, '2026-07-25', 740.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Handle' AND n.numero_nf = '93434' AND n.paciente = 'Silvio Batista dos Santos'),
     1, '2026-06-25', 1250.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Handle' AND n.numero_nf = '93434' AND n.paciente = 'Silvio Batista dos Santos'),
     2, '2026-07-25', 1250.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Max Spine' AND n.numero_nf = '166' AND n.paciente = 'Elizabete Oliveira de Souza'),
     1, '2026-06-10', 2135.12),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Max Spine' AND n.numero_nf = '166' AND n.paciente = 'Elizabete Oliveira de Souza'),
     2, '2026-07-10', 2135.12),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Max Spine' AND n.numero_nf = '166' AND n.paciente = 'Elizabete Oliveira de Souza'),
     3, '2026-08-10', 2135.12),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Max Spine' AND n.numero_nf = '166' AND n.paciente = 'Elizabete Oliveira de Souza'),
     4, '2026-09-10', 2135.12),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Surgipro' AND n.numero_nf = '68' AND n.paciente = 'Annameure de Carvalho Seixa Santos'),
     1, '2026-06-10', 1000.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Surgipro' AND n.numero_nf = '68' AND n.paciente = 'Annameure de Carvalho Seixa Santos'),
     1, '2026-07-10', 1000.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '5028' AND n.paciente = 'Maria de Lourdes dos Santos Ramos'),
     1, '2026-06-10', 7000.01),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '5028' AND n.paciente = 'Maria de Lourdes dos Santos Ramos'),
     2, '2026-07-10', 7000.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '5028' AND n.paciente = 'Maria de Lourdes dos Santos Ramos'),
     3, '2026-08-10', 7000.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '5028' AND n.paciente = 'Maria de Lourdes dos Santos Ramos'),
     4, '2026-09-10', 7000.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '5028' AND n.paciente = 'Maria de Lourdes dos Santos Ramos'),
     5, '2026-10-10', 7000.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Leoni' AND n.numero_nf = '18.077' AND n.paciente = 'Zelia Tavares Gonçalves'),
     1, '2026-07-10', 6180.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Leoni' AND n.numero_nf = '18139' AND n.paciente = 'Matheus Martins Rodrigues'),
     1, '2026-07-10', 6000.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Leoni' AND n.numero_nf = '18195' AND n.paciente = 'Dulcenete Motta de Melo'),
     1, '2026-07-10', 4000.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Leoni' AND n.numero_nf = '18195' AND n.paciente = 'Dulcenete Motta de Melo'),
     2, '2026-08-10', 4000.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Leoni' AND n.numero_nf = '18197' AND n.paciente = 'Kleber Fernandes dos Santos'),
     1, '2026-07-10', 900.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Leoni' AND n.numero_nf = '18.198' AND n.paciente = 'Maria Pureza de Jesus Santos'),
     1, '2026-07-10', 3400.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Leoni' AND n.numero_nf = '18.198' AND n.paciente = 'Maria Pureza de Jesus Santos'),
     2, '2026-08-10', 3400.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Leoni' AND n.numero_nf = '18.198' AND n.paciente = 'Maria Pureza de Jesus Santos'),
     3, '2026-09-10', 3400.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Surgipro' AND n.numero_nf = '77' AND n.paciente = 'Joel Gonçalves dos Santos'),
     1, '2026-07-10', 2625.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Surgipro' AND n.numero_nf = '77' AND n.paciente = 'Joel Gonçalves dos Santos'),
     2, '2026-08-10', 2625.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Surgipro' AND n.numero_nf = '77' AND n.paciente = 'Joel Gonçalves dos Santos'),
     3, '2026-09-10', 2625.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Surgipro' AND n.numero_nf = '77' AND n.paciente = 'Joel Gonçalves dos Santos'),
     4, '2026-10-10', 2625.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '5042' AND n.paciente = 'Wilma Alves Dias'),
     1, '2026-07-10', 6965.50),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '5042' AND n.paciente = 'Wilma Alves Dias'),
     2, '2026-08-10', 6965.50),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '5042' AND n.paciente = 'Wilma Alves Dias'),
     3, '2026-09-10', 6965.50),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '5042' AND n.paciente = 'Wilma Alves Dias'),
     4, '2026-10-10', 6965.50),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Leoni' AND n.numero_nf = '18314' AND n.paciente = 'Ednaldo de Pontes Gouveia'),
     1, '2026-07-10', 2000.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Leoni' AND n.numero_nf = '18314' AND n.paciente = 'Ednaldo de Pontes Gouveia'),
     2, '2026-08-10', 1990.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Surgipro' AND n.numero_nf = '75' AND n.paciente = 'Luiza Silva Pinto de Abreu'),
     1, '2026-07-10', 3166.67),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Surgipro' AND n.numero_nf = '75' AND n.paciente = 'Luiza Silva Pinto de Abreu'),
     2, '2026-08-10', 3166.67),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Surgipro' AND n.numero_nf = '75' AND n.paciente = 'Luiza Silva Pinto de Abreu'),
     3, '2026-09-10', 3166.67),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Russer' AND n.numero_nf = '111.751' AND n.paciente = 'Claudio Luiz dos Santos'),
     1, '2026-07-27', 3240.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Micromedical' AND n.numero_nf = '73340' AND n.paciente = 'Eli Hurtado Ferreira'),
     1, '2026-07-10', 8187.24),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Micromedical' AND n.numero_nf = '73340' AND n.paciente = 'Eli Hurtado Ferreira'),
     2, '2026-08-10', 8187.24),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Micromedical' AND n.numero_nf = '73340' AND n.paciente = 'Eli Hurtado Ferreira'),
     3, '2026-09-10', 8187.24),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Micromedical' AND n.numero_nf = '73340' AND n.paciente = 'Eli Hurtado Ferreira'),
     4, '2026-10-10', 8187.24),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Biotronik' AND n.numero_nf = '1177349' AND n.paciente = 'Antonio de Oliveira Paixão'),
     1, '2026-07-10', 1998.98),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Biotronik' AND n.numero_nf = '1177349' AND n.paciente = 'Antonio de Oliveira Paixão'),
     2, '2026-08-10', 1998.98),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Biotronik' AND n.numero_nf = '1177349' AND n.paciente = 'Antonio de Oliveira Paixão'),
     3, '2026-09-10', 2000.04),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Litoral Medic' AND n.numero_nf = '6090' AND n.paciente = 'Eleide dos Santos Andrade'),
     1, '2026-07-10', 4086.66),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Litoral Medic' AND n.numero_nf = '6090' AND n.paciente = 'Eleide dos Santos Andrade'),
     2, '2026-08-10', 4086.66),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Litoral Medic' AND n.numero_nf = '6090' AND n.paciente = 'Eleide dos Santos Andrade'),
     3, '2026-09-10', 4086.68),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Surgipro' AND n.numero_nf = '74' AND n.paciente = 'Ana Maria de Oliveira Souza'),
     1, '2026-07-10', 2000.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Surgipro' AND n.numero_nf = '74' AND n.paciente = 'Ana Maria de Oliveira Souza'),
     2, '2026-08-10', 2000.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Surgipro' AND n.numero_nf = '74' AND n.paciente = 'Ana Maria de Oliveira Souza'),
     3, '2026-09-10', 2000.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Surgipro' AND n.numero_nf = '74' AND n.paciente = 'Ana Maria de Oliveira Souza'),
     4, '2026-10-10', 2000.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Surgipro' AND n.numero_nf = '79' AND n.paciente = 'Milene Pontes Belem dos Santos'),
     1, '2026-07-10', 1000.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Mogami' AND n.numero_nf = '92.499' AND n.paciente = 'Wagner dos Santos Rodrigues'),
     1, '2026-07-23', 1555.85),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Mogami' AND n.numero_nf = '92.499' AND n.paciente = 'Wagner dos Santos Rodrigues'),
     2, '2026-08-24', 1555.85),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Mogami' AND n.numero_nf = '92.499' AND n.paciente = 'Wagner dos Santos Rodrigues'),
     3, '2026-09-21', 1555.85),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'HDL Logistica Hospitalar' AND n.numero_nf = '164.079' AND n.paciente = 'Rogerio Silvano'),
     1, '2026-07-17', 926.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Oteos MB' AND n.numero_nf = '42.335' AND n.paciente = 'Silvana Aparecida C. Silva'),
     1, '2026-07-24', 3500.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Oteos MB' AND n.numero_nf = '42.335' AND n.paciente = 'Silvana Aparecida C. Silva'),
     2, '2026-08-10', 3500.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Oteos MB' AND n.numero_nf = '42.335' AND n.paciente = 'Silvana Aparecida C. Silva'),
     3, '2026-09-10', 3500.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Oteos MB' AND n.numero_nf = '42.335' AND n.paciente = 'Silvana Aparecida C. Silva'),
     4, '2026-10-10', 3500.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Biotronik' AND n.numero_nf = '1177564' AND n.paciente = 'Luiz Armando Moraes'),
     1, '2026-07-24', 2933.30),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Biotronik' AND n.numero_nf = '1177564' AND n.paciente = 'Luiz Armando Moraes'),
     2, '2026-08-10', 2933.30),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Biotronik' AND n.numero_nf = '1177564' AND n.paciente = 'Luiz Armando Moraes'),
     3, '2026-09-10', 2933.30),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Gm Reis' AND n.numero_nf = '40.565' AND n.paciente = 'Gabriel de Almeida Leite'),
     5, '2026-08-16', 2596.16),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'SD Implantes' AND n.numero_nf = '6219' AND n.paciente = 'Luiz Tavares'),
     1, '2026-08-10', 7475.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'SD Implantes' AND n.numero_nf = '6219' AND n.paciente = 'Luiz Tavares'),
     2, '2026-09-10', 7475.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'SD Implantes' AND n.numero_nf = '6219' AND n.paciente = 'Luiz Tavares'),
     3, '2026-10-10', 7475.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'SD Implantes' AND n.numero_nf = '6219' AND n.paciente = 'Luiz Tavares'),
     4, '2026-11-10', 7475.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '5142' AND n.paciente = 'Ronaldo Lima Teixeira'),
     1, '2026-08-10', 4731.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '5142' AND n.paciente = 'Ronaldo Lima Teixeira'),
     2, '2026-09-10', 4731.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '5142' AND n.paciente = 'Ronaldo Lima Teixeira'),
     3, '2026-10-10', 4731.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '5142' AND n.paciente = 'Ronaldo Lima Teixeira'),
     4, '2026-11-10', 4731.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '5126' AND n.paciente = 'Alexsander Alves do Santos'),
     1, '2026-08-10', 423750.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '5126' AND n.paciente = 'Alexsander Alves do Santos'),
     2, '2026-09-10', 423750.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '5126' AND n.paciente = 'Alexsander Alves do Santos'),
     3, '2026-10-10', 423750.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '5126' AND n.paciente = 'Alexsander Alves do Santos'),
     4, '2026-11-10', 423750.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Leoni' AND n.numero_nf = '18.326' AND n.paciente = 'Carlos Alberto Carvalho Ferreira'),
     1, '2026-08-10', 28750.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Leoni' AND n.numero_nf = '18.326' AND n.paciente = 'Carlos Alberto Carvalho Ferreira'),
     3, '2026-09-10', 28750.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Leoni' AND n.numero_nf = '18.326' AND n.paciente = 'Carlos Alberto Carvalho Ferreira'),
     4, '2026-10-10', 28750.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Leoni' AND n.numero_nf = '18327' AND n.paciente = 'Ilda Damasceno Guimarães'),
     1, '2026-08-10', 16000.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Leoni' AND n.numero_nf = '18327' AND n.paciente = 'Ilda Damasceno Guimarães'),
     2, '2026-09-10', 16000.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Leoni' AND n.numero_nf = '18327' AND n.paciente = 'Ilda Damasceno Guimarães'),
     3, '2026-10-10', 16000.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Leoni' AND n.numero_nf = '18409' AND n.paciente = 'Mario Aparecido Agostinho'),
     1, '2026-08-10', 3367.34),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Leoni' AND n.numero_nf = '18409' AND n.paciente = 'Mario Aparecido Agostinho'),
     2, '2026-09-10', 3366.33),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Leoni' AND n.numero_nf = '18409' AND n.paciente = 'Mario Aparecido Agostinho'),
     3, '2026-10-10', 3366.33),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Leoni' AND n.numero_nf = '18440' AND n.paciente = 'Wanderley Sarkis Ligotte'),
     1, '2026-08-10', 7000.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Leoni' AND n.numero_nf = '18440' AND n.paciente = 'Wanderley Sarkis Ligotte'),
     2, '2026-09-10', 7000.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Leoni' AND n.numero_nf = '18440' AND n.paciente = 'Wanderley Sarkis Ligotte'),
     3, '2026-10-10', 7000.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Leoni' AND n.numero_nf = '18441' AND n.paciente = 'Mario Otini'),
     1, '2026-08-10', 4300.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Leoni' AND n.numero_nf = '18442' AND n.paciente = 'Wilson Osti'),
     1, '2026-08-10', 6500.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Leoni' AND n.numero_nf = '18442' AND n.paciente = 'Wilson Osti'),
     2, '2026-09-10', 6500.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Leoni' AND n.numero_nf = '18442' AND n.paciente = 'Wilson Osti'),
     3, '2026-10-10', 6500.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Surgipro' AND n.numero_nf = '84' AND n.paciente = 'Maria Queiroz de Souza'),
     1, '2026-08-10', 2100.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '5250' AND n.paciente = 'Andrea Pekny Carvalho Gomes'),
     1, '2026-08-10', 2400.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '5250' AND n.paciente = 'Andrea Pekny Carvalho Gomes'),
     1, '2026-09-10', 2400.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Surgipro' AND n.numero_nf = '85' AND n.paciente = 'Maria Hosana Alves da Silva'),
     1, '2026-08-10', 2624.99),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Surgipro' AND n.numero_nf = '85' AND n.paciente = 'Maria Hosana Alves da Silva'),
     2, '2026-09-10', 2625.01),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Surgipro' AND n.numero_nf = '85' AND n.paciente = 'Maria Hosana Alves da Silva'),
     3, '2026-10-10', 2624.99),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Surgipro' AND n.numero_nf = '85' AND n.paciente = 'Maria Hosana Alves da Silva'),
     4, '2026-11-10', 2624.99),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '5290' AND n.paciente = 'Moacir Rodrigues de Souza'),
     1, '2026-08-10', 4237.50),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '5290' AND n.paciente = 'Moacir Rodrigues de Souza'),
     2, '2026-09-10', 4237.50),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '5290' AND n.paciente = 'Moacir Rodrigues de Souza'),
     3, '2026-10-10', 4237.50),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '5290' AND n.paciente = 'Moacir Rodrigues de Souza'),
     4, '2026-11-10', 4237.50),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '5289' AND n.paciente = 'Valdirene do Valle Quaresma'),
     1, '2026-08-10', 999.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '5289' AND n.paciente = 'Valdirene do Valle Quaresma'),
     2, '2026-09-10', 999.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '5345' AND n.paciente = 'Maria Pedrosa de Sousa Alcantara Costa'),
     1, '2026-08-10', 1700.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '5345' AND n.paciente = 'Maria Pedrosa de Sousa Alcantara Costa'),
     2, '2026-09-10', 1700.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '5364' AND n.paciente = 'Anameure de Carvalho Seixa Santos'),
     1, '2026-08-10', 7200.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '5364' AND n.paciente = 'Anameure de Carvalho Seixa Santos'),
     2, '2026-09-10', 7200.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '5364' AND n.paciente = 'Anameure de Carvalho Seixa Santos'),
     3, '2026-10-10', 7200.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '5364' AND n.paciente = 'Anameure de Carvalho Seixa Santos'),
     4, '2026-11-10', 7200.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Leoni' AND n.numero_nf = '18441' AND n.paciente = 'Mario Otani'),
     2, '2026-09-10', 4300.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Leoni' AND n.numero_nf = '18504' AND n.paciente = 'Kelci Rodrigues Ocanha Saraiva'),
     1, '2026-09-10', 3366.33),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Leoni' AND n.numero_nf = '18504' AND n.paciente = 'Kelci Rodrigues Ocanha Saraiva'),
     2, '2026-10-10', 3366.33),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Leoni' AND n.numero_nf = '18504' AND n.paciente = 'Kelci Rodrigues Ocanha Saraiva'),
     3, '2026-11-10', 3367.34),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '5212' AND n.paciente = 'Lindinaldo Cordeiro de Andrade'),
     1, '2026-09-10', 4237.50),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '5212' AND n.paciente = 'Lindinaldo Cordeiro de Andrade'),
     2, '2026-10-10', 4237.50),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '5212' AND n.paciente = 'Lindinaldo Cordeiro de Andrade'),
     3, '2026-11-10', 4237.50),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Millenium' AND n.numero_nf = '5212' AND n.paciente = 'Lindinaldo Cordeiro de Andrade'),
     4, '2026-12-10', 4237.50),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Motiva' AND n.numero_nf = '584272' AND n.paciente = 'Sonia Maria dos Santos Rodrigues'),
     1, '2026-09-10', 1217.25),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Motiva' AND n.numero_nf = '584272' AND n.paciente = 'Sonia Maria dos Santos Rodrigues'),
     2, '2026-10-10', 1217.25),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Motiva' AND n.numero_nf = '584272' AND n.paciente = 'Sonia Maria dos Santos Rodrigues'),
     3, '2026-11-10', 1217.25),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Motiva' AND n.numero_nf = '584272' AND n.paciente = 'Sonia Maria dos Santos Rodrigues'),
     4, '2026-12-10', 1217.25),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Mogami' AND n.numero_nf = '100.268' AND n.paciente = 'Ruana Santana'),
     1, '2026-09-10', 1492.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Mogami' AND n.numero_nf = '100.268' AND n.paciente = 'Ruana Santana'),
     2, '2026-10-10', 1492.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Mogami' AND n.numero_nf = '100.268' AND n.paciente = 'Ruana Santana'),
     3, '2026-11-10', 1492.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Mogami' AND n.numero_nf = '100.268' AND n.paciente = 'Ruana Santana'),
     4, '2026-12-10', 1492.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Mogami' AND n.numero_nf = '100.268' AND n.paciente = 'Ruana Santana'),
     5, '2027-01-10', 1492.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Mogami' AND n.numero_nf = '100.267' AND n.paciente = 'Caroline Feliciano Baessa'),
     1, '2026-09-10', 1492.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Mogami' AND n.numero_nf = '100.267' AND n.paciente = 'Caroline Feliciano Baessa'),
     2, '2026-10-10', 1492.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Mogami' AND n.numero_nf = '100.267' AND n.paciente = 'Caroline Feliciano Baessa'),
     3, '2026-11-10', 1492.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Mogami' AND n.numero_nf = '100.267' AND n.paciente = 'Caroline Feliciano Baessa'),
     4, '2026-12-10', 1492.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Mogami' AND n.numero_nf = '100.267' AND n.paciente = 'Caroline Feliciano Baessa'),
     5, '2027-01-10', 1492.00),
    ((SELECT n.id FROM opme_notas n
        JOIN opme_fornecedores f ON f.id = n.fornecedor_id
       WHERE f.nome = 'Surgipro' AND n.numero_nf = '95' AND n.paciente = 'Solange Maria dos Santos Silva'),
     1, '2026-09-10', 1500.00)
ON DUPLICATE KEY UPDATE numero = VALUES(numero), valor = VALUES(valor);

SELECT
    (SELECT COUNT(*) FROM opme_fornecedores) AS fornecedores,
    (SELECT COUNT(*) FROM opme_notas)        AS notas,
    (SELECT COUNT(*) FROM opme_parcelas)     AS parcelas,
    (SELECT FORMAT(SUM(valor), 2) FROM opme_parcelas) AS soma_parcelas;
