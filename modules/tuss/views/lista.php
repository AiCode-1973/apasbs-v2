<?php
/** @var array  $resultados */
/** @var string $termo */
/** @var bool   $vigentes */
/** @var int    $pagina, $totalPags, $total, $totalGeral, $porPagina */
declare(strict_types=1);

$linkPagina = function (int $p) use ($termo, $vigentes): string {
    $params = ['p' => $p];
    if ($termo !== '') { $params['q'] = $termo; }
    if ($vigentes)     { $params['vigentes'] = '1'; }
    return url('tuss', 'lista', $params);
};

$inicio = $total > 0 ? (($pagina - 1) * $porPagina) + 1 : 0;
$fim    = min($pagina * $porPagina, $total);
$n      = fn(int $v): string => number_format($v, 0, ',', '.');
?>

<div class="mb-5">
    <h1 class="titulo-pagina">TUSS — Tabela 22</h1>
    <p class="sub-pagina">
        Procedimentos e eventos em saúde da ANS · <?= $n($totalGeral) ?> registros · versão 202607
    </p>
</div>

<div class="card overflow-hidden">
    <form method="get" action="<?= BASE_URL ?>/" class="toolbar">
        <input type="hidden" name="mod" value="tuss">
        <input type="hidden" name="action" value="lista">

        <div class="campo-icone w-full sm:w-96">
            <?= Icon::svg('buscar') ?>
            <input type="search" name="q" value="<?= e($termo) ?>" autofocus
                   class="input" placeholder="Código, termo ou descrição do procedimento…" aria-label="Buscar na tabela TUSS">
        </div>

        <label class="inline-flex cursor-pointer items-center gap-2 text-[0.8125rem] text-slate-600 dark:text-slate-300">
            <input type="checkbox" name="vigentes" value="1" class="check" <?= $vigentes ? 'checked' : '' ?>>
            Somente vigentes
        </label>

        <button type="submit" class="btn btn-outline">Buscar</button>

        <?php if ($termo !== '' || $vigentes): ?>
            <a href="<?= url('tuss', 'lista') ?>" class="btn btn-ghost">Limpar</a>
        <?php endif; ?>

        <span class="ml-auto text-xs text-slate-500 dark:text-slate-400">
            <?= $total > 0 ? $n($inicio) . '–' . $n($fim) . ' de ' . $n($total) : 'Nenhum resultado' ?>
        </span>
    </form>

    <?php if (empty($resultados)): ?>
        <div class="vazio">
            <span class="vazio-icone"><?= Icon::svg('buscar', 'w-5 h-5') ?></span>
            <p class="vazio-titulo">Nada encontrado</p>
            <p class="vazio-texto">Tente outro código ou parte da descrição do procedimento.</p>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="tabela">
                <thead>
                    <tr>
                        <th class="w-32">Código</th>
                        <th>Termo</th>
                        <th class="w-40">Vigência</th>
                        <th class="w-16 text-right">Ver</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($resultados as $r): ?>
                        <?php $encerrado = !empty($r['vigencia_fim']) && strtotime((string) $r['vigencia_fim']) < time(); ?>
                        <tr>
                            <td class="mono font-semibold text-marine-700 dark:text-marine-300"><?= e($r['codigo']) ?></td>
                            <td>
                                <span class="celula-principal block"><?= e($r['termo']) ?></span>
                                <?php if (!empty($r['descricao'])): ?>
                                    <span class="celula-suave mt-0.5 block max-w-3xl truncate text-xs">
                                        <?= e($r['descricao']) ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($encerrado): ?>
                                    <span class="badge badge-cinza">encerrada em <?= data_br($r['vigencia_fim']) ?></span>
                                <?php else: ?>
                                    <span class="badge badge-ponto badge-verde">Vigente</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-right">
                                <a href="<?= url('tuss', 'detalhe', ['codigo' => (int) $r['codigo']]) ?>"
                                   class="btn-acao ml-auto" title="Ver detalhes" aria-label="Ver detalhes do código <?= e($r['codigo']) ?>">
                                    <?= Icon::svg('ver', 'w-4 h-4') ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?= Paginacao::barra([
            'modulo'    => 'tuss',
            'param'     => 'p',
            'pagina'    => $pagina,
            'total'     => $total,
            'porPagina' => $porPagina,
            'padrao'    => 50,
            'rotulo'    => ['procedimento', 'procedimentos'],
            'base'      => array_filter([
                'q'        => $termo !== '' ? $termo : null,
                'vigentes' => $vigentes ? '1' : null,
            ]),
        ]) ?>
    <?php endif; ?>
</div>
