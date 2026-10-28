<?php
/**
 * Lista das notas de OPME.
 *
 * A planilha listava parcelas; aqui a linha é a NOTA, com o andamento das
 * parcelas ao lado. Quem procura por vencimento tem a tela própria.
 *
 * @var array $notas
 * @var int   $total
 * @var int   $pagina
 * @var int   $porPagina
 * @var array $totais
 * @var array $panorama
 * @var array $fornecedores
 * @var array $anos
 * @var array $filtro
 */
declare(strict_types=1);

$podeCriar   = Auth::temPermissao('opme', 'pode_criar');
$podeEditar  = Auth::temPermissao('opme', 'pode_editar');
$podeExcluir = Auth::temPermissao('opme', 'pode_excluir');
$hoje        = date('Y-m-d');
$temFiltro   = $filtro['busca'] !== '' || $filtro['fornecedor_id'] || $filtro['situacao']
               || $filtro['ano'] || $filtro['em_aberto'];
?>

<div class="mb-5 flex flex-wrap items-end justify-between gap-3">
    <div>
        <h1 class="titulo-pagina">OPME</h1>
        <p class="sub-pagina">Órteses, próteses e materiais especiais — notas e parcelas</p>
    </div>

    <div class="flex flex-wrap items-center gap-2">
        <a href="<?= url('opme', 'vencimentos') ?>" class="btn btn-outline">
            <?= Icon::svg('calendario', 'w-4 h-4') ?> Vencimentos do mês
        </a>
        <a href="<?= url('opme', 'fornecedores') ?>" class="btn btn-outline">
            <?= Icon::svg('setores', 'w-4 h-4') ?> Fornecedores
        </a>
        <?php if ($podeCriar): ?>
            <a href="<?= url('opme', 'novo') ?>" class="btn btn-primary">
                <?= Icon::svg('novo', 'w-4 h-4') ?> Nova nota
            </a>
        <?php endif; ?>
    </div>
</div>

<div class="card mb-4">
    <div class="card-corpo grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div>
            <p class="rotulo-num">Notas ativas</p>
            <p class="valor-num"><?= numero((int) ($panorama['notas'] ?? 0)) ?></p>
            <p class="ajuda">sem contar canceladas e doações</p>
        </div>
        <div>
            <p class="rotulo-num">A pagar</p>
            <p class="valor-num"><?= e(dinheiro_br((float) ($panorama['a_pagar'] ?? 0))) ?></p>
            <p class="ajuda">parcelas ainda sem baixa</p>
        </div>
        <div>
            <p class="rotulo-num">Vencido</p>
            <p class="valor-num <?= ($panorama['vencido'] ?? 0) > 0 ? 'text-rose-600 dark:text-rose-400' : '' ?>">
                <?= e(dinheiro_br((float) ($panorama['vencido'] ?? 0))) ?>
            </p>
            <p class="ajuda">
                <?= numero((int) ($panorama['qtd_vencida'] ?? 0)) ?> parcela<?= (int) ($panorama['qtd_vencida'] ?? 0) === 1 ? '' : 's' ?>
                com vencimento passado
            </p>
        </div>
        <div>
            <p class="rotulo-num">No filtro atual</p>
            <p class="valor-num"><?= e(dinheiro_br((float) ($totais['valor_total'] ?? 0))) ?></p>
            <p class="ajuda">
                <?= numero((int) ($totais['notas'] ?? 0)) ?> nota<?= (int) ($totais['notas'] ?? 0) === 1 ? '' : 's' ?>
                · <?= e(dinheiro_br((float) ($totais['a_pagar'] ?? 0))) ?> em aberto
            </p>
        </div>
    </div>
</div>

