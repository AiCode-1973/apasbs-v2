<?php
/**
 * As tabelas de valores: um edital de um convênio, com a data em que
 * passou a valer.
 *
 * Ela é a capa dos itens. Trocar de edital não apaga o anterior — é por
 * isso que dá para explicar, meses depois, quanto valia um procedimento no
 * dia do atendimento.
 *
 * @var array      $tabelas, $erros
 * @var array|null $editando
 */
declare(strict_types=1);

$podeEditar  = Auth::temPermissao('comercial', 'pode_editar');
$podeExcluir = Auth::temPermissao('comercial', 'pode_excluir');

$emEdicao = $editando !== null;
$erroCls  = fn(string $c): string => isset($erros[$c]) ? 'input-error' : '';
$v        = fn(string $c, string $padrao = ''): string => (string) ($editando[$c] ?? $padrao);

$hoje = date('Y-m-d');
?>

<div class="mb-5 flex flex-wrap items-end justify-between gap-3">
    <div class="flex items-start gap-3">
        <a href="<?= url_lista('comercial') ?>" class="btn-acao mt-0.5" aria-label="Voltar aos itens">
            <?= Icon::svg('anterior', 'w-4 h-4') ?>
        </a>
        <div>
            <h1 class="titulo-pagina">Tabelas de valores</h1>
            <p class="sub-pagina">Um edital de um convênio, com a vigência</p>
        </div>
    </div>
</div>

