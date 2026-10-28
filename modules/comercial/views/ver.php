<?php
/**
 * O item por dentro — e o que ele valia nas vigências anteriores.
 *
 * A coluna da direita é o motivo de abrir aqui em vez de só olhar a lista:
 * é nela que aparece o que o reajuste fez com aquele código.
 *
 * @var array $i, $historico
 */
declare(strict_types=1);

$podeEditar  = Auth::temPermissao('comercial', 'pode_editar');
$podeExcluir = Auth::temPermissao('comercial', 'pode_excluir');

$extras = ComercialModel::extras($i['extras']);

$linha = function (string $rotulo, ?string $valor, bool $mono = false): string {
    $conteudo = ($valor !== null && $valor !== '')
        ? '<span class="' . ($mono ? 'mono ' : '') . 'text-slate-800 dark:text-slate-200">' . e($valor) . '</span>'
        : '<span class="text-slate-400 dark:text-slate-600">—</span>';

    return '<div class="flex flex-wrap items-baseline gap-x-2 py-1">'
         . '<span class="w-48 shrink-0 text-xs text-slate-500 dark:text-slate-400">' . e($rotulo) . '</span>'
         . '<span class="text-[0.8125rem]">' . $conteudo . '</span></div>';
};
?>

<div class="mb-5 flex flex-wrap items-start justify-between gap-3">
    <div class="flex items-start gap-3">
        <a href="<?= url('comercial', 'lista', ['tabela' => (int) $i['tabela_id']]) ?>"
           class="btn-acao mt-0.5" aria-label="Voltar">
            <?= Icon::svg('anterior', 'w-4 h-4') ?>
        </a>
        <div>
            <h1 class="titulo-pagina"><?= e($i['descricao']) ?></h1>
            <p class="sub-pagina">
                <?= e(ComercialModel::TIPOS[$i['tipo']] ?? $i['tipo']) ?>
                <?= $i['codigo'] ? ' · ' . e($i['codigo']) : '' ?>
                · <?= e($i['convenio']) ?>, vigência <?= e(data_br($i['vigencia'])) ?>
            </p>
        </div>
    </div>

    <?php if ($podeEditar): ?>
        <a href="<?= url('comercial', 'editar', ['id' => (int) $i['id']]) ?>" class="btn btn-primary">
            <?= Icon::svg('editar', 'w-4 h-4') ?> Editar
        </a>
    <?php endif; ?>
</div>

<div class="grid gap-4 lg:grid-cols-[1.6fr_1fr] lg:items-start">
    <div class="space-y-4">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">O item</h2>
                <span class="text-lg font-semibold text-slate-900 dark:text-slate-100">
                    <?= $i['valor'] !== null ? e(dinheiro_br((float) $i['valor'])) : '—' ?>
                </span>
            </div>
            <div class="card-corpo">
                <?= $linha('Código', $i['codigo'], true) ?>
                <?= $linha('Tipo', ComercialModel::TIPOS[$i['tipo']] ?? $i['tipo']) ?>
                <?= $linha('Grupo no edital', $i['grupo']) ?>
                <?= $linha('Variante', $i['variante']) ?>
                <?= $linha('Origem', $i['origem'] === 'edital' ? 'Carga do edital' : 'Incluído pela tela') ?>
            </div>
        </div>

        <?php if ($extras): ?>
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">O que mais o edital traz</h2>
                </div>
                <div class="card-corpo">
                    <?php foreach ($extras as $rotulo => $valor): ?>
                        <?= $linha($rotulo, $valor) ?>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($podeExcluir): ?>
            <div class="card">
                <div class="border-t-0 px-4 py-3">
                    <form method="post" action="<?= url('comercial', 'excluir') ?>"
                          data-titulo="Excluir item da tabela"
                          data-texto="<?= e($i['descricao']) ?> será removido desta tabela de valores.<?= $i['origem'] === 'edital' ? ' Atenção: este item veio da carga do edital e volta na próxima recarga do arquivo.' : '' ?>">
                        <?= Csrf::campo() ?>
                        <input type="hidden" name="id" value="<?= (int) $i['id'] ?>">
                        <button type="button" @click="abrir" class="btn btn-outline btn-sm text-rose-600 dark:text-rose-400">
                            <?= Icon::svg('excluir', 'w-3.5 h-3.5') ?> Excluir item
                        </button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="card self-start">
        <div class="card-header">
            <h2 class="card-title">Este código em outras vigências</h2>
        </div>

        <?php if (count($historico) <= 1): ?>
            <div class="px-4 py-6 text-center text-[0.8125rem] text-slate-500 dark:text-slate-400">
                Só existe nesta tabela. Quando o próximo edital for carregado,
                a comparação aparece aqui.
            </div>
        <?php else: ?>
            <ul class="divide-y divide-slate-100 dark:divide-slate-800">
                <?php foreach ($historico as $h): ?>
                    <li class="flex items-baseline justify-between gap-2 px-4 py-2.5 text-[0.8125rem]">
                        <span class="text-slate-600 dark:text-slate-400">
                            <?= e(data_br($h['vigencia'])) ?>
                            <?php if ($h['variante']): ?>
                                <span class="text-xs text-slate-400 dark:text-slate-500">· <?= e($h['variante']) ?></span>
                            <?php endif; ?>
                        </span>
                        <span class="font-medium text-slate-800 dark:text-slate-200">
                            <?= $h['valor'] !== null ? e(dinheiro_br((float) $h['valor'])) : '—' ?>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>
