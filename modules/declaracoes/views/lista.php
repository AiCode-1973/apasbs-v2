<?php
/**
 * As declarações de saúde.
 *
 * @var array $declaracoes, $panorama, $filtro
 * @var int   $total, $pagina, $porPagina
 */
declare(strict_types=1);

$podeCriar = Auth::temPermissao('declaracoes', 'pode_criar');

$temFiltro = $filtro['busca'] !== '' || $filtro['status'] !== '' || $filtro['com_dlp'];

$base = array_filter([
    'q'      => $filtro['busca'] !== '' ? $filtro['busca'] : null,
    'status' => $filtro['status'] !== '' ? $filtro['status'] : null,
    'dlp'    => $filtro['com_dlp'] ? 1 : null,
]);
?>

<div class="mb-5 flex flex-wrap items-end justify-between gap-3">
    <div>
        <h1 class="titulo-pagina">Declaração de saúde</h1>
        <p class="sub-pagina">Doenças preexistentes declaradas na adesão</p>
    </div>

    <?php if ($podeCriar): ?>
        <a href="<?= url('declaracoes', 'novo') ?>" class="btn btn-primary">
            <?= Icon::svg('novo', 'w-4 h-4') ?> Nova declaração
        </a>
    <?php endif; ?>
</div>

<?php /* Dado de saúde, sob sigilo — o mesmo aviso do prontuário ocupacional,
         pela mesma razão: quem abre esta tela está num balcão. */ ?>
<div class="mb-4 flex items-start gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-[0.8125rem] text-slate-600 dark:border-slate-800 dark:bg-slate-900/60 dark:text-slate-400">
    <?= Icon::svg('permissoes', 'w-4 h-4 mt-0.5 shrink-0') ?>
    <span>
        <strong>Confidencial.</strong> Declaração de saúde é dado sensível (LGPD) e
        parte do contrato. Cada impressão fica registrada na auditoria com o nome de
        quem imprimiu.
    </span>
</div>

<div class="card mb-4">
    <div class="card-corpo grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <a href="<?= url('declaracoes', 'lista') ?>"
           class="rounded-lg p-1 transition-colors hover:bg-slate-50 dark:hover:bg-slate-800/60">
            <p class="rotulo-num">Declarações</p>
            <p class="valor-num"><?= numero((int) ($panorama['total'] ?? 0)) ?></p>
            <p class="ajuda">documentos registrados</p>
        </a>

        <a href="<?= url('declaracoes', 'lista', ['status' => 'em_preenchimento']) ?>"
           class="rounded-lg p-1 transition-colors hover:bg-slate-50 dark:hover:bg-slate-800/60">
            <p class="rotulo-num">Em preenchimento</p>
            <p class="valor-num <?= ($panorama['abertas'] ?? 0) > 0 ? 'text-amber-600 dark:text-amber-400' : '' ?>">
                <?= numero((int) ($panorama['abertas'] ?? 0)) ?>
            </p>
            <p class="ajuda">falta terminar ou assinar</p>
        </a>

        <a href="<?= url('declaracoes', 'lista', ['status' => 'concluida']) ?>"
           class="rounded-lg p-1 transition-colors hover:bg-slate-50 dark:hover:bg-slate-800/60">
            <p class="rotulo-num">Concluídas</p>
            <p class="valor-num"><?= numero((int) ($panorama['concluidas'] ?? 0)) ?></p>
            <p class="ajuda">prontas e assinadas</p>
        </a>

        <a href="<?= url('declaracoes', 'lista', ['dlp' => 1]) ?>"
           class="rounded-lg p-1 transition-colors hover:bg-slate-50 dark:hover:bg-slate-800/60">
            <p class="rotulo-num">Pessoas com DLP</p>
            <p class="valor-num"><?= numero((int) ($panorama['pessoas_dlp'] ?? 0)) ?></p>
            <p class="ajuda">declararam alguma doença</p>
        </a>
    </div>
</div>