<div class="grid gap-4 lg:grid-cols-[22rem_minmax(0,1fr)] lg:items-start">

    <?php if ($podeEditar): ?>
        <div class="card">
            <div class="card-header">
                <h2 class="card-title"><?= $emEdicao ? 'Editar tabela' : 'Nova tabela' ?></h2>
                <?php if ($emEdicao): ?>
                    <a href="<?= url('comercial', 'tabelas') ?>" class="btn btn-ghost btn-sm">Cancelar</a>
                <?php endif; ?>
            </div>

            <form method="post" action="<?= url('comercial', 'salvar_tabela') ?>"
                  class="card-corpo space-y-3.5" x-data="envio" @submit="enviar" novalidate>
                <?= Csrf::campo() ?>
                <?php if ($emEdicao): ?>
                    <input type="hidden" name="id" value="<?= (int) $editando['id'] ?>">
                <?php endif; ?>

                <div>
                    <label class="label" for="convenio">Convênio <span class="obrigatorio">*</span></label>
                    <input type="text" name="convenio" id="convenio" maxlength="80" required
                           class="input <?= $erroCls('convenio') ?>" value="<?= e($v('convenio')) ?>"
                           placeholder="Ex.: CAPEP-SAÚDE">
                    <?php if (isset($erros['convenio'])): ?>
                        <p class="erro-campo"><?= Icon::svg('erro', 'w-3.5 h-3.5') ?> <?= e($erros['convenio']) ?></p>
                    <?php endif; ?>
                </div>

                <div>
                    <label class="label" for="referencia">Edital ou contrato <span class="obrigatorio">*</span></label>
                    <input type="text" name="referencia" id="referencia" maxlength="180" required
                           class="input <?= $erroCls('referencia') ?>" value="<?= e($v('referencia')) ?>"
                           placeholder="Ex.: Edital 011/2022 — 29º retificado">
                    <?php if (isset($erros['referencia'])): ?>
                        <p class="erro-campo"><?= Icon::svg('erro', 'w-3.5 h-3.5') ?> <?= e($erros['referencia']) ?></p>
                    <?php endif; ?>
                </div>

                <div>
                    <label class="label" for="vigencia">Vigência <span class="obrigatorio">*</span></label>
                    <input type="date" name="vigencia" id="vigencia" required
                           class="input <?= $erroCls('vigencia') ?>"
                           value="<?= e(substr($v('vigencia'), 0, 10)) ?>">
                    <?php if (isset($erros['vigencia'])): ?>
                        <p class="erro-campo"><?= Icon::svg('erro', 'w-3.5 h-3.5') ?> <?= e($erros['vigencia']) ?></p>
                    <?php endif; ?>
                    <p class="ajuda">A data em que estes valores passam a valer.</p>
                </div>

                <div>
                    <label class="label" for="observacao">Observação</label>
                    <textarea name="observacao" id="observacao" rows="2" maxlength="400"
                              class="textarea"><?= e($v('observacao')) ?></textarea>
                </div>

                <label class="flex cursor-pointer items-start gap-3">
                    <input type="checkbox" name="ativo" class="check mt-0.5"
                           <?= ($editando['ativo'] ?? 1) ? 'checked' : '' ?>>
                    <span class="text-sm text-slate-700 dark:text-slate-300">
                        Ativa
                        <span class="block text-xs text-slate-500 dark:text-slate-400">
                            Tabela desativada não é escolhida sozinha na lista de itens.
                        </span>
                    </span>
                </label>

                <div class="form-acoes">
                    <button type="submit" class="btn btn-primary w-full" :disabled="enviando">
                        <span x-show="!enviando"><?= Icon::svg('salvar', 'w-4 h-4') ?></span>
                        <span x-show="enviando" x-cloak class="girando"></span>
                        <?= $emEdicao ? 'Salvar alterações' : 'Cadastrar tabela' ?>
                    </button>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <div class="card overflow-hidden">
        <div class="card-header">
            <h2 class="card-title">Tabelas cadastradas</h2>
            <span class="text-xs text-slate-500 dark:text-slate-400">
                <?= numero(count($tabelas)) ?> no total
            </span>
        </div>

        <?php if (empty($tabelas)): ?>
            <div class="vazio">
                <span class="vazio-icone"><?= Icon::svg('comercial', 'w-5 h-5') ?></span>
                <p class="vazio-titulo">Nenhuma tabela cadastrada</p>
                <p class="vazio-texto">
                    Cadastre o convênio e o edital ao lado. Os itens entram depois,
                    pela carga do arquivo SQL ou um a um pela tela.
                </p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="tabela">
                    <thead>
                        <tr>
                            <th>Convênio e edital</th>
                            <th class="w-28">Vigência</th>
                            <th class="w-24 text-right">Itens</th>
                            <th class="w-24">Situação</th>
                            <th class="w-24 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tabelas as $t): ?>
                            <?php $futura = $t['vigencia'] > $hoje; ?>
                            <tr>
                                <td>
                                    <a href="<?= url('comercial', 'lista', ['tabela' => (int) $t['id']]) ?>"
                                       class="celula-principal block hover:underline"><?= e($t['convenio']) ?></a>
                                    <span class="block text-xs text-slate-500 dark:text-slate-400">
                                        <?= e($t['referencia']) ?>
                                    </span>
                                </td>

                                <td class="celula-suave text-xs">
                                    <?= e(data_br($t['vigencia'])) ?>
                                    <?php if ($futura): ?>
                                        <span class="block text-[0.6875rem] text-amber-600 dark:text-amber-400">
                                            ainda não vale
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td class="text-right text-xs text-slate-600 dark:text-slate-400">
                                    <?= numero((int) $t['itens']) ?>
                                </td>

                                <td>
                                    <span class="badge <?= $t['ativo'] ? 'badge-verde' : 'badge-cinza' ?>">
                                        <?= $t['ativo'] ? 'Ativa' : 'Inativa' ?>
                                    </span>
                                </td>

                                <td>
                                    <div class="flex items-center justify-end gap-0.5">
                                        <?php if ($podeEditar): ?>
                                            <a href="<?= url('comercial', 'tabelas', ['id' => (int) $t['id']]) ?>"
                                               class="btn-acao" title="Editar" aria-label="Editar <?= e($t['convenio']) ?>">
                                                <?= Icon::svg('editar', 'w-4 h-4') ?>
                                            </a>
                                        <?php endif; ?>

                                        <?php if ($podeExcluir): ?>
                                            <form method="post" action="<?= url('comercial', 'excluir_tabela') ?>" class="inline"
                                                  data-titulo="Excluir tabela de valores"
                                                  data-texto="A tabela <?= e($t['convenio']) ?> — <?= e($t['referencia']) ?> será removida com os <?= e(numero((int) $t['itens'])) ?> itens dentro dela. Para tirar de circulação sem perder os valores, desmarque &quot;Ativa&quot;.">
                                                <?= Csrf::campo() ?>
                                                <input type="hidden" name="id" value="<?= (int) $t['id'] ?>">
                                                <?php if (!$t['ativo']): ?>
                                                    <input type="hidden" name="confirmo_itens" value="1">
                                                <?php endif; ?>
                                                <button type="button" @click="abrir" class="btn-acao btn-acao-perigo"
                                                        title="Excluir" aria-label="Excluir">
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

            <div class="border-t border-slate-100 px-4 py-2.5 text-xs text-slate-500 dark:border-slate-800 dark:text-slate-400">
                Tabela com itens só pode ser excluída depois de desativada — são
                milhares de linhas, e elas vão junto.
            </div>
        <?php endif; ?>
    </div>
</div>
