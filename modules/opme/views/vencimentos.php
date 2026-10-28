<?php
/**
 * Vencimentos do mês — a aba mensal da planilha, agora calculada.
 *
 * @var int   $ano
 * @var int   $mes
 * @var array $parcelas
 * @var array $totais
 * @var array $porFornecedor
 * @var array $fornecedores
 * @var array $meses
 * @var array $filtro
 */
declare(strict_types=1);

$podeEditar = Auth::temPermissao('opme', 'pode_editar');
$hoje       = date('Y-m-d');

$anterior = mktime(0, 0, 0, $mes - 1, 1, $ano);
$proximo  = mktime(0, 0, 0, $mes + 1, 1, $ano);
$base     = array_filter([
    'fornecedor' => $filtro['fornecedor_id'] ?: null,
    'aberto'     => $filtro['somente_abertas'] ? 1 : null,
]);
?>

<div class="mb-5 flex flex-wrap items-end justify-between gap-3">
    <div class="flex items-center gap-3">
        <a href="<?= url_lista('opme') ?>" class="btn btn-icon btn-outline" aria-label="Voltar">
            <?= Icon::svg('voltar', 'w-4 h-4') ?>
        </a>
        <div>
            <h1 class="titulo-pagina">Vencimentos de <?= e(competencia_br($ano, $mes)) ?></h1>
            <p class="sub-pagina">O que a planilha mostrava numa aba por mês</p>
        </div>
    </div>

    <div class="flex flex-wrap items-center gap-2">
        <div class="flex items-center gap-1 rounded-lg border border-slate-300 p-0.5 dark:border-slate-700">
            <a href="<?= url('opme', 'vencimentos', $base + ['ano' => (int) date('Y', $anterior), 'mes' => (int) date('n', $anterior)]) ?>"
               class="btn btn-ghost btn-sm" aria-label="Mês anterior">
                <?= Icon::svg('anterior', 'w-3.5 h-3.5') ?>
            </a>
            <span class="px-2 text-xs font-medium text-slate-600 dark:text-slate-300">
                <?= e(competencia_curta($ano, $mes)) ?>
            </span>
            <a href="<?= url('opme', 'vencimentos', $base + ['ano' => (int) date('Y', $proximo), 'mes' => (int) date('n', $proximo)]) ?>"
               class="btn btn-ghost btn-sm" aria-label="Próximo mês">
                <?= Icon::svg('proximo', 'w-3.5 h-3.5') ?>
            </a>
        </div>

        <a href="<?= url('opme', 'imprimir', ['ano' => $ano, 'mes' => $mes]) ?>" class="btn btn-outline">
            <?= Icon::svg('imprimir', 'w-4 h-4') ?> Imprimir
        </a>
    </div>
</div>

<div class="card mb-4">
    <div class="card-corpo grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div>
            <p class="rotulo-num">Vence no mês</p>
            <p class="valor-num"><?= e(dinheiro_br((float) ($totais['total'] ?? 0))) ?></p>
            <p class="ajuda">
                <?= numero((int) ($totais['parcelas'] ?? 0)) ?> parcela<?= (int) ($totais['parcelas'] ?? 0) === 1 ? '' : 's' ?>
                · <?= numero((int) ($totais['fornecedores'] ?? 0)) ?> fornecedores
            </p>
        </div>
        <div>
            <p class="rotulo-num">Já pago</p>
            <p class="valor-num text-emerald-600 dark:text-emerald-400">
                <?= e(dinheiro_br((float) ($totais['pago'] ?? 0))) ?>
            </p>
        </div>
        <div>
            <p class="rotulo-num">Em aberto</p>
            <p class="valor-num"><?= e(dinheiro_br((float) ($totais['aberto'] ?? 0))) ?></p>
        </div>
        <div>
            <p class="rotulo-num">Maior fornecedor</p>
            <?php $maior = $porFornecedor[0] ?? null; ?>
            <p class="valor-num text-lg"><?= $maior ? e($maior['fornecedor']) : '—' ?></p>
            <p class="ajuda"><?= $maior ? e(dinheiro_br((float) $maior['total'])) : 'nada a vencer' ?></p>
        </div>
    </div>
</div>

