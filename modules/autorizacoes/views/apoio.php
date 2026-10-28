<?php
/**
 * Cadastro de apoio das autorizações: convênios e procedimentos.
 *
 * Uma view só para os dois, porque são a mesma tela com outro rótulo —
 * duas cópias divergiriam na primeira mudança.
 *
 * @var string     $cadastro   'convenios' | 'procedimentos'
 * @var string     $titulo, $subtitulo
 * @var array      $itens, $uso, $erros
 * @var array|null $edicao
 */
declare(strict_types=1);

$podeEditar  = Auth::temPermissao('autorizacoes', 'pode_editar');
$podeExcluir = Auth::temPermissao('autorizacoes', 'pode_excluir');

$ehConvenio = $cadastro === 'convenios';
$editando   = $edicao !== null;
$erroCls    = fn(string $c): string => isset($erros[$c]) ? 'input-error' : '';
$v          = fn(string $c): string => (string) ($edicao[$c] ?? '');

$singular = $ehConvenio ? 'convênio' : 'procedimento';
?>

<div class="mb-5 flex flex-wrap items-end justify-between gap-3">
    <div class="flex items-start gap-3">
        <a href="<?= url_lista('autorizacoes') ?>" class="btn-acao mt-0.5" aria-label="Voltar às autorizações">
            <?= Icon::svg('anterior', 'w-4 h-4') ?>
        </a>
        <div>
            <h1 class="titulo-pagina"><?= e($titulo) ?></h1>
            <p class="sub-pagina"><?= e($subtitulo) ?></p>
        </div>
    </div>

    <a href="<?= url('autorizacoes', $ehConvenio ? 'procedimentos' : 'convenios') ?>" class="btn btn-outline">
        <?= Icon::svg($ehConvenio ? 'exames' : 'setores', 'w-4 h-4') ?>
        <?= $ehConvenio ? 'Procedimentos' : 'Convênios' ?>
    </a>
</div>