<div class="card overflow-hidden">
    <form method="get" action="<?= BASE_URL ?>/" class="toolbar">
        <input type="hidden" name="mod" value="declaracoes">
        <input type="hidden" name="action" value="lista">

        <div class="campo-icone w-full sm:w-72">
            <?= Icon::svg('buscar') ?>
            <input type="search" name="q" value="<?= e($filtro['busca']) ?>" class="input"
                   placeholder="Nome, CPF ou nº da proposta…" aria-label="Buscar declarações">
        </div>

        <select name="status" class="select w-auto" aria-label="Situação">
            <option value="">Todas as situações</option>
            <?php foreach (DeclaracaoModel::STATUS as $chave => $rotulo): ?>
                <option value="<?= e($chave) ?>" <?= $filtro['status'] === $chave ? 'selected' : '' ?>>
                    <?= e($rotulo) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label class="flex cursor-pointer items-center gap-1.5 text-xs text-slate-600 dark:text-slate-400">
            <input type="checkbox" name="dlp" value="1" class="check" <?= $filtro['com_dlp'] ? 'checked' : '' ?>>
            Só as que têm doença declarada
        </label>

        <button type="submit" class="btn btn-outline">Filtrar</button>

        <?php if ($temFiltro): ?>
            <a href="<?= url('declaracoes', 'lista') ?>" class="btn btn-ghost">Limpar</a>
        <?php endif; ?>
    </form>

    <?php if (empty($declaracoes)): ?>
        <div class="vazio">
            <span class="vazio-icone"><?= Icon::svg('declaracoes', 'w-5 h-5') ?></span>
            <p class="vazio-titulo"><?= $temFiltro ? 'Nenhuma declaração encontrada' : 'Nenhuma declaração registrada' ?></p>
            <p class="vazio-texto">
                <?= $temFiltro
                    ? 'Nenhum resultado com esses filtros. Tente limpar e buscar de novo.'
                    : 'A declaração é preenchida na adesão, por contrato. Escolha uma família já cadastrada para começar.' ?>
            </p>
            <?php if ($podeCriar && !$temFiltro): ?>
                <a href="<?= url('declaracoes', 'novo') ?>" class="btn btn-primary btn-sm mt-1">
                    <?= Icon::svg('novo', 'w-3.5 h-3.5') ?> Criar a primeira
                </a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="tabela">
                <thead>
                    <tr>
                        <th class="w-28">Preenchida</th>
                        <th>Titular</th>
                        <th class="w-28">Proposta</th>
                        <th class="w-36">Declarações</th>
                        <th class="w-36">Situação</th>
                        <th class="w-20 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($declaracoes as $d): ?>
                        <tr>
                            <td class="celula-suave text-xs"><?= e(data_br($d['data_preenchimento'])) ?></td>

                            <td>
                                <a href="<?= url('declaracoes', 'ver', ['id' => (int) $d['id']]) ?>"
                                   class="celula-principal block hover:underline">
                                    <?= e($d['titular'] ?: 'contrato sem titular') ?>
                                </a>
                                <span class="block text-xs text-slate-500 dark:text-slate-400">
                                    <?= e(Elegibilidade::TIPOS_CONTRATO[$d['tipo_contrato']] ?? '') ?>
                                </span>
                            </td>

                            <td class="celula-suave mono text-xs">
                                <?= $d['numero_proposta']
                                    ? e($d['numero_proposta'])
                                    : '<span class="text-slate-400 dark:text-slate-600">#' . (int) $d['contrato_id'] . '</span>' ?>
                            </td>

                            <td class="text-xs">
                                <?php if ((int) $d['com_dlp'] > 0): ?>
                                    <span class="badge badge-ambar">
                                        <?= numero((int) $d['com_dlp']) ?>
                                        pessoa<?= (int) $d['com_dlp'] === 1 ? '' : 's' ?> com DLP
                                    </span>
                                <?php elseif ((int) $d['respondidos'] > 0): ?>
                                    <span class="badge badge-verde">Nada declarado</span>
                                <?php else: ?>
                                    <span class="text-amber-600 dark:text-amber-400">não respondida</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <span class="badge <?= $d['status'] === 'concluida' ? 'badge-verde' : 'badge-ambar' ?>">
                                    <?= e(DeclaracaoModel::STATUS[$d['status']] ?? $d['status']) ?>
                                </span>
                                <?php if ($d['status'] === 'concluida' && !$d['data_assinatura']): ?>
                                    <span class="mt-0.5 block text-[0.625rem] text-amber-600 dark:text-amber-400">
                                        sem data de assinatura
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <div class="flex items-center justify-end gap-0.5">
                                    <a href="<?= url('declaracoes', 'imprimir', ['id' => (int) $d['id']]) ?>"
                                       class="btn-acao" title="Imprimir" target="_blank"
                                       aria-label="Imprimir a declaração de <?= e($d['titular'] ?: 'contrato') ?>">
                                        <?= Icon::svg('imprimir', 'w-4 h-4') ?>
                                    </a>
                                    <a href="<?= url('declaracoes', 'ver', ['id' => (int) $d['id']]) ?>"
                                       class="btn-acao" title="Abrir" aria-label="Abrir">
                                        <?= Icon::svg('ver', 'w-4 h-4') ?>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?= Paginacao::barra([
            'modulo'    => 'declaracoes',
            'pagina'    => $pagina,
            'total'     => $total,
            'porPagina' => $porPagina,
            'padrao'    => 50,
            'rotulo'    => ['declaração', 'declarações'],
            'base'      => $base,
        ]) ?>
    <?php endif; ?>
</div>
