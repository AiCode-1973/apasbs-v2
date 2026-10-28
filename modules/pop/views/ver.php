<?php
/**
 * @var array $pop
 * @var array $blocos
 */
declare(strict_types=1);

$podeEditar = Auth::temPermissao('pop', 'pode_editar');
?>

<div class="mb-5 flex flex-wrap items-start justify-between gap-3">
    <div class="flex items-start gap-3">
        <a href="<?= url_lista('pop') ?>" class="btn btn-icon btn-outline mt-0.5" aria-label="Voltar">
            <?= Icon::svg('voltar', 'w-4 h-4') ?>
        </a>
        <div>
            <p class="mono mb-0.5 text-[0.6875rem] font-semibold text-marine-600 dark:text-marine-300">
                <?= e($pop['codigo']) ?> · versão <?= e($pop['versao']) ?>
                <?php if (!empty($pop['revisado_em'])): ?>
                    · revisado em <?= data_br($pop['revisado_em']) ?>
                <?php endif; ?>
            </p>
            <h1 class="titulo-pagina"><?= e($pop['titulo']) ?></h1>
            <?php if (!empty($pop['resumo'])): ?>
                <p class="sub-pagina max-w-3xl"><?= e($pop['resumo']) ?></p>
            <?php endif; ?>
        </div>
    </div>

    <div class="flex items-center gap-2 nao-imprimir">
        <a href="<?= url('pop', 'imprimir', ['id' => (int) $pop['id']]) ?>"
           target="_blank" rel="noopener" class="btn btn-outline">
            <?= Icon::svg('imprimir', 'w-4 h-4') ?> Imprimir
        </a>
        <?php if ($podeEditar): ?>
            <a href="<?= url('pop', 'editar', ['id' => (int) $pop['id']]) ?>" class="btn btn-outline">
                <?= Icon::svg('editar', 'w-4 h-4') ?> Editar
            </a>
        <?php endif; ?>
    </div>
</div>

<?php if (!$pop['ativo']): ?>
    <div class="alerta alerta-aviso mb-4">
        <?= Icon::svg('aviso', 'w-4 h-4') ?>
        <span>Este procedimento está <strong>despublicado</strong> — só quem administra consegue vê-lo.</span>
    </div>
<?php endif; ?>

<article class="card max-w-4xl p-5 lg:p-7">
    <?php require __DIR__ . '/_blocos.php'; ?>

    <footer class="mt-6 border-t border-slate-200 pt-3 text-[0.6875rem] text-slate-400 dark:border-slate-800 dark:text-slate-500">
        Última alteração em <?= datahora_br($pop['updated_at']) ?>
        <?php if (!empty($pop['usuario_nome'])): ?>
            por <?= e($pop['usuario_nome']) ?>
        <?php endif; ?>
    </footer>
</article>
