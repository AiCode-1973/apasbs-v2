<?php
/**
 * Códigos de exame por convênio.
 *
 * @var array $exames
 * @var int   $total, $pagina, $porPagina
 * @var array $panorama, $convenios, $filtro
 */
declare(strict_types=1);

$podeCriar   = Auth::temPermissao('codigos', 'pode_criar');
$podeEditar  = Auth::temPermissao('codigos', 'pode_editar');
$podeExcluir = Auth::temPermissao('codigos', 'pode_excluir');

$temFiltro = $filtro['busca'] !== '' || $filtro['convenio_id']
             || $filtro['so_ativos'] || $filtro['sem_codigo'];

$base = array_filter([
    'q'        => $filtro['busca'] !== '' ? $filtro['busca'] : null,
    'convenio' => $filtro['convenio_id'] ?: null,
    'ativos'   => $filtro['so_ativos'] ? 1 : null,
    'sem'      => $filtro['sem_codigo'] ? 1 : null,
]);

// O convênio vira cabeçalho de bloco: a lista vem ordenada por ele, e
// repetir o nome em toda linha só gastaria uma coluna.
$convenioAnterior = null;
?>

<div class="mb-5 flex flex-wrap items-end justify-between gap-3">
    <div>
        <h1 class="titulo-pagina">Códigos de exames</h1>
        <p class="sub-pagina">Com que código cada convênio fatura cada exame</p>
    </div>

    <?php if ($podeCriar): ?>
        <a href="<?= url('codigos', 'novo', $filtro['convenio_id'] ? ['convenio' => $filtro['convenio_id']] : []) ?>"
           class="btn btn-primary">
            <?= Icon::svg('novo', 'w-4 h-4') ?> Novo exame
        </a>
    <?php endif; ?>
</div>

<div class="card mb-4">
    <div class="card-corpo grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div>
            <p class="rotulo-num">Exames ativos</p>
            <p class="valor-num"><?= numero((int) $panorama['exames']) ?></p>
            <p class="ajuda">combinações de convênio e exame</p>
        </div>
        <div>
            <p class="rotulo-num">Códigos</p>
            <p class="valor-num"><?= numero((int) $panorama['codigos']) ?></p>
            <p class="ajuda">somando todos os exames</p>
        </div>
        <div>
            <p class="rotulo-num">Convênios</p>
            <p class="valor-num"><?= numero((int) $panorama['convenios']) ?></p>
            <p class="ajuda">com pelo menos um exame</p>
        </div>
        <div>
            <p class="rotulo-num">Sem código</p>
            <p class="valor-num <?= $panorama['sem_codigo'] > 0 ? 'text-amber-600 dark:text-amber-400' : '' ?>">
                <?= numero((int) $panorama['sem_codigo']) ?>
            </p>
            <p class="ajuda">
                <?php if ($panorama['sem_codigo'] > 0): ?>
                    <a href="<?= url('codigos', 'lista', ['sem' => 1]) ?>"
                       class="text-marine-700 hover:underline dark:text-marine-300">cadastrados pela metade →</a>
                <?php else: ?>
                    todos têm ao menos um
                <?php endif; ?>
            </p>
        </div>
    </div>
</div>

