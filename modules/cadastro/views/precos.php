<?php
/**
 * A tabela de mensalidades por faixa etária.
 *
 * Versionada por vigência: o reajuste anual da ANS entra como um conjunto
 * novo, e não como edição do que já existe. Assim a mensalidade de março
 * continua sendo a de março depois que o reajuste de novembro passar a
 * valer — e ninguém precisa mexer no código uma vez por ano.
 *
 * @var array      $planos, $vigencias, $vigentes, $erros
 * @var array|null $editando
 */
declare(strict_types=1);

$podeEditar  = Auth::temPermissao('cadastro', 'pode_editar');
$podeExcluir = Auth::temPermissao('cadastro', 'pode_excluir');

$hoje     = date('Y-m-d');
$emEdicao = $editando !== null;
$erroCls  = fn(string $x): string => isset($erros[$x]) ? 'input-error' : '';

$nomePlano = [];
foreach ($planos as $p) {
    $nomePlano[(int) $p['id']] = $p['nome'];
}
?>

<div class="mb-5 flex flex-wrap items-end justify-between gap-3">
    <div class="flex items-start gap-3">
        <a href="<?= url_lista('cadastro') ?>" class="btn-acao mt-0.5" aria-label="Voltar aos contratos">
            <?= Icon::svg('anterior', 'w-4 h-4') ?>
        </a>
        <div>
            <h1 class="titulo-pagina">Tabela de preços</h1>
            <p class="sub-pagina">Mensalidade por plano e faixa etária, com vigência</p>
        </div>
    </div>
</div>

<!-- O que vale hoje -->
<div class="card mb-4 overflow-hidden">
    <div class="card-header">
        <h2 class="card-title">Em vigor hoje</h2>
        <span class="text-xs text-slate-500 dark:text-slate-400"><?= e(data_br($hoje)) ?></span>
    </div>

    <div class="overflow-x-auto">
        <table class="tabela">
            <thead>
                <tr>
                    <th class="w-32">Faixa etária</th>
                    <?php foreach ($planos as $p): ?>
                        <th class="text-right">
                            <?= e($p['nome']) ?>
                            <span class="block text-[0.625rem] font-normal text-slate-400 dark:text-slate-500">
                                <?= $p['coparticipacao'] ? 'com' : 'sem' ?> coparticipação
                            </span>
                        </th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach (Elegibilidade::FAIXAS as $i => $rotulo): ?>
                    <tr>
                        <td class="celula-suave text-xs"><?= e($rotulo) ?> anos</td>
                        <?php foreach ($planos as $p): ?>
                            <?php $valor = $vigentes[(int) $p['id']][$i] ?? null; ?>
                            <td class="mono text-right text-xs">
                                <?= $valor !== null
                                    ? e(dinheiro_br($valor))
                                    : '<span class="text-amber-600 dark:text-amber-400">sem preço</span>' ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="border-t border-slate-100 px-4 py-2.5 text-xs text-slate-500 dark:border-slate-800 dark:text-slate-400">
        A faixa de cada pessoa é calculada da data de nascimento na hora da consulta —
        ela muda no aniversário, e a mensalidade acompanha.
    </div>
</div>

