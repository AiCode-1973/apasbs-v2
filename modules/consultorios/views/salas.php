<?php
/**
 * Cadastro das salas do ambulatório. Tela simples de manutenção: a lista
 * e um formulário ao lado, porque salas são poucas e mudam pouco.
 *
 * @var array $salas
 * @var array $sala
 * @var array $erros
 */
declare(strict_types=1);

$podeCriar   = Auth::temPermissao('consultorios', 'pode_criar');
$podeEditar  = Auth::temPermissao('consultorios', 'pode_editar');
$podeExcluir = Auth::temPermissao('consultorios', 'pode_excluir');
$editando    = !empty($sala['id']);
?>

<div class="mb-5 flex flex-wrap items-end justify-between gap-3">
    <div class="flex items-center gap-3">
        <a href="<?= url('consultorios', 'mapa') ?>" class="btn btn-icon btn-outline" aria-label="Voltar ao mapa">
            <?= Icon::svg('voltar', 'w-4 h-4') ?>
        </a>
        <div>
            <h1 class="titulo-pagina">Salas do ambulatório</h1>
            <p class="sub-pagina">As linhas do mapa — a ordem aqui é a ordem lá</p>
        </div>
    </div>
</div>

<div class="grid gap-4 lg:grid-cols-[1.5fr_1fr]">

    <div class="card overflow-hidden">
        <div class="card-header">
            <h2 class="card-title">Cadastradas</h2>
            <span class="text-xs text-slate-500 dark:text-slate-400">
                <?= count($salas) ?> sala<?= count($salas) === 1 ? '' : 's' ?>
            </span>
        </div>

        <?php if (empty($salas)): ?>
            <div class="vazio">
                <span class="vazio-icone"><?= Icon::svg('setores', 'w-5 h-5') ?></span>
                <p class="vazio-titulo">Nenhuma sala cadastrada</p>
                <p class="vazio-texto">Comece pela Sala 1. O mapa se monta a partir daqui.</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="tabela">
                    <thead>
                        <tr>
                            <th>Sala</th>
                            <th class="w-32 text-right">Ocupações</th>
                            <th class="w-24">Situação</th>
                            <th class="w-24 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($salas as $s): ?>
                            <?php $qtd = (int) $s['qtd_ocupacoes']; ?>
                            <tr>
                                <td>
                                    <span class="celula-principal"><?= e($s['nome']) ?></span>
                                    <?php if (!empty($s['observacao'])): ?>
                                        <span class="block text-xs text-slate-500 dark:text-slate-400">
                                            <?= e($s['observacao']) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td class="celula-suave text-right text-xs">
                                    <?= $qtd > 0 ? numero($qtd) : '—' ?>
                                </td>

                                <td>
                                    <span class="badge <?= $s['ativo'] ? 'badge-verde' : 'badge-cinza' ?>">
                                        <?= $s['ativo'] ? 'No mapa' : 'Fora' ?>
                                    </span>
                                </td>

                                <td>
                                    <div class="flex items-center justify-end gap-0.5">
                                        <?php if ($podeEditar): ?>
                                            <a href="<?= url('consultorios', 'salas', ['editar' => (int) $s['id']]) ?>"
                                               class="btn-acao" title="Editar" aria-label="Editar <?= e($s['nome']) ?>">
                                                <?= Icon::svg('editar', 'w-4 h-4') ?>
                                            </a>
                                        <?php endif; ?>

                                        <?php if ($podeExcluir): ?>
                                            <form method="post" action="<?= url('consultorios', 'excluir_sala') ?>" class="inline"
                                                  data-titulo="Excluir sala"
                                                  data-texto="<?= e($s['nome']) ?> será removida<?= $qtd > 0
                                                      ? ', junto com as ' . $qtd . ' ocupações lançadas nela'
                                                      : '' ?>.">
                                                <?= Csrf::campo() ?>
                                                <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
                                                <button type="button" @click="abrir" class="btn-acao btn-acao-perigo"
                                                        title="Excluir" aria-label="Excluir <?= e($s['nome']) ?>">
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

    <?php if ($podeCriar || $podeEditar): ?>
        <div class="card">
            <div class="card-header">
                <h2 class="card-title"><?= $editando ? 'Editar sala' : 'Nova sala' ?></h2>
                <?php if ($editando): ?>
                    <a href="<?= url('consultorios', 'salas') ?>" class="btn btn-ghost btn-sm">Cancelar</a>
                <?php endif; ?>
            </div>

            <form method="post" action="<?= url('consultorios', 'salvar_sala') ?>" class="card-corpo space-y-4"
                  novalidate x-data="envio" @submit="enviar">
                <?= Csrf::campo() ?>
                <?php if ($editando): ?>
                    <input type="hidden" name="id" value="<?= (int) $sala['id'] ?>">
                <?php endif; ?>

                <div>
                    <label class="label" for="nome">Nome <span class="obrigatorio">*</span></label>
                    <input type="text" name="nome" id="nome" maxlength="60" required
                           class="input <?= isset($erros['nome']) ? 'input-error' : '' ?>"
                           value="<?= e($sala['nome'] ?? '') ?>" placeholder="Ex.: Sala 9">
                    <?php if (isset($erros['nome'])): ?>
                        <p class="erro-campo"><?= Icon::svg('erro', 'w-3.5 h-3.5') ?> <?= e($erros['nome']) ?></p>
                    <?php endif; ?>
                </div>

                <div>
                    <label class="label" for="observacao">Observação</label>
                    <input type="text" name="observacao" id="observacao" maxlength="200" class="input"
                           value="<?= e($sala['observacao'] ?? '') ?>"
                           placeholder="Ex.: sala pequena, sem maca">
                    <p class="ajuda">Aparece em letra miúda embaixo do nome, no mapa.</p>
                </div>

                <div>
                    <label class="label" for="ordem">Ordem</label>
                    <input type="number" name="ordem" id="ordem" min="0" max="9999" class="input num"
                           value="<?= (int) ($sala['ordem'] ?? 0) ?>">
                    <p class="ajuda">Menor primeiro. É a ordem das linhas do mapa.</p>
                </div>

                <label class="flex cursor-pointer items-start gap-3">
                    <input type="checkbox" name="ativo" class="check mt-0.5" <?= ($sala['ativo'] ?? 1) ? 'checked' : '' ?>>
                    <span>
                        <span class="block text-[0.8125rem] font-medium text-slate-800 dark:text-slate-100">No mapa</span>
                        <span class="block text-xs text-slate-500 dark:text-slate-400">
                            Desmarque para tirar a sala da grade sem apagar nada — reforma, por exemplo.
                        </span>
                    </span>
                </label>

                <div class="flex justify-end">
                    <button type="submit" class="btn btn-primary" :disabled="enviando">
                        <span x-show="!enviando"><?= Icon::svg('salvar', 'w-4 h-4') ?></span>
                        <span x-show="enviando" x-cloak class="girando"></span>
                        <?= $editando ? 'Salvar' : 'Cadastrar' ?>
                    </button>
                </div>
            </form>
        </div>
    <?php endif; ?>
</div>
