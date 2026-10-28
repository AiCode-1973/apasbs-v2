<?php
/**
 * Os vendedores das empresas parceiras.
 *
 * Gente de fora, que normalmente não tem login no APASBS. Quem tiver, fica
 * amarrado ao usuário — é por esse vínculo que o sistema mostra a ele
 * apenas as vendas dele.
 *
 * @var array      $vendedores, $empresas, $usuarios, $erros
 * @var array|null $editando
 */
declare(strict_types=1);

$podeEditar  = Auth::temPermissao('vendas', 'pode_editar');
$podeExcluir = Auth::temPermissao('vendas', 'pode_excluir');

$emEdicao = $editando !== null;
$erroCls  = fn(string $c): string => isset($erros[$c]) ? 'input-error' : '';
$v        = fn(string $c, string $padrao = ''): string => (string) ($editando[$c] ?? $padrao);

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
            <h1 class="titulo-pagina">Vendedores</h1>
            <p class="sub-pagina">Quem vende, por qual empresa e com qual comissão</p>
        </div>
    </div>

    <a href="<?= url('vendas', 'empresas') ?>" class="btn btn-outline">
        <?= Icon::svg('setores', 'w-4 h-4') ?> Empresas parceiras
    </a>
</div>

<div class="grid gap-4 lg:grid-cols-[24rem_minmax(0,1fr)] lg:items-start">

    <?php if ($podeEditar): ?>
        <div class="card">
            <div class="card-header">
                <h2 class="card-title"><?= $emEdicao ? 'Editar vendedor' : 'Incluir vendedor' ?></h2>
                <?php if ($emEdicao): ?>
                    <a href="<?= url('vendas', 'vendedores') ?>" class="btn btn-ghost btn-sm">Cancelar</a>
                <?php endif; ?>
            </div>

            <form method="post" action="<?= url('vendas', 'salvar_vendedor') ?>"
                  class="card-corpo space-y-3.5" x-data="envio" @submit="enviar" novalidate>
                <?= Csrf::campo() ?>
                <?php if ($emEdicao): ?>
                    <input type="hidden" name="id" value="<?= (int) $editando['id'] ?>">
                <?php endif; ?>

                <div>
                    <label class="label" for="empresa_id">Empresa parceira <span class="obrigatorio">*</span></label>
                    <select name="empresa_id" id="empresa_id" required class="select <?= $erroCls('empresa_id') ?>">
                        <option value="">Escolha…</option>
                        <?php foreach ($empresas as $emp): ?>
                            <option value="<?= (int) $emp['id'] ?>"
                                <?= (int) $v('empresa_id') === (int) $emp['id'] ? 'selected' : '' ?>>
                                <?= e($emp['nome']) ?><?= $emp['ativo'] ? '' : ' (inativa)' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?= $erroDe('empresa_id') ?>
                </div>

                <div>
                    <label class="label" for="nome">Nome <span class="obrigatorio">*</span></label>
                    <input type="text" name="nome" id="nome" maxlength="150" required
                           class="input <?= $erroCls('nome') ?>" value="<?= e($v('nome')) ?>">
                    <?= $erroDe('nome') ?>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="label" for="cpf">CPF</label>
                        <input type="text" name="cpf" id="cpf" inputmode="numeric" maxlength="14"
                               class="input mono <?= $erroCls('cpf') ?>"
                               value="<?= e($v('cpf') !== '' ? formatar_cpf($v('cpf')) : '') ?>"
                               x-data="cpf" @input="formatar" placeholder="000.000.000-00">
                        <?= $erroDe('cpf') ?>
                    </div>

                    <div>
                        <label class="label" for="comissao_percent">Comissão (%)</label>
                        <input type="text" name="comissao_percent" id="comissao_percent" inputmode="decimal"
                               class="input num <?= $erroCls('comissao_percent') ?>"
                               value="<?= e(number_format((float) $v('comissao_percent', '0'), 2, ',', '')) ?>">
                        <?= $erroDe('comissao_percent') ?>
                    </div>
                </div>

                <div>
                    <label class="label" for="contato">Telefone</label>
                    <input type="tel" name="contato" id="contato" inputmode="numeric" maxlength="15"
                           class="input" value="<?= e(telefone_br($v('contato'))) ?>"
                           x-data="telefone" @input="formatar" placeholder="(13) 99999-9999">
                </div>

                <div>
                    <label class="label" for="email">E-mail</label>
                    <input type="email" name="email" id="email" maxlength="150"
                           class="input <?= $erroCls('email') ?>" value="<?= e($v('email')) ?>">
                    <?= $erroDe('email') ?>
                </div>

                <div>
                    <label class="label" for="usuario_id">Login no sistema</label>
                    <select name="usuario_id" id="usuario_id" class="select <?= $erroCls('usuario_id') ?>">
                        <option value="0">Não tem login</option>
                        <?php foreach ($usuarios as $u): ?>
                            <option value="<?= (int) $u['id'] ?>"
                                <?= (int) $v('usuario_id') === (int) $u['id'] ? 'selected' : '' ?>>
                                <?= e($u['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?= $erroDe('usuario_id') ?>
                    <p class="ajuda">Com login amarrado, ele enxerga só as vendas dele.</p>
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
                        Ativo
                        <span class="block text-xs text-slate-500 dark:text-slate-400">
                            Inativo sai das listas, e as vendas dele continuam no histórico.
                        </span>
                    </span>
                </label>

                <div class="form-acoes">
                    <button type="submit" class="btn btn-primary w-full" :disabled="enviando">
                        <span x-show="!enviando"><?= Icon::svg('salvar', 'w-4 h-4') ?></span>
                        <span x-show="enviando" x-cloak class="girando"></span>
                        <?= $emEdicao ? 'Salvar alterações' : 'Cadastrar vendedor' ?>
                    </button>
                </div>
            </form>
        </div>

        <?php /* A comissão combinada costuma ser a mesma para a equipe toda; sem
                 isto, mudar a tabela vira um vendedor por vez. */ ?>
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Comissão para todos</h2>
            </div>

            <form method="post" action="<?= url('vendas', 'comissao_todos') ?>"
                  class="card-corpo space-y-3.5"
                  data-titulo="Aplicar a comissão a todos"
                  data-acao="Aplicar a todos" data-tom="normal"
                  data-texto="Todos os vendedores passam a ter o mesmo percentual. As propostas já fechadas não mudam — o percentual delas foi congelado no dia do fechamento.">
                <?= Csrf::campo() ?>

                <div>
                    <label class="label" for="comissao_todos">Percentual</label>
                    <input type="text" name="comissao_percent" id="comissao_todos" inputmode="decimal"
                           class="input num" placeholder="30,00" required>
                    <p class="ajuda">Vale para as vendas daqui para frente.</p>
                </div>

                <label class="flex cursor-pointer items-start gap-3">
                    <input type="checkbox" name="incluir_inativos" class="check mt-0.5">
                    <span class="text-sm text-slate-700 dark:text-slate-300">
                        Incluir os inativos
                        <span class="block text-xs text-slate-500 dark:text-slate-400">
                            Sem marcar, só os vendedores ativos mudam.
                        </span>
                    </span>
                </label>

                <button type="button" @click="abrir" class="btn btn-outline w-full">
                    <?= Icon::svg('salvar', 'w-4 h-4') ?> Aplicar a todos
                </button>
            </form>
        </div>
    <?php endif; ?>

    <div class="card overflow-hidden">
        <div class="card-header">
            <h2 class="card-title">Vendedores cadastrados</h2>
            <span class="text-xs text-slate-500 dark:text-slate-400"><?= numero(count($vendedores)) ?> no total</span>
        </div>

        <?php if (empty($vendedores)): ?>
            <div class="vazio">
                <span class="vazio-icone"><?= Icon::svg('usuarios', 'w-5 h-5') ?></span>
                <p class="vazio-titulo">Nenhum vendedor cadastrado</p>
                <p class="vazio-texto">
                    Cadastre primeiro a empresa parceira; depois os vendedores dela.
                </p>
                <a href="<?= url('vendas', 'empresas') ?>" class="btn btn-primary btn-sm mt-1">
                    <?= Icon::svg('novo', 'w-3.5 h-3.5') ?> Cadastrar empresa
                </a>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="tabela">
                    <thead>
                        <tr>
                            <th>Vendedor</th>
                            <th class="w-40">Empresa</th>
                            <th class="w-20 text-right">Comissão</th>
                            <th class="w-28 text-right">Propostas</th>
                            <th class="w-24">Situação</th>
                            <th class="w-24 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($vendedores as $vd): ?>
                            <tr>
                                <td>
                                    <span class="celula-principal block"><?= e($vd['nome']) ?></span>
                                    <span class="block text-xs text-slate-500 dark:text-slate-400">
                                        <?php if ($vd['cpf']): ?>
                                            <span class="mono"><?= e(formatar_cpf((string) $vd['cpf'])) ?></span>
                                        <?php endif; ?>
                                        <?php if ($vd['usuario']): ?>
                                            <?= $vd['cpf'] ? ' · ' : '' ?>login: <?= e($vd['usuario']) ?>
                                        <?php endif; ?>
                                    </span>
                                </td>

                                <td class="celula-suave text-xs"><?= e($vd['empresa']) ?></td>

                                <td class="text-right text-xs text-slate-600 dark:text-slate-400">
                                    <?= e(number_format((float) $vd['comissao_percent'], 2, ',', '')) ?>%
                                </td>

                                <td class="text-right text-xs text-slate-600 dark:text-slate-400">
                                    <?= numero((int) $vd['propostas']) ?>
                                    <span class="block text-[0.6875rem] text-slate-400 dark:text-slate-500">
                                        <?= numero((int) $vd['fechadas']) ?> fechada<?= (int) $vd['fechadas'] === 1 ? '' : 's' ?>
                                    </span>
                                </td>

                                <td>
                                    <span class="badge <?= $vd['ativo'] ? 'badge-verde' : 'badge-cinza' ?>">
                                        <?= $vd['ativo'] ? 'Ativo' : 'Inativo' ?>
                                    </span>
                                </td>

                                <td>
                                    <div class="flex items-center justify-end gap-0.5">
                                        <a href="<?= url('vendas', 'lista', ['vendedor' => (int) $vd['id']]) ?>"
                                           class="btn-acao" title="Ver as vendas" aria-label="Ver as vendas de <?= e($vd['nome']) ?>">
                                            <?= Icon::svg('ver', 'w-4 h-4') ?>
                                        </a>

                                        <?php if ($podeEditar): ?>
                                            <a href="<?= url('vendas', 'vendedores', ['id' => (int) $vd['id']]) ?>"
                                               class="btn-acao" title="Editar" aria-label="Editar <?= e($vd['nome']) ?>">
                                                <?= Icon::svg('editar', 'w-4 h-4') ?>
                                            </a>
                                        <?php endif; ?>

                                        <?php if ($podeExcluir): ?>
                                            <form method="post" action="<?= url('vendas', 'excluir_vendedor') ?>" class="inline"
                                                  data-titulo="Excluir vendedor"
                                                  data-texto="<?= e($vd['nome']) ?> será removido do cadastro. Quem já tem proposta registrada não pode ser excluído — desmarque &quot;Ativo&quot; para tirar das listas sem perder o histórico.">
                                                <?= Csrf::campo() ?>
                                                <input type="hidden" name="id" value="<?= (int) $vd['id'] ?>">
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
