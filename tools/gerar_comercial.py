#!/usr/bin/env python3
"""Gera sql/com_capep_2026.sql a partir do edital da CAPEP-SAÚDE.

Uso:
    pdftotext -layout edital.pdf edital.txt
    python3 tools/gerar_comercial.py edital.txt > sql/com_capep_2026.sql

O edital é um PDF de 354 páginas; o que vira tabela de valores está nas
páginas 22 a 31 (diárias, taxas e pacotes de pronto-socorro) e 42 a 266
(Anexos V a IX). Cada trecho tem um desenho de coluna diferente, então o
parser é escrito por seção, e não genérico — parser genérico em PDF de
edital erra calado, que é o pior jeito de errar numa tabela de preço.
"""
import re
import sys
import unicodedata

# ── Leitura ──────────────────────────────────────────────────────────────

def paginas(caminho):
    texto = open(caminho, encoding='utf-8', errors='replace').read()
    return texto.split('\f')


RE_VALOR = re.compile(r'R\$\s*([\d.]+,\d{2})')
RE_CODIGO = re.compile(r'^\s*(\d{6,10})\s+')


def dinheiro(txt):
    return float(txt.replace('.', '').replace(',', '.'))


def limpa(txt):
    txt = unicodedata.normalize('NFC', txt)
    txt = txt.replace('’', "'").replace('“', '"').replace('”', '"')
    return re.sub(r'\s+', ' ', txt).strip()


class Coletor:
    def __init__(self):
        self.itens = []

    def add(self, tipo, grupo, descricao, valor, codigo=None, variante=None, **extras):
        descricao = limpa(descricao)
        if not descricao:
            return
        self.itens.append({
            'tipo': tipo,
            'grupo': grupo,
            'codigo': limpa(codigo) if codigo else None,
            'descricao': descricao[:400],
            'variante': variante,
            'valor': valor,
            'extras': {k: v for k, v in extras.items() if v not in (None, '', [])},
        })


# ── 1. Diárias, taxas e pacotes de pronto-socorro (páginas 22 a 31) ──────
#
# Tabelas curtas, de uma ou três colunas de preço (PORTE I, II e III).
# O ruído aqui é o texto corrido do edital, que também cita valores
# ("materiais de valor até R$ 325,89"): a linha só vira item quando o
# rótulo à esquerda parece um rótulo de tabela, e não uma frase.

PORTES_HOSP = ['PORTE I', 'PORTE II', 'PORTE III']

GRUPOS_22_31 = [
    (r'^\s*a\)\s*Acomoda[çc][ãa]o', 'Diárias de acomodação'),
    (r'^\s*b\)\s*UTI', 'Diárias de UTI'),
    (r'b\.1\)\s*DI[ÁA]RIAS DE HEMODIN', 'Diárias de hemodinâmica'),
    (r'c\.1\)\s*Taxas? de sala para centro cir', 'Taxas de sala — centro cirúrgico'),
    (r'c\.2\)\s*Taxa de sala ambulatorial', 'Taxa de sala ambulatorial'),
    (r'^\s*d\)\s*Taxa de uso de equipamentos', 'Taxa de uso de equipamentos (vídeo)'),
    (r'Assist[êe]ncia em Pronto Socorro\s*-\s*Adulto', 'Pronto-socorro — adulto e pediátrico'),
    (r'f\)\s*Assist[êe]ncia em Pronto Socorro', 'Pronto-socorro — pediatria, ginecologia e obstetrícia'),
    (r'Outras Informa[çc][õo]es', 'Outras informações'),
    (r'REFER[ÊE]NCIA\s*$', 'Materiais de referência (tabela própria)'),
]

FRASE = re.compile(r'(valor unit[áa]rio|conforme|exceto|dever[ãa]o|inclus|acima de|at[ée] R\$|;)', re.I)


