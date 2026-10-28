<?php
/** @var array  $exames */
/** @var string $busca */
declare(strict_types=1);

$podeCriar   = Auth::temPermissao('exames', 'pode_criar');
$podeEditar  = Auth::temPermissao('exames', 'pode_editar');
$podeExcluir = Auth::temPermissao('exames', 'pode_excluir');
?>

<div class="mb-5 flex flex-wrap items-end justify-between gap-3">
    <div>
        <h1 class="titulo-pagina">Tipos de exame</h1>
        <p class="sub-pagina">Cada tipo tem contagem de vagas própria dentro do mesmo bloco da agenda</p>
    </div>

    <?php if ($podeCriar): ?>
        <a href="<?= url('exames', 'novo') ?>" class="btn btn-primary">
            <?= Icon::svg('novo', 'w-4 h-4') ?> Novo tipo
        </a>
    <?php endif; ?>
</div>

<div class="card overflow-hidden">
    <form method="get" action="<?= BASE_URL ?>/" class="toolbar">
        <input type="hidden" name="mod" value="exames">
        <input type="hidden" name="action" value="lista">

        <div class="campo-icone w-full sm:w-80">
            <?= Icon::svg('buscar') ?>
            <input type="search" name="q" value="<?= e($busca) ?>"
                   class="input" placeholder="Buscar por código, nome ou descrição…" aria-label="Buscar tipos de exame">
        </div>

        <button type="submit" class="btn btn-outline">Buscar</button>

        <?php if ($busca !== ''): ?>
            <a href="<?= url('exames', 'lista') ?>" class="btn btn-ghost">Limpar</a>
        <?php endif; ?>

        <span class="ml-auto text-xs text-slate-500 dark:text-slate-400">
            <?= count($exames) ?> registro<?= count($exames) === 1 ? '' : 's' ?>
        </span>
    </form>

    <?php if (empty($exames)): ?>
        <div class="vazio">
            <span class="vazio-icone"><?= Icon::svg('exames', 'w-5 h-5') ?></span>
            <p class="vazio-titulo">
                <?= $busca !== '' ? 'Nenhum tipo encontrado' : 'Nenhum tipo de exame cadastrado' ?>
            </p>
            <p class="vazio-texto">
                <?= $busca !== ''
                    ? 'Nenhum resultado para “' . e($busca) . '”. Tente outro termo.'
                    : 'Combo, Colonoscopia e EDA já vêm no script de instalação. Cadastre aqui qualquer outro tipo que o ambulatório passar a atender.' ?>
            </p>
            <?php if ($podeCriar && $busca === ''): ?>
                <a href="<?= url('exames', 'novo') ?>" class="btn btn-primary btn-sm mt-1">
                    <?= Icon::svg('novo', 'w-3.5 h-3.5') ?> Novo tipo
                </a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="tabela">
                <thead>
                    <tr>
                        <th class="w-32">Código</th>
                        <th class="w-64">Nome</th>
                        <th>Descrição</th>
                        <th class="w-20 text-right">Ordem</th>
                        <th class="w-36">Na agenda</th>
                        <th class="w-28">Situação</th>
                        <th class="w-24 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($exames as $x): ?>
                        <?php $vagas = (int) $x['qtd_vagas']; ?>
                        <tr>
                            <td>
                                <span class="mono rounded-md bg-slate-100 px-2 py-0.5 font-semibold text-marine-700 dark:bg-slate-800 dark:text-marine-300">
                                    <?= e($x['codigo']) ?>
                                </span>
                            </td>

                            <td><span class="celula-principal"><?= e($x['nome']) ?></span></td>

                            <td class="celula-suave max-w-md">
                                <span class="block truncate" title="<?= e($x['descricao'] ?? '') ?>">
                                    <?= $x['descricao'] !== null && $x['descricao'] !== '' ? e($x['descricao']) : '—' ?>
                                </span>
                            </td>

                            <td class="num celula-suave text-right text-xs"><?= (int) $x['ordem'] ?></td>

                            <td class="celula-suave text-xs">
                                <?= $vagas > 0
                                    ? numero($vagas) . ' lançamento' . ($vagas === 1 ? '' : 's')
                                    : 'Sem lançamento' ?>
                            </td>

                            <td>
                                <span class="badge <?= $x['ativo'] ? 'badge-verde' : 'badge-cinza' ?>">
                                    <?= $x['ativo'] ? 'Ativo' : 'Inativo' ?>
                                </span>
                            </td>

                            <td>
                                <div class="flex items-center justify-end gap-0.5">
                                    <?php if ($podeEditar): ?>
                                        <a href="<?= url('exames', 'editar', ['id' => (int) $x['id']]) ?>"
                                           class="btn-acao" title="Editar"
                                           aria-label="Editar <?= e($x['nome']) ?>">
                                            <?= Icon::svg('editar', 'w-4 h-4') ?>
                                        </a>
                                    <?php endif; ?>

                                    <?php if ($podeExcluir): ?>
                                        <?php if ($vagas > 0): ?>
                                            <span class="btn-acao-off"
                                                  title="Não pode excluir: já tem vagas lançadas na agenda">
                                                <?= Icon::svg('excluir', 'w-4 h-4') ?>
                                            </span>
                                        <?php else: ?>
                                            <form method="post" action="<?= url('exames', 'excluir') ?>" class="inline"
                                                  data-titulo="Excluir tipo de exame"
                                                  data-texto="<?= e($x['nome']) ?> será removido permanentemente.">
                                                <?= Csrf::campo() ?>
                                                <input type="hidden" name="id" value="<?= (int) $x['id'] ?>">
                                                <button type="button" @click="abrir" class="btn-acao btn-acao-perigo"
                                                        title="Excluir" aria-label="Excluir <?= e($x['nome']) ?>">
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
    <?php endif; ?>
</div>
