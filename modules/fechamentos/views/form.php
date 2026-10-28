<?php
/** @var array $fechamento */
/** @var array $erros */
/** @var array|false $ultimo */
/** @var array $motivos */
/** @var array $quantidades */
declare(strict_types=1);

$editando  = !empty($fechamento['id']);
$titulo    = $editando ? 'Editar fechamento' : 'Novo fechamento';
$actionUrl = url('fechamentos', $editando ? 'atualizar' : 'salvar');

$anoAtual = (int) date('Y');
$anos     = range($anoAtual, 2000);

$mesSel = (int) ($fechamento['mes'] ?? 0);
$anoSel = (int) ($fechamento['ano'] ?? $anoAtual);

// Mostra o total já formatado; em branco quando o registro é novo
$totalSel = isset($fechamento['total_atendimentos']) && $fechamento['total_atendimentos'] !== ''
    ? numero((int) $fechamento['total_atendimentos'])
    : '';
?>

<div class="mb-5 flex items-center gap-3">
    <a href="<?= url_lista('fechamentos') ?>" class="btn btn-icon btn-outline" aria-label="Voltar">
        <?= Icon::svg('voltar', 'w-4 h-4') ?>
    </a>
    <div>
        <h1 class="titulo-pagina"><?= e($titulo) ?></h1>
        <p class="sub-pagina">
            <?= $editando
                ? 'Ajuste a competência ou o total de atendimentos'
                : 'Informe a competência e o total de atendimentos do mês' ?>
        </p>
    </div>
</div>

