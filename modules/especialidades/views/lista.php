<?php
/** @var array $especialidades */
declare(strict_types=1);

$podeCriar   = Auth::temPermissao('especialidades', 'pode_criar');
$podeEditar  = Auth::temPermissao('especialidades', 'pode_editar');
$podeExcluir = Auth::temPermissao('especialidades', 'pode_excluir');
?>

<div class="mb-5 flex flex-wrap items-end justify-between gap-3">
    <div>
        <h1 class="titulo-pagina">Especialidades médicas</h1>
        <p class="sub-pagina">Referência do Conselho Federal de Medicina</p>
    </div>

    <?php if ($podeCriar): ?>
        <a href="<?= url('especialidades', 'novo') ?>" class="btn btn-primary">
            <?= Icon::svg('novo', 'w-4 h-4') ?> Nova especialidade
        </a>
    <?php endif; ?>
</div>

<div class="card overflow-hidden">
    <div class="card-header">
        <h2 class="card-title">Especialidades cadastradas</h2>
        <span class="text-xs text-slate-500 dark:text-slate-400">
            <?= count($especialidades) ?> registro<?= count($especialidades) === 1 ? '' : 's' ?>
        </span>
    </div>

    <?php if (empty($especialidades)): ?>
        <div class="vazio">
            <span class="vazio-icone"><?= Icon::svg('especialidades', 'w-5 h-5') ?></span>
            <p class="vazio-titulo">Nenhuma especialidade cadastrada</p>
            <p class="vazio-texto">
                Rode o script <code class="rounded bg-slate-100 px-1 py-0.5 text-[0.7rem] dark:bg-slate-800">sql/especialidades.sql</code>
                para carregar as 55 especialidades reconhecidas pelo CFM.
            </p>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="tabela">
                <thead>
                    <tr>
                        <th>Especialidade</th>
                        <th class="w-32">Cirurgias</th>
                        <th class="w-28">Situação</th>
                        <th class="w-24 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($especialidades as $esp): ?>
                        <?php $usos = (int) $esp['total_cirurgias']; ?>
                        <tr>
                            <td class="celula-principal"><?= e($esp['nome']) ?></td>
                            <td class="celula-suave"><?= $usos ?></td>
                            <td>
                                <span class="badge badge-ponto <?= $esp['ativo'] ? 'badge-verde' : 'badge-cinza' ?>">
                                    <?= $esp['ativo'] ? 'Ativa' : 'Inativa' ?>
                                </span>
                            </td>
                            <td>
                                <div class="flex items-center justify-end gap-0.5">
                                    <?php if ($podeEditar): ?>
                                        <a href="<?= url('especialidades', 'editar', ['id' => (int) $esp['id']]) ?>"
                                           class="btn-acao" title="Editar" aria-label="Editar <?= e($esp['nome']) ?>">
                                            <?= Icon::svg('editar', 'w-4 h-4') ?>
                                        </a>
                                    <?php endif; ?>

                                    <?php if ($podeExcluir): ?>
                                        <?php if ($usos > 0): ?>
                                            <span class="btn-acao-off" title="Não é possível excluir: há cirurgias vinculadas">
                                                <?= Icon::svg('excluir', 'w-4 h-4') ?>
                                            </span>
                                        <?php else: ?>
                                            <form method="post" action="<?= url('especialidades', 'excluir') ?>" class="inline"
                                                  data-titulo="Excluir especialidade"
                                                  data-texto="A especialidade &quot;<?= e($esp['nome']) ?>&quot; será removida permanentemente.">
                                                <?= Csrf::campo() ?>
                                                <input type="hidden" name="id" value="<?= (int) $esp['id'] ?>">
                                                <button type="button" @click="abrir" class="btn-acao btn-acao-perigo"
                                                        title="Excluir" aria-label="Excluir <?= e($esp['nome']) ?>">
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