<div class="grid gap-4 lg:grid-cols-[22rem_minmax(0,1fr)] lg:items-start">

    <!-- Incluir / editar -->
    <?php if ($podeEditar): ?>
        <div class="card">
            <div class="card-header">
                <h2 class="card-title"><?= $editando ? 'Editar' : 'Incluir' ?></h2>
                <?php if ($editando): ?>
                    <a href="<?= url('autorizacoes', $cadastro) ?>" class="btn btn-ghost btn-sm">Cancelar</a>
                <?php endif; ?>
            </div>

            <form method="post" action="<?= url('autorizacoes', 'salvarApoio') ?>"
                  class="card-corpo space-y-3.5" x-data="envio" @submit="enviar" novalidate>
                <?= Csrf::campo() ?>
                <input type="hidden" name="cadastro" value="<?= e($cadastro) ?>">
                <?php if ($editando): ?>
                    <input type="hidden" name="id" value="<?= (int) $edicao['id'] ?>">
                <?php endif; ?>

                <div>
                    <label class="label" for="nome">Nome <span class="obrigatorio">*</span></label>
                    <input type="text" name="nome" id="nome" maxlength="120" required
                           class="input <?= $erroCls('nome') ?>" value="<?= e($v('nome')) ?>"
                           placeholder="<?= $ehConvenio ? 'Ex.: Bluemed' : 'Ex.: Ecocardiograma' ?>">
                    <?php if (isset($erros['nome'])): ?>
                        <p class="erro-campo"><?= Icon::svg('erro', 'w-3.5 h-3.5') ?> <?= e($erros['nome']) ?></p>
                    <?php endif; ?>
                </div>

                <?php if ($ehConvenio): ?>
                    <div>
                        <label class="label" for="telefone">Telefone da autorização</label>
                        <input type="tel" name="telefone" id="telefone" maxlength="40" class="input"
                               value="<?= e($v('telefone')) ?>" placeholder="Para quem liga atrás da senha">
                    </div>
                <?php else: ?>
                    <div>
                        <label class="label" for="codigo">Código TUSS</label>
                        <input type="text" name="codigo" id="codigo" maxlength="20" class="input mono"
                               value="<?= e($v('codigo')) ?>" placeholder="Quando o convênio exige">
                    </div>
                <?php endif; ?>

                <div>
                    <label class="label" for="observacao">Observação</label>
                    <input type="text" name="observacao" id="observacao" maxlength="300" class="input"
                           value="<?= e($v('observacao')) ?>">
                </div>

                <label class="flex cursor-pointer items-start gap-2">
                    <input type="checkbox" name="ativo" value="1" class="check mt-0.5"
                        <?= !$editando || !empty($edicao['ativo']) ? 'checked' : '' ?>>
                    <span class="text-[0.8125rem]">
                        Ativo
                        <span class="block text-xs text-slate-500 dark:text-slate-400">
                            Só os ativos aparecem na lista ao registrar uma autorização.
                        </span>
                    </span>
                </label>

                <div class="flex justify-end pt-1">
                    <button type="submit" class="btn btn-primary btn-sm" :disabled="enviando">
                        <span x-show="!enviando"><?= Icon::svg('salvar', 'w-3.5 h-3.5') ?></span>
                        <span x-show="enviando" x-cloak class="girando"></span>
                        <?= $editando ? 'Salvar' : 'Incluir' ?>
                    </button>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <!-- Lista -->
    <div class="card overflow-hidden <?= $podeEditar ? '' : 'lg:col-span-2' ?>">
        <div class="card-header">
            <h2 class="card-title">Cadastrados</h2>
            <span class="text-xs text-slate-500 dark:text-slate-400"><?= count($itens) ?></span>
        </div>

        <?php if (empty($itens)): ?>
            <div class="vazio">
                <p class="vazio-titulo">Nenhum <?= e($singular) ?> cadastrado</p>
                <p class="vazio-texto">Inclua o primeiro no formulário ao lado.</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="tabela">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th class="w-44"><?= $ehConvenio ? 'Telefone' : 'Código' ?></th>
                            <th class="w-28 text-right">Em uso</th>
                            <th class="w-24">Situação</th>
                            <th class="w-20 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($itens as $i): ?>
                            <?php $usos = (int) ($uso[(int) $i['id']] ?? 0); ?>
                            <tr>
                                <td>
                                    <span class="celula-principal block"><?= e($i['nome']) ?></span>
                                    <?php if ($i['observacao']): ?>
                                        <span class="block text-xs text-slate-500 dark:text-slate-400">
                                            <?= e($i['observacao']) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td class="celula-suave mono text-xs">
                                    <?= $ehConvenio
                                        ? ($i['telefone'] ? e(telefone_br($i['telefone'])) : '—')
                                        : ($i['codigo'] ?: '—') ?>
                                </td>

                                <td class="num text-right celula-suave text-xs">
                                    <?= $usos > 0 ? numero($usos) : '—' ?>
                                </td>

                                <td>
                                    <span class="badge badge-ponto <?= $i['ativo'] ? 'badge-verde' : 'badge-cinza' ?>">
                                        <?= $i['ativo'] ? 'Ativo' : 'Inativo' ?>
                                    </span>
                                </td>

                                <td>
                                    <div class="flex items-center justify-end gap-0.5">
                                        <?php if ($podeEditar): ?>
                                            <a href="<?= url('autorizacoes', $cadastro, ['id' => (int) $i['id']]) ?>"
                                               class="btn-acao" title="Editar" aria-label="Editar <?= e($i['nome']) ?>">
                                                <?= Icon::svg('editar', 'w-4 h-4') ?>
                                            </a>
                                        <?php endif; ?>

                                        <?php if ($podeExcluir && $usos === 0): ?>
                                            <form method="post" action="<?= url('autorizacoes', 'excluirApoio') ?>" class="inline"
                                                  data-titulo="Excluir <?= e($singular) ?>"
                                                  data-texto="&quot;<?= e($i['nome']) ?>&quot; sai do cadastro. Esta ação não pode ser desfeita.">
                                                <?= Csrf::campo() ?>
                                                <input type="hidden" name="cadastro" value="<?= e($cadastro) ?>">
                                                <input type="hidden" name="id" value="<?= (int) $i['id'] ?>">
                                                <button type="button" @click="abrir" class="btn-acao btn-acao-perigo"
                                                        title="Excluir" aria-label="Excluir <?= e($i['nome']) ?>">
                                                    <?= Icon::svg('excluir', 'w-4 h-4') ?>
                                                </button>
                                            </form>
                                        <?php elseif ($podeExcluir): ?>
                                            <?php /* Excluir o que já foi usado apagaria o passado das
                                                     autorizações antigas. Desativar tira da lista sem
                                                     mexer no histórico. */ ?>
                                            <span class="btn-acao-off" title="Em uso por <?= $usos ?> autorização(ões) — desative em vez de excluir">
                                                <?= Icon::svg('excluir', 'w-4 h-4') ?>
                                            </span>
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
