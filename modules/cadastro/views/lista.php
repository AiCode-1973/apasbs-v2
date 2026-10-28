<?php
/**
 * Os contratos do plano de saúde.
 *
 * A linha é a família, e não a pessoa: é assim que a mensalidade é cobrada
 * e é assim que o atendimento pensa — "o contrato do sargento Fulano".
 *
 * @var array $contratos, $panorama, $filtro
 * @var int   $total, $pagina, $porPagina
 */
declare(strict_types=1);

$podeCriar   = Auth::temPermissao('cadastro', 'pode_criar');
$podeEditar  = Auth::temPermissao('cadastro', 'pode_editar');
$podeExcluir = Auth::temPermissao('cadastro', 'pode_excluir');

$cores = [
    'em_analise' => 'badge-ambar',
    'ativo'      => 'badge-verde',
    'suspenso'   => 'badge-ceu',
    'cancelado'  => 'badge-rosa',
];

$temFiltro = $filtro['busca'] !== '' || $filtro['status'] !== '' || $filtro['tipo'] !== '';

$base = array_filter([
    'q'      => $filtro['busca'] !== '' ? $filtro['busca'] : null,
    'status' => $filtro['status'] !== '' ? $filtro['status'] : null,
    'tipo'   => $filtro['tipo'] !== '' ? $filtro['tipo'] : null,
]);
?>

<div class="mb-5 flex flex-wrap items-end justify-between gap-3">
    <div>
        <h1 class="titulo-pagina">Cadastro de beneficiários</h1>
        <p class="sub-pagina">Titulares, dependentes e a mensalidade de cada família</p>
    </div>

    <div class="flex flex-wrap items-center gap-2">
        <a href="<?= url('cadastro', 'precos') ?>" class="btn btn-outline">
            <?= Icon::svg('opme', 'w-4 h-4') ?> Tabela de preços
        </a>
        <?php if ($podeCriar): ?>
            <a href="<?= url('cadastro', 'novo') ?>" class="btn btn-primary">
                <?= Icon::svg('novo', 'w-4 h-4') ?> Novo contrato
            </a>
        <?php endif; ?>
    </div>
</div>

<div class="card mb-4">
    <div class="card-corpo grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <a href="<?= url('cadastro', 'lista') ?>"
           class="rounded-lg p-1 transition-colors hover:bg-slate-50 dark:hover:bg-slate-800/60">
            <p class="rotulo-num">Contratos</p>
            <p class="valor-num"><?= numero((int) ($panorama['contratos'] ?? 0)) ?></p>
            <p class="ajuda">famílias cadastradas</p>
        </a>

        <a href="<?= url('cadastro', 'lista', ['status' => 'ativo']) ?>"
           class="rounded-lg p-1 transition-colors hover:bg-slate-50 dark:hover:bg-slate-800/60">
            <p class="rotulo-num">Ativos</p>
            <p class="valor-num"><?= numero((int) ($panorama['ativos'] ?? 0)) ?></p>
            <p class="ajuda">contratos em vigor</p>
        </a>

        <a href="<?= url('cadastro', 'lista', ['status' => 'em_analise']) ?>"
           class="rounded-lg p-1 transition-colors hover:bg-slate-50 dark:hover:bg-slate-800/60">
            <p class="rotulo-num">Em análise</p>
            <p class="valor-num <?= ($panorama['em_analise'] ?? 0) > 0 ? 'text-amber-600 dark:text-amber-400' : '' ?>">
                <?= numero((int) ($panorama['em_analise'] ?? 0)) ?>
            </p>
            <p class="ajuda">propostas esperando conclusão</p>
        </a>

        <div class="rounded-lg p-1">
            <p class="rotulo-num">Beneficiários</p>
            <p class="valor-num"><?= numero((int) ($panorama['pessoas'] ?? 0)) ?></p>
            <p class="ajuda">pessoas ativas no plano</p>
        </div>
    </div>
</div>