<div class="card overflow-hidden">
    <form method="get" action="<?= BASE_URL ?>/" class="toolbar">
        <input type="hidden" name="mod" value="opme">
        <input type="hidden" name="action" value="lista">

        <div class="campo-icone w-full sm:w-72">
            <?= Icon::svg('buscar') ?>
            <input type="search" name="q" value="<?= e($filtro['busca']) ?>" class="input"
                   placeholder="Paciente, NF, médico ou fornecedor…" aria-label="Buscar notas">
        </div>

        <select name="fornecedor" class="select w-auto" aria-label="Fornecedor">
            <option value="">Todos os fornecedores</option>
            <?php foreach ($fornecedores as $f): ?>
                <option value="<?= (int) $f['id'] ?>" <?= $filtro['fornecedor_id'] === (int) $f['id'] ? 'selected' : '' ?>>
                    <?= e($f['nome']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select name="situacao" class="select w-auto" aria-label="Situação">
            <option value="">Todas as situações</option>
            <?php foreach (OpmeModel::SITUACOES as $valor => $rotulo): ?>
                <option value="<?= e($valor) ?>" <?= $filtro['situacao'] === $valor ? 'selected' : '' ?>>
                    <?= e($rotulo) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <?php if ($anos): ?>
            <select name="ano" class="select w-auto" aria-label="Ano da cirurgia">
                <option value="">Todos os anos</option>
                <?php foreach ($anos as $a): ?>
                    <option value="<?= (int) $a ?>" <?= $filtro['ano'] === (int) $a ? 'selected' : '' ?>>
                        Cirurgia em <?= (int) $a ?>
                    </option>
                <?php endforeach; ?>
            </select>
        <?php endif; ?>

        <label class="flex cursor-pointer items-center gap-1.5 text-xs text-slate-600 dark:text-slate-400">
            <input type="checkbox" name="aberto" value="1" class="check" <?= $filtro['em_aberto'] ? 'checked' : '' ?>>
            Só com parcela em aberto
        </label>

        <button type="submit" class="btn btn-outline">Filtrar</button>

        <?php if ($temFiltro): ?>
            <a href="<?= url('opme', 'lista') ?>" class="btn btn-ghost">Limpar</a>
        <?php endif; ?>

        <span class="ml-auto text-xs text-slate-500 dark:text-slate-400">
            <?= numero($total) ?> nota<?= $total === 1 ? '' : 's' ?>
        </span>
    </form>

    <?php if (empty($notas)): ?>
        <div class="vazio">
            <span class="vazio-icone"><?= Icon::svg('opme', 'w-5 h-5') ?></span>
            <p class="vazio-titulo"><?= $temFiltro ? 'Nenhuma nota encontrada' : 'Nenhuma nota lançada' ?></p>
            <p class="vazio-texto">
                <?= $temFiltro
                    ? 'Nenhum resultado com esses filtros. Tente limpar e buscar de novo.'
                    : 'Lance a primeira nota de material: paciente, fornecedor, valor e as parcelas.' ?>
            </p>
            <?php if ($podeCriar && !$temFiltro): ?>
                <a href="<?= url('opme', 'novo') ?>" class="btn btn-primary btn-sm mt-1">
                    <?= Icon::svg('novo', 'w-3.5 h-3.5') ?> Nova nota
                </a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="tabela">
                <thead>
                    <tr>
                        <th>Paciente</th>
                        <th class="w-40">Fornecedor</th>
                        <th class="w-28">Nota</th>
                        <th class="w-28">Cirurgia</th>
                        <th class="w-32 text-right">Valor</th>
                        <th class="w-44">Parcelas</th>
                        <th class="w-24 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($notas as $n): ?>
                        <?php
                        $soma    = (float) $n['soma_parcelas'];
                        $pago    = (float) $n['pago'];
                        $aberto  = max(0, $soma - $pago);
                        $pct     = $soma > 0 ? (int) round($pago / $soma * 100) : 0;
                        $atraso  = $n['proximo_venc'] && $n['proximo_venc'] < $hoje && $n['situacao'] === 'normal';
                        ?>
                        <tr>
                            <td>
                                <a href="<?= url('opme', 'ver', ['id' => (int) $n['id']]) ?>"
                                   class="celula-principal text-marine-700 hover:underline dark:text-marine-300">
                                    <?= e($n['paciente']) ?>
                                </a>
                                <span class="block text-xs text-slate-500 dark:text-slate-400">
                                    <?= $n['medico'] ? e($n['medico']) : 'sem médico informado' ?>
                                    <?= $n['hospital'] ? ' · ' . e($n['hospital']) : '' ?>
                                </span>
                            </td>

                            <td class="celula-suave"><?= e($n['fornecedor']) ?></td>

                            <td class="celula-suave mono text-xs">
                                <?= e($n['numero_nf']) ?>
                                <?php if ($n['situacao'] !== 'normal'): ?>
                                    <span class="badge <?= $n['situacao'] === 'cancelada' ? 'badge-rosa' : 'badge-violeta' ?> mt-1">
                                        <?= e(OpmeModel::SITUACOES[$n['situacao']]) ?>
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td class="celula-suave text-xs"><?= e(data_br($n['data_cirurgia'])) ?></td>

                            <td class="num text-right">
                                <span class="celula-principal"><?= e(dinheiro_br((float) $n['valor_total'], false)) ?></span>
                                <?php if ($aberto > 0.005 && $n['situacao'] === 'normal'): ?>
                                    <span class="block text-xs <?= $atraso ? 'font-semibold text-rose-600 dark:text-rose-400' : 'text-slate-500 dark:text-slate-400' ?>">
                                        <?= e(dinheiro_br($aberto, false)) ?> em aberto
                                    </span>
                                <?php elseif ($soma > 0): ?>
                                    <span class="block text-xs text-emerald-600 dark:text-emerald-400">quitada</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php if ((int) $n['parcelas'] === 0): ?>
                                    <span class="text-xs text-slate-400 dark:text-slate-500">sem parcelas</span>
                                <?php else: ?>
                                    <span class="mb-1 block text-xs text-slate-600 dark:text-slate-400">
                                        <?= (int) $n['parcelas'] ?> de <?= (int) $n['total_parcelas'] ?> lançadas
                                        <?php if ($n['proximo_venc']): ?>
                                            · próxima <?= e(data_br($n['proximo_venc'])) ?>
                                        <?php endif; ?>
                                    </span>
                                    <span class="barra <?= $pct >= 100 ? 'barra-cheia' : '' ?>">
                                        <span style="width: <?= max(2, min(100, $pct)) ?>%"></span>
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <div class="flex items-center justify-end gap-0.5">
                                    <a href="<?= url('opme', 'ver', ['id' => (int) $n['id']]) ?>"
                                       class="btn-acao" title="Abrir" aria-label="Abrir a nota de <?= e($n['paciente']) ?>">
                                        <?= Icon::svg('ver', 'w-4 h-4') ?>
                                    </a>

                                    <?php if ($podeEditar): ?>
                                        <a href="<?= url('opme', 'editar', ['id' => (int) $n['id']]) ?>"
                                           class="btn-acao" title="Editar" aria-label="Editar a nota de <?= e($n['paciente']) ?>">
                                            <?= Icon::svg('editar', 'w-4 h-4') ?>
                                        </a>
                                    <?php endif; ?>

                                    <?php if ($podeExcluir): ?>
                                        <form method="post" action="<?= url('opme', 'excluir') ?>" class="inline"
                                              data-titulo="Excluir nota"
                                              data-texto="A NF <?= e($n['numero_nf']) ?> de <?= e($n['paciente']) ?> e as <?= (int) $n['parcelas'] ?> parcelas dela serão removidas.">
                                            <?= Csrf::campo() ?>
                                            <input type="hidden" name="id" value="<?= (int) $n['id'] ?>">
                                            <button type="button" @click="abrir" class="btn-acao btn-acao-perigo"
                                                    title="Excluir" aria-label="Excluir a nota de <?= e($n['paciente']) ?>">
                                                <?= Icon::svg('excluir', 'w-4 h-4') ?>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?= Paginacao::barra([
            'modulo'    => 'opme',
            'pagina'    => $pagina,
            'total'     => $total,
            'porPagina' => $porPagina,
            'padrao'    => 50,
            'rotulo'    => ['nota', 'notas'],
            'base'      => array_filter([
                'q'          => $filtro['busca'],
                'fornecedor' => $filtro['fornecedor_id'] ?: null,
                'situacao'   => $filtro['situacao'] ?: null,
                'ano'        => $filtro['ano'] ?: null,
                'aberto'     => $filtro['em_aberto'] ? 1 : null,
            ]),
        ]) ?>
    <?php endif; ?>
</div>
