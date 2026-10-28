<?php
/**
 * Vendas — as propostas, da negociação ao contrato.
 *
 * A linha é a proposta, e a coluna que importa é o vendedor: é por ela que
 * a coordenação acompanha quem está vendendo.
 *
 * @var array $propostas, $panorama, $vendedores, $empresas, $filtro
 * @var array|false $meu
 * @var int   $trava, $total, $pagina, $porPagina
 */
declare(strict_types=1);

$podeCriar   = Auth::temPermissao('vendas', 'pode_criar');
$podeEditar  = Auth::temPermissao('vendas', 'pode_editar');
$podeExcluir = Auth::temPermissao('vendas', 'pode_excluir');

$cores = [
    'em_negociacao' => 'badge-ambar',
    'fechada'       => 'badge-verde',
    'perdida'       => 'badge-rosa',
];

$temFiltro = $filtro['busca'] !== '' || $filtro['status'] !== '' || $filtro['de'] || $filtro['ate']
             || (!$trava && ($filtro['vendedor_id'] || $filtro['empresa_id']));

$base = array_filter([
    'q'        => $filtro['busca'] !== '' ? $filtro['busca'] : null,
    'vendedor' => !$trava ? ($filtro['vendedor_id'] ?: null) : null,
    'empresa'  => !$trava ? ($filtro['empresa_id'] ?: null) : null,
    'status'   => $filtro['status'] !== '' ? $filtro['status'] : null,
    'de'       => $filtro['de'] ?: null,
    'ate'      => $filtro['ate'] ?: null,
]);
?>

<div class="mb-5 flex flex-wrap items-end justify-between gap-3">
    <div>
        <h1 class="titulo-pagina">Vendas</h1>
        <p class="sub-pagina">
            <?php if ($trava && $meu): ?>
                Suas propostas — <?= e($meu['nome']) ?>
            <?php else: ?>
                Propostas de plano, por vendedor
            <?php endif; ?>
        </p>
    </div>

    <div class="flex flex-wrap items-center gap-2">
        <a href="<?= url('vendas', 'painel') ?>" class="btn btn-outline">
            <?= Icon::svg('painel', 'w-4 h-4') ?> Desempenho
        </a>
        <?php if (!$trava): ?>
            <a href="<?= url('vendas', 'vendedores') ?>" class="btn btn-outline">
                <?= Icon::svg('usuarios', 'w-4 h-4') ?> Vendedores
            </a>
        <?php endif; ?>
        <?php if ($podeCriar): ?>
            <a href="<?= url('vendas', 'novo') ?>" class="btn btn-primary">
                <?= Icon::svg('novo', 'w-4 h-4') ?> Nova proposta
            </a>
        <?php endif; ?>
    </div>
</div>

<?php if ($trava): ?>
    <div class="mb-4 flex items-start gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-[0.8125rem] text-slate-600 dark:border-slate-800 dark:bg-slate-900/60 dark:text-slate-400">
        <?= Icon::svg('permissoes', 'w-4 h-4 mt-0.5 shrink-0') ?>
        <span>Você está vendo apenas as suas vendas. A coordenação enxerga as de todos.</span>
    </div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-corpo grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <a href="<?= url('vendas', 'lista', ['status' => 'em_negociacao']) ?>"
           class="rounded-lg p-1 transition-colors hover:bg-slate-50 dark:hover:bg-slate-800/60">
            <p class="rotulo-num">Em negociação</p>
            <p class="valor-num text-amber-600 dark:text-amber-400">
                <?= numero((int) ($panorama['em_negociacao'] ?? 0)) ?>
            </p>
            <p class="ajuda">propostas abertas</p>
        </a>

        <a href="<?= url('vendas', 'lista', ['status' => 'fechada']) ?>"
           class="rounded-lg p-1 transition-colors hover:bg-slate-50 dark:hover:bg-slate-800/60">
            <p class="rotulo-num">Fechadas</p>
            <p class="valor-num"><?= numero((int) ($panorama['fechadas'] ?? 0)) ?></p>
            <p class="ajuda"><?= numero((int) ($panorama['fechadas_mes'] ?? 0)) ?> neste mês</p>
        </a>

        <div class="rounded-lg p-1">
            <p class="rotulo-num">Vidas vendidas</p>
            <p class="valor-num"><?= numero((int) ($panorama['vidas'] ?? 0)) ?></p>
            <p class="ajuda">previstas nas propostas fechadas</p>
        </div>

        <a href="<?= url('vendas', 'lista', ['status' => 'perdida']) ?>"
           class="rounded-lg p-1 transition-colors hover:bg-slate-50 dark:hover:bg-slate-800/60">
            <p class="rotulo-num">Perdidas</p>
            <p class="valor-num"><?= numero((int) ($panorama['perdidas'] ?? 0)) ?></p>
            <p class="ajuda">o motivo fica registrado</p>
        </a>
    </div>