<div class="card overflow-hidden">
    <form method="get" action="<?= BASE_URL ?>/" class="toolbar">
        <input type="hidden" name="mod" value="cadastro">
        <input type="hidden" name="action" value="lista">

        <div class="campo-icone w-full sm:w-72">
            <?= Icon::svg('buscar') ?>
            <input type="search" name="q" value="<?= e($filtro['busca']) ?>" class="input"
                   placeholder="Nome, CPF ou nº da proposta…" aria-label="Buscar contratos">
        </div>

        <select name="status" class="select w-auto" aria-label="Situação">
            <option value="">Todas as situações</option>
            <?php foreach (CadastroModel::STATUS as $chave => $rotulo): ?>
                <option value="<?= e($chave) ?>" <?= $filtro['status'] === $chave ? 'selected' : '' ?>>
                    <?= e($rotulo) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select name="tipo" class="select w-auto" aria-label="Tipo de contrato">
            <option value="">Com e sem coparticipação</option>
            <?php foreach (Elegibilidade::TIPOS_CONTRATO as $chave => $rotulo): ?>
                <option value="<?= e($chave) ?>" <?= $filtro['tipo'] === $chave ? 'selected' : '' ?>>
                    <?= e($rotulo) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button type="submit" class="btn btn-outline">Filtrar</button>

        <?php if ($temFiltro): ?>
            <a href="<?= url('cadastro', 'lista') ?>" class="btn btn-ghost">Limpar</a>
        <?php endif; ?>
    </form>

    <?php if (empty($contratos)): ?>
        <div class="vazio">
            <span class="vazio-icone"><?= Icon::svg('cadastro', 'w-5 h-5') ?></span>
            <p class="vazio-titulo"><?= $temFiltro ? 'Nenhum contrato encontrado' : 'Nenhum contrato cadastrado' ?></p>
            <p class="vazio-texto">
                <?= $temFiltro
                    ? 'Nenhum resultado com esses filtros. Tente limpar e buscar de novo.'
                    : 'Comece pelo contrato: escolha com ou sem coparticipação e cadastre o titular. Os dependentes entram depois.' ?>
            </p>
            <?php if ($podeCriar && !$temFiltro): ?>
                <a href="<?= url('cadastro', 'novo') ?>" class="btn btn-primary btn-sm mt-1">
                    <?= Icon::svg('novo', 'w-3.5 h-3.5') ?> Criar o primeiro contrato
                </a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="tabela">
                <thead>
                    <tr>
                        <th class="w-28">Proposta</th>
                        <th>Titular</th>
                        <th class="w-40">Categoria</th>
                        <th class="w-40">Contrato</th>
                        <th class="w-24">Pessoas</th>
                        <th class="w-32">Situação</th>
                        <th class="w-20 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($contratos as $c): ?>
                        <tr>
                            <td class="celula-suave mono text-xs">
                                <?= $c['numero_proposta']
                                    ? e($c['numero_proposta'])
                                    : '<span class="text-slate-400 dark:text-slate-600">#' . (int) $c['id'] . '</span>' ?>
                            </td>

                            <td>
                                <?php if ($c['titular']): ?>
                                    <a href="<?= url('cadastro', 'ver', ['id' => (int) $c['id']]) ?>"
                                       class="celula-principal block hover:underline"><?= e($c['titular']) ?></a>
                                    <?php if ($c['titular_cpf']): ?>
                                        <span class="mono block text-xs text-slate-500 dark:text-slate-400">
                                            <?= e(formatar_cpf((string) $c['titular_cpf'])) ?>
                                        </span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <?php /* Contrato sem titular é cadastro pela metade: a
                                             linha diz isso e leva direto para completar. */ ?>
                                    <a href="<?= url('cadastro', 'pessoa', ['contrato' => (int) $c['id'], 'tipo' => 'titular']) ?>"
                                       class="text-[0.8125rem] text-amber-600 underline decoration-dotted hover:text-amber-700 dark:text-amber-400">
                                        sem titular — cadastrar
                                    </a>
                                <?php endif; ?>
                            </td>

                            <td class="celula-suave text-xs">
                                <?= $c['categoria']
                                    ? e(Elegibilidade::CATEGORIAS[$c['categoria']] ?? $c['categoria'])
                                    : '<span class="text-slate-400 dark:text-slate-600">—</span>' ?>
                            </td>

                            <td class="text-xs">
                                <?php /* O tipo do contrato é o que decide o plano de todo
                                         mundo dentro dele — merece estar à vista. */ ?>
                                <span class="badge <?= $c['tipo'] === 'sem_copart' ? 'badge-ceu' : 'badge-cinza' ?>">
                                    <?= e(Elegibilidade::TIPOS_CONTRATO[$c['tipo']] ?? $c['tipo']) ?>
                                </span>
                                <?php if ($c['data_adesao']): ?>
                                    <span class="mt-0.5 block text-[0.625rem] text-slate-400 dark:text-slate-500">
                                        adesão em <?= e(data_br($c['data_adesao'])) ?>
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td class="celula-suave text-xs">
                                <?= numero((int) $c['pessoas']) ?>
                            </td>

                            <td>
                                <span class="badge <?= $cores[$c['status']] ?? 'badge-cinza' ?>">
                                    <?= e(CadastroModel::STATUS[$c['status']] ?? $c['status']) ?>
                                </span>
                            </td>

                            <td>
                                <div class="flex items-center justify-end gap-0.5">
                                    <a href="<?= url('cadastro', 'ver', ['id' => (int) $c['id']]) ?>"
                                       class="btn-acao" title="Abrir o contrato"
                                       aria-label="Abrir o contrato de <?= e($c['titular'] ?: 'sem titular') ?>">
                                        <?= Icon::svg('ver', 'w-4 h-4') ?>
                                    </a>

                                    <?php if ($podeEditar): ?>
                                        <a href="<?= url('cadastro', 'editar', ['id' => (int) $c['id']]) ?>"
                                           class="btn-acao" title="Editar o contrato" aria-label="Editar">
                                            <?= Icon::svg('editar', 'w-4 h-4') ?>
                                        </a>
                                    <?php endif; ?>

                                    <?php if ($podeExcluir):
                                        $qtd  = (int) $c['cadastradas'];
                                        $nome = $c['titular'] ?: ($c['numero_proposta'] ? 'proposta ' . $c['numero_proposta'] : 'sem titular');
                                    ?>
                                        <?php if ((int) $c['declaracoes'] > 0): ?>
                                            <span class="btn-acao cursor-not-allowed opacity-40"
                                                  title="Tem declaração de saúde (documento assinado) — não pode ser excluído. Mude a situação para Cancelado."
                                                  aria-label="Exclusão bloqueada: o contrato tem declaração de saúde">
                                                <?= Icon::svg('excluir', 'w-4 h-4') ?>
                                            </span>
                                        <?php else: ?>
                                            <form method="post" action="<?= url('cadastro', 'excluir') ?>" class="inline"
                                                  data-titulo="Excluir contrato"
                                                  data-texto="<?= e($qtd > 0
                                                      ? sprintf('O contrato de %s será removido junto com %d pessoa%s cadastrada%s. Esta ação não pode ser desfeita — para encerrar sem perder o histórico, mude a situação para Cancelado.',
                                                          $nome, $qtd, $qtd === 1 ? '' : 's', $qtd === 1 ? '' : 's')
                                                      : sprintf('O contrato de %s será removido. Esta ação não pode ser desfeita.', $nome)) ?>">
                                                <?= Csrf::campo() ?>
                                                <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                                                <button type="button" @click="abrir" class="btn-acao btn-acao-perigo"
                                                        title="Excluir o contrato" aria-label="Excluir o contrato de <?= e($nome) ?>">
                                                    <?= Icon::svg('excluir', 'w-4 h-4') ?>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?= Paginacao::barra([
            'modulo'    => 'cadastro',
            'pagina'    => $pagina,
            'total'     => $total,
            'porPagina' => $porPagina,
            'padrao'    => 50,
            'rotulo'    => ['contrato', 'contratos'],
            'base'      => $base,
        ]) ?>
    <?php endif; ?>
</div>
