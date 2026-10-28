<?php
/**
 * Desempenho por vendedor.
 *
 * A pergunta que a coordenação faz no fim do mês: quem vendeu quanto, e
 * quanto isso gera de comissão. A comissão é calculada aqui, contrato a
 * contrato — mensalidade de hoje vezes o percentual congelado no
 * fechamento —, e não somada de uma coluna gravada que envelheceria.
 *
 * @var array $linhas, $filtro
 * @var int   $trava
 */
declare(strict_types=1);

$totais = ['propostas' => 0, 'fechadas' => 0, 'perdidas' => 0, 'em_negociacao' => 0,
           'vidas' => 0, 'mensalidade' => 0.0, 'comissao' => 0.0];

foreach ($linhas as $l) {
    foreach (array_keys($totais) as $chave) {
        $totais[$chave] += $l[$chave];
    }
}

$conversao = fn(array $l): ?float => $l['propostas'] > 0
    ? round($l['fechadas'] * 100 / $l['propostas'], 1) : null;
?>

<div class="mb-5 flex flex-wrap items-end justify-between gap-3">
    <div class="flex items-start gap-3">
        <a href="<?= url_lista('vendas') ?>" class="btn-acao mt-0.5" aria-label="Voltar às propostas">
            <?= Icon::svg('anterior', 'w-4 h-4') ?>
        </a>
        <div>
            <h1 class="titulo-pagina">Desempenho</h1>
            <p class="sub-pagina">
                <?= $trava ? 'Suas vendas' : 'Por vendedor, com a comissão de hoje' ?>
            </p>
        </div>
    </div>
</div>

<div class="card overflow-hidden">
    <form method="get" action="<?= BASE_URL ?>/" class="toolbar">
        <input type="hidden" name="mod" value="vendas">
        <input type="hidden" name="action" value="painel">

        <label class="flex items-center gap-1.5 text-xs text-slate-600 dark:text-slate-400">
            <span class="whitespace-nowrap">Propostas de</span>
            <input type="date" name="de" value="<?= e((string) $filtro['de']) ?>" class="input input-sm w-auto"
                   aria-label="Propostas a partir de">
            <span>até</span>
            <input type="date" name="ate" value="<?= e((string) $filtro['ate']) ?>" class="input input-sm w-auto"
                   aria-label="Propostas até">
        </label>

        <button type="submit" class="btn btn-outline">Filtrar</button>

        <?php if ($filtro['de'] || $filtro['ate']): ?>
            <a href="<?= url('vendas', 'painel') ?>" class="btn btn-ghost">Limpar</a>
        <?php endif; ?>
    </form>

    <?php if (empty($linhas)): ?>
        <div class="vazio">
            <span class="vazio-icone"><?= Icon::svg('painel', 'w-5 h-5') ?></span>
            <p class="vazio-titulo">Nenhum vendedor cadastrado</p>
            <p class="vazio-texto">
                Cadastre a empresa parceira e os vendedores dela para acompanhar o
                desempenho aqui.
            </p>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="tabela">
                <thead>
                    <tr>
                        <th>Vendedor</th>
                        <th class="w-24 text-right">Propostas</th>
                        <th class="w-24 text-right">Fechadas</th>
                        <th class="w-24 text-right">Conversão</th>
                        <th class="w-20 text-right">Vidas</th>
                        <th class="w-32 text-right">Mensalidade</th>
                        <th class="w-32 text-right">Comissão</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($linhas as $l): ?>
                        <tr>
                            <td>
                                <a href="<?= url('vendas', 'lista', ['vendedor' => (int) $l['id']]) ?>"
                                   class="celula-principal block hover:underline"><?= e($l['vendedor']) ?></a>
                                <span class="block text-xs text-slate-500 dark:text-slate-400">
                                    <?= e($l['empresa']) ?>
                                    · <?= e(number_format($l['percent_atual'], 2, ',', '')) ?>%
                                    <?php if (!$l['ativo']): ?>
                                        · <span class="text-slate-400 dark:text-slate-500">inativo</span>
                                    <?php endif; ?>
                                </span>
                            </td>

                            <td class="text-right text-xs text-slate-600 dark:text-slate-400">
                                <?= numero($l['propostas']) ?>
                                <?php if ($l['em_negociacao'] > 0): ?>
                                    <span class="block text-[0.6875rem] text-amber-600 dark:text-amber-400">
                                        <?= numero($l['em_negociacao']) ?> em aberto
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td class="text-right text-xs font-medium text-slate-700 dark:text-slate-300">
                                <?= numero($l['fechadas']) ?>
                            </td>

                            <td class="text-right text-xs text-slate-600 dark:text-slate-400">
                                <?= $conversao($l) !== null
                                    ? e(number_format($conversao($l), 1, ',', '')) . '%'
                                    : '<span class="text-slate-400 dark:text-slate-600">—</span>' ?>
                            </td>

                            <td class="text-right text-xs text-slate-600 dark:text-slate-400">
                                <?= numero($l['vidas']) ?>
                            </td>

                            <td class="whitespace-nowrap text-right text-xs text-slate-600 dark:text-slate-400">
                                <?= e(dinheiro_br($l['mensalidade'])) ?>
                            </td>

                            <td class="whitespace-nowrap text-right font-medium text-slate-800 dark:text-slate-200">
                                <?= e(dinheiro_br($l['comissao'])) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>

                <?php if (count($linhas) > 1): ?>
                    <tfoot>
                        <tr class="border-t-2 border-slate-200 font-medium dark:border-slate-700">
                            <td class="text-xs text-slate-500 dark:text-slate-400">Total</td>
                            <td class="text-right text-xs"><?= numero($totais['propostas']) ?></td>
                            <td class="text-right text-xs"><?= numero($totais['fechadas']) ?></td>
                            <td class="text-right text-xs">
                                <?= $totais['propostas'] > 0
                                    ? e(number_format($totais['fechadas'] * 100 / $totais['propostas'], 1, ',', '')) . '%'
                                    : '—' ?>
                            </td>
                            <td class="text-right text-xs"><?= numero($totais['vidas']) ?></td>
                            <td class="whitespace-nowrap text-right text-xs"><?= e(dinheiro_br($totais['mensalidade'])) ?></td>
                            <td class="whitespace-nowrap text-right"><?= e(dinheiro_br($totais['comissao'])) ?></td>
                        </tr>
                    </tfoot>
                <?php endif; ?>
            </table>
        </div>

        <div class="border-t border-slate-100 px-4 py-2.5 text-xs text-slate-500 dark:border-slate-800 dark:text-slate-400">
            A mensalidade é a de hoje, somada dos contratos que cada venda gerou. A
            comissão usa o percentual congelado no dia do fechamento, e não o combinado
            de agora — renegociar não mexe no que já foi vendido.
        </div>
    <?php endif; ?>
</div>
