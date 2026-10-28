<?php
/**
 * Folha do POP para imprimir e deixar no balcão da central.
 *
 * @var array $pop
 * @var array $blocos
 */
declare(strict_types=1);

$tituloPagina  = $pop['codigo'];
$paraImpressao = true;
?>

<div class="mb-4 flex items-center justify-end nao-imprimir">
    <button type="button" data-imprimir class="btn btn-primary">
        <?= Icon::svg('imprimir', 'w-4 h-4') ?> Imprimir
    </button>
</div>

<div class="card p-6 print:border-0 print:p-0 print:shadow-none">
    <?php /* O mesmo cabeçalho das outras folhas do sistema: a marca do
             hospital à esquerda, a identificação no meio e a da APAS à
             direita. O print-color-adjust vai no atributo porque sem ele o
             Chrome imprime as marcas lavadas, por economia de tinta. */ ?>
    <header class="mb-5 flex items-center justify-between gap-4 border-b border-slate-200 pb-3">
        <img src="<?= BASE_URL ?>/image/logo_hospital.png" alt="Hospital Santo Expedito"
             class="h-10 w-auto shrink-0 object-contain"
             style="print-color-adjust:exact;-webkit-print-color-adjust:exact">

        <div class="min-w-0 flex-1">
            <p class="mono text-[0.6875rem] font-semibold text-marine-600">
                <?= e($pop['codigo']) ?> · versão <?= e($pop['versao']) ?>
                <?php if (!empty($pop['revisado_em'])): ?>
                    · revisado em <?= data_br($pop['revisado_em']) ?>
                <?php endif; ?>
            </p>
            <h1 class="mt-0.5 text-lg font-semibold leading-tight text-marine-900"><?= e($pop['titulo']) ?></h1>
            <p class="mt-0.5 text-sm text-slate-600">Central de Agendamento · <?= e($pop['categoria']) ?></p>
        </div>
        <img src="<?= BASE_URL ?>/image/logo_apas.png" alt="APAS Baixada Santista"
             class="h-7 w-auto shrink-0 object-contain"
             style="print-color-adjust:exact;-webkit-print-color-adjust:exact">
    </header>

    <?php require __DIR__ . '/_blocos.php'; ?>

    <footer class="mt-6 border-t border-slate-200 pt-3 text-[0.6875rem] text-slate-400">
        Impresso em <?= e(date('d/m/Y \à\s H:i')) ?> por <?= e((string) Auth::nome()) ?> · APASBS.
        Confira sempre a versão no sistema antes de seguir uma folha impressa.
    </footer>
</div>