def secao_diarias(pgs, col):
    grupo = 'Diárias e taxas'
    for n in range(22, 32):
        linhas = pgs[n - 1].splitlines()
        for idx, linha in enumerate(linhas):
            crua = linha.rstrip()
            for regex, nome in GRUPOS_22_31:
                if re.search(regex, crua, re.I):
                    grupo = nome
            valores = RE_VALOR.findall(crua)
            if not valores:
                continue

            # O rótulo é tudo à esquerda do primeiro valor, com os espaços de
            # alinhamento do PDF colapsados — senão o comprimento medido é o
            # da coluna, e não o do texto.
            rotulo = limpa(crua[:crua.index('R$')])

            # Rótulo que não coube na mesma linha do valor: o PDF quebra a
            # célula e o valor sobra sozinho. Pega as linhas de cima e de
            # baixo, que é como a célula estava desenhada.
            if rotulo == '':
                acima = next((limpa(l) for l in reversed(linhas[:idx]) if limpa(l)), '')
                abaixo = limpa(linhas[idx + 1]) if idx + 1 < len(linhas) else ''
                if 'R$' in abaixo or len(abaixo) > 60:
                    abaixo = ''
                rotulo = limpa(acima + ' ' + abaixo)
                if rotulo.lower().endswith('valor'):
                    rotulo = rotulo[:-5].strip()

            rotulo = re.sub(r'^\d{1,2}\s+', '', rotulo)            # nº do item na lista de referência

            codigo = None
            m = RE_CODIGO.match(crua)
            if m:
                codigo = m.group(1)
                rotulo = re.sub(r'^' + codigo + r'\s*', '', rotulo).strip()

            unidade = None
            m = re.search(r'\s(DIA|UN|M2|M²)$', rotulo)
            if m:
                unidade = m.group(1)
                rotulo = rotulo[:m.start()].strip()

            # Rótulo de tabela começa em maiúscula ou dígito; frase cortada do
            # texto corrido do edital ("os materiais de valor até R$ 325,89")
            # começa em minúscula — é o que separa um do outro.
            if not rotulo or len(rotulo) > 90 or FRASE.search(rotulo):
                continue
            if not re.match(r'^[A-ZÀ-Ú0-9]', rotulo):
                continue

            tipo = ('diaria' if 'Diária' in grupo or 'Diárias' in grupo
                    else 'material' if 'Materiais' in grupo
                    else 'pacote' if 'Pronto-socorro' in grupo
                    else 'taxa')

            if len(valores) == 3:
                for porte, v in zip(PORTES_HOSP, valores):
                    col.add(tipo, grupo, rotulo, dinheiro(v), codigo=codigo, variante=porte,
                            pagina=n)
            else:
                col.add(tipo, grupo, rotulo, dinheiro(valores[0]), codigo=codigo,
                        unidade=unidade, pagina=n)


# ── 2. Anexo V — procedimentos, SADT e honorários (páginas 42 a 145) ─────
#
# Uma linha por código. A descrição às vezes não cabe na mesma linha do
# valor: o PDF quebra a célula e o código fica numa linha com o texto
# acima e abaixo. Por isso a leitura olha os vizinhos quando a célula da
# descrição vem vazia.
#
# Depois do primeiro valor vêm colunas opcionais (fração, porte, valor HM,
# UCO, nº de auxiliares, porte do anestesista, m² e valor do filme). O que
# dá para identificar com segurança vira campo; o resto fica guardado como
# texto, em `extras.colunas`, para não inventar número em tabela de preço.

RE_PORTE = re.compile(r'\b(\d{1,2}[A-C])\b')


def _descricao_vizinha(linhas, idx, desc):
    if limpa(desc):
        return desc
    acima = next((l for l in reversed(linhas[:idx]) if limpa(l) and 'R$' not in l and not RE_CODIGO.match(l)), '')
    abaixo = ''
    if idx + 1 < len(linhas):
        seguinte = linhas[idx + 1]
        if limpa(seguinte) and 'R$' not in seguinte and not RE_CODIGO.match(seguinte):
            abaixo = seguinte
    return limpa(acima + ' ' + abaixo)


def _colunas(resto):
    """Lê o que vem depois do primeiro valor da linha."""
    dados = {}
    porte = RE_PORTE.search(resto)
    if porte:
        dados['porte'] = porte.group(1)

    outros = RE_VALOR.findall(resto)
    if outros:
        dados['valor_hm'] = dinheiro(outros[0])
    if len(outros) > 1:
        dados['valor_filme'] = dinheiro(outros[-1])

    resto = limpa(resto)
    if resto:
        dados['colunas'] = resto[:120]
    return dados