</div>

<div class="card overflow-hidden">
    <form method="get" action="<?= BASE_URL ?>/" class="toolbar">
        <input type="hidden" name="mod" value="vendas">
        <input type="hidden" name="action" value="lista">

        <div class="campo-icone w-full sm:w-72">
            <?= Icon::svg('buscar') ?>
            <input type="search" name="q" value="<?= e($filtro['busca']) ?>" class="input"
                   placeholder="Número, titular, CPF ou vendedor…" aria-label="Buscar propostas">
        </div>

        <?php if (!$trava): ?>
            <select name="vendedor" class="select w-auto" aria-label="Vendedor">
                <option value="">Todos os vendedores</option>
                <?php foreach ($vendedores as $v): ?>
                    <option value="<?= (int) $v['id'] ?>" <?= $filtro['vendedor_id'] === (int) $v['id'] ? 'selected' : '' ?>>
                        <?= e($v['nome']) ?> (<?= e($v['empresa']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="empresa" class="select w-auto" aria-label="Empresa parceira">
                <option value="">Todas as empresas</option>
                <?php foreach ($empresas as $emp): ?>
                    <option value="<?= (int) $emp['id'] ?>" <?= $filtro['empresa_id'] === (int) $emp['id'] ? 'selected' : '' ?>>
                        <?= e($emp['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        <?php endif; ?>

        <select name="status" class="select w-auto" aria-label="Situação">
            <option value="">Todas as situações</option>
            <?php foreach (VendasModel::STATUS as $chave => $rotulo): ?>
                <option value="<?= e($chave) ?>" <?= $filtro['status'] === $chave ? 'selected' : '' ?>>
                    <?= e($rotulo) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label class="flex items-center gap-1.5 text-xs text-slate-600 dark:text-slate-400">
            <span class="whitespace-nowrap">Propostas de</span>
            <input type="date" name="de" value="<?= e((string) $filtro['de']) ?>" class="input input-sm w-auto"
                   aria-label="Propostas a partir de">
            <span>até</span>
            <input type="date" name="ate" value="<?= e((string) $filtro['ate']) ?>" class="input input-sm w-auto"
                   aria-label="Propostas até">
        </label>

        <button type="submit" class="btn btn-outline">Filtrar</button>

        <?php if ($temFiltro): ?>
            <a href="<?= url('vendas', 'lista') ?>" class="btn btn-ghost">Limpar</a>
        <?php endif; ?>
    </form>

    <?php if (empty($propostas)): ?>
        <div class="vazio">
            <span class="vazio-icone"><?= Icon::svg('vendas', 'w-5 h-5') ?></span>
            <p class="vazio-titulo"><?= $temFiltro ? 'Nenhuma proposta encontrada' : 'Nenhuma proposta registrada' ?></p>
            <p class="vazio-texto">
                <?= $temFiltro
                    ? 'Nenhum resultado com esses filtros. Tente limpar e buscar de novo.'
                    : 'A proposta é a venda enquanto ela ainda é promessa. Fechada, ela cria o contrato no Cadastro sem ninguém redigitar.' ?>
            </p>
            <?php if ($podeCriar && !$temFiltro): ?>
                <a href="<?= url('vendas', 'novo') ?>" class="btn btn-primary btn-sm mt-1">
                    <?= Icon::svg('novo', 'w-3.5 h-3.5') ?> Registrar a primeira proposta
                </a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="tabela">
                <thead>
                    <tr>
                        <th class="w-24">Proposta</th>
                        <th>Titular</th>
                        <th class="w-44">Vendedor</th>
                        <th class="w-28">Plano</th>
                        <th class="w-16 text-right">Vidas</th>
                        <th class="w-36">Situação</th>
                        <th class="w-24 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($propostas as $p): ?>
                        <tr>
                            <td>
                                <a href="<?= url('vendas', 'ver', ['id' => (int) $p['id']]) ?>"
                                   class="mono text-xs font-medium text-marine-700 hover:underline dark:text-marine-300">
                                    <?= e($p['numero']) ?>
                                </a>
                                <span class="block text-[0.6875rem] text-slate-400 dark:text-slate-500">
                                    <?= e(data_br($p['data_proposta'])) ?>
                                </span>
                            </td>

                            <td>
                                <a href="<?= url('vendas', 'ver', ['id' => (int) $p['id']]) ?>"
                                   class="celula-principal block hover:underline"><?= e($p['titular_nome']) ?></a>
                                <span class="block text-xs text-slate-500 dark:text-slate-400">
                                    <?php if ($p['titular_cpf']): ?>
                                        <span class="mono"><?= e(formatar_cpf((string) $p['titular_cpf'])) ?></span>
                                    <?php endif; ?>
                                    <?php if ($p['titular_categoria']): ?>
                                        <?= $p['titular_cpf'] ? ' · ' : '' ?><?= e(Elegibilidade::CATEGORIAS[$p['titular_categoria']] ?? '') ?>
                                    <?php endif; ?>
                                </span>
                            </td>

                            <td class="celula-suave text-xs">
                                <?= e($p['vendedor']) ?>
                                <span class="block text-[0.6875rem] text-slate-400 dark:text-slate-500">
                                    <?= e($p['empresa']) ?>
                                </span>
                            </td>

                            <td class="celula-suave text-xs">
                                <?= e(Elegibilidade::TIPOS_CONTRATO[$p['tipo']] ?? $p['tipo']) ?>
                            </td>

                            <td class="text-right text-xs text-slate-600 dark:text-slate-400">
                                <?= numero((int) $p['vidas']) ?>
                            </td>

                            <td>
                                <span class="badge whitespace-nowrap <?= $cores[$p['status']] ?? 'badge-cinza' ?>">
                                    <?= e(VendasModel::STATUS[$p['status']] ?? $p['status']) ?>
                                </span>
                                <?php if ($p['status'] === 'fechada' && $p['contrato_id']): ?>
                                    <a href="<?= url('cadastro', 'ver', ['id' => (int) $p['contrato_id']]) ?>"
                                       class="mt-0.5 block text-[0.6875rem] text-marine-700 hover:underline dark:text-marine-300">
                                        abrir o contrato →
                                    </a>
                                <?php elseif ($p['status'] === 'perdida' && $p['motivo_perda']): ?>
                                    <span class="mt-0.5 block text-[0.6875rem] text-slate-500 dark:text-slate-400">
                                        <?= e($p['motivo_perda']) ?>
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <div class="flex items-center justify-end gap-0.5">
                                    <?php if ($podeEditar && $p['status'] !== 'fechada'): ?>
                                        <a href="<?= url('vendas', 'editar', ['id' => (int) $p['id']]) ?>"
                                           class="btn-acao" title="Editar" aria-label="Editar a proposta <?= e($p['numero']) ?>">
                                            <?= Icon::svg('editar', 'w-4 h-4') ?>
                                        </a>
                                    <?php endif; ?>

                                    <?php if ($podeExcluir && $p['status'] !== 'fechada'): ?>
                                        <form method="post" action="<?= url('vendas', 'excluir') ?>" class="inline"
                                              data-titulo="Excluir proposta"
                                              data-texto="A proposta <?= e($p['numero']) ?>, de <?= e($p['titular_nome']) ?>, será removida. Proposta perdida costuma valer mais guardada que apagada — o motivo ensina a corrigir o discurso de venda.">
                                            <?= Csrf::campo() ?>
                                            <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                                            <button type="button" @click="abrir" class="btn-acao btn-acao-perigo"
                                                    title="Excluir" aria-label="Excluir">
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
            'modulo'    => 'vendas',
            'pagina'    => $pagina,
            'total'     => $total,
            'porPagina' => $porPagina,
            'padrao'    => 50,
            'rotulo'    => ['proposta', 'propostas'],
            'base'      => $base,
        ]) ?>
    <?php endif; ?>
</div>