<form method="post" action="<?= $actionUrl ?>" class="max-w-4xl" novalidate
      x-data="fechamentoForm" @submit="enviar">
    <?= Csrf::campo() ?>
    <?php if ($editando): ?>
        <input type="hidden" name="id" value="<?= (int) $fechamento['id'] ?>">
    <?php endif; ?>

    <div class="card p-5 lg:p-6">

        <div class="form-secao">
            <div>
                <h2 class="form-secao-titulo">Competência</h2>
                <p class="form-secao-desc">
                    O mês de referência do fechamento. Cada competência só pode ser
                    lançada uma vez, e meses que ainda não terminaram não são aceitos.
                </p>
            </div>

            <div class="form-campos">
                <div>
                    <label class="label" for="mes">Mês <span class="obrigatorio">*</span></label>
                    <select name="mes" id="mes" required
                            class="select <?= isset($erros['mes']) ? 'input-error' : '' ?>">
                        <option value="">Selecione…</option>
                        <?php foreach (meses() as $numero => $nome): ?>
                            <option value="<?= $numero ?>" <?= $mesSel === $numero ? 'selected' : '' ?>>
                                <?= e($nome) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($erros['mes'])): ?>
                        <p class="erro-campo"><?= Icon::svg('erro', 'w-3.5 h-3.5') ?> <?= e($erros['mes']) ?></p>
                    <?php endif; ?>
                </div>

                <div>
                    <label class="label" for="ano">Ano <span class="obrigatorio">*</span></label>
                    <select name="ano" id="ano" required
                            class="select <?= isset($erros['ano']) ? 'input-error' : '' ?>">
                        <?php foreach ($anos as $a): ?>
                            <option value="<?= $a ?>" <?= $anoSel === $a ? 'selected' : '' ?>><?= $a ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($erros['ano'])): ?>
                        <p class="erro-campo"><?= Icon::svg('erro', 'w-3.5 h-3.5') ?> <?= e($erros['ano']) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="form-secao">
            <div>
                <h2 class="form-secao-titulo">Total de atendimentos</h2>
                <p class="form-secao-desc">
                    Número consolidado do mês inteiro. Pode colar direto do relatório —
                    pontos e separadores são ignorados.
                </p>
            </div>

            <div class="form-campos">
                <div>
                    <label class="label" for="total_atendimentos">Atendimentos <span class="obrigatorio">*</span></label>
                    <input type="text" name="total_atendimentos" id="total_atendimentos"
                           inputmode="numeric" required autofocus maxlength="12"
                           class="input <?= isset($erros['total_atendimentos']) ? 'input-error' : '' ?>"
                           value="<?= e($totalSel) ?>"
                           placeholder="Ex.: 1.248"
                           @input="recalcular">
                    <?php if (isset($erros['total_atendimentos'])): ?>
                        <p class="erro-campo"><?= Icon::svg('erro', 'w-3.5 h-3.5') ?> <?= e($erros['total_atendimentos']) ?></p>
                    <?php endif; ?>
                </div>

                <?php if (!$editando && $ultimo): ?>
                    <div class="flex items-end">
                        <p class="rounded-lg bg-slate-50 px-3 py-2 text-xs text-slate-600 dark:bg-slate-800/60 dark:text-slate-300">
                            Último fechamento:
                            <strong class="text-marine-700 dark:text-marine-300">
                                <?= e(competencia_br((int) $ultimo['ano'], (int) $ultimo['mes'])) ?>
                            </strong>
                            com <?= numero((int) $ultimo['total_atendimentos']) ?> atendimentos.
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="form-secao">
            <div>
                <h2 class="form-secao-titulo">Atendimentos por motivo</h2>
                <p class="form-secao-desc">
                    Opcional. Informe quantos atendimentos do mês foram encerrados
                    por cada motivo. O sistema soma e compara com o total acima —
                    é conferência, não trava: você pode salvar mesmo se não bater.
                </p>
            </div>

            <div>
                <?php if (empty($motivos)): ?>
                    <div class="alerta alerta-info">
                        <span class="mt-0.5 shrink-0"><?= Icon::svg('info', 'w-4 h-4') ?></span>
                        <p>
                            Nenhum motivo cadastrado ainda.
                            <?php if (Auth::temPermissao('motivos', 'pode_criar')): ?>
                                <a href="<?= url('motivos', 'novo') ?>" class="font-medium underline">Cadastre o primeiro</a>
                                para poder detalhar o fechamento.
                            <?php else: ?>
                                Peça ao responsável para cadastrar os motivos da central.
                            <?php endif; ?>
                        </p>
                    </div>
                <?php else: ?>
                    <div class="overflow-hidden rounded-lg border border-slate-200 dark:border-slate-700">
                        <table class="tabela">
                            <thead>
                                <tr>
                                    <th>Motivo</th>
                                    <th class="w-36 text-right">Quantidade</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($motivos as $m): ?>
                                    <?php
                                    $mid = (int) $m['id'];
                                    $qtd = $quantidades[$mid] ?? 0;
                                    ?>
                                    <tr>
                                        <td>
                                            <label for="motivo_<?= $mid ?>" class="flex cursor-pointer items-center gap-2.5">
                                                <span class="mono rounded bg-slate-100 px-1.5 py-0.5 text-[0.68rem] font-semibold text-marine-700 dark:bg-slate-800 dark:text-marine-300">
                                                    <?= e($m['codigo']) ?>
                                                </span>
                                                <span class="min-w-0">
                                                    <span class="celula-principal block"><?= e($m['nome']) ?></span>
                                                    <?php if (empty($m['ativo'])): ?>
                                                        <span class="text-[0.68rem] text-amber-600 dark:text-amber-400">motivo inativo, mantido por já ter lançamento</span>
                                                    <?php endif; ?>
                                                </span>
                                            </label>
                                        </td>
                                        <td>
                                            <input type="text" inputmode="numeric" maxlength="9"
                                                   id="motivo_<?= $mid ?>"
                                                   name="motivos[<?= $mid ?>]"
                                                   data-motivo="<?= $mid ?>"
                                                   class="input input-sm text-right"
                                                   value="<?= $qtd > 0 ? numero($qtd) : '' ?>"
                                                   placeholder="0"
                                                   @input="recalcular">
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <!-- Resumo ao vivo da conferência -->
                        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 bg-slate-50 px-4 py-3 dark:border-slate-700 dark:bg-slate-800/60">
                            <div class="flex items-center gap-4 text-xs">
                                <span class="text-slate-500 dark:text-slate-400">
                                    Soma dos motivos:
                                    <strong class="num text-marine-900 dark:text-white" x-text="soma.toLocaleString('pt-BR')"></strong>
                                </span>
                                <span class="text-slate-500 dark:text-slate-400">
                                    Total informado:
                                    <strong class="num text-marine-900 dark:text-white" x-text="total.toLocaleString('pt-BR')"></strong>
                                </span>
                            </div>

                            <span class="badge"
                                  :class="!lancou ? 'badge-cinza' : (confere ? 'badge-verde' : 'badge-ambar')"
                                  x-text="!lancou ? 'sem detalhamento' : (confere ? 'confere' : 'diferença de ' + fmt(diferenca))"></span>
                        </div>
                    </div>

                    <p class="ajuda" x-text="textoDiferenca"></p>
                <?php endif; ?>
            </div>
        </div>

        <div class="form-secao">
            <div>
                <h2 class="form-secao-titulo">Observação</h2>
                <p class="form-secao-desc">
                    Opcional. Use para justificar uma variação fora do normal — feriado
                    prolongado, mutirão, sistema fora do ar.
                </p>
            </div>

            <div>
                <label class="label" for="observacao">Anotação do mês</label>
                <textarea name="observacao" id="observacao" rows="3" maxlength="500"
                          class="textarea <?= isset($erros['observacao']) ? 'input-error' : '' ?>"
                          placeholder="Ex.: mês com dois feriados e mutirão de retornos na última semana."><?= e($fechamento['observacao'] ?? '') ?></textarea>
                <?php if (isset($erros['observacao'])): ?>
                    <p class="erro-campo"><?= Icon::svg('erro', 'w-3.5 h-3.5') ?> <?= e($erros['observacao']) ?></p>
                <?php else: ?>
                    <p class="ajuda">Até 500 caracteres.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="form-acoes">
        <a href="<?= url_lista('fechamentos') ?>" class="btn btn-outline">Cancelar</a>
        <button type="submit" class="btn btn-primary" :disabled="enviando">
            <span x-show="!enviando"><?= Icon::svg('salvar', 'w-4 h-4') ?></span>
            <span x-show="enviando" x-cloak class="girando"></span>
            <?= $editando ? 'Salvar alterações' : 'Registrar fechamento' ?>
        </button>
    </div>
</form>
