<?php
/**
 * As empresas parceiras que vendem os planos.
 *
 * Vira cadastro porque se repete em toda proposta e porque relatório por
 * empresa com o nome escrito de três jeitos não é relatório.
 *
 * @var array      $empresas, $erros
 * @var array|null $editando
 */
declare(strict_types=1);

$podeEditar  = Auth::temPermissao('vendas', 'pode_editar');
$podeExcluir = Auth::temPermissao('vendas', 'pode_excluir');

$emEdicao = $editando !== null;
$erroCls  = fn(string $c): string => isset($erros[$c]) ? 'input-error' : '';
$v        = fn(string $c): string => (string) ($editando[$c] ?? '');

$erroDe = function (string $campo) use ($erros): string {
    return isset($erros[$campo])
        ? '<p class="erro-campo">' . Icon::svg('erro', 'w-3.5 h-3.5') . ' ' . e($erros[$campo]) . '</p>'
        : '';
};
?>

<div class="mb-5 flex flex-wrap items-end justify-between gap-3">
    <div class="flex items-start gap-3">
        <a href="<?= url_lista('vendas') ?>" class="btn-acao mt-0.5" aria-label="Voltar às propostas">
            <?= Icon::svg('anterior', 'w-4 h-4') ?>
        </a>
        <div>
            <h1 class="titulo-pagina">Empresas parceiras</h1>
            <p class="sub-pagina">As corretoras que vendem os planos</p>
        </div>
    </div>

    <a href="<?= url('vendas', 'vendedores') ?>" class="btn btn-outline">
        <?= Icon::svg('usuarios', 'w-4 h-4') ?> Vendedores
    </a>
</div>

<div class="grid gap-4 lg:grid-cols-[22rem_minmax(0,1fr)] lg:items-start">

    <?php if ($podeEditar): ?>
        <div class="card">
            <div class="card-header">
                <h2 class="card-title"><?= $emEdicao ? 'Editar empresa' : 'Incluir empresa' ?></h2>
                <?php if ($emEdicao): ?>
                    <a href="<?= url('vendas', 'empresas') ?>" class="btn btn-ghost btn-sm">Cancelar</a>
                <?php endif; ?>
            </div>

            <form method="post" action="<?= url('vendas', 'salvar_empresa') ?>"
                  class="card-corpo space-y-3.5" x-data="envio" @submit="enviar" novalidate>
                <?= Csrf::campo() ?>
                <?php if ($emEdicao): ?>
                    <input type="hidden" name="id" value="<?= (int) $editando['id'] ?>">
                <?php endif; ?>

                <div>
                    <label class="label" for="nome">Nome <span class="obrigatorio">*</span></label>
                    <input type="text" name="nome" id="nome" maxlength="150" required
                           class="input <?= $erroCls('nome') ?>" value="<?= e($v('nome')) ?>"
                           placeholder="Ex.: Corretora Litoral">
                    <?= $erroDe('nome') ?>
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
                    <?= $erroDe('contato') ?>
                </div>

                <div>
                    <label class="label" for="email">E-mail</label>
                    <input type="email" name="email" id="email" maxlength="150"
                           class="input <?= $erroCls('email') ?>" value="<?= e($v('email')) ?>">
                    <?= $erroDe('email') ?>
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
                            Inativa sai das listas e o histórico de vendas continua.
                        </span>
                    </span>
                </label>

                <div class="form-acoes">
                    <button type="submit" class="btn btn-primary w-full" :disabled="enviando">
                        <span x-show="!enviando"><?= Icon::svg('salvar', 'w-4 h-4') ?></span>
                        <span x-show="enviando" x-cloak class="girando"></span>
                        <?= $emEdicao ? 'Salvar alterações' : 'Cadastrar empresa' ?>
                    </button>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <div class="card overflow-hidden">
        <div class="card-header">
            <h2 class="card-title">Empresas cadastradas</h2>
            <span class="text-xs text-slate-500 dark:text-slate-400"><?= numero(count($empresas)) ?> no total</span>
        </div>

        <?php if (empty($empresas)): ?>
            <div class="vazio">
                <span class="vazio-icone"><?= Icon::svg('setores', 'w-5 h-5') ?></span>
                <p class="vazio-titulo">Nenhuma empresa cadastrada</p>
                <p class="vazio-texto">
                    A empresa parceira é quem vende os planos. Cadastre a primeira ao lado
                    e depois inclua os vendedores dela.
                </p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="tabela">
                    <thead>
                        <tr>
                            <th>Empresa</th>
                            <th class="w-40">CNPJ</th>
                            <th class="w-36">Contato</th>
                            <th class="w-28 text-right">Vendedores</th>
                            <th class="w-24">Situação</th>
                            <th class="w-24 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($empresas as $emp): ?>
                            <tr>
                                <td>
                                    <span class="celula-principal block"><?= e($emp['nome']) ?></span>
                                    <?php if ($emp['email']): ?>
                                        <span class="block text-xs text-slate-500 dark:text-slate-400">
                                            <?= e($emp['email']) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td class="celula-suave mono text-xs">
                                    <?= $emp['cnpj'] ? e($emp['cnpj']) : '<span class="text-slate-400 dark:text-slate-600">—</span>' ?>
                                </td>

                                <td class="celula-suave text-xs">
                                    <?= $emp['contato'] ? e(telefone_br($emp['contato'])) : '<span class="text-slate-400 dark:text-slate-600">—</span>' ?>
                                </td>

                                <td class="text-right text-xs text-slate-600 dark:text-slate-400">
                                    <?= numero((int) $emp['vendedores']) ?>
                                </td>

                                <td>
                                    <span class="badge <?= $emp['ativo'] ? 'badge-verde' : 'badge-cinza' ?>">
                                        <?= $emp['ativo'] ? 'Ativa' : 'Inativa' ?>
                                    </span>
                                </td>

                                <td>
                                    <div class="flex items-center justify-end gap-0.5">
                                        <?php if ($podeEditar): ?>
                                            <a href="<?= url('vendas', 'empresas', ['id' => (int) $emp['id']]) ?>"
                                               class="btn-acao" title="Editar" aria-label="Editar <?= e($emp['nome']) ?>">
                                                <?= Icon::svg('editar', 'w-4 h-4') ?>
                                            </a>
                                        <?php endif; ?>

                                        <?php if ($podeExcluir): ?>
                                            <form method="post" action="<?= url('vendas', 'excluir_empresa') ?>" class="inline"
                                                  data-titulo="Excluir empresa parceira"
                                                  data-texto="<?= e($emp['nome']) ?> será removida. Empresa com vendedor cadastrado não pode ser excluída — desmarque &quot;Ativa&quot; para tirar das listas sem perder o histórico.">
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
