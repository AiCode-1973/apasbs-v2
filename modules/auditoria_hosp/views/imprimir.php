<?php
/**
 * Auditoria Hospitalar — o consolidado em folha, para a reunião com o convênio.
 *
 * Os números são os mesmos da tela e vêm da mesma função: a folha não refaz
 * conta nenhuma. O que ela acrescenta é o recorte escrito por extenso no
 * cabeçalho — uma folha de consolidado sem a competência e o filtro que a
 * geraram não serve para discutir nada depois.
 *
 * @var string $tipo
 * @var array  $cfg, $linhas, $total, $setores, $filtro, $convenios
 */
declare(strict_types=1);

require __DIR__ . '/_consolidado_apoio.php';

$tituloPagina  = 'Consolidado ' . $cfg['plural'];
$paraImpressao = true;

$nomeConvenio = '';
foreach ($convenios as $c) {
    if ((int) $c['id'] === $filtro['convenio_id']) {
        $nomeConvenio = (string) $c['nome_convenio'];
    }
}

// O recorte por extenso: só o que foi realmente escolhido entra na linha.
$recorte = array_filter([
    $nomeConvenio !== '' ? 'convênio ' . $nomeConvenio : null,
    $filtro['setor'] !== '' ? 'setor ' . $filtro['setor'] : null,
    $filtro['status'] !== '' ? 'situação ' . $filtro['status'] : null,
]);

$pct = AuditoriaHospModel::percentuais($total);
?>

<div class="mb-4 flex items-center justify-end nao-imprimir">
    <button type="button" data-imprimir class="btn btn-primary">
        <?= Icon::svg('imprimir', 'w-4 h-4') ?> Imprimir
    </button>
</div>

<div class="card p-6 print:border-0 print:p-0 print:shadow-none">
    <header class="mb-5 flex items-center justify-between gap-4 border-b border-slate-200 pb-3">
        <img src="<?= BASE_URL ?>/image/logo_hospital.png" alt="Hospital Santo Expedito"
             class="h-10 w-auto shrink-0 object-contain"
             style="print-color-adjust:exact;-webkit-print-color-adjust:exact">

        <div class="min-w-0 flex-1">
            <p class="mono text-[0.6875rem] font-semibold text-marine-600">
                Auditoria Hospitalar · <?= e($cfg['plural']) ?>
            </p>
            <h1 class="mt-0.5 text-lg font-semibold leading-tight text-marine-900">
                Consolidado da competência
                <?= $filtro['competencia'] !== '' ? e($mesBr($filtro['competencia'])) : '— todas' ?>
            </h1>
            <p class="mt-0.5 text-sm text-slate-600">
                <?= $recorte ? e(ucfirst(implode(' · ', $recorte))) : 'Todos os convênios e situações' ?>
            </p>
        </div>

        <img src="<?= BASE_URL ?>/image/logo_apas.png" alt="APAS Baixada Santista"
             class="h-7 w-auto shrink-0 object-contain"
             style="print-color-adjust:exact;-webkit-print-color-adjust:exact">
    </header>

    <?php /* Os números do recorte antes da tabela: quem lê a folha na reunião
             quase sempre quer só estes cinco. */ ?>
    <div class="mb-5 grid grid-cols-2 gap-x-6 gap-y-3 sm:grid-cols-5">
        <div>
            <p class="text-[0.625rem] font-semibold uppercase tracking-wider text-slate-500">Contas</p>
            <p class="text-base font-semibold text-marine-900"><?= numero((int) $total['contas']) ?></p>
        </div>
        <div>
            <p class="text-[0.625rem] font-semibold uppercase tracking-wider text-slate-500">Valor inicial</p>
            <p class="text-base font-semibold text-marine-900"><?= e(dinheiro_br((float) $total['valor_inicial'])) ?></p>
        </div>
        <div>
            <p class="text-[0.625rem] font-semibold uppercase tracking-wider text-slate-500">Valor final</p>
            <p class="text-base font-semibold text-marine-900"><?= e(dinheiro_br((float) $total['valor_total'])) ?></p>
        </div>
        <div>
            <p class="text-[0.625rem] font-semibold uppercase tracking-wider text-slate-500">Glosado</p>
            <p class="text-base font-semibold text-rose-700"><?= e(dinheiro_br((float) $total['valor_glosado'])) ?></p>
            <p class="text-[0.625rem] text-slate-500"><?= e($percTexto($pct['glosado'], 'do valor final')) ?></p>
        </div>
        <div>
            <p class="text-[0.625rem] font-semibold uppercase tracking-wider text-slate-500">Aceito no recurso</p>
            <p class="text-base font-semibold text-emerald-700"><?= e(dinheiro_br((float) $total['valor_aceito'])) ?></p>
            <p class="text-[0.625rem] text-slate-500"><?= e($percTexto($pct['aceito'], 'da glosa revertida')) ?></p>
        </div>
    </div>

    <?php if (!$linhas): ?>
        <p class="py-6 text-center text-sm text-slate-500">Nenhuma conta neste recorte.</p>
    <?php else: ?>
        <?= $tabela($linhas, $total, ($linhas[0]['setor'] ?? '') !== '', $tipo, $filtro, false) ?>

        <?php if ((int) $total['sem_final'] > 0): ?>
            <p class="mt-3 text-[0.6875rem] text-amber-700">
                <?= (int) $total['sem_final'] === 1
                    ? 'Uma conta deste recorte ainda está sem valor final'
                    : numero((int) $total['sem_final']) . ' contas deste recorte ainda estão sem valor final' ?>,
                então o percentual de glosa acima vai cair quando a auditoria terminar.
            </p>
        <?php endif; ?>

        <?php if ($setores && ($linhas[0]['setor'] ?? '') === '' && count($setores) > 1): ?>
            <h2 class="mt-6 mb-2 text-sm font-semibold text-marine-900">Por setor</h2>
            <table class="tabela">
                <thead>
                    <tr>
                        <th>Setor</th>
                        <th class="w-16 text-right">Contas</th>
                        <th class="w-28 text-right">Inicial</th>
                        <th class="w-28 text-right">Final</th>
                        <th class="w-32 text-right">Glosado</th>
                        <th class="w-28 text-right">Aceito</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($setores as $s): ?>
                        <?php $ps = AuditoriaHospModel::percentuais($s); ?>
                        <tr>
                            <td class="celula-principal"><?= e((string) $s['setor']) ?></td>
                            <td class="text-right text-xs"><?= numero((int) $s['contas']) ?></td>
                            <td class="text-right text-xs"><?= e(dinheiro_br((float) $s['valor_inicial'], false)) ?></td>
                            <td class="text-right text-xs"><?= e(dinheiro_br((float) $s['valor_total'], false)) ?></td>
                            <td class="text-right text-xs">
                                <?= e(dinheiro_br((float) $s['valor_glosado'], false)) ?>
                                <?php if ($ps['glosado'] !== null): ?>
                                    <span class="block text-[0.625rem] text-slate-500">
                                        <?= e(number_format($ps['glosado'], 2, ',', '.')) ?>%
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="text-right text-xs"><?= e(dinheiro_br((float) $s['valor_aceito'], false)) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    <?php endif; ?>

    <footer class="mt-6 border-t border-slate-200 pt-3 text-[0.6875rem] text-slate-400">
        Impresso em <?= e(date('d/m/Y \à\s H:i')) ?> por <?= e((string) Auth::nome()) ?> · APASBS.
        Os valores são somados das contas no momento da impressão — uma folha antiga
        não acompanha a auditoria que continuou depois dela.
    </footer>
</div>
