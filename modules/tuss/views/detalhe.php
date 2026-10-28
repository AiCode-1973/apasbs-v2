<?php
/** @var array $item */
declare(strict_types=1);

$encerrado = !empty($item['vigencia_fim']) && strtotime((string) $item['vigencia_fim']) < time();
?>

<div class="mb-5 flex items-center gap-3">
    <a href="<?= url_lista('tuss') ?>" class="btn btn-icon btn-outline" aria-label="Voltar">
        <?= Icon::svg('voltar', 'w-4 h-4') ?>
    </a>
    <div>
        <h1 class="titulo-pagina">Detalhe do procedimento</h1>
        <p class="sub-pagina">Tabela TUSS 22 · ANS</p>
    </div>
</div>

<div class="card max-w-3xl">
    <div class="border-b border-slate-200 p-5 dark:border-slate-800">
        <div class="flex flex-wrap items-center gap-3">
            <span class="rounded-lg bg-marine-50 px-3 py-1.5 font-mono text-lg font-bold text-marine-700 dark:bg-marine-500/10 dark:text-marine-300">
                <?= e($item['codigo']) ?>
            </span>
            <?php if ($encerrado): ?>
                <span class="badge badge-cinza">Vigência encerrada em <?= data_br($item['vigencia_fim']) ?></span>
            <?php else: ?>
                <span class="badge badge-ponto badge-verde">Vigente</span>
            <?php endif; ?>
        </div>

        <h2 class="mt-4 text-lg font-semibold leading-snug text-marine-900 dark:text-white">
            <?= e($item['termo']) ?>
        </h2>
    </div>

    <?php if (!empty($item['descricao'])): ?>
        <div class="border-b border-slate-200 p-5 dark:border-slate-800">
            <p class="label">Descrição detalhada</p>
            <p class="text-sm leading-relaxed text-slate-600 dark:text-slate-300">
                <?= nl2br(e($item['descricao'])) ?>
            </p>
        </div>
    <?php endif; ?>

    <dl class="grid gap-5 p-5 sm:grid-cols-3">
        <div>
            <dt class="label">Início de vigência</dt>
            <dd class="text-sm font-medium text-slate-800 dark:text-slate-100"><?= data_br($item['vigencia_inicio'] ?? null) ?></dd>
        </div>
        <div>
            <dt class="label">Fim de vigência</dt>
            <dd class="text-sm font-medium text-slate-800 dark:text-slate-100"><?= data_br($item['vigencia_fim'] ?? null) ?></dd>
        </div>
        <div>
            <dt class="label">Fim de implantação</dt>
            <dd class="text-sm font-medium text-slate-800 dark:text-slate-100"><?= data_br($item['fim_implantacao'] ?? null) ?></dd>
        </div>
    </dl>
</div>
