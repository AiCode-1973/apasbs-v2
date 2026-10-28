<?php
/** @var array $fechamentos */
/** @var array $anos */
/** @var int   $anoFiltro */
/** @var int   $totalPeriodo */
declare(strict_types=1);

$podeCriar   = Auth::temPermissao('fechamentos', 'pode_criar');
$podeEditar  = Auth::temPermissao('fechamentos', 'pode_editar');
$podeExcluir = Auth::temPermissao('fechamentos', 'pode_excluir');

$qtd   = count($fechamentos);
$media = $qtd > 0 ? (int) round($totalPeriodo / $qtd) : 0;
?>

<div class="mb-5 flex flex-wrap items-end justify-between gap-3">
    <div>
        <h1 class="titulo-pagina">Fechamentos mensais</h1>
        <p class="sub-pagina">Total de atendimentos consolidado a cada mês pela Central de Agendamento</p>
    </div>

    <?php if ($podeCriar): ?>
        <a href="<?= url('fechamentos', 'novo') ?>" class="btn btn-primary">
            <?= Icon::svg('novo', 'w-4 h-4') ?> Novo fechamento
        </a>
    <?php endif; ?>
</div>

<?php if ($qtd > 0): ?>
    <div class="card mb-4 grid divide-y divide-slate-200 sm:grid-cols-3 sm:divide-x sm:divide-y-0 dark:divide-slate-800">
        <div class="px-4 py-3.5">
            <span class="block text-xl font-semibold leading-none text-marine-900 dark:text-white"><?= numero($totalPeriodo) ?></span>
            <span class="mt-1 block text-xs text-slate-500 dark:text-slate-400">
                Atendimentos <?= $anoFiltro > 0 ? 'em ' . $anoFiltro : 'no total' ?>
            </span>
        </div>
        <div class="px-4 py-3.5">
            <span class="block text-xl font-semibold leading-none text-marine-900 dark:text-white"><?= numero($media) ?></span>
            <span class="mt-1 block text-xs text-slate-500 dark:text-slate-400">Média por mês</span>
        </div>
        <div class="px-4 py-3.5">
            <span class="block text-xl font-semibold leading-none text-marine-900 dark:text-white"><?= numero($qtd) ?></span>
            <span class="mt-1 block text-xs text-slate-500 dark:text-slate-400">
                Mês<?= $qtd === 1 ? '' : 'es' ?> lançado<?= $qtd === 1 ? '' : 's' ?>
            </span>
        </div>
    </div>
<?php endif; ?>

