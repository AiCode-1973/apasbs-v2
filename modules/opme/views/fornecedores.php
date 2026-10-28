<?php
/**
 * Cadastro dos fornecedores de OPME.
 *
 * @var array  $fornecedores
 * @var array  $fornecedor
 * @var array  $erros
 * @var string $busca
 */
declare(strict_types=1);

$podeCriar   = Auth::temPermissao('opme', 'pode_criar');
$podeEditar  = Auth::temPermissao('opme', 'pode_editar');
$podeExcluir = Auth::temPermissao('opme', 'pode_excluir');
$editando    = !empty($fornecedor['id']);
?>

<div class="mb-5 flex flex-wrap items-end justify-between gap-3">
    <div class="flex items-center gap-3">
        <a href="<?= url_lista('opme') ?>" class="btn btn-icon btn-outline" aria-label="Voltar">
            <?= Icon::svg('voltar', 'w-4 h-4') ?>
        </a>
        <div>
            <h1 class="titulo-pagina">Fornecedores de OPME</h1>
            <p class="sub-pagina">Quem fornece o material — e quanto se deve a cada um</p>
        </div>
    </div>
</div>

<div class="grid gap-4 lg:grid-cols-[1.7fr_1fr]">

    <div class="card overflow-hidden">
        <form method="get" action="<?= BASE_URL ?>/" class="toolbar">
            <input type="hidden" name="mod" value="opme">
            <input type="hidden" name="action" value="fornecedores">

            <div class="campo-icone w-full sm:w-72">
                <?= Icon::svg('buscar') ?>
                <input type="search" name="q" value="<?= e($busca) ?>" class="input"
                       placeholder="Nome, CNPJ ou contato…" aria-label="Buscar fornecedores">
            </div>

            <button type="submit" class="btn btn-outline">Buscar</button>

            <?php if ($busca !== ''): ?>
                <a href="<?= url('opme', 'fornecedores') ?>" class="btn btn-ghost">Limpar</a>
            <?php endif; ?>

            <span class="ml-auto text-xs text-slate-500 dark:text-slate-400">
                <?= count($fornecedores) ?> cadastrado<?= count($fornecedores) === 1 ? '' : 's' ?>
            </span>
        </form>

        <?php if (empty($fornecedores)): ?>
            <div class="vazio">
                <span class="vazio-icone"><?= Icon::svg('setores', 'w-5 h-5') ?></span>
                <p class="vazio-titulo">
                    <?= $busca !== '' ? 'Nenhum fornecedor encontrado' : 'Nenhum fornecedor cadastrado' ?>
                </p>
                <p class="vazio-texto">
                    Cadastre os fornecedores antes de lançar as notas — é por eles que
                    o pagamento é organizado.
                </p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="tabela">
                    <thead>
                        <tr>
                            <th>Fornecedor</th>
                            <th class="w-24 text-right">Notas</th>
                            <th class="w-32 text-right">A pagar</th>
                            <th class="w-24">Situação</th>
                            <th class="w-24 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($fornecedores as $f): ?>
                            <?php $notas = (int) $f['qtd_notas']; ?>
                            <tr>
                                <td>
                                    <span class="celula-principal"><?= e($f['nome']) ?></span>
                                    <?php if ($f['contato'] || $f['telefone']): ?>
                                        <span class="block text-xs text-slate-500 dark:text-slate-400">
                                            <?= e(trim(($f['contato'] ?? '') . ($f['telefone'] ? ' · ' . $f['telefone'] : ''), ' ·')) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td class="celula-suave text-right text-xs"><?= $notas > 0 ? numero($notas) : '—' ?></td>

                                <td class="num text-right text-[0.8125rem]">
                                    <?= (float) $f['a_pagar'] > 0.005
                                        ? e(dinheiro_br((float) $f['a_pagar'], false))
                                        : '<span class="text-slate-400 dark:text-slate-600">—</span>' ?>
                                </td>

                                <td>
                                    <span class="badge <?= $f['ativo'] ? 'badge-verde' : 'badge-cinza' ?>">
                                        <?= $f['ativo'] ? 'Ativo' : 'Inativo' ?>
                                    </span>
                                </td>

                                <td>
                                    <div class="flex items-center justify-end gap-0.5">
                                        <?php if ($podeEditar): ?>
                                            <a href="<?= url('opme', 'fornecedores', ['editar' => (int) $f['id']]) ?>"
                                               class="btn-acao" title="Editar" aria-label="Editar <?= e($f['nome']) ?>">
                                                <?= Icon::svg('editar', 'w-4 h-4') ?>
                                            </a>
                                        <?php endif; ?>

                                        <?php if ($podeExcluir): ?>
                                            <?php if ($notas > 0): ?>
                                                <span class="btn-acao-off"
                                                      title="Não pode excluir: já tem <?= $notas ?> nota(s) lançada(s). Marque como inativo.">
                                                    <?= Icon::svg('excluir', 'w-4 h-4') ?>
                                                </span>
                                            <?php else: ?>
                                                <form method="post" action="<?= url('opme', 'excluir_fornecedor') ?>" class="inline"
                                                      data-titulo="Excluir fornecedor"
                                                      data-texto="<?= e($f['nome']) ?> será removido do cadastro.">
                                                    <?= Csrf::campo() ?>
                                                    <input type="hidden" name="id" value="<?= (int) $f['id'] ?>">
                                                    <button type="button" @click="abrir" class="btn-acao btn-acao-perigo"
                                                            title="Excluir" aria-label="Excluir <?= e($f['nome']) ?>">
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

    <?php if ($podeCriar || $podeEditar): ?>
        <div class="card self-start">
            <div class="card-header">
                <h2 class="card-title"><?= $editando ? 'Editar fornecedor' : 'Novo fornecedor' ?></h2>
                <?php if ($editando): ?>
                    <a href="<?= url('opme', 'fornecedores') ?>" class="btn btn-ghost btn-sm">Cancelar</a>
                <?php endif; ?>
            </div>

            <form method="post" action="<?= url('opme', 'salvar_fornecedor') ?>"
                  class="card-corpo space-y-4" novalidate x-data="envio" @submit="enviar">
                <?= Csrf::campo() ?>
                <?php if ($editando): ?>
                    <input type="hidden" name="id" value="<?= (int) $fornecedor['id'] ?>">
                <?php endif; ?>

                <div>
                    <label class="label" for="nome">Nome <span class="obrigatorio">*</span></label>
                    <input type="text" name="nome" id="nome" maxlength="120" required
                           class="input <?= isset($erros['nome']) ? 'input-error' : '' ?>"
                           value="<?= e($fornecedor['nome'] ?? '') ?>" placeholder="Ex.: Millenium">
                    <?php if (isset($erros['nome'])): ?>
                        <p class="erro-campo"><?= Icon::svg('erro', 'w-3.5 h-3.5') ?> <?= e($erros['nome']) ?></p>
                    <?php endif; ?>
                </div>

                <div>
                    <label class="label" for="cnpj">CNPJ</label>
                    <input type="text" name="cnpj" id="cnpj" maxlength="20" class="input mono"
                           value="<?= e($fornecedor['cnpj'] ?? '') ?>">
                </div>

                <div>
                    <label class="label" for="contato">Contato</label>
                    <input type="text" name="contato" id="contato" maxlength="120" class="input"
                           value="<?= e($fornecedor['contato'] ?? '') ?>" placeholder="Nome de quem atende">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="label" for="telefone">Telefone</label>
                        <input type="text" name="telefone" id="telefone" maxlength="30" class="input"
                               value="<?= e($fornecedor['telefone'] ?? '') ?>">
                    </div>
                    <div>
                        <label class="label" for="email">E-mail</label>
                        <input type="email" name="email" id="email" maxlength="150"
                               class="input <?= isset($erros['email']) ? 'input-error' : '' ?>"
                               value="<?= e($fornecedor['email'] ?? '') ?>">
                    </div>
                    <?php if (isset($erros['email'])): ?>
                        <p class="erro-campo col-span-2"><?= Icon::svg('erro', 'w-3.5 h-3.5') ?> <?= e($erros['email']) ?></p>
                    <?php endif; ?>
                </div>

                <div>
                    <label class="label" for="observacao">Observação</label>
                    <input type="text" name="observacao" id="observacao" maxlength="300" class="input"
                           value="<?= e($fornecedor['observacao'] ?? '') ?>">
                </div>

                <label class="flex cursor-pointer items-start gap-3">
                    <input type="checkbox" name="ativo" class="check mt-0.5" <?= ($fornecedor['ativo'] ?? 1) ? 'checked' : '' ?>>
                    <span>
                        <span class="block text-[0.8125rem] font-medium text-slate-800 dark:text-slate-100">Ativo</span>
                        <span class="block text-xs text-slate-500 dark:text-slate-400">
                            Inativo some da lista de escolha. As notas dele continuam no sistema.
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
