<?php
/**
 * As empresas empregadoras.
 *
 * Vira cadastro porque se repete muito e porque relatório por empresa com o
 * nome escrito de três jeitos não é relatório.
 *
 * @var array      $empresas, $uso, $erros
 * @var array|null $editando
 */
declare(strict_types=1);

$podeEditar  = Auth::temPermissao('ocupacional', 'pode_editar');
$podeExcluir = Auth::temPermissao('ocupacional', 'pode_excluir');

$emEdicao = $editando !== null;
$erroCls  = fn(string $c): string => isset($erros[$c]) ? 'input-error' : '';
$v        = fn(string $c): string => (string) ($editando[$c] ?? '');
?>

<div class="mb-5 flex flex-wrap items-end justify-between gap-3">
    <div class="flex items-start gap-3">
        <a href="<?= url_lista('ocupacional') ?>" class="btn-acao mt-0.5" aria-label="Voltar às fichas">
            <?= Icon::svg('anterior', 'w-4 h-4') ?>
        </a>
        <div>
            <h1 class="titulo-pagina">Empresas</h1>
            <p class="sub-pagina">As empregadoras dos trabalhadores examinados</p>
        </div>
    </div>

    <a href="<?= url('ocupacional', 'trabalhadores') ?>" class="btn btn-outline">
        <?= Icon::svg('usuarios', 'w-4 h-4') ?> Trabalhadores
    </a>
</div>

