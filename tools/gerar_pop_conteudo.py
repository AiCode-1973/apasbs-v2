#!/usr/bin/env python3
"""
Gera sql/central_pop_conteudo.sql a partir das ORIENTAÇÕES EDA/COLONO 2.0.

O conteúdo mora aqui, em estrutura, e não espalhado num arquivo .sql escrito
à mão: assim uma correção de texto é uma linha em Python, e o SQL é
regenerado sem risco de esquecer aspas ou quebrar um bloco.

Uso:  python3 tools/gerar_pop_conteudo.py > sql/central_pop_conteudo.sql
"""

ENDERECO = 'Rua Carvalho de Mendonça, 335 – Santos – 1º Andar.'

# Campos entre colchetes são o que a atendente substitui. O PDF original
# trazia "20/07" e "08:00" como exemplo, o que se confunde com valor real.
CABECALHO_MSG = """Hospital Santo Expedito
🩺 Exame marcado com sucesso!

📅 Data: [DATA DO EXAME]
⏰ Horário: [HORÁRIO]
👨‍⚕️ Médico(a): Dr(a).: [NOME DO MÉDICO]
🩺 Especialidade: {especialidade}

📍 Endereço: {endereco}

Aguarde mais um pouco, iremos enviar agora o preparo para o exame.

Favor trazer o pedido médico original + guia autorizada."""

AVISO_PEDIDO_3DIAS = """⚠️ É necessário entregar o *pedido médico original* com pelo menos 3 dias de antecedência no setor de exames. Esse prazo é necessário para que seja feita a reserva do quarto para a internação."""

AVISO_ANESTESISTA = """⚠️ Trazer o *pedido médico original* no dia da consulta com o anestesista. Após a consulta, entregue o pedido médico original e a carta de liberação do anestesista na recepção de exames, localizada no 1º andar, para que seja realizada a reserva do quarto para a internação."""

def aviso_internacao(quando):
    return ('⚠️ Observação: Como o exame será realizado com internação, será '
            'necessário comparecer ao hospital ' + quando +
            ', no setor de internação do hospital (térreo).')


def montar(especialidade_msg, internacao, turno, anestesista):
    """Devolve a lista de blocos de um roteiro."""
    blocos = []

    # ── O que fazer ──────────────────────────────────────────────────────
    if not internacao:
        passos = [
            'Confirme com a paciente que ela não tem comorbidade cardiológica, pulmonar ou renal (ver POP-CA-013).',
            'Agende o exame respeitando o limite de vagas do médico.',
            'Passe para a paciente a data e o horário em que o exame será realizado.',
            'Envie a mensagem de confirmação (modelo abaixo).',
            'Envie em seguida o preparo do exame.',
        ]
    else:
        if turno == 'manha':
            comparecer = 'um dia antes do exame, às 16:00, no setor de internação'
            quando_msg = 'no dia [DATA DA VÉSPERA], às 16:00'
        else:
            comparecer = 'no dia do exame, às 05:30, no setor de internação'
            quando_msg = 'no dia [DATA DO EXAME], às 05:30'

        passos = [
            'Confirme com a paciente que ela não tem comorbidade cardiológica, pulmonar ou renal (ver POP-CA-013).',
            'Agende o exame respeitando o limite de vagas do médico.',
            'Passe para a paciente a data e o horário em que o exame será realizado.',
            'Passe também a data e o horário de comparecimento na internação: ' + comparecer + '.',
        ]
        if anestesista:
            passos.append(
                'Aproveite a presença na consulta com o anestesista para solicitar a entrega do '
                'pedido médico original e da carta de liberação do anestesista no setor de exames.')
        passos += [
            'Envie a mensagem de confirmação (modelo abaixo).',
            'Envie em seguida o preparo do exame.',
        ]

    blocos.append(('passos', 'O que fazer', '\n'.join(passos)))

    # ── Alertas ──────────────────────────────────────────────────────────
    if internacao and turno == 'manha':
        blocos.append(('alerta', 'Convênios que não podem ser agendados',
                       'Não agendar Trasmontano e CAPEP neste roteiro.'))

    if anestesista:
        blocos.append(('alerta', 'Distância entre a consulta e o exame',
                       'Evite marcar o anestesista muito perto da data do exame. '
                       'O setor de exames ainda precisa descer a pasta da paciente '
                       'para o setor de internação.'))

    # ── Mensagem ─────────────────────────────────────────────────────────
    msg = CABECALHO_MSG.format(especialidade=especialidade_msg, endereco=ENDERECO)

    if internacao:
        msg += '\n\n' + (AVISO_ANESTESISTA if anestesista else AVISO_PEDIDO_3DIAS)
        # Linha em branco entre os dois avisos: são recados diferentes e,
        # grudados, viram um parágrafo só na tela e no WhatsApp.
        msg += '\n\n' + aviso_internacao(quando_msg)

    blocos.append(('mensagem', 'Mensagem para a paciente', msg))
    return blocos


# ── Os doze roteiros ─────────────────────────────────────────────────────
ROTEIROS = []
codigo = 0

for exame, esp_sem, esp_com in [
    ('Endoscopia', 'ENDOSCOPIA', 'ENDOSCOPIA COM INTERNAÇÃO'),
    ('Colonoscopia / Combo', 'COLONOSCOPIA', 'COLONOSCOPIA COM INTERNAÇÃO'),
]:
    for turno, turno_txt in [('manha', 'manhã'), ('tarde', 'tarde')]:
        for internacao, anestesista in [(False, False), (True, False), (True, True)]:
            codigo += 1
            nome = exame + ' — '
            nome += 'com internação' if internacao else 'sem internação'
            nome += ' (' + turno_txt + ')'
            if anestesista:
                nome += ' + anestesista'

            resumo = ('Roteiro de agendamento e mensagem de confirmação para '
                      + exame.lower() + ' '
                      + ('com' if internacao else 'sem') + ' internação, no período da '
                      + turno_txt + (', com consulta prévia do anestesista' if anestesista else '')
                      + '.')

            ROTEIROS.append({
                'codigo': 'POP-CA-%03d' % codigo,
                'titulo': nome,
                'categoria': 'Roteiros de agendamento',
                'resumo': resumo,
                'ordem': codigo * 10,
                'blocos': montar(esp_com if internacao else esp_sem,
                                 internacao, turno, anestesista),
            })