def secao_anexo_v(pgs, col):
    faixas = [(42, 93, 'procedimento', 'Atendimentos e SADT'),
              (95, 145, 'honorario', 'Honorários')]

    for inicio, fim, tipo, grupo in faixas:
        for n in range(inicio, fim + 1):
            linhas = pgs[n - 1].splitlines()
            for idx, linha in enumerate(linhas):
                m = RE_CODIGO.match(linha)
                if not m:
                    continue
                valores = RE_VALOR.search(linha)
                if not valores:
                    continue

                codigo = m.group(1)
                corpo = linha[m.end():]
                desc = corpo[:corpo.index('R$')]
                resto = corpo[corpo.index('R$') + len(valores.group(0)):]

                desc = _descricao_vizinha(linhas, idx, desc)
                if not desc:
                    continue

                col.add(tipo, grupo, desc, dinheiro(valores.group(1)), codigo=codigo,
                        pagina=n, **_colunas(resto))


# ── 3. Pacotes de honorários e OPME (páginas 146 a 189) ──────────────────
#
# Três formas diferentes no mesmo trecho:
#   • linha com código 8000xxxx/8010xxxx → pacote de honorário médico ou de
#     anestesista, com o porte da sala cirúrgica na ponta;
#   • bloco "Procedimento - 7000xxxx" + descrição + valor → pacote de OPME;
#   • linha com código 7000xxxx e valor → item avulso de OPME.

CABECALHOS_146_189 = [
    (r'^B\)\s*Pacotes de Honor[áa]rios Ortopedia', 'Pacotes de honorários — ortopedia e neurocirurgia'),
    (r'^B\)\s*Pacotes de Honor[áa]rios Cirurgias Hep', 'Pacotes de honorários — cirurgias hepáticas'),
    (r'^D\)\s*Tabela de OPME de Ortopedia', 'OPME — ortopedia e neurocirurgia'),
    (r'^E\)\s*Pacotes de Honor[áa]rios de Cirurgia de Cabe', 'Pacotes de honorários — cabeça, pescoço e buco-maxilo'),
    (r'^F\)\s*Tabela de OPME de Cirurgia de Cabe', 'OPME — cabeça, pescoço e buco-maxilo'),
]

RE_PROC_OPME = re.compile(r'^\s*Procedimento\s*-\s*(\d{6,10})')


def secao_pacotes_opme(pgs, col):
    grupo = 'Pacotes de honorários'
    for n in range(146, 190):
        linhas = pgs[n - 1].splitlines()
        for idx, linha in enumerate(linhas):
            texto = linha.strip()

            for regex, nome in CABECALHOS_146_189:
                if re.match(regex, texto, re.I):
                    grupo = nome

            # Bloco de pacote de OPME: o código vem no rótulo e a descrição
            # na linha seguinte, junto com o valor.
            m = RE_PROC_OPME.match(linha)
            if m:
                codigo = m.group(1)
                for adiante in linhas[idx + 1:idx + 5]:
                    valor = RE_VALOR.search(adiante)
                    if not valor:
                        continue
                    desc = limpa(adiante[:adiante.index('R$')])
                    if not desc:
                        desc = next((limpa(l) for l in linhas[idx + 1:idx + 5]
                                     if limpa(l) and 'R$' not in l and not l.strip().startswith('OBS')), '')
                    col.add('opme', grupo, desc, dinheiro(valor.group(1)), codigo=codigo, pagina=n)
                    break
                continue

            mc = RE_CODIGO.match(linha)
            valor = RE_VALOR.search(linha)
            if not mc or not valor:
                continue

            codigo = mc.group(1)
            corpo = linha[mc.end():]
            desc = limpa(corpo[:corpo.index('R$')])
            resto = limpa(corpo[corpo.index('R$') + len(valor.group(0)):])

            if not desc:
                desc = _descricao_vizinha(linhas, idx, '')
            if not desc:
                continue

            tipo = 'opme' if codigo.startswith('7') else 'pacote'
            sala = re.match(r'^(\d{1,2})$', resto)

            col.add(tipo, grupo, desc, dinheiro(valor.group(1)), codigo=codigo,
                    porte_sala=sala.group(1) if sala else None, pagina=n)


# ── 4. Anexo VI — referencial de portes e UCO (páginas 190 e 191) ────────