<div class="grid gap-4 lg:grid-cols-[24rem_minmax(0,1fr)] lg:items-start">

    <?php if ($podeEditar): ?>
        <div class="card">
            <div class="card-header">
                <h2 class="card-title"><?= $emEdicao ? 'Editar vigência' : 'Nova vigência' ?></h2>
                <?php if ($emEdicao): ?>
                    <a href="<?= url('cadastro', 'precos') ?>" class="btn btn-ghost btn-sm">Cancelar</a>
                <?php endif; ?>
            </div>

            <form method="post" action="<?= url('cadastro', 'salvar_precos') ?>"
                  class="card-corpo space-y-3.5" x-data="envio" @submit="enviar" novalidate>
                <?= Csrf::campo() ?>

                <div>
                    <label class="label" for="plano_id">Plano <span class="obrigatorio">*</span></label>
                    <select name="plano_id" id="plano_id" required class="select <?= $erroCls('plano_id') ?>">
                        <option value="">Escolha…</option>
                        <?php foreach ($planos as $p): ?>
                            <option value="<?= (int) $p['id'] ?>"
                                <?= $emEdicao && $editando['plano_id'] === (int) $p['id'] ? 'selected' : '' ?>>
                                <?= e($p['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($erros['plano_id'])): ?>
                        <p class="erro-campo"><?= Icon::svg('erro', 'w-3.5 h-3.5') ?> <?= e($erros['plano_id']) ?></p>
                    <?php endif; ?>
                </div>

                <div>
                    <label class="label" for="vigencia">Passa a valer em <span class="obrigatorio">*</span></label>
                    <input type="date" name="vigencia" id="vigencia" required
                           class="input <?= $erroCls('vigencia') ?>"
                           value="<?= e($emEdicao ? $editando['vigencia'] : '') ?>">
                    <?php if (isset($erros['vigencia'])): ?>
                        <p class="erro-campo"><?= Icon::svg('erro', 'w-3.5 h-3.5') ?> <?= e($erros['vigencia']) ?></p>
                    <?php endif; ?>
                    <p class="ajuda">
                        Data futura fica guardada e só começa a valer no dia — dá para
                        lançar o reajuste com antecedência.
                    </p>
                </div>

                <div>
                    <span class="label">Valor de cada faixa <span class="obrigatorio">*</span></span>
                    <?php if (isset($erros['valor'])): ?>
                        <p class="erro-campo"><?= Icon::svg('erro', 'w-3.5 h-3.5') ?> <?= e($erros['valor']) ?></p>
                    <?php endif; ?>

                    <div class="mt-1 space-y-1.5">
                        <?php foreach (Elegibilidade::FAIXAS as $i => $rotulo): ?>
                            <div class="flex items-center gap-2">
                                <span class="w-24 shrink-0 text-xs text-slate-500 dark:text-slate-400">
                                    <?= e($rotulo) ?>
                                </span>
                                <div class="campo-icone flex-1">
                                    <span class="text-xs text-slate-400">R$</span>
                                    <input type="text" name="valor[<?= $i ?>]" inputmode="decimal"
                                           class="input input-sm mono" placeholder="0,00"
                                           value="<?= $emEdicao && isset($editando['valores'][$i])
                                                    ? e(dinheiro_br($editando['valores'][$i], false)) : '' ?>"
                                           @focus="$event.target.select()"
                                           aria-label="Valor da faixa <?= e($rotulo) ?>">
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-full" :disabled="enviando">
                    <?= Icon::svg('salvar', 'w-4 h-4') ?>
                    <?= $emEdicao ? 'Salvar esta vigência' : 'Gravar tabela' ?>
                </button>
            </form>
        </div>
    <?php endif; ?>

    <div class="card overflow-hidden">
        <div class="card-header"><h2 class="card-title">Vigências gravadas</h2></div>

        <?php if (empty($vigencias)): ?>
            <div class="vazio">
                <span class="vazio-icone"><?= Icon::svg('opme', 'w-5 h-5') ?></span>
                <p class="vazio-titulo">Nenhuma tabela gravada</p>
                <p class="vazio-texto">Lance ao lado os valores de cada plano.</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="tabela">
                    <thead>
                        <tr>
                            <th>Plano</th>
                            <th class="w-32">Vigência</th>
                            <th class="w-24">Faixas</th>
                            <th class="w-44">Intervalo</th>
                            <th class="w-24 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($vigencias as $v): ?>
                            <?php
                            $futura = $v['vigencia'] > $hoje;
                            $completa = (int) $v['faixas'] === 10;
                            ?>
                            <tr>
                                <td class="celula-principal"><?= e($v['plano']) ?></td>

                                <td class="text-xs">
                                    <?= e(data_br($v['vigencia'])) ?>
                                    <?php if ($futura): ?>
                                        <span class="block text-[0.625rem] text-sky-600 dark:text-sky-400">
                                            ainda não vale
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td class="text-xs">
                                    <?php if ($completa): ?>
                                        <span class="celula-suave">10 de 10</span>
                                    <?php else: ?>
                                        <span class="text-amber-600 dark:text-amber-400">
                                            <?= (int) $v['faixas'] ?> de 10
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td class="mono celula-suave text-xs">
                                    <?= e(dinheiro_br((float) $v['menor'])) ?> a <?= e(dinheiro_br((float) $v['maior'])) ?>
                                </td>

                                <td>
                                    <div class="flex items-center justify-end gap-0.5">
                                        <?php if ($podeEditar): ?>
                                            <a href="<?= url('cadastro', 'precos', [
                                                    'plano' => (int) $v['plano_id'],
                                                    'vigencia' => $v['vigencia'],
                                               ]) ?>"
                                               class="btn-acao" title="Editar esta vigência"
                                               aria-label="Editar <?= e($v['plano']) ?> de <?= e(data_br($v['vigencia'])) ?>">
                                                <?= Icon::svg('editar', 'w-4 h-4') ?>
                                            </a>
                                        <?php endif; ?>

                                        <?php if ($podeExcluir): ?>
                                            <form method="post" action="<?= url('cadastro', 'excluir_precos') ?>" class="inline"
                                                  data-titulo="Excluir vigência"
                                                  data-texto="Os valores de <?= e($v['plano']) ?> a partir de <?= e(data_br($v['vigencia'])) ?> serão apagados. Se esta for a tabela em vigor, as mensalidades ficam sem valor até outra ser lançada.">
                                                <?= Csrf::campo() ?>
                                                <input type="hidden" name="plano_id" value="<?= (int) $v['plano_id'] ?>">
                                                <input type="hidden" name="vigencia" value="<?= e($v['vigencia']) ?>">
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