# ── Regras gerais ────────────────────────────────────────────────────────
REGRAS = [
    ('CAPEP com internação',
     'Não agendar no período da manhã. O agendamento só acontece depois de autorizado. '
     'Solicite que a paciente compareça ao hospital, no setor de internação, portando o '
     'pedido médico original, a carteirinha da CAPEP e um documento oficial de identidade — '
     'é ali que se pede a autorização da CAPEP para a internação.'),

    ('Consulta com o anestesista',
     'Evite marcar o anestesista muito perto da data do exame, principalmente em exames '
     'com internação: o setor de exames ainda vai precisar descer a pasta da paciente para '
     'o setor de internação.'),

    ('Exames que passam pela supervisão',
     'Colono com argônio, endoscopia por cápsula, CPRE, broncoscopia, dilatação de esôfago, '
     'passagem de SNE por EDA, abertura de GTT e troca de sonda de GTT não são agendados '
     'pela central. Encaminhe à supervisão: o agendamento requer outros equipamentos ou materiais.'),

    ('Encaixe',
     'Caso precise de encaixe, falar com a Enf. Sandra.'),

    ('Remarcação de exame com internação',
     'Sempre que uma paciente com internação tiver a data do exame remarcada, informe o setor '
     'de exames. Eles precisam saber da nova data para encaminhar a pasta ao setor de internação '
     'no prazo adequado.'),

    ('Comorbidades',
     'Antes de agendar qualquer exame, verifique com a paciente se possui alguma comorbidade '
     'cardiológica, pulmonar ou renal. Se possuir, ela precisa primeiro passar em consulta e '
     'obter a liberação para a realização do exame.'),

    ('Uso de Mounjaro',
     'Verifique se a paciente faz uso de Mounjaro. Em caso positivo, o exame só pode ser '
     'realizado após 21 dias sem o uso.'),

    ('Limite de agendamentos por médico',
     'Respeite a quantidade limite de agendamentos de cada médico, enviada pela enfermagem '
     'todo mês por e-mail — por exemplo, "Dr. X atende 4 colonoscopias + 2 combos". '
     'No sistema, esse limite é o que está lançado em Ambulatório › Agenda: quando o bloco '
     'lota, a tela não deixa marcar mais.'),

    ('CAPEP e biópsia',
     'Para a CAPEP, é imprescindível que o pedido médico de endoscopia e/ou colonoscopia '
     'contenha a solicitação de biópsia.'),
]

REGRAS_BLOCOS = [('texto', None,
                  'Regras que valem para todos os agendamentos de endoscopia e colonoscopia, '
                  'independentemente do roteiro. Na dúvida, estas prevalecem.')]
for i, (titulo, texto) in enumerate(REGRAS, start=1):
    REGRAS_BLOCOS.append(('alerta', '%d. %s' % (i, titulo), texto))

ROTEIROS.append({
    'codigo': 'POP-CA-013',
    'titulo': 'Regras gerais de agendamento de EDA e Colonoscopia',
    'categoria': 'Regras gerais',
    'resumo': 'As nove regras que valem para todos os roteiros: convênios, comorbidades, '
              'Mounjaro, limites por médico, encaixe e exames que passam pela supervisão.',
    'ordem': 5,
    'blocos': REGRAS_BLOCOS,
})


# ── Emissão do SQL ───────────────────────────────────────────────────────
def esc(v):
    if v is None:
        return 'NULL'
    return "'" + str(v).replace('\\', '\\\\').replace("'", "''") + "'"


print("""-- APASBS v2 — Conteúdo dos POPs da Central de Agendamento
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
""")

for p in ROTEIROS:
    print('-- ' + '─' * 68)
    print('-- ' + p['codigo'] + ' · ' + p['titulo'])
    print('INSERT INTO central_pops (codigo, titulo, categoria, resumo, versao, revisado_em, ordem)')
    print('VALUES (%s, %s, %s, %s, \'2.0\', \'2026-08-20\', %d)' % (
        esc(p['codigo']), esc(p['titulo']), esc(p['categoria']), esc(p['resumo']), p['ordem']))
    print('ON DUPLICATE KEY UPDATE titulo = VALUES(titulo), categoria = VALUES(categoria),')
    print('    resumo = VALUES(resumo), versao = VALUES(versao),')
    print('    revisado_em = VALUES(revisado_em), ordem = VALUES(ordem);')
    print()
    print("SET @pop := (SELECT id FROM central_pops WHERE codigo = %s);" % esc(p['codigo']))
    print('DELETE FROM central_pop_blocos WHERE pop_id = @pop;')
    print('INSERT INTO central_pop_blocos (pop_id, tipo, titulo, conteudo, ordem) VALUES')

    linhas = []
    for i, (tipo, titulo, conteudo) in enumerate(p['blocos'], start=1):
        linhas.append('    (@pop, %s, %s, %s, %d)' % (esc(tipo), esc(titulo), esc(conteudo), i * 10))
    print(',\n'.join(linhas) + ';')
    print()

print("SELECT categoria, COUNT(*) AS pops FROM central_pops GROUP BY categoria;")
