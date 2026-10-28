<?php
/** @var array $setores */
declare(strict_types=1);

$podeCriar   = Auth::temPermissao('setores', 'pode_criar');
$podeEditar  = Auth::temPermissao('setores', 'pode_editar');
$podeExcluir = Auth::temPermissao('setores', 'pode_excluir');
?>

<div class="mb-5 flex flex-wrap items-end justify-between gap-3">
    <div>
        <h1 class="titulo-pagina">Setores</h1>
        <p class="sub-pagina">Áreas da organização às quais os profissionais são vinculados</p>
    </div>

    <?php if ($podeCriar): ?>
        <a href="<?= url('setores', 'novo') ?>" class="btn btn-primary">
            <?= Icon::svg('novo', 'w-4 h-4') ?> Novo setor
        </a>
    <?php endif; ?>
</div>

<div class="card overflow-hidden">
    <div class="card-header">
        <h2 class="card-title">Setores cadastrados</h2>
        <span class="text-xs text-slate-500 dark:text-slate-400">
            <?= count($setores) ?> registro<?= count($setores) === 1 ? '' : 's' ?>
        </span>
    </div>

    <?php if (empty($setores)): ?>
        <div class="vazio">
            <span class="vazio-icone"><?= Icon::svg('setores', 'w-5 h-5') ?></span>
            <p class="vazio-titulo">Nenhum setor cadastrado</p>
            <p class="vazio-texto">Os setores organizam os profissionais dentro da instituição.</p>
            <?php if ($podeCriar): ?>
                <a href="<?= url('setores', 'novo') ?>" class="btn btn-primary btn-sm mt-1">
                    <?= Icon::svg('novo', 'w-3.5 h-3.5') ?> Novo setor
                </a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="tabela">
                <thead>
                    <tr>
                        <th>Setor</th>
                        <th class="w-36">Usuários</th>
                        <th class="w-28">Situação</th>
                        <th class="w-24 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($setores as $s): ?>
                        <?php $vinculados = (int) $s['total_usuarios']; ?>
                        <tr>
                            <td class="celula-principal"><?= e($s['nome']) ?></td>
                            <td class="celula-suave">
                                <?= $vinculados ?> usuário<?= $vinculados === 1 ? '' : 's' ?>
                            </td>
                            <td>
                                <span class="badge badge-ponto <?= $s['ativo'] ? 'badge-verde' : 'badge-cinza' ?>">
                                    <?= $s['ativo'] ? 'Ativo' : 'Inativo' ?>
                                </span>
                            </td>
                            <td>
                                <div class="flex items-center justify-end gap-0.5">
                                    <?php if ($podeEditar): ?>
                                        <a href="<?= url('setores', 'editar', ['id' => (int) $s['id']]) ?>"
                                           class="btn-acao" title="Editar" aria-label="Editar <?= e($s['nome']) ?>">
                                            <?= Icon::svg('editar', 'w-4 h-4') ?>
                                        </a>
                                    <?php endif; ?>

                                    <?php if ($podeExcluir): ?>
                                        <?php if ($vinculados > 0): ?>
                                            <span class="btn-acao-off" title="Não é possível excluir: há usuários vinculados">
                                                <?= Icon::svg('excluir', 'w-4 h-4') ?>
                                            </span>
                                        <?php else: ?>
                                            <form method="post" action="<?= url('setores', 'excluir') ?>" class="inline"
                                                  data-titulo="Excluir setor"
                                                  data-texto="O setor &quot;<?= e($s['nome']) ?>&quot; será removido permanentemente.">
                                                <?= Csrf::campo() ?>
                                                <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
                                                <button type="button" @click="abrir" class="btn-acao btn-acao-perigo"
                                                        title="Excluir" aria-label="Excluir <?= e($s['nome']) ?>">
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