def secao_portes(pgs, col):
    grupo = 'Referencial de portes e UCO'
    for n in (190, 191):
        for linha in pgs[n - 1].splitlines():
            texto = limpa(linha)
            if re.match(r'^Legenda Porte', texto, re.I):
                grupo = 'Legenda de porte anestésico'

            m = re.match(r'^(\d{1,2}[A-C]|UCO|Filme)\s+R\$\s*([\d.]+,\d{2})(?:\s+R\$\s*([\d.]+,\d{2}))?', texto)
            if m and grupo == 'Referencial de portes e UCO':
                col.add('porte', grupo, m.group(1), dinheiro(m.group(2)),
                        variante='Honorários', pagina=n)
                if m.group(3):
                    col.add('porte', grupo, m.group(1), dinheiro(m.group(3)),
                            variante='SADT', pagina=n)
                continue

            # Legenda: numeração do porte anestésico → código CBHPM e valor
            m = re.match(r'^(\d)\s+(\d{1,2}[A-C])\s+R\$\s*([\d.]+,\d{2})', texto)
            if m:
                col.add('porte', 'Legenda de porte anestésico',
                        'Porte anestésico ' + m.group(1) + ' (CBHPM ' + m.group(2) + ')',
                        dinheiro(m.group(3)), variante=m.group(2), pagina=n)


# ── 5. Anexo VII — materiais médico-hospitalares (páginas 192 a 216) ─────
#
# Tabela larga: código TUSS, descrição, fabricante, unidade, valor e os
# dados de registro. As colunas vêm separadas por dois ou mais espaços, e o
# número do registro Anvisa costuma vir quebrado na linha de cima — ele é
# lido como dado da linha seguinte, não como item.

def secao_materiais(pgs, col):
    grupo = 'Materiais médico-hospitalares (Anexo VII)'
    for n in range(192, 217):
        anvisa_acima = None
        for linha in pgs[n - 1].splitlines():
            solto = limpa(linha)
            if re.fullmatch(r'\d{9,14}', solto):
                anvisa_acima = solto      # o registro Anvisa quebrou de linha
                continue

            m = RE_CODIGO.match(linha)
            valor = RE_VALOR.search(linha)
            if not m or not valor:
                continue

            codigo = m.group(1)
            corpo = linha[m.end():]
            antes = [c for c in re.split(r'\s{2,}', corpo[:corpo.index('R$')].strip()) if c]
            depois = [c for c in re.split(r'\s{2,}', corpo[corpo.index('R$') + len(valor.group(0)):].strip()) if c]

            descricao = antes[0] if antes else ''
            fabricante = antes[1] if len(antes) > 1 else None
            unidade = antes[2] if len(antes) > 2 else None

            col.add('material', grupo, descricao, dinheiro(valor.group(1)), codigo=codigo,
                    fabricante=fabricante, unidade=unidade,
                    simpro=depois[1] if len(depois) > 1 else None,
                    anvisa=(anvisa_acima or '') + (depois[2] if len(depois) > 2 else '') or None,
                    validade=depois[3] if len(depois) > 3 else None,
                    classificacao=depois[4] if len(depois) > 4 else None,
                    pagina=n)
            anvisa_acima = None


# ── 6. Anexo VIII — pacotes (páginas 217 a 264) ──────────────────────────
#
# Aqui o edital escreve em texto corrido: "Pacote X (código)." e, logo
# abaixo, o valor. O que está incluso vem em lista — guardado como
# observação, porque é o que decide se a conta pode ou não ser cobrada à
# parte.

RE_COD_PACOTE = re.compile(r'(\d(?:[\d.]{4,}\d)(?:-\d)?)')
RE_VALOR_SOLTO = re.compile(r'Valor(?:\s+Total)?\s*:?\s*(?:R\$)?\s*([\d.]+,\d{2})', re.I)


