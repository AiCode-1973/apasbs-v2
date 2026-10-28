<?php
/**
 * A ficha em papel, no desenho do modelo impresso.
 *
 * Documento inteiro e autossuficiente: nada da casca do sistema, estilo
 * embutido, nenhuma requisição externa. É a folha que vai assinada para o
 * arquivo de 20 anos, e ela precisa sair igual num computador qualquer do
 * ambulatório, inclusive sem rede.
 *
 * O que está preenchido aparece escrito; o que está vazio vira linha para
 * completar à mão — é assim que o papel funciona quando a paciente está na
 * frente e o sistema não estava aberto.
 *
 * @var array $f
 */
declare(strict_types=1);

$valorImc = imc($f['peso'], $f['altura']);

/** Valor preenchido, ou uma linha para escrever à mão. */
$campo = function (?string $valor, int $espaco = 14): string {
    $valor = trim((string) $valor);
    return $valor !== ''
        ? '<span class="v">' . e($valor) . '</span>'
        : '<span class="linha">' . str_repeat('&nbsp;', $espaco) . '</span>';
};

/** Caixa de marcar: [X] quando escolhida, [ ] quando não. */
$caixa = fn(bool $marcada, string $rotulo): string =>
    '<span class="cx">[' . ($marcada ? '<b>X</b>' : '&nbsp;&nbsp;') . ']</span> ' . e($rotulo);

$data = fn(?string $d): string => $d ? data_br($d) : '';

/** Data por extenso ou o esqueleto __/__/__ para preencher. */
$dataOuLinha = fn(?string $d): string =>
    $d ? '<span class="v">' . e(data_br($d)) . '</span>' : '<span class="linha-data">__/__/__</span>';

/**
 * O registro no conselho, escrito uma vez só.
 *
 * Quem preenche costuma digitar "COREN-SP 123456" inteiro; quem digita só o
 * número conta com o rótulo. Repetir o rótulo sobre o valor completo daria
 * "COREN: COREN-SP 123456" — então o rótulo só entra quando falta.
 */
$registro = function (?string $valor, string $conselho): string {
    $valor = trim((string) $valor);

    if ($valor === '') {
        return $conselho . ': __________';
    }

    return stripos($valor, $conselho) === 0 ? $valor : $conselho . ': ' . $valor;
};

