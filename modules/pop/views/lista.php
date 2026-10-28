<?php
/**
 * @var array  $porCategoria
 * @var int    $total
 * @var string $busca
 * @var bool   $gerencia
 */
declare(strict_types=1);

$podeCriar   = Auth::temPermissao('pop', 'pode_criar');
$podeEditar  = Auth::temPermissao('pop', 'pode_editar');
$podeExcluir = Auth::temPermissao('pop', 'pode_excluir');
?>

<div class="mb-5 flex flex-wrap items-end justify-between gap-3">
    <div>
        <h1 class="titulo-pagina">Procedimentos (POP)</h1>
        <p class="sub-pagina">O passo a passo da central: o que fazer, o que conferir e o que dizer à paciente</p>
    </div>

    <?php if ($podeCriar): ?>
        <a href="<?= url('pop', 'novo') ?>" class="btn btn-primary">
            <?= Icon::svg('novo', 'w-4 h-4') ?> Novo POP
        </a>
    <?php endif; ?>
</div>

<div class="card mb-4">
    <form method="get" action="<?= BASE_URL ?>/" class="toolbar border-b-0">
        <input type="hidden" name="mod" value="pop">
        <input type="hidden" name="action" value="lista">

        <div class="campo-icone w-full sm:w-96">
            <?= Icon::svg('buscar') ?>
            <input type="search" name="q" value="<?= e($busca) ?>" class="input"
                   placeholder="Buscar por situação, convênio, regra…" aria-label="Buscar procedimentos">
        </div>

        <button type="submit" class="btn btn-outline">Buscar</button>

        <?php if ($busca !== ''): ?>
            <a href="<?= url('pop', 'lista') ?>" class="btn btn-ghost">Limpar</a>
        <?php endif; ?>

        <span class="ml-auto text-xs text-slate-500 dark:text-slate-400">
            <?= numero($total) ?> procedimento<?= $total === 1 ? '' : 's' ?>
        </span>
    </form>

    <?php if ($busca === ''): ?>
        <p class="border-t border-slate-200 px-4 py-2.5 text-xs text-slate-500 dark:border-slate-800 dark:text-slate-400">
            A busca procura também dentro do texto — digite “Mounjaro”, “CAPEP” ou “encaixe”
            para achar a regra sem saber em qual procedimento ela está.
        </p>
    <?php endif; ?>
</div>

<?php if (empty($porCategoria)): ?>
    <div class="card">
        <div class="vazio">
            <span class="vazio-icone"><?= Icon::svg('pop', 'w-5 h-5') ?></span>
            <p class="vazio-titulo">
                <?= $busca !== '' ? 'Nenhum procedimento encontrado' : 'Nenhum procedimento cadastrado' ?>
            </p>
            <p class="vazio-texto">
                <?= $busca !== ''
                    ? 'Nenhum resultado para “' . e($busca) . '”. Tente outro termo.'
                    : 'Cadastre aqui os POPs da central, para todo mundo seguir o mesmo caminho.' ?>
            </p>
        </div>
    </div>
<?php else: ?>
    <?php foreach ($porCategoria as $categoria => $pops): ?>
        <section class="mb-5">
            <h2 class="mb-2.5 text-[0.6875rem] font-bold uppercase tracking-[0.12em] text-slate-500 dark:text-slate-400">
                <?= e($categoria) ?>
            </h2>

            <div class="card divide-y divide-slate-100 dark:divide-slate-800">
                <?php foreach ($pops as $p): ?>
                    <div class="flex items-start gap-3 p-3.5 <?= $p['ativo'] ? '' : 'opacity-60' ?>">
                        <span class="mono mt-0.5 shrink-0 rounded-md bg-slate-100 px-2 py-0.5
                                     text-[0.6875rem] font-semibold text-marine-700
                                     dark:bg-slate-800 dark:text-marine-300">
                            <?= e($p['codigo']) ?>
                        </span>

                        <div class="min-w-0 flex-1">
                            <a href="<?= url('pop', 'ver', ['id' => (int) $p['id']]) ?>"
                               class="text-[0.875rem] font-semibold leading-snug text-marine-900 hover:underline dark:text-white">
                                <?= e($p['titulo']) ?>
                            </a>

                            <?php if (!empty($p['resumo'])): ?>
                                <p class="mt-0.5 text-[0.8125rem] leading-snug text-slate-500 dark:text-slate-400">
                                    <?= e($p['resumo']) ?>
                                </p>
                            <?php endif; ?>

                            <p class="mt-1 flex flex-wrap items-center gap-x-2 text-[0.6875rem] text-slate-400 dark:text-slate-500">
                                <span>Versão <?= e($p['versao']) ?></span>
                                <?php if (!empty($p['revisado_em'])): ?>
                                    <span>·</span><span>Revisado em <?= data_br($p['revisado_em']) ?></span>
                                <?php endif; ?>
                                <?php if (!$p['ativo']): ?>
                                    <span class="badge badge-cinza">Despublicado</span>
                                <?php endif; ?>
                            </p>
                        </div>

                        <div class="flex shrink-0 items-center gap-0.5">
                            <a href="<?= url('pop', 'ver', ['id' => (int) $p['id']]) ?>"
                               class="btn-acao" title="Abrir" aria-label="Abrir <?= e($p['titulo']) ?>">
                                <?= Icon::svg('ver', 'w-4 h-4') ?>
                            </a>
                            <?php if ($podeEditar): ?>
                                <a href="<?= url('pop', 'editar', ['id' => (int) $p['id']]) ?>"
                                   class="btn-acao" title="Editar" aria-label="Editar <?= e($p['titulo']) ?>">
                                    <?= Icon::svg('editar', 'w-4 h-4') ?>
                                </a>
                            <?php endif; ?>
                            <?php if ($podeExcluir): ?>
                                <form method="post" action="<?= url('pop', 'excluir') ?>" class="inline"
                                      data-titulo="Excluir procedimento"
                                      data-texto="<?= e($p['codigo']) ?> — <?= e($p['titulo']) ?> será removido, junto com todo o conteúdo.">
                                    <?= Csrf::campo() ?>
                                    <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                                    <button type="button" @click="abrir" class="btn-acao btn-acao-perigo"
                                            title="Excluir" aria-label="Excluir <?= e($p['titulo']) ?>">
                                        <?= Icon::svg('excluir', 'w-4 h-4') ?>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endforeach; ?>
<?php endif; ?>