def _nome_e_codigo(texto):
    """De "Pacote X (9.09.09.03-7)." tira o nome e o código.

    O edital escreve o código de três jeitos — entre parênteses, depois de
    "cód." e no formato TUSS pontuado —, às vezes quebrando no meio da
    linha. Pegar o ÚLTIMO número que parece código e limpar o resto é o que
    aguenta os três sem uma regex por caso.
    """
    m = None
    for m in RE_COD_PACOTE.finditer(texto):
        pass
    if m is None:
        return None, None

    codigo = m.group(1)
    nome = texto[:m.start()] + ' ' + texto[m.end():]
    nome = RE_VALOR.sub('', nome)                      # o valor às vezes vem na mesma linha
    nome = re.sub(r'[–-]?\s*c[óo]d(igo)?\.?\s*', ' ', nome, flags=re.I)
    nome = limpa(nome).strip(' .–-')

    # O código morava dentro de um parêntese que agora ficou pela metade.
    nome = re.sub(r'\(\s*\)', '', nome).strip(' .–-')
    if nome.count('(') > nome.count(')'):
        nome += ')'

    return limpa(nome), codigo


def secao_pacotes_anexo_viii(pgs, col):
    grupo = 'Pacotes (Anexo VIII)'
    blocos = []
    atual = None

    for n in range(217, 265):
        linhas = [limpa(l) for l in pgs[n - 1].splitlines()]
        for idx, texto in enumerate(linhas):
            if not texto:
                continue
            if re.match(r'^PACOTES\s+[A-ZÀ-Ú]', texto):
                grupo = 'Pacotes — ' + texto.title().replace('Pacotes ', '')
                continue

            if re.match(r'^Pacote\b', texto, re.I):
                # Nome que quebrou no meio do código: junta com a linha de baixo.
                junto = texto
                if junto.count('(') > junto.count(')') and idx + 1 < len(linhas):
                    junto = limpa(junto + ' ' + linhas[idx + 1])
                # "9.80.00.03-" numa linha e "8)." na outra viram um código só.
                junto = re.sub(r'-\s+(\d)', r'-\1', junto)

                nome, codigo = _nome_e_codigo(junto)
                if not nome:
                    continue

                valor = RE_VALOR.search(junto)
                atual = {'nome': nome, 'codigo': codigo, 'grupo': grupo,
                         'valor': dinheiro(valor.group(1)) if valor else None,
                         'inclui': [], 'pagina': n}
                blocos.append(atual)
                continue

            if atual is None:
                continue

            if atual['valor'] is None:
                v = RE_VALOR_SOLTO.search(texto)
                if v:
                    atual['valor'] = dinheiro(v.group(1))
                    continue
                v = re.match(r'^R\$\s*([\d.]+,\d{2})$', texto)
                if v:
                    atual['valor'] = dinheiro(v.group(1))
                    continue
                if texto.lower().startswith('valor'):
                    continue

            if len(' '.join(atual['inclui'])) < 900:
                atual['inclui'].append(texto)

    for b in blocos:
        if b['valor'] is None:
            continue
        col.add('pacote', b['grupo'], b['nome'], b['valor'], codigo=b['codigo'],
                observacao=limpa(' '.join(b['inclui']))[:900], pagina=b['pagina'])


# ── 7. Anexo IX — dietas e suplementos (páginas 265 e 266) ───────────────

def secao_dietas(pgs, col):
    grupo = 'Dietas enterais'
    for n in (265, 266):
        linhas = pgs[n - 1].splitlines()
        for idx, linha in enumerate(linhas):
            texto = limpa(linha)
            if re.match(r'^(DIETAS ENTERAIS|DIETAS PARENTERAIS|SUPLEMENTOS|M[ÓO]DULOS?)\b', texto, re.I):
                grupo = texto.capitalize()
                continue

            valor = RE_VALOR.search(linha)
            if not valor:
                continue

            celulas = [limpa(c) for c in re.split(r'\s{2,}', linha[:linha.index('R$')]) if limpa(c)]
            if not celulas:
                celulas = [limpa(linhas[idx - 1])] if idx else []
            if not celulas:
                continue

            formulacao = celulas[0]
            apresentacao = celulas[1] if len(celulas) > 1 else None

            col.add('dieta', 'Dietas e suplementos (Anexo IX)',
                    formulacao + (' — ' + apresentacao if apresentacao else ''),
                    dinheiro(valor.group(1)), variante=grupo,
                    apresentacao=apresentacao, pagina=n)


# ── Saída ────────────────────────────────────────────────────────────────

CONVENIO = 'CAPEP-SAÚDE'
REFERENCIA = 'Edital de Credenciamento 011/2022 — 29º retificado'
VIGENCIA = '2026-06-01'

