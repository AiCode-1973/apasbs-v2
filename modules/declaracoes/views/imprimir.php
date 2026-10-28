<?php
/**
 * A declaração de saúde em papel, no desenho do formulário.
 *
 * Documento inteiro e autossuficiente: nada da casca do sistema, estilo
 * embutido, nenhuma requisição externa. É o que vai assinado e arquivado.
 *
 * Aqui a grade do papel faz sentido — 22 itens nas linhas, pessoas nas
 * colunas —, ao contrário da tela de preenchimento, onde ela obrigaria a
 * acertar a coluna a cada resposta.
 *
 * @var array $d, $pessoas, $respostas
 */
declare(strict_types=1);

$itens = DeclaracaoModel::ITENS;

$titular = null;
$deps    = [];
foreach ($pessoas as $p) {
    if ($p['tipo'] === 'titular') {
        $titular = $p;
    } else {
        $deps[] = $p;
    }
}

// As colunas da grade: T para o titular, 1..N para os dependentes. O papel
// original tem seis colunas; aqui entram todos, e a folha acompanha.
$colunas = [];
if ($titular) {
    $colunas[] = ['rotulo' => 'T', 'pessoa' => $titular];
}
foreach ($deps as $i => $p) {
    $colunas[] = ['rotulo' => (string) ($i + 1), 'pessoa' => $p];
}

/** A resposta de uma pessoa num item, como sai no papel. */
$celula = function (array $pessoa, int $n) use ($respostas, $itens): string {
    $r = $respostas[(int) $pessoa['id']][$n] ?? null;

    if ($itens[$n]['tipo'] === 'valor') {
        return e((string) ($r['valor'] ?? ''));
    }

    return match ($r['resposta'] ?? null) {
        'S' => '<b>S</b>',
        'N' => 'N',
        default => '',
    };
};

// A tabela de descrições do fim do formulário: item, beneficiário, o quê.
$descricoes = [];
foreach ($colunas as $col) {
    $p = $col['pessoa'];
    foreach ($itens as $n => $def) {
        $r = $respostas[(int) $p['id']][$n] ?? null;
        if ($def['tipo'] === 'sn' && ($r['resposta'] ?? null) === 'S') {
            $descricoes[] = [
                'item'  => $n,
                'quem'  => $col['rotulo'] . ' — ' . $p['nome'],
                'o_que' => (string) ($r['descricao'] ?? ''),
            ];
        }
    }
}

$linha = fn(?string $v, int $esp = 20): string => trim((string) $v) !== ''
    ? '<span class="v">' . e((string) $v) . '</span>'
    : '<span class="lin">' . str_repeat('&nbsp;', $esp) . '</span>';

