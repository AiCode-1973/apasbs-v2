<?php
/**
 * Uma nota de OPME com as parcelas dela.
 *
 * @var array $nota
 * @var array $parcelas
 * @var bool  $numeracaoRuim
 */
declare(strict_types=1);

$podeEditar  = Auth::temPermissao('opme', 'pode_editar');
$podeExcluir = Auth::temPermissao('opme', 'pode_excluir');
$hoje        = date('Y-m-d');

$soma = 0.0;
$pago = 0.0;
foreach ($parcelas as $p) {
    $soma += (float) $p['valor'];
    if ($p['pago']) {
        $pago += (float) $p['valor'];
    }
}
$aberto = max(0, $soma - $pago);
$difere = abs($soma - (float) $nota['valor_total']) > 1.00;
?>

<div class="mb-5 flex flex-wrap items-start justify-between gap-3">
    <div class="flex items-start gap-3">
        <a href="<?= url_lista('opme') ?>" class="btn btn-icon btn-outline" aria-label="Voltar">
            <?= Icon::svg('voltar', 'w-4 h-4') ?>
        </a>
        <div>
            <p class="mono text-xs text-slate-500 dark:text-slate-400">
                NF <?= e($nota['numero_nf']) ?> · <?= e($nota['fornecedor']) ?>
                <?php if ($nota['situacao'] !== 'normal'): ?>
                    <span class="badge <?= $nota['situacao'] === 'cancelada' ? 'badge-rosa' : 'badge-violeta' ?> ml-1">
                        <?= e(OpmeModel::SITUACOES[$nota['situacao']]) ?>
                    </span>
                <?php endif; ?>
            </p>
            <h1 class="titulo-pagina"><?= e($nota['paciente']) ?></h1>
            <p class="sub-pagina">
                <?= e(dinheiro_br((float) $nota['valor_total'])) ?>
                em <?= (int) $nota['total_parcelas'] ?> parcela<?= (int) $nota['total_parcelas'] === 1 ? '' : 's' ?>
            </p>
        </div>
    </div>

    <div class="flex flex-wrap items-center gap-2 nao-imprimir">
        <?php if ($podeEditar): ?>
            <a href="<?= url('opme', 'editar', ['id' => (int) $nota['id']]) ?>" class="btn btn-outline">
                <?= Icon::svg('editar', 'w-4 h-4') ?> Editar
            </a>
        <?php endif; ?>

        <?php if ($podeExcluir): ?>
            <form method="post" action="<?= url('opme', 'excluir') ?>"
                  data-titulo="Excluir nota"
                  data-texto="A NF <?= e($nota['numero_nf']) ?> de <?= e($nota['paciente']) ?> e as <?= count($parcelas) ?> parcelas dela serão removidas.">
                <?= Csrf::campo() ?>
                <input type="hidden" name="id" value="<?= (int) $nota['id'] ?>">
                <button type="button" @click="abrir" class="btn btn-danger-outline">
                    <?= Icon::svg('excluir', 'w-4 h-4') ?> Excluir
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>

