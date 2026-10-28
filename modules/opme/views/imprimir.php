<?php
/**
 * Folha dos vencimentos do mês, para levar à reunião de pagamento.
 *
 * Paisagem: são nove colunas, as mesmas que a planilha tinha.
 *
 * @var int   $ano
 * @var int   $mes
 * @var array $parcelas
 * @var array $totais
 * @var array $porFornecedor
 */
declare(strict_types=1);

$tituloPagina   = 'OPME ' . competencia_curta($ano, $mes);
$paginaPaisagem = true;
$hoje           = date('Y-m-d');
?>

<div class="mb-4 flex flex-wrap items-center justify-end gap-2 nao-imprimir">
    <span class="mr-auto text-xs text-slate-500">
        A folha sai em paisagem, com as mesmas colunas da planilha.
    </span>
    <button type="button" data-imprimir class="btn btn-primary">
        <?= Icon::svg('imprimir', 'w-4 h-4') ?> Imprimir
    </button>
</div>

<div class="card p-6 print:border-0 print:p-0 print:shadow-none">

    <header class="mb-4 flex items-start justify-between gap-4 border-b border-slate-200 pb-3">
        <div>
            <h1 class="text-lg font-semibold leading-tight text-marine-900">
                OPME — vencimentos de <?= e(competencia_br($ano, $mes)) ?>
            </h1>
            <p class="mt-0.5 text-sm text-slate-600">
                <?= numero((int) ($totais['parcelas'] ?? 0)) ?> parcelas ·
                <?= numero((int) ($totais['fornecedores'] ?? 0)) ?> fornecedores ·
                <?= e(dinheiro_br((float) ($totais['total'] ?? 0))) ?>
            </p>
        </div>

        <img src="<?= BASE_URL ?>/image/logo_apas.png" alt="APAS" class="h-10 w-auto object-contain">
    </header>

    <?php if (empty($parcelas)): ?>
        <p class="text-sm text-slate-600">Nenhuma parcela vence nesta competência.</p>
    <?php else: ?>
        <table class="folha-opme">
            <thead>
                <tr>
                    <th>Paciente</th>
                    <th>Cirurgia</th>
                    <th>Hospital</th>
                    <th>Médico</th>
                    <th>Fornecedor</th>
                    <th>NF</th>
                    <th class="text-right">Valor total</th>
                    <th>Parc.</th>
                    <th class="text-right">Valor</th>
                    <th>Venc.</th>
                    <th>Pago</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($parcelas as $p): ?>
                    <tr>
                        <td><?= e($p['paciente']) ?></td>
                        <td><?= e(data_br($p['data_cirurgia'], '—')) ?></td>
                        <td><?= $p['hospital'] ? e($p['hospital']) : '—' ?></td>
                        <td><?= $p['medico'] ? e($p['medico']) : '—' ?></td>
                        <td><?= e($p['fornecedor']) ?></td>
                        <td class="mono"><?= e($p['numero_nf']) ?></td>
                        <td class="num text-right"><?= e(dinheiro_br((float) $p['valor_total'], false)) ?></td>
                        <td><?= (int) $p['numero'] ?>/<?= (int) $p['total_parcelas'] ?></td>
                        <td class="num text-right"><?= e(dinheiro_br((float) $p['valor'], false)) ?></td>
                        <td><?= e(data_br($p['vencimento'])) ?></td>
                        <td class="text-center">
                            <?= $p['pago'] ? '&#10003;' : ($p['situacao'] !== 'normal'
                                ? e(OpmeModel::SITUACOES[$p['situacao']]) : '') ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="8">Total do mês</td>
                    <td class="num text-right">
                        <?php
                        $soma = 0.0;
                        foreach ($parcelas as $p) {
                            $soma += (float) $p['valor'];
                        }
                        echo e(dinheiro_br($soma, false));
                        ?>
                    </td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        </table>

        <?php if ($porFornecedor): ?>
            <section class="mt-5 break-inside-avoid">
                <h2 class="mb-2 text-[0.9375rem] font-semibold text-marine-900">Por fornecedor</h2>
                <p class="text-[0.8125rem] leading-relaxed text-slate-700">
                    <?php
                    $partes = [];
                    foreach ($porFornecedor as $f) {
                        $partes[] = e($f['fornecedor']) . ' <strong>'
                            . e(dinheiro_br((float) $f['total'], false)) . '</strong>';
                    }
                    echo implode(' · ', $partes);
                    ?>
                </p>
            </section>
        <?php endif; ?>
    <?php endif; ?>

    <footer class="mt-5 border-t border-slate-200 pt-3 text-[0.6875rem] text-slate-400">
        Emitido em <?= e(date('d/m/Y \à\s H:i')) ?> por <?= e((string) Auth::nome()) ?> · APASBS
    </footer>
</div>