CABECALHO = """-- APASBS v2 — Comercial: tabela de valores da {convenio}
-- GERADO por tools/gerar_comercial.py — não edite à mão.
--
--   pdftotext -layout edital.pdf edital.txt
--   python3 tools/gerar_comercial.py edital.txt > sql/com_capep_2026.sql
--
-- Execute DEPOIS de sql/com_comercial.sql:
--   mysql -u usuario -p apasbs < sql/com_capep_2026.sql
--
-- Fonte: {referencia}, vigência {vigencia}.
-- {total} itens lidos das páginas 22 a 31 (diárias, taxas e pacotes de
-- pronto-socorro) e 42 a 266 (Anexos V a IX).
--
-- Rodar de novo apaga e recria SÓ os itens de origem 'edital' desta tabela.
-- Item incluído pela tela (origem 'manual') não é tocado.

SET NAMES utf8mb4;

INSERT INTO com_tabelas (convenio, referencia, vigencia, observacao, ativo)
VALUES ('{convenio}', '{referencia}', '{vigencia}',
        'Carga automática do edital em PDF. Conferir antes de usar para faturamento.', 1)
ON DUPLICATE KEY UPDATE observacao = VALUES(observacao), ativo = 1;

SET @tab := (SELECT id FROM com_tabelas
              WHERE convenio = '{convenio}' AND referencia = '{referencia}'
                AND vigencia = '{vigencia}');

DELETE FROM com_itens WHERE tabela_id = @tab AND origem = 'edital';
"""


def escapa(valor):
    if valor is None:
        return 'NULL'
    if isinstance(valor, (int, float)):
        return repr(valor)
    texto = str(valor).replace('\\', '\\\\').replace("'", "''")
    return "'" + texto + "'"


def json_curto(extras):
    if not extras:
        return None
    partes = []
    for chave, valor in extras.items():
        if isinstance(valor, float):
            partes.append('"%s":%s' % (chave, repr(valor)))
        elif isinstance(valor, int):
            partes.append('"%s":%d' % (chave, valor))
        else:
            texto = str(valor).replace('\\', '\\\\').replace('"', '\\"')
            partes.append('"%s":"%s"' % (chave, texto))
    return '{' + ','.join(partes) + '}'


def main():
    if len(sys.argv) < 2:
        print(__doc__)
        return 1

    pgs = paginas(sys.argv[1])
    col = Coletor()

    secao_diarias(pgs, col)
    secao_anexo_v(pgs, col)
    secao_pacotes_opme(pgs, col)
    secao_portes(pgs, col)
    secao_materiais(pgs, col)
    secao_pacotes_anexo_viii(pgs, col)
    secao_dietas(pgs, col)

    saida = [CABECALHO.format(convenio=CONVENIO, referencia=REFERENCIA,
                              vigencia=VIGENCIA, total=len(col.itens))]

    colunas = '(tabela_id, tipo, grupo, codigo, descricao, variante, valor, extras, origem)'
    lote = []

    for item in col.itens:
        lote.append('(@tab, %s, %s, %s, %s, %s, %s, %s, %s)' % (
            escapa(item['tipo']), escapa(item['grupo']), escapa(item['codigo']),
            escapa(item['descricao']), escapa(item['variante']),
            ('%.2f' % item['valor']) if item['valor'] is not None else 'NULL',
            escapa(json_curto(item['extras'])), escapa('edital')))

        # Lotes de 200: INSERT gigante estoura o max_allowed_packet de
        # hospedagem compartilhada, e o erro aparece no meio da carga.
        if len(lote) == 200:
            saida.append('\nINSERT INTO com_itens ' + colunas + ' VALUES\n'
                         + ',\n'.join(lote) + ';\n')
            lote = []

    if lote:
        saida.append('\nINSERT INTO com_itens ' + colunas + ' VALUES\n'
                     + ',\n'.join(lote) + ';\n')

    saida.append("""
SELECT tipo, COUNT(*) AS itens, MIN(valor) AS menor, MAX(valor) AS maior
  FROM com_itens WHERE tabela_id = @tab GROUP BY tipo ORDER BY itens DESC;
""")

    sys.stdout.write('\n'.join(saida))
    return 0


if __name__ == '__main__':
    sys.exit(main())