<div class="grid gap-4 lg:grid-cols-[1fr_1.4fr]">

    <div class="card self-start">
        <div class="card-header">
            <h2 class="card-title">A nota</h2>
        </div>

        <dl class="card-corpo space-y-3">
            <div>
                <dt class="label">Fornecedor</dt>
                <dd class="text-[0.875rem] font-medium text-slate-800 dark:text-slate-100"><?= e($nota['fornecedor']) ?></dd>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <dt class="label">Nota fiscal</dt>
                    <dd class="mono text-[0.875rem] font-semibold text-slate-800 dark:text-slate-100"><?= e($nota['numero_nf']) ?></dd>
                </div>
                <div>
                    <dt class="label">Data da NF</dt>
                    <dd class="text-[0.875rem] font-medium text-slate-800 dark:text-slate-100"><?= e(data_br($nota['data_nf'])) ?></dd>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <dt class="label">Cirurgia</dt>
                    <dd class="text-[0.875rem] font-medium text-slate-800 dark:text-slate-100"><?= e(data_br($nota['data_cirurgia'])) ?></dd>
                </div>
                <div>
                    <dt class="label">Hospital</dt>
                    <dd class="text-[0.875rem] font-medium text-slate-800 dark:text-slate-100"><?= $nota['hospital'] ? e($nota['hospital']) : '—' ?></dd>
                </div>
            </div>
            <div>
                <dt class="label">Médico responsável</dt>
                <dd class="text-[0.875rem] font-medium text-slate-800 dark:text-slate-100"><?= $nota['medico'] ? e($nota['medico']) : '—' ?></dd>
            </div>
            <div>
                <dt class="label">Valor total</dt>
                <dd class="text-base font-semibold text-slate-900 dark:text-white"><?= e(dinheiro_br((float) $nota['valor_total'])) ?></dd>
            </div>
            <div>
                <dt class="label">NF encaminhada ao HSE</dt>
                <dd class="text-[0.875rem] font-medium text-slate-800 dark:text-slate-100">
                    <span class="badge <?= $nota['encaminhado_hse'] ? 'badge-verde' : 'badge-cinza' ?>">
                        <?= $nota['encaminhado_hse'] ? 'Sim' : 'Não' ?>
                    </span>
                </dd>
            </div>
            <?php if (!empty($nota['observacao'])): ?>
                <div>
                    <dt class="label">Observação</dt>
                    <dd class="text-[0.875rem] font-medium text-slate-800 dark:text-slate-100"><?= e($nota['observacao']) ?></dd>
                </div>
            <?php endif; ?>
        </dl>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Parcelas</h2>
            <span class="text-xs text-slate-500 dark:text-slate-400">
                <?= e(dinheiro_br($pago, false)) ?> pago ·
                <span class="<?= $aberto > 0.005 ? 'font-semibold text-slate-700 dark:text-slate-200' : '' ?>">
                    <?= e(dinheiro_br($aberto, false)) ?> em aberto
                </span>
            </span>
        </div>

        <?php if ($difere && $parcelas): ?>
            <div class="mx-4 mt-4 rounded-lg border-l-4 border-amber-400 bg-amber-50/70 px-3 py-2 text-xs text-amber-900 dark:border-amber-500/60 dark:bg-amber-500/10 dark:text-amber-200">
                A soma das parcelas (<?= e(dinheiro_br($soma)) ?>) não bate com o valor da nota
                (<?= e(dinheiro_br((float) $nota['valor_total'])) ?>).
                <?php if (count($parcelas) < (int) $nota['total_parcelas']): ?>
                    É o esperado quando parte das parcelas venceu antes do controle começar:
                    <?= count($parcelas) ?> de <?= (int) $nota['total_parcelas'] ?> estão lançadas.
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($numeracaoRuim && $podeEditar): ?>
            <div class="mx-4 mt-4 flex flex-wrap items-center justify-between gap-2 rounded-lg border-l-4 border-marine-400 bg-marine-50/70 px-3 py-2 text-xs text-marine-900 dark:border-marine-500/60 dark:bg-marine-500/10 dark:text-marine-200">
                <span>
                    A numeração das parcelas está fora de sequência — herança da planilha,
                    onde a linha era copiada de um mês para o outro e o número ficava para trás.
                </span>
                <form method="post" action="<?= url('opme', 'renumerar') ?>" class="nao-imprimir">
                    <?= Csrf::campo() ?>
                    <input type="hidden" name="id" value="<?= (int) $nota['id'] ?>">
                    <button type="submit" class="btn btn-outline btn-sm">
                        <?= Icon::svg('igual', 'w-3.5 h-3.5') ?> Renumerar por vencimento
                    </button>
                </form>
            </div>
        <?php endif; ?>

        <?php if (empty($parcelas)): ?>
            <div class="vazio">
                <span class="vazio-icone"><?= Icon::svg('fechamentos', 'w-5 h-5') ?></span>
                <p class="vazio-titulo">Sem parcelas lançadas</p>
                <p class="vazio-texto">
                    <?= $nota['situacao'] === 'normal'
                        ? 'Edite a nota para lançar os vencimentos.'
                        : 'Nota ' . mb_strtolower(OpmeModel::SITUACOES[$nota['situacao']]) . ': não há o que pagar.' ?>
                </p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="tabela">
                    <thead>
                        <tr>
                            <th class="w-16">#</th>
                            <th class="w-32">Vencimento</th>
                            <th class="w-32 text-right">Valor</th>
                            <th>Situação</th>
                            <?php if ($podeEditar): ?>
                                <th class="w-28 text-right">Baixa</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($parcelas as $p): ?>
                            <?php $atrasada = !$p['pago'] && $p['vencimento'] < $hoje && $nota['situacao'] === 'normal'; ?>
                            <tr>
                                <td class="celula-suave mono text-xs">
                                    <?= (int) $p['numero'] ?>/<?= (int) $nota['total_parcelas'] ?>
                                </td>

                                <td class="<?= $atrasada ? 'font-semibold text-rose-600 dark:text-rose-400' : '' ?>">
                                    <?= e(data_br($p['vencimento'])) ?>
                                </td>

                                <td class="num text-right"><?= e(dinheiro_br((float) $p['valor'], false)) ?></td>

                                <td>
                                    <?php if ($p['pago']): ?>
                                        <span class="badge badge-verde">
                                            Paga<?= $p['data_pagamento'] ? ' em ' . e(data_br($p['data_pagamento'])) : '' ?>
                                        </span>
                                    <?php elseif ($atrasada): ?>
                                        <span class="badge badge-rosa">Vencida</span>
                                    <?php else: ?>
                                        <span class="badge badge-cinza">Em aberto</span>
                                    <?php endif; ?>
                                </td>

                                <?php if ($podeEditar): ?>
                                    <td class="text-right">
                                        <form method="post" action="<?= url('opme', 'pagar') ?>" class="inline">
                                            <?= Csrf::campo() ?>
                                            <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                                            <button type="submit" class="btn btn-sm <?= $p['pago'] ? 'btn-ghost' : 'btn-outline' ?>">
                                                <?= $p['pago']
                                                    ? Icon::svg('cancelar', 'w-3.5 h-3.5') . ' Desfazer'
                                                    : Icon::svg('check', 'w-3.5 h-3.5') . ' Baixar' ?>
                                            </button>
                                        </form>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="2" class="font-semibold">Total lançado</td>
                            <td class="num text-right font-semibold"><?= e(dinheiro_br($soma, false)) ?></td>
                            <td colspan="<?= $podeEditar ? 2 : 1 ?>" class="text-xs text-slate-500 dark:text-slate-400">
                                de <?= e(dinheiro_br((float) $nota['valor_total'], false)) ?> da nota
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
