-- APASBS v2 — POP de treinamento da Central de Agendamento
-- Execute DEPOIS de sql/central_pop.sql:
--   mysql -u usuario -p apasbs < sql/central_pop_treinamento.sql
--
-- Script ADITIVO e re-executável: rodar de novo reescreve ESTE POP por
-- completo (apaga e recria os blocos dele), e não toca em nenhum outro
-- código. Edições feitas pela tela no POP-CA-014 se perdem ao rodar.
--
-- Mesma estrutura do conteúdo gerado por tools/gerar_pop_conteudo.py, só
-- que escrito à mão: este POP não sai das "Orientações EDA e Colono" e sim
-- do treinamento de conferência de dados.

SET NAMES utf8mb4;

-- ────────────────────────────────────────────────────────────────────
-- POP-CA-014 · Conferência dos dados do paciente no agendamento
INSERT INTO central_pops (codigo, titulo, categoria, resumo, versao, revisado_em, ordem)
VALUES ('POP-CA-014',
        'Conferência dos dados do paciente no agendamento',
        'Regras gerais',
        'Comandos básicos de segurança e qualidade no atendimento: nome, data de nascimento, convênio e telefone confirmados verbalmente com o paciente antes de concluir qualquer agendamento.',
        '1.0', '2026-10-01', 6)
ON DUPLICATE KEY UPDATE titulo = VALUES(titulo), categoria = VALUES(categoria),
    resumo = VALUES(resumo), versao = VALUES(versao),
    revisado_em = VALUES(revisado_em), ordem = VALUES(ordem);

SET @pop := (SELECT id FROM central_pops WHERE codigo = 'POP-CA-014');
DELETE FROM central_pop_blocos WHERE pop_id = @pop;
INSERT INTO central_pop_blocos (pop_id, tipo, titulo, conteudo, ordem) VALUES

    (@pop, 'texto', 'Objetivo', 'Padronizar o atendimento da Central de Agendamento, garantindo que todos os dados do paciente sejam confirmados antes da realização de qualquer agendamento.

A conferência correta das informações é responsabilidade da Central de Agendamento, e existe para evitar erro de cadastro, problema de autorização, divergência de convênio, dificuldade no atendimento e transtorno na recepção.', 10),

    (@pop, 'passos', 'Os quatro dados que se confirma sempre', 'Nome completo — confirme o nome inteiro, sem abreviação e sem informação pela metade.
Data de nascimento — confirme a data completa, com dia, mês e ano.
Convênio — confirme por qual convênio o paciente será atendido. Não presuma pelo agendamento anterior.
Telefone — confirme o número atualizado e, sempre que der, se é o melhor telefone para contato.', 20),

    (@pop, 'alerta', 'Nenhum agendamento sem conferência', 'A conferência acontece ANTES de concluir o agendamento.

Não basta olhar o cadastro no sistema: os dados são confirmados em voz alta com o paciente ou com o responsável. Só depois da confirmação o agendamento é concluído.', 30),

    (@pop, 'mensagem', 'Como abrir a conferência', 'Antes de realizar o seu agendamento, vou confirmar alguns dados para garantir que tudo fique correto, tudo bem?

Seu nome completo é [NOME COMPLETO]?
Sua data de nascimento é [DATA DE NASCIMENTO]?
O atendimento será pelo convênio [CONVÊNIO]?
O telefone para contato continua sendo [TELEFONE]?', 40),

    (@pop, 'alerta', 'Convênio: confirmar, não presumir', 'O convênio é confirmado em todo agendamento, mesmo quando o paciente já tem cadastro.

Pergunte assim: “Só para confirmar, o seu atendimento será realizado pelo convênio [CONVÊNIO], correto?”

Evite a pergunta que já entrega a resposta, do tipo “é o mesmo convênio de sempre, né?”. Ela convida o paciente a concordar sem conferir.', 50),

    (@pop, 'alerta', 'Telefone: confirmar e atualizar na hora', 'O telefone é o que permite avisar o paciente de alteração de horário, cancelamento, mudança de médico, encaixe, confirmação do atendimento e qualquer orientação sobre o agendamento.

Pergunte assim: “O telefone para contato continua sendo o final [FINAL DO TELEFONE]?”

Se estiver diferente, atualize o cadastro na hora. Anotar para corrigir depois é o mesmo que não corrigir.', 60),

    (@pop, 'texto', 'Por que isso importa', 'Um dado conferido errado não para na central: ele chega à recepção e vira fila.

• dificuldade para localizar o cadastro
• cadastro de paciente trocado
• agendamento em nome de outra pessoa
• divergência de data de nascimento
• problema com o convênio
• dificuldade na autorização
• necessidade de refazer o atendimento
• atraso no atendimento do paciente
• reclamação
• retrabalho para a equipe da recepção
• transtorno para o paciente e para o acompanhante

O atendimento não termina quando o horário é marcado. Ele está correto quando as informações foram conferidas e o agendamento foi feito de forma segura.', 70),

    (@pop, 'alerta', 'A ordem é conferir, confirmar, agendar e registrar', 'A Central de Agendamento é a primeira etapa do processo, e é nela que o dado entra certo.

CONFERIR → CONFIRMAR → AGENDAR → REGISTRAR

E não: agendar e deixar para a recepção conferir. A recepção não é a segunda etapa de correção do que deveria ter sido confirmado no agendamento.', 80),

    (@pop, 'texto', 'Responsabilidade e padronização', 'A conferência dos dados é obrigação operacional de todos os colaboradores da Central de Agendamento.

Quando aparecerem erros repetidos, falha na conferência ou problema causado pela ausência de confirmação, a gestão trata a situação conforme as normas da instituição, com as orientações necessárias.

O objetivo não é apontar erro: é garantir que o processo seja feito do mesmo jeito por todo mundo.', 90),

    (@pop, 'passos', 'Checklist antes de finalizar o agendamento', 'Nome completo confirmado
Data de nascimento confirmada
Convênio confirmado
Telefone confirmado ou atualizado
Especialidade confirmada
Médico confirmado
Data e horário confirmados
Orientações necessárias passadas ao paciente
Agendamento registrado corretamente no sistema', 100),

    (@pop, 'mensagem', 'Frase padrão para finalizar', 'Pronto, seu agendamento está realizado. Vou repetir as informações para confirmar se está tudo correto: atendimento com o Dr(a). [NOME DO MÉDICO], especialidade [ESPECIALIDADE], no dia [DATA], às [HORÁRIO], pelo convênio [CONVÊNIO]. Está correto?', 110),

    (@pop, 'alerta', 'Um dado conferido hoje evita um problema na recepção amanhã', 'A Central de Agendamento e a recepção fazem parte do mesmo processo. Cada informação registrada corretamente é mais segurança, menos retrabalho, menos reclamação e melhor atendimento ao paciente.

A qualidade do atendimento começa no primeiro contato: conferir os dados é parte do agendamento.', 120);

SELECT codigo, titulo, categoria, versao, ordem FROM central_pops ORDER BY categoria, ordem, codigo;