<div class="card overflow-hidden">
    <?php if ($anos): ?>
        <form method="get" action="<?= BASE_URL ?>/" class="toolbar">
            <input type="hidden" name="mod" value="fechamentos">
            <input type="hidden" name="action" value="lista">

            <label class="label mb-0 shrink-0" for="ano">Ano</label>
            <select name="ano" id="ano" class="select w-full sm:w-40">
                <option value="">Todos os anos</option>
                <?php foreach ($anos as $a): ?>
                    <option value="<?= $a ?>" <?= $anoFiltro === $a ? 'selected' : '' ?>><?= $a ?></option>
                <?php endforeach; ?>
            </select>

            <button type="submit" class="btn btn-outline">Filtrar</button>

            <?php if ($anoFiltro > 0): ?>
                <a href="<?= url('fechamentos', 'lista') ?>" class="btn btn-ghost">Limpar</a>
            <?php endif; ?>

            <span class="ml-auto text-xs text-slate-500 dark:text-slate-400">
                <?= numero($qtd) ?> registro<?= $qtd === 1 ? '' : 's' ?>
            </span>
        </form>
    <?php endif; ?>

    <?php if (empty($fechamentos)): ?>
        <div class="vazio">
            <span class="vazio-icone"><?= Icon::svg('fechamentos', 'w-5 h-5') ?></span>
            <p class="vazio-titulo">
                <?= $anoFiltro > 0 ? 'Nenhum fechamento em ' . $anoFiltro : 'Nenhum fechamento lançado' ?>
            </p>
            <p class="vazio-texto">
                Registre o total de atendimentos de cada mês para acompanhar a evolução da central.
            </p>
            <?php if ($podeCriar && $anoFiltro === 0): ?>
                <a href="<?= url('fechamentos', 'novo') ?>" class="btn btn-primary btn-sm mt-1">
                    <?= Icon::svg('novo', 'w-3.5 h-3.5') ?> Novo fechamento
                </a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="tabela">
                <thead>
                    <tr>
                        <th class="w-48">Competência</th>
                        <th class="w-40 text-right">Atendimentos</th>
                        <th class="w-40">Variação</th>
                        <th class="w-48">Detalhamento</th>
                        <th>Observação</th>
                        <th class="w-44">Último lançamento</th>
                        <th class="w-24 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($fechamentos as $f): ?>
                        <?php
                        $ano   = (int) $f['ano'];
                        $mes   = (int) $f['mes'];
                        $total = (int) $f['total_atendimentos'];
                        $ant   = $f['total_anterior'] !== null ? (int) $f['total_anterior'] : null;

                        $variacao = ($ant !== null && $ant > 0)
                            ? round((($total - $ant) / $ant) * 100, 1)
                            : null;

                        $soma       = (int) $f['soma_motivos'];
                        $qtdMotivos = (int) $f['qtd_motivos'];
                        $diferenca  = $total - $soma;
                        ?>
                        <tr>
                            <td>
                                <span class="celula-principal block"><?= e(competencia_br($ano, $mes)) ?></span>
                                <span class="mono celula-suave"><?= e(competencia_curta($ano, $mes)) ?></span>
                            </td>

                            <td class="num text-right text-[0.9375rem] font-semibold text-marine-900 dark:text-white">
                                <?= numero($total) ?>
                            </td>

                            <td>
                                <?php if ($variacao === null): ?>
                                    <span class="celula-suave text-xs">—</span>
                                <?php else: ?>
                                    <?php
                                    [$icone, $classe] = match (true) {
                                        $variacao > 0  => ['subiu',  'badge-verde'],
                                        $variacao < 0  => ['desceu', 'badge-rosa'],
                                        default        => ['igual',  'badge-cinza'],
                                    };
                                    ?>
                                    <span class="badge <?= $classe ?>" title="Mês anterior: <?= numero((int) $ant) ?>">
                                        <?= Icon::svg($icone, 'w-3 h-3') ?>
                                        <?= ($variacao > 0 ? '+' : '') . number_format($variacao, 1, ',', '.') ?>%
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php if ($qtdMotivos === 0): ?>
                                    <span class="celula-suave text-xs">—</span>
                                <?php else: ?>
                                    <span class="badge <?= $diferenca === 0 ? 'badge-verde' : 'badge-ambar' ?>"
                                          title="Soma dos motivos: <?= numero($soma) ?>">
                                        <?= $qtdMotivos ?> motivo<?= $qtdMotivos === 1 ? '' : 's' ?>
                                        <?= $diferenca === 0 ? '· confere' : '· difere ' . numero(abs($diferenca)) ?>
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td class="celula-suave max-w-sm">
                                <span class="block truncate" title="<?= e($f['observacao'] ?? '') ?>">
                                    <?= $f['observacao'] !== null && $f['observacao'] !== '' ? e($f['observacao']) : '—' ?>
                                </span>
                            </td>

                            <td class="celula-suave text-xs">
                                <?= datahora_br($f['updated_at']) ?>
                                <?php if (!empty($f['usuario_nome'])): ?>
                                    <span class="block truncate text-[0.68rem] text-slate-400"><?= e($f['usuario_nome']) ?></span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <div class="flex items-center justify-end gap-0.5">
                                    <?php if ($podeEditar): ?>
                                        <a href="<?= url('fechamentos', 'editar', ['id' => (int) $f['id']]) ?>"
                                           class="btn-acao" title="Editar"
                                           aria-label="Editar fechamento de <?= e(competencia_br($ano, $mes)) ?>">
                                            <?= Icon::svg('editar', 'w-4 h-4') ?>
                                        </a>
                                    <?php endif; ?>

                                    <?php if ($podeExcluir): ?>
                                        <form method="post" action="<?= url('fechamentos', 'excluir') ?>" class="inline"
                                              data-titulo="Excluir fechamento"
                                              data-texto="O fechamento de <?= e(competencia_br($ano, $mes)) ?> será removido permanentemente.">
                                            <?= Csrf::campo() ?>
                                            <input type="hidden" name="id" value="<?= (int) $f['id'] ?>">
                                            <button type="button" @click="abrir" class="btn-acao btn-acao-perigo"
                                                    title="Excluir"
                                                    aria-label="Excluir fechamento de <?= e(competencia_br($ano, $mes)) ?>">
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
    <?php endif; ?>
</div>
