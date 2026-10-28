#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Gera sql/amb_consultorios_mapa.sql — o mapa de sala inicial, extraído da
planilha "MAPA DE SALA 2026" (aba M.S. CLÁUDIA).

O conteúdo mora aqui, em estrutura, e não espalhado num .sql escrito à mão:
corrigir um horário é mudar uma linha em Python e gerar de novo.

Uso:  python3 tools/gerar_mapa_salas.py > sql/amb_consultorios_mapa.sql

Cada linha é:
    (turno, sala, dia da semana, profissional, especialidade,
     hora inicial, hora final, quinzenal?, observações)

"LIVRE" da planilha não vira registro: livre é a ausência de ocupação.
"""

DADOS = [
    ('manha', 1, 1, 'RICARDO RAMIREZ', 'MASTO', '10:00', '12:00', False, []),
    ('manha', 1, 2, 'JULIANA', 'DERMATO', '07:00', '12:00', False, []),
    ('manha', 1, 3, 'SERGIO FEIJÓ', 'INFECTO', '10:00', '12:00', False, []),
    ('manha', 1, 4, 'KADER', 'NEFRO', '07:00', '08:00', False, []),
    ('manha', 1, 4, 'NICOLAS', 'ENDO', '09:00', '12:00', False, []),
    ('manha', 1, 5, 'KITTY', 'CARDIO', '07:30', '12:00', False, []),
    ('manha', 2, 1, 'ELIZABETH', 'PSIQUI', '08:00', '13:00', False, []),
    ('manha', 2, 2, 'JULIENY', 'GATRO', '08:00', '11:00', False, []),
    ('manha', 2, 3, 'ANGELICA', 'NUTRI', '09:00', '14:00', False, []),
    ('manha', 2, 4, 'TARCISIO', 'URO', '11:00', '13:00', False, []),
    ('manha', 2, 5, 'BEATRIZ', 'CARDIO', '08:00', '12:00', False, []),
    ('manha', 2, 5, 'MATHEUS', 'BUCO', '09:00', '11:00', False, []),
    ('manha', 3, 1, 'JEFFERSON', 'GINECO', '08:00', '11:00', False, []),
    ('manha', 3, 3, 'JULIENY', 'GASTRO', '08:00', '12:00', False, []),
    ('manha', 3, 4, 'JOÃO PAULO', 'HEMATO', '07:00', '11:00', False, []),
    ('manha', 3, 5, 'JULIENY', 'CLINICA', '08:00', '11:00', False, []),
    ('manha', 4, 1, 'PROVA VENTILATÓRIA', None, '08:00', '10:00', False, []),
    ('manha', 4, 1, 'ALICE', 'PSICO', '11:00', '12:00', False, []),
    ('manha', 4, 2, 'ANDERSON', 'ORTOP. COLUNA', '09:30', '11:00', False, []),
    ('manha', 4, 3, 'SERGIO MAHALHÃES', 'ORTOP. GERAL', '08:00', '10:00', False, []),
    ('manha', 4, 5, 'R. CARAVIELLO', 'ORTOP. GERAL', '07:00', '10:00', False, []),
    ('manha', 5, 1, 'WELBER', 'ORTOP. COLUNA', '08:00', '10:00', False, []),
    ('manha', 5, 3, 'JOJI', 'CIR. GERAL', '08:00', '09:00', False, []),
    ('manha', 5, 4, 'WELBER', 'ORTOP. COLUNA', '08:00', '11:00', False, []),
    ('manha', 5, 5, 'ANDRE COSTA E RUBENS GARRIDO', 'NEUROCIRURGIÃO', '08:00', '11:00', True, []),
    ('manha', 6, 1, 'NATHALIE', 'NUTRI', '08:00', '11:00', False, []),
    ('manha', 6, 2, 'ELIZABETH', 'PSIQ', '08:00', '12:00', False, []),
    ('manha', 6, 3, 'MAURICIO', 'NEURO', '08:00', '12:00', False, []),
    ('manha', 6, 4, 'ELIZABETH', 'PSIQUI', '08:00', '12:00', False, []),
    ('manha', 6, 5, 'ROGÉRIO NAKASONE', 'ORTOP. JOELHO', '08:00', '12:00', False, []),
    ('manha', 7, 2, 'ALFREDO', 'GASTRO', '08:00', '09:00', False, []),
    ('manha', 7, 3, 'JULIANA E MARIA FERNANDA', 'DERMATO', '08:00', '12:00', True, []),
    ('manha', 7, 4, 'LEANDRO TUZUKI', 'PLÁSTICO', '07:00', '10:00', False, []),
    ('manha', 7, 5, 'MARIA FERNANDA', 'CARDIO', '08:00', '13:00', False, []),
    ('manha', 8, 3, 'FELIPE G.', 'CIR. CAB. PESCOÇO', '08:00', '11:00', True, []),
    ('manha', 8, 4, 'WAGNER RIVA', 'CIR. GERAL', '08:00', '10:00', False, []),
    ('manha', 8, 5, 'ITALLO', 'URO', '08:00', '12:00', False, []),
    ('tarde', 1, 1, 'THADEU', 'ENDOCRINO', '13:00', '17:00', False, []),
    ('tarde', 1, 2, 'FABIO', 'URO', '13:00', '16:00', False, []),
    ('tarde', 1, 3, 'ACUMPUNTURA', None, '14:00', '17:00', False, []),
    ('tarde', 1, 4, 'NICOLAS', 'ENDOCRI/CLINICO', '13:00', '17:00', False, []),
    ('tarde', 2, 1, 'MARIA FERNANDA', 'CARDIO', '13:30', '16:00', False, []),
    ('tarde', 2, 2, 'JULIENY', 'GATRO', '14:30', '17:00', False, []),
    ('tarde', 2, 3, 'ACUMPUNTURA', None, '14:00', '17:00', False, []),
    ('tarde', 2, 4, 'THADEU', 'ENDOCRINO', '13:00', '17:00', False, []),
    ('tarde', 2, 5, 'NICOLAS', 'ENDOCRINO', '13:00', '17:00', False, []),
    ('tarde', 3, 1, 'DANIEL', 'GINECO', '14:00', '16:00', False, []),
    ('tarde', 3, 1, 'JOSÉ MARCELO', 'GINECO', '16:00', '17:00', False, []),
    ('tarde', 3, 2, 'JAMILE', 'GINECO', '12:00', '16:00', True, []),
    ('tarde', 3, 3, 'BEATRIZ', 'GINECO', '13:00', '17:00', True, []),
    ('tarde', 3, 5, 'MARIANA', 'PSIQUIATRA', '13:00', '17:00', False, []),
    ('tarde', 4, 1, 'ANA CAROLINE', 'PSIQUI', '13:00', '16:00', False, []),
    ('tarde', 4, 2, 'ANA CAROLINE', 'PSQUIATRA', '13:00', '17:00', False, []),
    ('tarde', 4, 3, 'ANESTESISTA', None, '13:00', '17:00', False, []),
    ('tarde', 4, 4, 'JOSE PEDRO', 'URO', '13:00', '16:00', True, []),
    ('tarde', 4, 5, 'ANESTESISTA', None, '13:00', '15:00', False, []),
    ('tarde', 5, 1, 'LEONARDO', 'PNEUMO', '08:00', '10:00', False, []),
    ('tarde', 5, 2, 'LAURA', 'CARDIO', '13:00', '17:00', False, []),
    ('tarde', 5, 3, 'LAURA', 'CARDIO', '13:00', '17:00', False, []),
    ('tarde', 5, 4, 'WELBER', 'ORTOP. COLUNA', '14:30', '16:30', True, ['OUTUBRO']),
    ('tarde', 5, 5, 'SILVIO', 'ONCO', '15:00', '17:00', False, []),
    ('tarde', 6, 1, 'MAURICIO', 'NEURO', '12:30', '16:00', False, []),
    ('tarde', 6, 2, 'LUIZ CARLOS', 'ORTOP. JOELHO', '12:00', '14:00', False, []),
    ('tarde', 6, 3, 'ALINE', 'FISIATRA', '12:30', '13:30', False, []),
    ('tarde', 6, 3, 'MARIO PIERRY', 'ORTOP. OMBRO', '14:00', '16:00', False, []),
    ('tarde', 6, 4, 'ANDRE ANDRIOLO', 'NEURO', '12:30', '15:00', False, []),
    ('tarde', 6, 5, 'ROGÉRIO BUCHMAN', 'ORTOP. QUADRIL', '14:00', '16:00', False, []),
    ('tarde', 7, 1, 'ALEXANDRE EVENCIO', 'ORTOP. PÉ', '14:00', '17:00', False, []),
    ('tarde', 7, 2, 'ALISSON', 'CIR. VASCULAR', '14:00', '16:00', False, []),
    ('tarde', 7, 3, 'WAGNER BATISTA', 'ORTOP. MÃO', '13:30', '15:30', False, []),
    ('tarde', 7, 4, 'ANA CAROLINA POZZI', 'REUMATOLOGISTA', '13:00', '17:00', False, []),
    ('tarde', 7, 5, 'MARCELO SPANO', 'CIR. VASCULAR', '13:30', '15:00', False, []),
    ('tarde', 8, 1, 'ANESTESISTA', None, '13:00', '15:00', False, []),
    ('tarde', 8, 2, 'MARIANA', 'PSIQUI', '13:30', '17:00', False, []),
    ('tarde', 8, 3, 'LUCAS', 'PROCTO', '13:00', '16:00', False, []),
    ('tarde', 8, 4, 'MARIA FERNANDA', 'DERMATO', '13:00', '17:00', False, []),
    ('tarde', 8, 5, 'ITALLO', 'URO', '13:00', '15:00', False, ['OUTUBRO']),
]
# total 77

# ── Padronização ────────────────────────────────────────────────────────
# A planilha foi digitada ao longo do tempo por gente diferente, então a
# mesma especialidade aparece escrita de três formas. Aqui só corrijo o que
# é erro de digitação ou variação da mesma coisa — nada é inventado.
ESPECIALIDADES = {
    'GATRO':            'Gastro',
    'GASTRO':           'Gastro',
    'PSIQUI':           'Psiquiatria',
    'PSIQ':             'Psiquiatria',
    'PSIQUIATRA':       'Psiquiatria',
    'PSQUIATRA':        'Psiquiatria',
    'PSICO':            'Psicologia',
    'ENDO':             'Endócrino',
    'ENDOCRINO':        'Endócrino',
    'ENDOCRI/CLINICO':  'Endócrino / Clínica',
    'CLINICA':          'Clínica',
    'REUMATOLOGISTA':   'Reumato',
    'NEUROCIRURGIÃO':   'Neurocirurgia',
    'FISIATRA':         'Fisiatria',
    'PLÁSTICO':         'Cir. Plástica',
    'BUCO':             'Buco-maxilo',
}

# Nomes escritos errado na planilha. Acento que falta em nome próprio eu
# não invento — quem sabe como se escreve é a central, e dá para corrigir
# pela tela depois.
NOMES = {
    'SERGIO MAHALHÃES': 'Sergio Magalhães',
    'ACUMPUNTURA':      'Acupuntura',
}

MESES = {
    'SETEMBRO': 'somente em setembro',
    'OUTUBRO':  'a partir de outubro',
}

MINUSCULAS = {'e', 'de', 'da', 'do', 'dos', 'das', 'e/ou'}

# Não são pessoas: ocupam sala como qualquer médico, mas podem acontecer em
# duas salas ao mesmo tempo. Sem essa marca, o sistema apontaria a acupuntura
# da Sala 1 e a da Sala 2 como "a mesma pessoa em dois lugares".
ATIVIDADES = {'ACUMPUNTURA', 'ACUPUNTURA', 'ANESTESISTA', 'PROVA VENTILATÓRIA'}


def caixa(texto):
    """MAIÚSCULAS DA PLANILHA → Caixa de nome próprio."""
    palavras = []
    for i, p in enumerate(texto.lower().split()):
        if i > 0 and p in MINUSCULAS:
            palavras.append(p)
        elif '.' in p and len(p) <= 5:          # abreviações: cir., ortop.
            palavras.append(p.capitalize())
        else:
            palavras.append(p[:1].upper() + p[1:])
    return ' '.join(palavras)


def nome_profissional(bruto):
    if bruto.upper() in NOMES:
        return NOMES[bruto.upper()]
    return caixa(bruto)


def nome_especialidade(bruto):
    if not bruto:
        return None
    chave = bruto.upper().strip()
    if chave in ESPECIALIDADES:
        return ESPECIALIDADES[chave]
    return caixa(bruto)


def observacao(obs, quinzenal):
    partes = [MESES.get(o.upper(), o.lower()) for o in obs]
    return '; '.join(partes) if partes else None


def sql_txt(valor):
    if valor is None or valor == '':
        return 'NULL'
    return "'" + str(valor).replace('\\', '\\\\').replace("'", "''") + "'"


# ── Saída ───────────────────────────────────────────────────────────────
L = []
w = L.append

w('-- APASBS v2 — Mapa de sala inicial do ambulatório')
w('-- GERADO por tools/gerar_mapa_salas.py — não edite à mão.')
w('-- Fonte: planilha "MAPA DE SALA 2026", aba M.S. CLÁUDIA.')
w('--')
w('-- Execute DEPOIS de sql/amb_consultorios.sql:')
w('--   mysql -u usuario -p apasbs < sql/amb_consultorios_mapa.sql')
w('--')
w('-- Reexecutar reescreve as ocupações que vieram da planilha (mesma sala,')
w('-- dia, turno, horário e profissional). O que a central cadastrou pela')
w('-- tela depois disso não é tocado.')
w('')
w('SET NAMES utf8mb4;')
w('')

# ── Salas ───────────────────────────────────────────────────────────────
w('-- ── As oito salas ───────────────────────────────────────────────────')
w('INSERT INTO amb_salas (nome, ordem) VALUES')
salas = sorted({d[1] for d in DADOS})
w(',\n'.join("    ('Sala %d', %d)" % (s, s) for s in salas))
w('ON DUPLICATE KEY UPDATE ordem = VALUES(ordem);')
w('')

# ── Especialidades ──────────────────────────────────────────────────────
especialidades = sorted({nome_especialidade(d[4]) for d in DADOS if d[4]})
w('-- ── Especialidades ──────────────────────────────────────────────────')
w('INSERT INTO amb_especialidades (nome) VALUES')
w(',\n'.join('    (%s)' % sql_txt(e) for e in especialidades))
w('ON DUPLICATE KEY UPDATE ativo = 1;')
w('')

# ── Profissionais ───────────────────────────────────────────────────────
# A especialidade habitual é a que a pessoa tem em mais ocupações da
# planilha. Quem atende duas coisas — clínica num dia, gastro no outro —
# fica com a mais frequente aqui, e a ocupação guarda a sua.
contagem = {}
atividade_de = {}
for turno, sala, dia, nome, esp, ini, fim, quinz, obs in DADOS:
    p = nome_profissional(nome)
    atividade_de[p] = atividade_de.get(p, 0) or (1 if nome.upper() in ATIVIDADES else 0)
    if esp:
        chave = (p, nome_especialidade(esp))
        contagem[chave] = contagem.get(chave, 0) + 1

habitual = {}
for (p, e), n in sorted(contagem.items(), key=lambda kv: (kv[0][0], -kv[1], kv[0][1])):
    habitual.setdefault(p, e)

profissionais = sorted(atividade_de)
w('-- ── Profissionais e atividades ───────────────────────────────────────')
w('INSERT INTO amb_profissionais (nome, especialidade_id, atividade) VALUES')
linhas = []
for p in profissionais:
    esp = habitual.get(p)
    ref = ("(SELECT id FROM amb_especialidades WHERE nome = %s)" % sql_txt(esp)) if esp else 'NULL'
    linhas.append('    (%s, %s, %d)' % (sql_txt(p), ref, atividade_de[p]))
w(',\n'.join(linhas))
w('ON DUPLICATE KEY UPDATE')
w('    especialidade_id = VALUES(especialidade_id),')
w('    atividade        = VALUES(atividade),')
w('    ativo            = 1;')
w('')

# ── Ocupações ───────────────────────────────────────────────────────────
w('-- ── Ocupações ───────────────────────────────────────────────────────')
w('INSERT INTO amb_ocupacoes')
w('    (sala_id, profissional_id, especialidade_id, dia_semana, turno,')
w('     hora_inicio, hora_fim, periodicidade, observacao)')
w('VALUES')

linhas = []
for turno, sala, dia, nome, esp, ini, fim, quinz, obs in DADOS:
    p = nome_profissional(nome)
    e = nome_especialidade(esp)
    ref_esp = ("(SELECT id FROM amb_especialidades WHERE nome = %s)" % sql_txt(e)) if e else 'NULL'
    linhas.append(
        "    ((SELECT id FROM amb_salas WHERE nome = 'Sala %d'),\n"
        "     (SELECT id FROM amb_profissionais WHERE nome = %s),\n"
        "     %s,\n"
        "     %d, '%s', '%s', '%s', '%s', %s)"
        % (sala, sql_txt(p), ref_esp, dia, turno, ini, fim,
           'quinzenal' if quinz else 'semanal',
           sql_txt(observacao(obs, quinz)))
    )

w(',\n'.join(linhas))
w('ON DUPLICATE KEY UPDATE')
w('    hora_fim         = VALUES(hora_fim),')
w('    especialidade_id = VALUES(especialidade_id),')
w('    periodicidade    = VALUES(periodicidade),')
w('    observacao       = VALUES(observacao),')
w('    ativo            = 1;')
w('')
w('SELECT s.nome AS sala, COUNT(o.id) AS ocupacoes')
w('  FROM amb_salas s LEFT JOIN amb_ocupacoes o ON o.sala_id = s.id')
w(' GROUP BY s.id, s.nome ORDER BY s.ordem;')

print('\n'.join(L))
