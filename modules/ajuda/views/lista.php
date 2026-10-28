<?php
/**
 * Biblioteca de manuais. A mesma tela serve para quem só lê e para quem
 * publica — o que muda é a presença dos botões.
 *
 * @var array  $porCategoria
 * @var int    $total
 * @var string $busca
 * @var bool   $gerencia
 */
declare(strict_types=1);

$podeCriar   = Auth::temPermissao('ajuda', 'pode_criar');
$podeEditar  = Auth::temPermissao('ajuda', 'pode_editar');
$podeExcluir = Auth::temPermissao('ajuda', 'pode_excluir');
?>

<div class="mb-5 flex flex-wrap items-end justify-between gap-3">
    <div>
        <h1 class="titulo-pagina">Manuais</h1>
        <p class="sub-pagina">Como usar cada parte do sistema, para consultar na hora da dúvida</p>
    </div>

    <?php if ($podeCriar): ?>
        <a href="<?= url('ajuda', 'novo') ?>" class="btn btn-primary">
            <?= Icon::svg('novo', 'w-4 h-4') ?> Publicar manual
        </a>
    <?php endif; ?>
</div>

<div class="card mb-4">
    <form method="get" action="<?= BASE_URL ?>/" class="toolbar border-b-0">
        <input type="hidden" name="mod" value="ajuda">
        <input type="hidden" name="action" value="lista">

        <div class="campo-icone w-full sm:w-96">
            <?= Icon::svg('buscar') ?>
            <input type="search" name="q" value="<?= e($busca) ?>" class="input"
                   placeholder="Buscar por título, assunto ou categoria…" aria-label="Buscar manuais">
        </div>

        <button type="submit" class="btn btn-outline">Buscar</button>

        <?php if ($busca !== ''): ?>
            <a href="<?= url('ajuda', 'lista') ?>" class="btn btn-ghost">Limpar</a>
        <?php endif; ?>

        <span class="ml-auto text-xs text-slate-500 dark:text-slate-400">
            <?= numero($total) ?> manual<?= $total === 1 ? '' : 'is' ?>
        </span>
    </form>
</div>

<?php if (empty($porCategoria)): ?>
    <div class="card">
        <div class="vazio">
            <span class="vazio-icone"><?= Icon::svg('ajuda', 'w-5 h-5') ?></span>
            <p class="vazio-titulo">
                <?= $busca !== '' ? 'Nenhum manual encontrado' : 'Nenhum manual publicado ainda' ?>
            </p>
            <p class="vazio-texto">
                <?= $busca !== ''
                    ? 'Nenhum resultado para “' . e($busca) . '”. Tente outro termo.'
                    : 'Publique aqui os manuais em PDF. Quem tiver dúvida abre direto pelo sistema, sem precisar procurar arquivo em pasta de rede.' ?>
            </p>
            <?php if ($podeCriar && $busca === ''): ?>
                <a href="<?= url('ajuda', 'novo') ?>" class="btn btn-primary btn-sm mt-1">
                    <?= Icon::svg('novo', 'w-3.5 h-3.5') ?> Publicar manual
                </a>
            <?php endif; ?>
        </div>
    </div>
<?php else: ?>

    <?php foreach ($porCategoria as $categoria => $manuais): ?>
        <section class="mb-5">
            <h2 class="mb-2.5 text-[0.6875rem] font-bold uppercase tracking-[0.12em] text-slate-500 dark:text-slate-400">
                <?= e($categoria) ?>
            </h2>

            <div class="grid gap-3 lg:grid-cols-2">
                <?php foreach ($manuais as $m): ?>
                    <?php
                    $ehPdf   = $m['mime'] === 'application/pdf';
                    $urlVer  = url('ajuda', 'arquivo', ['id' => (int) $m['id']]);
                    $urlBaix = url('ajuda', 'arquivo', ['id' => (int) $m['id'], 'baixar' => 1]);
                    ?>
                    <article class="card flex flex-col p-4 <?= $m['ativo'] ? '' : 'opacity-60' ?>">
                        <div class="flex items-start gap-3">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg
                                         bg-marine-50 text-marine-600
                                         dark:bg-marine-600/15 dark:text-marine-300">
                                <?= Icon::svg($ehPdf ? 'arquivo' : 'imagem', 'w-5 h-5') ?>
                            </span>

                            <div class="min-w-0 flex-1">
                                <h3 class="text-[0.875rem] font-semibold leading-snug text-marine-900 dark:text-white">
                                    <?= e($m['titulo']) ?>
                                </h3>

                                <?php if (!empty($m['descricao'])): ?>
                                    <p class="mt-1 text-[0.8125rem] leading-snug text-slate-500 dark:text-slate-400">
                                        <?= e($m['descricao']) ?>
                                    </p>
                                <?php endif; ?>

                                <p class="mt-2 flex flex-wrap items-center gap-x-2 gap-y-1 text-[0.6875rem] text-slate-400 dark:text-slate-500">
                                    <span class="mono uppercase"><?= e($ehPdf ? 'PDF' : 'Imagem') ?></span>
                                    <span>·</span>
                                    <span><?= e(formatar_tamanho((int) $m['tamanho'])) ?></span>
                                    <span>·</span>
                                    <span>Atualizado em <?= data_br($m['updated_at']) ?></span>
                                    <?php if (!$m['ativo']): ?>
                                        <span class="badge badge-cinza">Despublicado</span>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>

                        <div class="mt-3.5 flex flex-wrap items-center gap-2 border-t border-slate-100 pt-3 dark:border-slate-800">
                            <a href="<?= $urlVer ?>" target="_blank" rel="noopener" class="btn btn-primary btn-sm">
                                <?= Icon::svg('ver', 'w-3.5 h-3.5') ?> Abrir
                            </a>
                            <a href="<?= $urlBaix ?>" class="btn btn-outline btn-sm">
                                <?= Icon::svg('download', 'w-3.5 h-3.5') ?> Baixar
                            </a>

                            <?php if ($podeEditar || $podeExcluir): ?>
                                <span class="ml-auto flex items-center gap-0.5">
                                    <?php if ($podeEditar): ?>
                                        <a href="<?= url('ajuda', 'editar', ['id' => (int) $m['id']]) ?>"
                                           class="btn-acao" title="Editar"
                                           aria-label="Editar “<?= e($m['titulo']) ?>”">
                                            <?= Icon::svg('editar', 'w-4 h-4') ?>
                                        </a>
                                    <?php endif; ?>

                                    <?php if ($podeExcluir): ?>
                                        <form method="post" action="<?= url('ajuda', 'excluir') ?>" class="inline"
                                              data-titulo="Excluir manual"
                                              data-texto="“<?= e($m['titulo']) ?>” será removido do sistema, junto com o arquivo. Para tirar da lista sem apagar, edite e desmarque “Publicado”.">
                                            <?= Csrf::campo() ?>
                                            <input type="hidden" name="id" value="<?= (int) $m['id'] ?>">
                                            <button type="button" @click="abrir" class="btn-acao btn-acao-perigo"
                                                    title="Excluir" aria-label="Excluir “<?= e($m['titulo']) ?>”">
                                                <?= Icon::svg('excluir', 'w-4 h-4') ?>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endforeach; ?>

    <?php if ($gerencia): ?>
        <p class="text-xs text-slate-400 dark:text-slate-500">
            Manuais despublicados aparecem esmaecidos e só para quem administra.
        </p>
    <?php endif; ?>
<?php endif; ?>