$coren = $registro($f['enfermeiro_coren'], 'COREN');
$crm   = $registro($f['medico_crm'], 'CRM');
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Prontuário de Saúde Ocupacional — <?= e($f['trabalhador']) ?></title>
<style>
    /* Tudo embutido: a folha tem de sair igual mesmo sem rede. */
    @page { size: A4 portrait; margin: 12mm; }

    * { box-sizing: border-box; }

    body {
        margin: 0;
        font-family: -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        font-size: 10.5pt;
        line-height: 1.45;
        color: #1f2937;
        background: #fff;
        padding: 6mm;
    }

    h1 {
        margin: 0 0 2px;
        text-align: center;
        font-size: 17pt;
        font-weight: 600;
        letter-spacing: .3px;
        color: #374151;
    }

    .resp {
        margin: 0 0 3px;
        text-align: center;
        font-size: 10pt;
        font-weight: 700;
        color: #1e3a5f;
    }

    /* Cabeçalho: as duas marcas nas pontas e o título no meio. Altura fixa e
       largura automática — logo trocado por outro de proporção diferente
       continua alinhado. */
    .topo {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        margin-bottom: 12px;
    }

    .topo .centro { flex: 1; }

    .marca {
        height: 56px;
        width: auto;
        flex: none;
        /* Sem isto o Chrome imprime a marca lavada por economia de tinta. */
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    .marca-apas { height: 32px; }

    .aviso {
        margin: 0;
        text-align: center;
        font-size: 8.5pt;
        color: #6b7280;
    }

    .caixa {
        border: 1px solid #1e3a5f;
        border-radius: 6px;
        background: #f8fafc;
        padding: 10px 12px;
        margin-bottom: 10px;
        /* Uma seção não se parte entre duas folhas: procurar a continuação
           do exame físico na página seguinte é como o papel se perde. */
        break-inside: avoid;
        page-break-inside: avoid;
    }

    .caixa h2 {
        margin: 0 0 8px;
        font-size: 10.5pt;
        font-weight: 700;
        color: #374151;
    }

    .caixa h2 .leve { font-weight: 400; }

    .l { margin: 0 0 7px; }
    .l:last-child { margin-bottom: 0; }

    .r { color: #4b5563; }

    /* O que foi preenchido sai sublinhado, para ler como preenchido a mão */
    .v {
        border-bottom: 1px solid #94a3b8;
        padding: 0 3px;
        font-weight: 600;
        color: #111827;
    }

    .linha      { border-bottom: 1px solid #9ca3af; display: inline-block; min-width: 60px; }
    .linha-data { letter-spacing: 1px; color: #6b7280; }
    .cx         { font-family: "Courier New", monospace; }

    .assinaturas { margin-top: 18px; break-inside: avoid; page-break-inside: avoid; }
    .assinaturas .par { display: flex; gap: 28px; margin-bottom: 4px; }
    .assinaturas .par > div { flex: 1; }
    .assinaturas .nome { font-weight: 700; font-size: 10pt; }
    .assinaturas .rub  { font-size: 8.5pt; color: #6b7280; margin-top: 2px; }
    .assinaturas .rag  { border-bottom: 1px solid #6b7280; display: inline-block; min-width: 150px; }

    .rodape-doc {
        margin-top: 14px;
        border-top: 1px solid #e5e7eb;
        padding-top: 5px;
        font-size: 7.5pt;
        color: #9ca3af;
        display: flex;
        justify-content: space-between;
    }

    .barra { text-align: center; margin-bottom: 14px; }

    .barra button {
        font: inherit;
        font-size: 10pt;
        padding: 7px 18px;
        margin: 0 4px;
        border: 1px solid #1e3a5f;
        border-radius: 6px;
        background: #1e3a5f;
        color: #fff;
        cursor: pointer;
    }

    .barra a {
        font-size: 10pt;
        padding: 7px 18px;
        margin: 0 4px;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        color: #374151;
        text-decoration: none;
        display: inline-block;
    }

    @media print {
        body { padding: 0; }
        .barra { display: none; }
    }
</style>
</head>
<body>

<div class="barra">
    <?php /* data-imprimir, e não onclick: a CSP do sistema bloqueia atributo
             de evento. O ouvinte delegado vive no app.js. */ ?>
    <button type="button" data-imprimir>Imprimir</button>
    <a href="<?= url('ocupacional', 'ver', ['id' => (int) $f['id']]) ?>">Voltar à ficha</a>
</div>

<header class="topo">
    <img class="marca" src="<?= BASE_URL ?>/image/logo_hospital.png" alt="Hospital Santo Expedito">

    <div class="centro">
        <h1>PRONTUÁRIO DE SAÚDE OCUPACIONAL</h1>
        <p class="resp">
            Responsável Técnico: <?= e($f['enfermeiro_nome'] ?: '________________________') ?>
            | <?= e($coren) ?>
        </p>
        <p class="aviso">CONFIDENCIAL — NR-7 / PCMSO / LGPD — Guardar por 20 anos</p>
    </div>

    <img class="marca marca-apas" src="<?= BASE_URL ?>/image/logo_apas.png" alt="APAS Baixada Santista">
</header>

<!-- 1 -->
<div class="caixa">
    <h2>1. IDENTIFICAÇÃO</h2>
    <p class="l">
        <span class="r">Nome:</span> <?= $campo($f['trabalhador'], 30) ?>
        &nbsp; <span class="r">CPF:</span> <?= $campo($f['cpf'] ? formatar_cpf((string) $f['cpf']) : '', 12) ?>
        &nbsp; <span class="r">Nasc.:</span> <?= $dataOuLinha($f['nascimento']) ?>
    </p>
    <p class="l">
        <span class="r">Função:</span> <?= $campo($f['funcao'], 12) ?>
        &nbsp; <span class="r">Setor:</span> <?= $campo($f['setor'], 12) ?>
        &nbsp; <span class="r">Admissão:</span> <?= $dataOuLinha($f['admissao']) ?>
        &nbsp; <span class="r">Empresa:</span> <?= $campo($f['empresa'], 18) ?>
    </p>
</div>

<!-- 2 -->
<div class="caixa">
    <h2>2. TIPO DE EXAME</h2>
    <p class="l">
        <?php foreach (OcupacionalModel::TIPOS as $chave => $rotulo): ?>
            <?= $caixa($f['tipo'] === $chave, $rotulo) ?>&nbsp;&nbsp;
        <?php endforeach; ?>
        <br>
        <span class="r">Data do exame:</span> <?= $dataOuLinha($f['data_exame']) ?>
    </p>
</div>

<!-- 3 -->
<div class="caixa">
    <h2>3. ANAMNESE OCUPACIONAL / RISCOS (PGR)</h2>
    <p class="l"><span class="r">Hist. Profissional:</span> <?= $campo($f['hist_profissional'], 60) ?></p>
    <p class="l">
        <span class="r">Riscos:</span>
        <?php foreach (OcupacionalModel::RISCOS as $campoRisco => $rotulo): ?>
            <?= $caixa(!empty($f[$campoRisco]), $rotulo) ?>&nbsp;
        <?php endforeach; ?>
        &nbsp; <span class="r">EPI:</span>
        <?= $caixa($f['epi'] !== null && (int) $f['epi'] === 1, 'Sim') ?>&nbsp;
        <?= $caixa($f['epi'] !== null && (int) $f['epi'] === 0, 'Não') ?>
    </p>
    <p class="l">
        <span class="r">Antecedentes / Medicações / Alergias / Hábitos:</span>
        <?= $campo($f['antecedentes'], 40) ?>
    </p>
</div>

<!-- 4 -->
<div class="caixa">
    <h2>4. EXAME FÍSICO <span class="leve">— Triagem de Enfermagem do Trabalho</span></h2>
    <p class="l">
        <span class="r">PA:</span>
        <?= $campo($f['pa_sistolica'] ? (string) $f['pa_sistolica'] : '', 3) ?> x
        <?= $campo($f['pa_diastolica'] ? (string) $f['pa_diastolica'] : '', 3) ?>
        &nbsp; <span class="r">FC:</span> <?= $campo($f['fc'] ? (string) $f['fc'] : '', 3) ?>
        &nbsp; <span class="r">Peso:</span>
        <?= $campo($f['peso'] ? number_format((float) $f['peso'], 1, ',', '') : '', 4) ?> kg
        &nbsp; <span class="r">Altura:</span>
        <?= $campo($f['altura'] ? number_format((float) $f['altura'], 2, ',', '') : '', 4) ?> m
        &nbsp; <span class="r">IMC:</span>
        <?= $campo($valorImc !== null ? number_format($valorImc, 1, ',', '') : '', 4) ?>
        &nbsp; <span class="r">Acuidade OD:</span> <?= $campo($f['acuidade_od'], 4) ?>
        &nbsp; <span class="r">OE:</span> <?= $campo($f['acuidade_oe'], 4) ?>
    </p>
    <p class="l">
        <span class="r">Queixas atuais / Exame direcionado:</span>
        <?= $campo($f['queixas'], 40) ?>
    </p>
</div>

<!-- 5 -->
<div class="caixa">
    <h2>5. EXAMES COMPLEMENTARES ANEXADOS</h2>
    <p class="l">
        <span class="r">Audiometria</span> <?= $dataOuLinha($f['audiometria_em']) ?>
        &nbsp;|&nbsp; <span class="r">Espirometria</span> <?= $dataOuLinha($f['espirometria_em']) ?>
        &nbsp;|&nbsp; <span class="r">Laboratorial</span> <?= $dataOuLinha($f['laboratorial_em']) ?>
        &nbsp;|&nbsp; <span class="r">Outros:</span> <?= $campo($f['outros_exames'], 14) ?>
    </p>
    <p class="l">
        <span class="r">Observações da Enfermagem:</span> <?= $campo($f['obs_enfermagem'], 40) ?>
    </p>
</div>

<!-- 6 -->
<div class="caixa">
    <h2>6. ENCAMINHAMENTO MÉDICO</h2>
    <p class="l">
        <span class="r">Encaminhado ao Médico do Trabalho em</span>
        <?= $dataOuLinha($f['encaminhado_em']) ?>
        <span class="r">para definição de APTO/INAPTO</span>
    </p>
    <p class="l">
        <span class="r">Conclusão Médica:</span>
        <?= $caixa($f['conclusao'] === 'apto', 'APTO') ?>&nbsp;
        <?= $caixa($f['conclusao'] === 'apto_restricao', 'APTO COM RESTRIÇÃO') ?>&nbsp;
        <?= $caixa($f['conclusao'] === 'inapto', 'INAPTO') ?>
        &nbsp; <span class="r">Validade:</span> <?= $dataOuLinha($f['validade']) ?>
    </p>
    <?php if ($f['conclusao'] === 'apto_restricao' && $f['restricoes']): ?>
        <p class="l"><span class="r">Restrição:</span> <?= $campo($f['restricoes'], 40) ?></p>
    <?php endif; ?>
</div>

<!-- Assinaturas -->
<div class="assinaturas">
    <?php /* As linhas ficam em branco de propósito: é nelas que se assina. O
             nome e o número do conselho vão embaixo, em letra menor, como no
             modelo em papel. */ ?>
    <div class="par">
        <div>
            <span class="nome">Enfermeiro do Trabalho</span>
            <span class="rag">&nbsp;</span>
        </div>
        <div>
            <span class="nome">Médico do Trabalho</span>
            <span class="rag">&nbsp;</span>
        </div>
    </div>

    <div class="par">
        <div class="rub">
            <?= e($f['enfermeiro_nome'] ?: '') ?>
            <?= $f['enfermeiro_nome'] ? ' · ' : '' ?><?= e($coren) ?>
            &nbsp; Assinatura/Carimbo
        </div>
        <div class="rub">
            <?= e($f['medico_nome'] ?: '') ?>
            <?= $f['medico_nome'] ? ' · ' : '' ?><?= e($crm) ?>
            &nbsp; Assinatura/Carimbo
        </div>
    </div>

    <p class="rub" style="margin-top:16px">
        Ciência do trabalhador: Data <?= $dataOuLinha($f['ciencia_em']) ?>
        &nbsp; Assinatura: <span class="rag" style="min-width:230px">&nbsp;</span>
    </p>
</div>

<div class="rodape-doc">
    <span>
        APASBS — Ficha nº <?= (int) $f['id'] ?> ·
        lançada por <?= e($f['usuario_nome'] ?: '—') ?>
    </span>
    <span>Impresso por <?= e(Auth::nome()) ?> em <?= e(date('d/m/Y H:i')) ?></span>
</div>

<?php /* Só pelo botão de imprimir. O estilo é embutido de propósito — a folha
         precisa sair certa mesmo se o CSS do sistema não carregar. */ ?>
<script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>