<div class="card overflow-hidden">
    <form method="get" action="<?= BASE_URL ?>/" class="toolbar">
        <input type="hidden" name="mod" value="codigos">
        <input type="hidden" name="action" value="lista">

        <div class="campo-icone w-full sm:w-80">
            <?= Icon::svg('buscar') ?>
            <input type="search" name="q" value="<?= e($filtro['busca']) ?>" class="input"
                   placeholder="Descrição, código ou nome do procedimento…"
                   aria-label="Buscar exames e códigos">
        </div>

        <select name="convenio" class="select w-auto" aria-label="Convênio">
            <option value="">Todos os convênios</option>
            <?php foreach ($convenios as $c): ?>
                <option value="<?= (int) $c['id'] ?>" <?= $filtro['convenio_id'] === (int) $c['id'] ? 'selected' : '' ?>>
                    <?= e($c['nome']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label class="flex cursor-pointer items-center gap-1.5 text-xs text-slate-600 dark:text-slate-400">
            <input type="checkbox" name="ativos" value="1" class="check" <?= $filtro['so_ativos'] ? 'checked' : '' ?>>
            Só os ativos
        </label>

        <label class="flex cursor-pointer items-center gap-1.5 text-xs text-slate-600 dark:text-slate-400">
            <input type="checkbox" name="sem" value="1" class="check" <?= $filtro['sem_codigo'] ? 'checked' : '' ?>>
            Só os sem código
        </label>

        <button type="submit" class="btn btn-outline">Filtrar</button>

        <?php if ($temFiltro): ?>
            <a href="<?= url('codigos', 'lista') ?>" class="btn btn-ghost">Limpar</a>
        <?php endif; ?>
    </form>

    <?php if (empty($exames)): ?>
        <div class="vazio">
            <span class="vazio-icone"><?= Icon::svg('codigos', 'w-5 h-5') ?></span>
            <p class="vazio-titulo"><?= $temFiltro ? 'Nenhum exame encontrado' : 'Nenhum exame cadastrado' ?></p>
            <p class="vazio-texto">
                <?= $temFiltro
                    ? 'Nenhum resultado com esses filtros. Tente limpar e buscar de novo.'
                    : 'Cadastre o primeiro: escolha o convênio, escreva como ele chama o exame e acrescente os códigos.' ?>
            </p>
            <?php if ($podeCriar && !$temFiltro): ?>
                <a href="<?= url('codigos', 'novo') ?>" class="btn btn-primary btn-sm mt-1">
                    <?= Icon::svg('novo', 'w-3.5 h-3.5') ?> Novo exame
                </a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="tabela">
                <thead>
                    <tr>
                        <th>Exame</th>
                        <th class="w-[28rem]">Códigos</th>
                        <th class="w-24">Situação</th>
                        <th class="w-24 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($exames as $ex): ?>
                        <?php if ($ex['convenio'] !== $convenioAnterior): ?>
                            <?php $convenioAnterior = $ex['convenio']; ?>
                            <tr class="bg-slate-50 dark:bg-slate-800/60">
                                <td colspan="4" class="py-1.5">
                                    <span class="text-[0.6875rem] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                        <?= e($ex['convenio']) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endif; ?>

                        <tr>
                            <td>
                                <a href="<?= url('codigos', 'ver', ['id' => (int) $ex['id']]) ?>"
                                   class="celula-principal text-marine-700 hover:underline dark:text-marine-300">
                                    <?= e($ex['descricao']) ?>
                                </a>
                                <?php if ($ex['observacao']): ?>
                                    <span class="block text-xs text-slate-500 dark:text-slate-400">
                                        <?= e($ex['observacao']) ?>
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php if (empty($ex['codigos'])): ?>
                                    <span class="text-xs text-amber-700 dark:text-amber-400">
                                        nenhum código ainda
                                    </span>
                                <?php else: ?>
                                    <div class="flex flex-wrap gap-1">
                                        <?php foreach ($ex['codigos'] as $c): ?>
                                            <span class="badge badge-cinza mono"
                                                  title="<?= e((string) ($c['termo'] ?? '')) ?>">
                                                <?= e($c['codigo']) ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php /* A descrição do primeiro código dá o contexto sem
                                             encher a coluna com todas elas. */ ?>
                                    <?php if (!empty($ex['codigos'][0]['termo'])): ?>
                                        <span class="mt-1 block truncate text-[0.6875rem] text-slate-500 dark:text-slate-400">
                                            <?= e($ex['codigos'][0]['termo']) ?>
                                            <?php if (count($ex['codigos']) > 1): ?>
                                                <span class="text-slate-400 dark:text-slate-600">
                                                    e mais <?= count($ex['codigos']) - 1 ?>
                                                </span>
                                            <?php endif; ?>
                                        </span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>

                            <td>
                                <span class="badge badge-ponto <?= $ex['ativo'] ? 'badge-verde' : 'badge-cinza' ?>">
                                    <?= $ex['ativo'] ? 'Ativo' : 'Inativo' ?>
                                </span>
                            </td>

                            <td>
                                <div class="flex items-center justify-end gap-0.5">
                                    <a href="<?= url('codigos', 'ver', ['id' => (int) $ex['id']]) ?>"
                                       class="btn-acao" title="Abrir"
                                       aria-label="Abrir <?= e($ex['descricao']) ?>">
                                        <?= Icon::svg('ver', 'w-4 h-4') ?>
                                    </a>

                                    <?php if ($podeEditar): ?>
                                        <a href="<?= url('codigos', 'editar', ['id' => (int) $ex['id']]) ?>"
                                           class="btn-acao" title="Editar"
                                           aria-label="Editar <?= e($ex['descricao']) ?>">
                                            <?= Icon::svg('editar', 'w-4 h-4') ?>
                                        </a>
                                    <?php endif; ?>

                                    <?php if ($podeExcluir): ?>
                                        <form method="post" action="<?= url('codigos', 'excluir') ?>" class="inline"
                                              data-titulo="Excluir exame"
                                              data-texto="&quot;<?= e($ex['descricao']) ?>&quot; do convênio <?= e($ex['convenio']) ?> e os <?= count($ex['codigos']) ?> código(s) dele serão removidos.">
                                            <?= Csrf::campo() ?>
                                            <input type="hidden" name="id" value="<?= (int) $ex['id'] ?>">
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
            'modulo'    => 'codigos',
            'pagina'    => $pagina,
            'total'     => $total,
            'porPagina' => $porPagina,
            'padrao'    => 50,
            'rotulo'    => ['exame', 'exames'],
            'base'      => $base,
        ]) ?>
    <?php endif; ?>
</div>