$dataOuLinha = fn(?string $x): string => $x
    ? '<span class="v">' . e(data_br($x)) . '</span>'
    : '<span class="lin-data">__/__/____</span>';
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Declaração de Saúde — <?= e($titular['nome'] ?? 'contrato ' . (int) $d['contrato_id']) ?></title>
<style>
    @page { size: A4 portrait; margin: 11mm; }
    * { box-sizing: border-box; }

    body {
        margin: 0; padding: 5mm;
        font-family: -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        font-size: 9pt; line-height: 1.4; color: #1f2937; background: #fff;
    }

    h1 { margin: 0 0 3px; text-align: center; font-size: 15pt; font-weight: 600; color: #374151; }
    .sub { margin: 0 0 10px; text-align: center; font-size: 8pt; color: #6b7280; }

    .caixa {
        border: 1px solid #1e3a5f; border-radius: 5px; background: #f8fafc;
        padding: 8px 10px; margin-bottom: 8px;
        break-inside: avoid; page-break-inside: avoid;
    }
    .caixa h2 { margin: 0 0 6px; font-size: 9.5pt; font-weight: 700; color: #374151; }

    .l { margin: 0 0 5px; }
    .l:last-child { margin-bottom: 0; }
    .r { color: #4b5563; }

    .v { border-bottom: 1px solid #94a3b8; padding: 0 3px; font-weight: 600; color: #111827; }
    .lin { border-bottom: 1px solid #9ca3af; display: inline-block; min-width: 50px; }
    .lin-data { letter-spacing: 1px; color: #6b7280; }
    .cx { font-family: "Courier New", monospace; }

    /* As instruções gerais: texto legal, corpo menor, sem caixa colorida */
    .instrucoes { font-size: 7.5pt; line-height: 1.35; color: #4b5563; margin-bottom: 8px; }
    .instrucoes ol { margin: 4px 0 0; padding-left: 16px; }
    .instrucoes li { margin-bottom: 3px; text-align: justify; }

    table { width: 100%; border-collapse: collapse; }
    th, td { border: 1px solid #cbd5e1; padding: 3px 4px; vertical-align: top; }
    thead th { background: #eef2f7; font-size: 8pt; font-weight: 700; text-align: center; }
    .q { font-size: 7.5pt; line-height: 1.3; text-align: left; }
    .q b { font-size: 8pt; }
    .resp { width: 26px; text-align: center; font-family: "Courier New", monospace; font-size: 9pt; }
    .resp b { color: #b45309; }
    .valor { width: 62px; text-align: center; font-size: 7.5pt; }

    .grade { break-inside: auto; }
    .grade tr { break-inside: avoid; page-break-inside: avoid; }

    .assin { margin-top: 14px; break-inside: avoid; page-break-inside: avoid; }
    .assin .par { display: flex; gap: 26px; margin-bottom: 4px; }
    .assin .par > div { flex: 1; }
    .assin .rag { border-bottom: 1px solid #6b7280; display: inline-block; min-width: 140px; }
    .assin .rub { font-size: 8pt; color: #6b7280; margin-top: 2px; }

    .declaro { font-size: 7.5pt; line-height: 1.35; text-align: justify; color: #374151; margin: 10px 0 6px; }

    .rodape {
        margin-top: 12px; border-top: 1px solid #e5e7eb; padding-top: 4px;
        font-size: 7pt; color: #9ca3af; display: flex; justify-content: space-between;
    }

    .barra { text-align: center; margin-bottom: 12px; }
    .barra button, .barra a {
        font: inherit; font-size: 9.5pt; padding: 6px 16px; margin: 0 4px;
        border: 1px solid #1e3a5f; border-radius: 5px;
        background: #1e3a5f; color: #fff; cursor: pointer; display: inline-block;
    }
    .barra a { background: #fff; color: #374151; border-color: #cbd5e1; text-decoration: none; }

    @media print { body { padding: 0; } .barra { display: none; } }
</style>
</head>
<body>

<div class="barra">
    <?php /* data-imprimir, e não onclick: a CSP bloqueia atributo de evento. */ ?>
    <button type="button" data-imprimir>Imprimir</button>
    <a href="<?= url('declaracoes', 'ver', ['id' => (int) $d['id']]) ?>">Voltar à declaração</a>
</div>

<h1>DECLARAÇÃO DE SAÚDE</h1>
<p class="sub">APAS Baixada Santista · CONFIDENCIAL — documento integrante do contrato</p>

<!-- Opção de orientação -->
<div class="caixa">
    <h2>OPÇÃO DE ORIENTAÇÃO PARA O PREENCHIMENTO</h2>
    <p class="l" style="font-size:8pt">
        Quanto à orientação médica para o preenchimento desta Declaração de Saúde, declaro que optei:
    </p>
    <p class="l" style="font-size:8pt">
        <span class="cx">[<?= $d['orientacao'] === 'apas' ? '<b>X</b>' : '&nbsp;&nbsp;' ?>]</span>
        Ser orientado(a) por médico(a) designado(a) pela APAS Baixada Santista<br>
        <span class="cx">[<?= $d['orientacao'] === 'proprio' ? '<b>X</b>' : '&nbsp;&nbsp;' ?>]</span>
        Ser orientado(a) por médico(a) da minha escolha<br>
        <span class="cx">[<?= $d['orientacao'] === 'dispensa' ? '<b>X</b>' : '&nbsp;&nbsp;' ?>]</span>
        Dispenso a presença do médico orientador, por não ter tido dúvida quanto às perguntas
    </p>
    <?php if ($d['orientacao'] !== 'dispensa'): ?>
        <p class="l" style="font-size:8pt">
            <span class="r">Dr.(a.):</span> <?= $linha($d['medico_nome'], 30) ?>
            &nbsp; <span class="r">CRM:</span> <?= $linha($d['medico_crm'], 12) ?>
        </p>
    <?php endif; ?>
</div>

<!-- Instruções gerais -->
<div class="instrucoes">
    <strong style="font-size:8.5pt;color:#374151">INSTRUÇÕES GERAIS</strong>
    <ol>
        <li>A declaração de saúde registra a existência de doenças e lesões preexistentes —
        aquelas de que o(a) Sr(a) tenha conhecimento ou diagnóstico confirmado no momento
        da assinatura do contrato, em relação a si próprio ou ao dependente que representa.</li>

        <li>Havendo declaração de doença ou lesão preexistente, será oferecida a
        <strong>Cobertura Parcial Temporária (CPT)</strong>, que admite a suspensão da
        cobertura de Procedimentos de Alta Complexidade, leitos de alta tecnologia e
        procedimentos cirúrgicos relacionados exclusivamente às doenças ou lesões
        declaradas, por período ininterrupto de até <strong>24 meses</strong> a partir da
        contratação ou adesão.</li>

        <li>Agravo é qualquer acréscimo no valor da mensalidade para que o beneficiário
        tenha direito integral à cobertura da doença declarada após as carências
        contratuais. Esta operadora se reserva o direito de não oferecer o Agravo como
        opção à CPT, conforme faculta o artigo 6º, §1º da Resolução Normativa nº 162/07.</li>

        <li>A omissão de informação sobre doença ou lesão preexistente de que o(a) Sr(a)
        saiba ser portador(a), desde que comprovada junto à ANS, pode acarretar a suspensão
        ou o cancelamento do contrato. Nesse caso, o(a) Sr(a) será responsável pelo pagamento
        das despesas do tratamento da doença omitida a partir da data do comunicado. Nenhuma
        cobertura poderá ser negada até que a operadora comprove a omissão perante a ANS.</li>

        <li>Todas as páginas devem ser rubricadas, e o preenchimento deve ser feito pelo
        próprio beneficiário — salvo menor de idade ou incapaz devidamente comprovado,
        quando assina o representante legal. Atenção: nem sempre o representante legal é o
        titular do plano, e é a assinatura do representante que se exige.</li>
    </ol>
</div>

<!-- Identificação -->
<div class="caixa">
    <p class="l">
        <span class="r">Tipo de Contrato:</span>
        <?= $linha(Elegibilidade::TIPOS_CONTRATO[$d['tipo_contrato']] ?? '', 22) ?>
        &nbsp; <span class="r">Nº da Proposta:</span> <?= $linha($d['numero_proposta'], 16) ?>
    </p>
    <p class="l">
        <span class="r">Titular:</span> <?= $linha($titular['nome'] ?? '', 40) ?>
    </p>
    <?php if ($deps): ?>
        <p class="l"><span class="r">Beneficiários (dependentes / agregados):</span></p>
        <p class="l" style="font-size:8pt">
            <?php foreach ($deps as $i => $p): ?>
                <span class="cx"><?= $i + 1 ?>.</span> <span class="v"><?= e($p['nome']) ?></span>
                <?= $i < count($deps) - 1 ? ' &nbsp; ' : '' ?>
            <?php endforeach; ?>
        </p>
    <?php endif; ?>
</div>

<!-- A grade do questionário -->
<table class="grade">
    <thead>
        <tr>
            <th class="q" style="width:auto">
                Questionário respondido pelo proponente contratante, por si e seus dependentes
                e/ou agregados — <strong>S</strong> para sim, <strong>N</strong> para não
            </th>
            <?php foreach ($colunas as $col): ?>
                <th style="width:26px"><?= e($col['rotulo']) ?></th>
            <?php endforeach; ?>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($itens as $n => $def): ?>
            <tr>
                <td class="q"><b><?= $n ?>.</b> <?= e($def['texto']) ?></td>
                <?php foreach ($colunas as $col): ?>
                    <td class="<?= $def['tipo'] === 'valor' ? 'valor' : 'resp' ?>">
                        <?= $celula($col['pessoa'], $n) ?>
                    </td>
                <?php endforeach; ?>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<!-- O que foi declarado -->
<div style="margin-top:8px">
    <table>
        <thead>
            <tr>
                <th style="width:40px">ITEM</th>
                <th style="width:34%">BENEFICIÁRIO</th>
                <th>DESCRIÇÃO</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($descricoes): ?>
                <?php foreach ($descricoes as $dsc): ?>
                    <tr>
                        <td class="resp"><?= (int) $dsc['item'] ?></td>
                        <td style="font-size:8pt"><?= e($dsc['quem']) ?></td>
                        <td style="font-size:8pt"><?= e($dsc['o_que']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <?php /* Nada declarado: linhas em branco, para o caso de a folha ser
                         preenchida à mão ou de surgir algo na conferência. */ ?>
                <?php for ($i = 0; $i < 4; $i++): ?>
                    <tr><td class="resp">&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
                <?php endfor; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Declarações finais -->
<p class="declaro">
    Declaro, para todos os fins, que prestei informações completas e verídicas sobre meu
    estado de saúde e o de meus dependentes e agregados, após ter lido e compreendido todos
    os quesitos, sem dúvidas quanto às questões formuladas, sendo que durante o preenchimento
    a APAS Baixada Santista disponibilizou médico orientador limitado a essa função.
    Declaro estar ciente de que a omissão de informações sobre doenças ou lesões preexistentes
    de que saiba ser portador(a), comprovada junto à ANS, pode acarretar a suspensão ou o
    cancelamento do contrato, respondendo eu pelas despesas do tratamento omitido a partir da
    notificação. Concordo que estas informações passem a integrar o contrato, ficando a
    operadora autorizada a solicitar esclarecimentos e relatórios a médicos, hospitais,
    clínicas ou qualquer instituição, a qualquer tempo, em amparo e defesa de seus direitos,
    sem que isso implique ofensa ao sigilo profissional. Por ser expressão da verdade, firmo
    a presente em duas vias de igual teor e forma.
</p>

<div class="assin">
    <p class="l" style="font-size:8pt">
        <span class="r">DATA:</span> <?= $dataOuLinha($d['data_assinatura'] ?: $d['data_preenchimento']) ?>
    </p>

    <div class="par" style="margin-top:14px">
        <div>
            <span class="rag">&nbsp;</span>
            <div class="rub">Assinatura do Contratante ou Responsável Legal</div>
        </div>
        <div>
            <div class="rub" style="margin-top:0">
                NOME: <?= $linha($d['assinante_nome'], 26) ?><br>
                CPF: <?= $linha($d['assinante_cpf'] ? formatar_cpf((string) $d['assinante_cpf']) : '', 16) ?>
            </div>
        </div>
    </div>

    <div class="par" style="margin-top:16px">
        <div>
            <span class="rag">&nbsp;</span>
            <div class="rub">Testemunha 1</div>
        </div>
        <div>
            <span class="rag">&nbsp;</span>
            <div class="rub">Testemunha 2</div>
        </div>
    </div>
</div>

<div class="rodape">
    <span>
        APASBS — Declaração nº <?= (int) $d['id'] ?> ·
        preenchida em <?= e(data_br($d['data_preenchimento'])) ?>
        <?php if ($d['data_contratacao']): ?>
            · CPT até <?= e(data_br((string) (new DateTimeImmutable($d['data_contratacao']))
                    ->modify('+' . DeclaracaoModel::MESES_CPT . ' months')->format('Y-m-d'))) ?>
        <?php endif; ?>
    </span>
    <span>Impresso por <?= e(Auth::nome()) ?> em <?= e(date('d/m/Y H:i')) ?></span>
</div>

<script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>
