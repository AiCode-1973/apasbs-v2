<?php
/** @var array  $usuarios */
/** @var string $busca */
declare(strict_types=1);

$podeCriar   = Auth::temPermissao('usuarios', 'pode_criar');
$podeEditar  = Auth::temPermissao('usuarios', 'pode_editar');
$podeExcluir = Auth::temPermissao('usuarios', 'pode_excluir');
?>

<div class="mb-5 flex flex-wrap items-end justify-between gap-3">
    <div>
        <h1 class="titulo-pagina">Usuários</h1>
        <p class="sub-pagina">Profissionais com acesso ao sistema</p>
    </div>

    <?php if ($podeCriar): ?>
        <a href="<?= url('usuarios', 'novo') ?>" class="btn btn-primary">
            <?= Icon::svg('novo', 'w-4 h-4') ?> Novo usuário
        </a>
    <?php endif; ?>
</div>

<div class="card overflow-hidden">
    <form method="get" action="<?= BASE_URL ?>/" class="toolbar">
        <input type="hidden" name="mod" value="usuarios">
        <input type="hidden" name="action" value="lista">

        <div class="campo-icone w-full sm:w-72">
            <?= Icon::svg('buscar') ?>
            <input type="search" name="q" value="<?= e($busca) ?>"
                   class="input" placeholder="Buscar por nome, CPF ou setor…" aria-label="Buscar usuários">
        </div>

        <button type="submit" class="btn btn-outline">Buscar</button>

        <?php if ($busca !== ''): ?>
            <a href="<?= url('usuarios', 'lista') ?>" class="btn btn-ghost">Limpar</a>
        <?php endif; ?>

        <span class="ml-auto text-xs text-slate-500 dark:text-slate-400">
            <?= count($usuarios) ?> registro<?= count($usuarios) === 1 ? '' : 's' ?>
        </span>
    </form>

    <?php if (empty($usuarios)): ?>
        <div class="vazio">
            <span class="vazio-icone"><?= Icon::svg('usuarios', 'w-5 h-5') ?></span>
            <p class="vazio-titulo"><?= $busca !== '' ? 'Nenhum usuário encontrado' : 'Nenhum usuário cadastrado' ?></p>
            <p class="vazio-texto">
                <?= $busca !== ''
                    ? 'Nenhum resultado para “' . e($busca) . '”. Tente outro termo.'
                    : 'Cadastre o primeiro profissional para começar.' ?>
            </p>
            <?php if ($podeCriar && $busca === ''): ?>
                <a href="<?= url('usuarios', 'novo') ?>" class="btn btn-primary btn-sm mt-1">
                    <?= Icon::svg('novo', 'w-3.5 h-3.5') ?> Novo usuário
                </a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="tabela">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th class="w-40">CPF</th>
                        <th class="w-52">Setor</th>
                        <th class="w-28">Situação</th>
                        <th class="w-24 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usuarios as $u): ?>
                        <tr>
                            <td>
                                <div class="flex items-center gap-2.5">
                                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-marine-50 text-[0.6rem] font-semibold text-marine-600 dark:bg-marine-500/10 dark:text-marine-300">
                                        <?= e(mb_strtoupper(mb_substr((string) $u['nome'], 0, 2))) ?>
                                    </span>
                                    <span class="min-w-0">
                                        <span class="celula-principal block truncate"><?= e($u['nome']) ?></span>
                                        <?php if (!empty($u['is_admin'])): ?>
                                            <span class="text-[0.6875rem] font-medium text-marine-600 dark:text-marine-300">Administrador</span>
                                        <?php endif; ?>
                                    </span>
                                </div>
                            </td>
                            <td class="mono celula-suave"><?= e(formatar_cpf((string) $u['cpf'])) ?></td>
                            <td class="celula-suave"><?= e($u['setor_nome']) ?></td>
                            <td>
                                <span class="badge badge-ponto <?= $u['ativo'] ? 'badge-verde' : 'badge-cinza' ?>">
                                    <?= $u['ativo'] ? 'Ativo' : 'Inativo' ?>
                                </span>
                                <?php /* Quem ainda usa a senha que o administrador digitou.
                                         O aviso some sozinho quando a pessoa troca. */ ?>
                                <?php if (!empty($u['senha_provisoria'])): ?>
                                    <span class="mt-1 block text-[0.6875rem] text-amber-700 dark:text-amber-400">
                                        senha provisória
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="flex items-center justify-end gap-0.5">
                                    <?php if ($podeEditar): ?>
                                        <a href="<?= url('usuarios', 'editar', ['id' => (int) $u['id']]) ?>"
                                           class="btn-acao" title="Editar" aria-label="Editar <?= e($u['nome']) ?>">
                                            <?= Icon::svg('editar', 'w-4 h-4') ?>
                                        </a>
                                    <?php endif; ?>

                                    <?php if ($podeExcluir): ?>
                                        <form method="post" action="<?= url('usuarios', 'excluir') ?>" class="inline"
                                              data-titulo="Excluir usuário"
                                              data-texto="O usuário &quot;<?= e($u['nome']) ?>&quot; e suas permissões serão removidos. Esta ação não pode ser desfeita.">
                                            <?= Csrf::campo() ?>
                                            <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                                            <button type="button" @click="abrir" class="btn-acao btn-acao-perigo"
                                                    title="Excluir" aria-label="Excluir <?= e($u['nome']) ?>">
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
