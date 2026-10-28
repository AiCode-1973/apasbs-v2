<?php
/**
 * Folha da agenda para imprimir e pendurar na central. Sem sidebar, sem
 * topbar e sem cor de fundo.
 *
 * Dois formatos, mesma competência:
 *   calendario → o mês inteiro numa página, em paisagem
 *   lista      → agrupado por médico e depois dia a dia, em retrato
 *
 * Em ambos a cor do médico é só um ponto na margem: o nome vai sempre
 * escrito, porque a maioria das impressoras da casa é preto e branco.
 *
 * @var int    $ano
 * @var int    $mes
 * @var array  $blocos
 * @var array  $porDia
 * @var array  $bloqueios
 * @var array  $porMedico
 * @var array  $porExame
 * @var int    $diasNoMes
 * @var int    $primeiroDiaW
 * @var string $formato
 */
declare(strict_types=1);

$tituloPagina = 'Agenda ' . competencia_curta($ano, $mes);

$totalVagas = 0;
foreach ($porExame as $x) {
    $totalVagas += (int) $x['capacidade'];
}

// Lido pelo layout `limpo`: sete colunas de dia não cabem em retrato
$paginaPaisagem = $formato === 'calendario';
$paramsBase = ['ano' => $ano, 'mes' => $mes];
?>

<div class="mb-4 flex flex-wrap items-center justify-end gap-2 nao-imprimir">
    <span class="mr-auto text-xs text-slate-500">
        <?= $formato === 'calendario'
            ? 'O calendário sai em paisagem, uma página.'
            : 'A lista sai em retrato, no formato da escala escrita.' ?>
    </span>

    <div class="flex items-center gap-1 rounded-lg border border-slate-300 p-0.5">
        <a href="<?= url('agenda', 'imprimir', $paramsBase + ['formato' => 'calendario']) ?>"
           class="btn btn-sm <?= $formato === 'calendario' ? 'btn-primary' : 'btn-ghost' ?>">
            <?= Icon::svg('calendario', 'w-3.5 h-3.5') ?> Calendário
        </a>
        <a href="<?= url('agenda', 'imprimir', $paramsBase + ['formato' => 'lista']) ?>"
           class="btn btn-sm <?= $formato === 'lista' ? 'btn-primary' : 'btn-ghost' ?>">
            <?= Icon::svg('motivos', 'w-3.5 h-3.5') ?> Lista
        </a>
    </div>

    <button type="button" data-imprimir class="btn btn-primary">
        <?= Icon::svg('imprimir', 'w-4 h-4') ?> Imprimir
    </button>
</div>

<div class="card p-6 print:border-0 print:p-0 print:shadow-none">

    <header class="mb-4 flex items-start justify-between gap-4 border-b border-slate-200 pb-3">
        <div>
            <h1 class="text-lg font-semibold leading-tight text-marine-900">
                Agenda de <?= e(implode(', ', array_column($porExame, 'nome'))) ?: 'exames' ?>
            </h1>
            <p class="mt-0.5 text-sm text-slate-600">
                <?= e(mb_strtoupper(competencia_br($ano, $mes))) ?> · Ambulatório
            </p>
        </div>

        <img src="<?= BASE_URL ?>/image/logo_apas.png" alt="APAS" class="h-10 w-auto object-contain">
    </header>

    <?php require __DIR__ . ($formato === 'calendario' ? '/_folha_calendario.php' : '/_folha_lista.php'); ?>

    <footer class="mt-5 border-t border-slate-200 pt-3 text-[0.6875rem] text-slate-400">
        Emitido em <?= e(date('d/m/Y \à\s H:i')) ?> por <?= e((string) Auth::nome()) ?> · APASBS
    </footer>
</div>