<div class="grid gap-4 lg:grid-cols-[22rem_minmax(0,1fr)] lg:items-start">

    <?php if ($podeEditar): ?>
        <div class="card">
            <div class="card-header">
                <h2 class="card-title"><?= $emEdicao ? 'Editar empresa' : 'Incluir empresa' ?></h2>
                <?php if ($emEdicao): ?>
                    <a href="<?= url('ocupacional', 'empresas') ?>" class="btn btn-ghost btn-sm">Cancelar</a>
                <?php endif; ?>
            </div>

            <form method="post" action="<?= url('ocupacional', 'salvar_empresa') ?>"
                  class="card-corpo space-y-3.5" x-data="envio" @submit="enviar" novalidate>
                <?= Csrf::campo() ?>
                <?php if ($emEdicao): ?>
                    <input type="hidden" name="id" value="<?= (int) $editando['id'] ?>">
                <?php endif; ?>

                <div>
                    <label class="label" for="nome">Nome <span class="obrigatorio">*</span></label>
                    <input type="text" name="nome" id="nome" maxlength="150" required
                           class="input <?= $erroCls('nome') ?>" value="<?= e($v('nome')) ?>"
                           placeholder="Ex.: APAS Baixada Santista">
                    <?php if (isset($erros['nome'])): ?>
                        <p class="erro-campo"><?= Icon::svg('erro', 'w-3.5 h-3.5') ?> <?= e($erros['nome']) ?></p>
                    <?php endif; ?>
                </div>

                <div>
                    <label class="label" for="cnpj">CNPJ</label>
                    <input type="text" name="cnpj" id="cnpj" maxlength="18" class="input mono"
                           value="<?= e($v('cnpj')) ?>" placeholder="00.000.000/0000-00">
                </div>

                <div>
                    <label class="label" for="contato">Telefone</label>
                    <input type="tel" name="contato" id="contato" inputmode="numeric" maxlength="15"
                           class="input <?= $erroCls('contato') ?>" value="<?= e(telefone_br($v('contato'))) ?>"
                           x-data="telefone" @input="formatar" placeholder="(13) 99999-9999">
                    <?php if (isset($erros['contato'])): ?>
                        <p class="erro-campo"><?= Icon::svg('erro', 'w-3.5 h-3.5') ?> <?= e($erros['contato']) ?></p>
                    <?php endif; ?>
                </div>

                <div>
                    <label class="label" for="observacao">Observação</label>
                    <input type="text" name="observacao" id="observacao" maxlength="400" class="input"
                           value="<?= e($v('observacao')) ?>" placeholder="Ex.: contato do RH é a Márcia">
                </div>

                <label class="flex cursor-pointer items-start gap-2">
                    <input type="checkbox" name="ativo" value="1" class="check mt-0.5"
                        <?= !$emEdicao || !empty($editando['ativo']) ? 'checked' : '' ?>>
                    <span class="text-[0.8125rem]">
                        Ativa
                        <span class="block text-xs text-slate-500 dark:text-slate-400">
                            Desmarque para tirar da lista sem perder o histórico.
                        </span>
                    </span>
                </label>

                <button type="submit" class="btn btn-primary w-full" :disabled="enviando">
                    <?= Icon::svg('salvar', 'w-4 h-4') ?>
                    <?= $emEdicao ? 'Salvar alterações' : 'Incluir empresa' ?>
                </button>
            </form>
        </div>
    <?php endif; ?>

    <div class="card overflow-hidden">
        <?php if (empty($empresas)): ?>
            <div class="vazio">
                <span class="vazio-icone"><?= Icon::svg('setores', 'w-5 h-5') ?></span>
                <p class="vazio-titulo">Nenhuma empresa cadastrada</p>
                <p class="vazio-texto">Cadastre a primeira ao lado — depois vêm os trabalhadores dela.</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="tabela">
                    <thead>
                        <tr>
                            <th>Empresa</th>
                            <th class="w-40">CNPJ</th>
                            <th class="w-36">Telefone</th>
                            <th class="w-32">Trabalhadores</th>
                            <th class="w-24 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($empresas as $emp): ?>
                            <?php $quantos = (int) ($uso[(int) $emp['id']] ?? 0); ?>
                            <tr>
                                <td>
                                    <span class="celula-principal block"><?= e($emp['nome']) ?></span>
                                    <?php if (!$emp['ativo']): ?>
                                        <span class="badge badge-cinza mt-0.5">Inativa</span>
                                    <?php endif; ?>
                                    <?php if ($emp['observacao']): ?>
                                        <span class="block text-xs text-slate-500 dark:text-slate-400">
                                            <?= e($emp['observacao']) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="celula-suave mono text-xs">
                                    <?= $emp['cnpj'] ? e($emp['cnpj']) : '<span class="text-slate-400 dark:text-slate-600">—</span>' ?>
                                </td>
                                <td class="celula-suave text-xs">
                                    <?php if ($emp['contato']): ?>
                                        <a href="tel:+55<?= e($emp['contato']) ?>" class="mono hover:underline">
                                            <?= e(telefone_br($emp['contato'])) ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-slate-400 dark:text-slate-600">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="celula-suave text-xs">
                                    <?php if ($quantos > 0): ?>
                                        <a href="<?= url('ocupacional', 'trabalhadores', ['empresa' => (int) $emp['id']]) ?>"
                                           class="hover:underline"><?= numero($quantos) ?></a>
                                    <?php else: ?>
                                        <span class="text-slate-400 dark:text-slate-600">nenhum</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="flex items-center justify-end gap-0.5">
                                        <?php if ($podeEditar): ?>
                                            <a href="<?= url('ocupacional', 'empresas', ['id' => (int) $emp['id']]) ?>"
                                               class="btn-acao" title="Editar"
                                               aria-label="Editar <?= e($emp['nome']) ?>">
                                                <?= Icon::svg('editar', 'w-4 h-4') ?>
                                            </a>
                                        <?php endif; ?>

                                        <?php /* Empresa com trabalhador não se apaga — o controller
                                                 recusa e explica. O botão some para não prometer
                                                 uma ação que vai ser negada. */ ?>
                                        <?php if ($podeExcluir && $quantos === 0): ?>
                                            <form method="post" action="<?= url('ocupacional', 'excluir_empresa') ?>" class="inline"
                                                  data-titulo="Excluir empresa"
                                                  data-texto="<?= e($emp['nome']) ?> será removida. Esta ação não pode ser desfeita.">
                                                <?= Csrf::campo() ?>
                                                <input type="hidden" name="id" value="<?= (int) $emp['id'] ?>">
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
        <?php endif; ?>
    </div>
</div>