<div class="grid gap-4 lg:grid-cols-[3fr_1fr]">

    <div class="card overflow-hidden">
        <form method="get" action="<?= BASE_URL ?>/" class="toolbar">
            <input type="hidden" name="mod" value="opme">
            <input type="hidden" name="action" value="vencimentos">
            <input type="hidden" name="ano" value="<?= $ano ?>">
            <input type="hidden" name="mes" value="<?= $mes ?>">

            <select name="fornecedor" class="select w-auto" aria-label="Fornecedor">
                <option value="">Todos os fornecedores</option>
                <?php foreach ($fornecedores as $f): ?>
                    <option value="<?= (int) $f['id'] ?>" <?= $filtro['fornecedor_id'] === (int) $f['id'] ? 'selected' : '' ?>>
                        <?= e($f['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label class="flex cursor-pointer items-center gap-1.5 text-xs text-slate-600 dark:text-slate-400">
                <input type="checkbox" name="aberto" value="1" class="check" <?= $filtro['somente_abertas'] ? 'checked' : '' ?>>
                Só as que faltam pagar
            </label>

            <button type="submit" class="btn btn-outline">Filtrar</button>

            <?php if ($base): ?>
                <a href="<?= url('opme', 'vencimentos', ['ano' => $ano, 'mes' => $mes]) ?>" class="btn btn-ghost">Limpar</a>
            <?php endif; ?>

            <span class="ml-auto text-xs text-slate-500 dark:text-slate-400">
                <?= numero(count($parcelas)) ?> na tela
            </span>
        </form>

        <?php if (empty($parcelas)): ?>
            <div class="vazio">
                <span class="vazio-icone"><?= Icon::svg('calendario', 'w-5 h-5') ?></span>
                <p class="vazio-titulo">Nada vence em <?= e(competencia_br($ano, $mes)) ?></p>
                <p class="vazio-texto">
                    Use as setas do topo para andar pelos meses, ou lance uma nota nova.
                </p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="tabela">
                    <thead>
                        <tr>
                            <th class="w-24">Vence</th>
                            <th>Paciente</th>
                            <th class="w-36">Fornecedor</th>
                            <th class="w-24">NF</th>
                            <th class="w-16">Parc.</th>
                            <th class="w-32 text-right">Valor</th>
                            <?php if ($podeEditar): ?>
                                <th class="w-28 text-right">Baixa</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($parcelas as $p): ?>
                            <?php $atrasada = !$p['pago'] && $p['vencimento'] < $hoje && $p['situacao'] === 'normal'; ?>
                            <tr class="<?= $p['pago'] ? 'opacity-60' : '' ?>">
                                <td class="text-xs <?= $atrasada ? 'font-semibold text-rose-600 dark:text-rose-400' : 'celula-suave' ?>">
                                    <?= e(data_br($p['vencimento'])) ?>
                                </td>

                                <td>
                                    <a href="<?= url('opme', 'ver', ['id' => (int) $p['nota_id']]) ?>"
                                       class="celula-principal text-marine-700 hover:underline dark:text-marine-300">
                                        <?= e($p['paciente']) ?>
                                    </a>
                                    <span class="block text-xs text-slate-500 dark:text-slate-400">
                                        <?= $p['medico'] ? e($p['medico']) : '—' ?>
                                        <?= $p['data_cirurgia'] ? ' · cirurgia ' . e(data_br($p['data_cirurgia'])) : '' ?>
                                    </span>
                                </td>

                                <td class="celula-suave text-xs"><?= e($p['fornecedor']) ?></td>

                                <td class="celula-suave mono text-xs">
                                    <?= e($p['numero_nf']) ?>
                                    <?php if ($p['situacao'] !== 'normal'): ?>
                                        <span class="badge <?= $p['situacao'] === 'cancelada' ? 'badge-rosa' : 'badge-violeta' ?> mt-1">
                                            <?= e(OpmeModel::SITUACOES[$p['situacao']]) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td class="celula-suave mono text-xs">
                                    <?= (int) $p['numero'] ?>/<?= (int) $p['total_parcelas'] ?>
                                </td>

                                <td class="num text-right">
                                    <span class="celula-principal"><?= e(dinheiro_br((float) $p['valor'], false)) ?></span>
                                    <span class="block text-xs text-slate-400 dark:text-slate-500">
                                        de <?= e(dinheiro_br((float) $p['valor_total'], false)) ?>
                                    </span>
                                </td>

                                <?php if ($podeEditar): ?>
                                    <td class="text-right">
                                        <form method="post" action="<?= url('opme', 'pagar') ?>" class="inline">
                                            <?= Csrf::campo() ?>
                                            <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                                            <input type="hidden" name="origem" value="vencimentos">
                                            <input type="hidden" name="ano" value="<?= $ano ?>">
                                            <input type="hidden" name="mes" value="<?= $mes ?>">
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
                            <td colspan="5" class="font-semibold">Total na tela</td>
                            <td class="num text-right font-semibold">
                                <?php
                                $somaTela = 0.0;
                                foreach ($parcelas as $p) {
                                    $somaTela += (float) $p['valor'];
                                }
                                echo e(dinheiro_br($somaTela, false));
                                ?>
                            </td>
                            <?php if ($podeEditar): ?><td></td><?php endif; ?>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <div class="card self-start">
        <div class="card-header">
            <h2 class="card-title">Por fornecedor</h2>
            <span class="text-xs text-slate-500 dark:text-slate-400">no mês</span>
        </div>

        <?php if (empty($porFornecedor)): ?>
            <div class="card-corpo text-xs text-slate-500 dark:text-slate-400">
                Nenhum vencimento neste mês.
            </div>
        <?php else: ?>
            <ul class="divide-y divide-slate-200 dark:divide-slate-800">
                <?php foreach ($porFornecedor as $f): ?>
                    <li class="flex items-center justify-between gap-3 px-4 py-2">
                        <span class="min-w-0">
                            <span class="block truncate text-[0.8125rem] font-medium text-slate-800 dark:text-slate-100">
                                <?= e($f['fornecedor']) ?>
                            </span>
                            <span class="block text-xs text-slate-500 dark:text-slate-400">
                                <?= (int) $f['parcelas'] ?> parcela<?= (int) $f['parcelas'] === 1 ? '' : 's' ?>
                                <?php if ((float) $f['aberto'] > 0.005 && (float) $f['aberto'] < (float) $f['total']): ?>
                                    · <?= e(dinheiro_br((float) $f['aberto'], false)) ?> em aberto
                                <?php elseif ((float) $f['aberto'] < 0.005): ?>
                                    · pago
                                <?php endif; ?>
                            </span>
                        </span>
                        <span class="num shrink-0 text-[0.8125rem] font-semibold text-slate-800 dark:text-slate-100">
                            <?= e(dinheiro_br((float) $f['total'], false)) ?>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>
