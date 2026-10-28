<?php
/**
 * O comprovante de entrega em papel, no desenho do modelo.
 *
 * Três partes na mesma folha, separadas por linha de corte: a entrega (fica
 * no setor), o recebimento e a 2ª via do colaborador. É por isso que a folha
 * não pode ter duas páginas — quem corta a 2ª via precisa cortar uma só.
 *
 * Documento inteiro e autossuficiente: nada da casca do sistema, estilo
 * embutido, nenhuma requisição externa. O que está preenchido sai escrito; o
 * que está vazio vira linha para completar à mão.
 *
 * @var array $a
 */
declare(strict_types=1);

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

/** Data por extenso ou o esqueleto __/__/____ para preencher. */
$dataOuLinha = fn(?string $d): string =>
    $d ? '<span class="v">' . e(data_br($d)) . '</span>' : '<span class="linha-data">__/__/____</span>';

/** Horário ou o esqueleto __:__ . */
$horaOuLinha = fn(?string $h): string =>
    $h ? '<span class="v">' . e(hora_curta($h)) . '</span>' : '<span class="linha-data">__:__</span>';

$dias = $a['dias'] !== null && $a['dias'] !== '' ? (string) (int) $a['dias'] : '';

$cnpj = OcupacionalModel::HOSPITAL_CNPJ;
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Entrega de atestado médico — <?= e($a['protocolo']) ?></title>
<style>
    /* Tudo embutido: a folha tem de sair igual mesmo sem rede. */
    @page { size: A4 portrait; margin: 10mm; }

    * { box-sizing: border-box; }

    body {
        margin: 0;
        font-family: -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        font-size: 10pt;
        line-height: 1.4;
        color: #1f2937;
        background: #fff;
        padding: 5mm;
    }

    h1 {
        margin: 0 0 2px;
        text-align: center;
        font-size: 15pt;
        font-weight: 600;
        letter-spacing: .3px;
        color: #374151;
    }

    .setor {
        margin: 0 0 2px;
        text-align: center;
        font-size: 10pt;
        font-weight: 700;
        color: #1e3a5f;
    }

    .casa {
        margin: 0;
        text-align: center;
        font-size: 9pt;
        color: #4b5563;
    }

    /* Cabeçalho: as duas marcas nas pontas e o título no meio. As imagens
       ficam com altura fixa e largura automática — logo trocado por um de
       proporção diferente continua alinhado. */
    .topo {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        margin-bottom: 8px;
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

    .marca-apas { height: 30px; }

    /* Bloco próprio, alinhado à direita: como float ele subia para dentro da
       primeira caixa depois que o cabeçalho virou flex. */
    .protocolo {
        width: fit-content;
        margin-left: auto;
        border: 1px solid #1e3a5f;
        border-radius: 6px;
        padding: 4px 10px;
        margin-bottom: 8px;
        font-size: 10pt;
        font-weight: 700;
        color: #1e3a5f;
    }

    .caixa {
        border: 1px solid #1e3a5f;
        border-radius: 6px;
        background: #f8fafc;
        padding: 8px 10px;
        margin-bottom: 8px;
        break-inside: avoid;
        page-break-inside: avoid;
    }

    .caixa h2 {
        margin: 0 0 6px;
        font-size: 10pt;
        font-weight: 700;
        color: #374151;
    }

    .l { margin: 0 0 6px; }
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

    .declaracao {
        margin: 0 0 6px;
        font-size: 9pt;
        color: #374151;
        text-align: justify;
    }

    .assinatura { margin-top: 14px; }
    .assinatura .rag { border-bottom: 1px solid #6b7280; display: inline-block; min-width: 220px; }
    .assinatura .rub { font-size: 8.5pt; color: #6b7280; margin-top: 2px; }

    /* A linha de corte: é por ela que a 2ª via se separa do resto. */
    .corte {
        margin: 10px 0;
        border-top: 1px dashed #9ca3af;
        text-align: center;
        font-size: 7.5pt;
        color: #9ca3af;
    }
    .corte span { position: relative; top: -6px; background: #fff; padding: 0 6px; }

    .obs {
        font-size: 8pt;
        color: #6b7280;
        margin: 6px 0 0;
    }

    .rodape-doc {
        margin-top: 10px;
        border-top: 1px solid #e5e7eb;
        padding-top: 4px;
        font-size: 7.5pt;
        color: #9ca3af;
        display: flex;
        justify-content: space-between;
    }

    .barra { text-align: center; margin-bottom: 12px; }

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
    <a href="<?= url('ocupacional', 'ver_atestado', ['id' => (int) $a['id']]) ?>">Voltar</a>
</div>

<header class="topo">
    <img class="marca" src="<?= BASE_URL ?>/image/logo_hospital.png" alt="Hospital Santo Expedito">

    <div class="centro">
        <h1>ENTREGA DE ATESTADO MÉDICO</h1>
        <p class="setor">SETOR DE SAÚDE OCUPACIONAL</p>
        <p class="casa">
            <?= e(OcupacionalModel::HOSPITAL) ?>
            &nbsp;|&nbsp; CNPJ: <?= $campo($cnpj, 16) ?>
        </p>
    </div>

    <img class="marca marca-apas" src="<?= BASE_URL ?>/image/logo_apas.png" alt="APAS Baixada Santista">
</header>

<div class="protocolo">PROTOCOLO Nº <?= e($a['protocolo']) ?></div>

<!-- Colaborador -->
<div class="caixa">
    <h2>DADOS DO COLABORADOR</h2>
    <p class="l">
        <span class="r">Nome:</span> <?= $campo($a['trabalhador'], 30) ?>
        &nbsp; <span class="r">Matrícula:</span> <?= $campo($a['matricula'], 8) ?>
    </p>
    <p class="l">
        <span class="r">Setor:</span> <?= $campo($a['setor'], 14) ?>
        &nbsp; <span class="r">Cargo:</span> <?= $campo($a['funcao'], 14) ?>
        &nbsp; <span class="r">Empresa:</span> <?= $campo($a['empresa'], 16) ?>
    </p>
</div>

<!-- Atestado -->
<div class="caixa">
    <h2>DADOS DO ATESTADO</h2>
    <p class="l">
        <span class="r">Data de emissão:</span> <?= $dataOuLinha($a['emissao']) ?>
        &nbsp; <span class="r">Início do afastamento:</span> <?= $dataOuLinha($a['inicio']) ?>
        &nbsp; <span class="r">Quantidade de dias:</span> <?= $campo($dias, 4) ?>
        &nbsp; <span class="r">Retorno previsto:</span> <?= $dataOuLinha($a['retorno']) ?>
    </p>
    <p class="l">
        <span class="r">Médico/Profissional emitente:</span> <?= $campo($a['medico_nome'], 24) ?>
        &nbsp; <span class="r">CRM/Registro profissional:</span> <?= $campo($a['medico_registro'], 14) ?>
    </p>
</div>

<!-- Entrega -->
<div class="caixa">
    <h2>ENTREGA DO ATESTADO</h2>
    <p class="l">
        <span class="r">Data da entrega:</span> <?= $dataOuLinha($a['entrega_data']) ?>
        &nbsp; <span class="r">Horário:</span> <?= $horaOuLinha($a['entrega_hora']) ?>
    </p>
    <p class="l">
        <span class="r">Forma de entrega:</span>
        <?= $caixa($a['forma'] === 'presencial', 'Presencial') ?>&nbsp;&nbsp;
        <?= $caixa($a['forma'] === 'sistema', 'Sistema institucional') ?>&nbsp;&nbsp;
        <?= $caixa($a['forma'] === 'email', 'E-mail institucional') ?>&nbsp;&nbsp;
        <?= $caixa($a['forma'] === 'outro', 'Outro:') ?> <?= $campo($a['forma'] === 'outro' ? $a['forma_outro'] : '', 14) ?>
    </p>
    <p class="declaracao">
        Declaro que realizei a entrega do atestado médico ao Setor de Saúde Ocupacional,
        ficando registrado o recebimento do documento para as providências administrativas
        e ocupacionais cabíveis.
    </p>
    <div class="assinatura">
        <span class="r">Assinatura do colaborador:</span> <span class="rag">&nbsp;</span>
    </div>
</div>

<!-- Recebimento -->
<div class="caixa">
    <h2>RECEBIMENTO — SAÚDE OCUPACIONAL</h2>
    <p class="l">
        <span class="r">Recebido por:</span> <?= $campo($a['recebido_por'], 26) ?>
    </p>
    <p class="l">
        <span class="r">Data:</span> <?= $dataOuLinha($a['recebido_data']) ?>
        &nbsp; <span class="r">Horário:</span> <?= $horaOuLinha($a['recebido_hora']) ?>
    </p>
    <div class="assinatura">
        <span class="r">Assinatura:</span> <span class="rag">&nbsp;</span>
    </div>
    <p class="obs">
        Observação: o presente documento comprova exclusivamente a entrega e o recebimento
        do atestado, não representando, isoladamente, sua validação ou aprovação.
    </p>
</div>

<div class="corte"><span>corte aqui — 2ª via do colaborador</span></div>

<!-- 2ª via -->
<div class="caixa">
    <h2>COMPROVANTE DO COLABORADOR — 2ª VIA</h2>
    <p class="l">
        <span class="r">PROTOCOLO Nº</span> <span class="v"><?= e($a['protocolo']) ?></span>
        &nbsp; <span class="r">Colaborador:</span> <?= $campo($a['trabalhador'], 26) ?>
    </p>
    <p class="l">
        <span class="r">Atestado entregue em</span> <?= $dataOuLinha($a['entrega_data']) ?>
        <span class="r">às</span> <?= $horaOuLinha($a['entrega_hora']) ?>
        &nbsp; <span class="r">Quantidade de dias:</span> <?= $campo($dias, 4) ?>
    </p>
    <p class="declaracao">
        Declaro que o atestado médico foi entregue ao Setor de Saúde Ocupacional nesta data.
    </p>
    <p class="l">
        <span class="r">Responsável pelo recebimento:</span> <?= $campo($a['recebido_por'], 24) ?>
    </p>
    <div class="assinatura">
        <span class="r">Assinatura do responsável:</span> <span class="rag">&nbsp;</span>
        &nbsp;&nbsp; <span class="r">Assinatura do colaborador:</span> <span class="rag">&nbsp;</span>
    </div>
    <p class="obs">
        Data: <?= $dataOuLinha($a['entrega_data']) ?> &nbsp;|&nbsp;
        Comprova a entrega e o recebimento do atestado, não a validação do afastamento.
    </p>
</div>

<div class="rodape-doc">
    <span>
        <?= e(OcupacionalModel::HOSPITAL) ?> — Protocolo <?= e($a['protocolo']) ?> ·
        registrado por <?= e($a['usuario_nome'] ?: '—') ?>
    </span>
    <span>Impresso por <?= e(Auth::nome()) ?> em <?= e(date('d/m/Y H:i')) ?></span>
</div>

<?php /* Só pelo botão de imprimir. O estilo é embutido de propósito — a folha
         precisa sair certa mesmo se o CSS do sistema não carregar. */ ?>
<script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>
