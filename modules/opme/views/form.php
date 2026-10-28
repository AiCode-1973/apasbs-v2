<?php
/**
 * Lançar ou editar uma nota de OPME, com as parcelas na mesma tela.
 *
 * As parcelas são montadas pelo Alpine: informe o valor, quantas parcelas e
 * o primeiro vencimento, e a tabela se preenche. Cada linha continua
 * editável — na prática o fornecedor nem sempre divide em valores iguais.
 *
 * @var array $nota
 * @var array $parcelas
 * @var array $erros
 * @var array $fornecedores
 * @var array $medicos
 * @var array $hospitais
 */
declare(strict_types=1);

$editando  = !empty($nota['id']);
$actionUrl = url('opme', $editando ? 'atualizar' : 'salvar');

$parcelasJson = json_encode(
    array_map(fn($p) => [
        'vencimento' => substr((string) ($p['vencimento'] ?? ''), 0, 10),
        'valor'      => dinheiro_br((float) ($p['valor'] ?? 0), false),
        'pago'       => (bool) ($p['pago'] ?? false),
    ], $parcelas),
    JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT
);
?>

<div class="mb-5 flex items-center gap-3">
    <a href="<?= $editando ? url('opme', 'ver', ['id' => (int) $nota['id']]) : url_lista('opme') ?>"
       class="btn btn-icon btn-outline" aria-label="Voltar">
        <?= Icon::svg('voltar', 'w-4 h-4') ?>
    </a>
    <div>
        <h1 class="titulo-pagina"><?= $editando ? 'Editar nota' : 'Nova nota de OPME' ?></h1>
        <p class="sub-pagina">
            <?= $editando
                ? 'Ajuste os dados da nota e as parcelas'
                : 'O material usado numa cirurgia, com o parcelamento combinado' ?>
        </p>
    </div>
</div>

<form method="post" action="<?= $actionUrl ?>" class="max-w-4xl" novalidate
      x-data="opmeParcelas(<?= e($parcelasJson) ?>)" @submit="enviar">
    <?= Csrf::campo() ?>
    <?php if ($editando): ?>
        <input type="hidden" name="id" value="<?= (int) $nota['id'] ?>">
    <?php endif; ?>

    <div class="card p-5 lg:p-6">

        <div class="form-secao">
            <div>
                <h2 class="form-secao-titulo">A cirurgia</h2>
                <p class="form-secao-desc">
                    De quem é o material e em que cirurgia foi usado. É por esses
                    campos que a nota é procurada depois.
                </p>
            </div>

            <div class="form-campos">
                <div class="form-largo">
                    <label class="label" for="paciente">Paciente <span class="obrigatorio">*</span></label>
                    <input type="text" name="paciente" id="paciente" maxlength="150" required
                           class="input <?= isset($erros['paciente']) ? 'input-error' : '' ?>"
                           value="<?= e($nota['paciente'] ?? '') ?>">
                    <?php if (isset($erros['paciente'])): ?>
                        <p class="erro-campo"><?= Icon::svg('erro', 'w-3.5 h-3.5') ?> <?= e($erros['paciente']) ?></p>
                    <?php endif; ?>
                </div>

                <div>
                    <label class="label" for="data_cirurgia">Data da cirurgia</label>
                    <input type="date" name="data_cirurgia" id="data_cirurgia" class="input"
                           value="<?= e(substr((string) ($nota['data_cirurgia'] ?? ''), 0, 10)) ?>">
                </div>

                <div>
                    <label class="label" for="hospital">Hospital</label>
                    <input type="text" name="hospital" id="hospital" maxlength="80" class="input"
                           list="lista-hospitais" autocomplete="off"
                           value="<?= e($nota['hospital'] ?? '') ?>" placeholder="Ex.: HSE">
                </div>

                <div class="form-largo">
                    <label class="label" for="medico">Médico responsável</label>
                    <input type="text" name="medico" id="medico" maxlength="120" class="input"
                           list="lista-medicos" autocomplete="off"
                           value="<?= e($nota['medico'] ?? '') ?>">
                </div>
            </div>
        </div>

        <div class="form-secao">
            <div>
                <h2 class="form-secao-titulo">A nota fiscal</h2>
                <p class="form-secao-desc">
                    Fornecedor, número e valor. O mesmo fornecedor não pode ter a
                    mesma nota lançada duas vezes para a mesma paciente.
                </p>
                <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                    Fornecedor novo?
                    <a href="<?= url('opme', 'fornecedores') ?>"
                       class="font-medium text-marine-600 hover:underline dark:text-marine-300">Cadastrar</a>.
                </p>
            </div>

            <div class="form-campos">
                <div class="form-largo">
                    <label class="label" for="fornecedor_id">Fornecedor <span class="obrigatorio">*</span></label>
                    <select name="fornecedor_id" id="fornecedor_id" required
                            class="select <?= isset($erros['fornecedor_id']) ? 'input-error' : '' ?>">
                        <option value="">Escolha…</option>
                        <?php foreach ($fornecedores as $f): ?>
                            <option value="<?= (int) $f['id'] ?>"
                                <?= (int) ($nota['fornecedor_id'] ?? 0) === (int) $f['id'] ? 'selected' : '' ?>>
                                <?= e($f['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($erros['fornecedor_id'])): ?>
                        <p class="erro-campo"><?= Icon::svg('erro', 'w-3.5 h-3.5') ?> <?= e($erros['fornecedor_id']) ?></p>
                    <?php endif; ?>
                </div>

                <div>
                    <label class="label" for="numero_nf">Número da NF <span class="obrigatorio">*</span></label>
                    <input type="text" name="numero_nf" id="numero_nf" maxlength="40" required
                           class="input mono <?= isset($erros['numero_nf']) ? 'input-error' : '' ?>"
                           value="<?= e($nota['numero_nf'] ?? '') ?>" placeholder="3824">
                    <?php if (isset($erros['numero_nf'])): ?>
                        <p class="erro-campo"><?= Icon::svg('erro', 'w-3.5 h-3.5') ?> <?= e($erros['numero_nf']) ?></p>
                    <?php endif; ?>
                </div>

                <div>
                    <label class="label" for="data_nf">Data da NF</label>
                    <input type="date" name="data_nf" id="data_nf" class="input"
                           value="<?= e(substr((string) ($nota['data_nf'] ?? ''), 0, 10)) ?>">
                </div>

                <div>
                    <label class="label" for="valor_total">Valor total <span class="obrigatorio">*</span></label>
                    <div class="campo-moeda">
                        <span class="moeda-simbolo" aria-hidden="true">R$</span>
                        <input type="text" name="valor_total" id="valor_total" inputmode="decimal" required
                               class="input num <?= isset($erros['valor_total']) ? 'input-error' : '' ?>"
                               x-model="valorTotal" @input="soDinheiro($event)"
                               @focus="$event.target.select()" @blur="valorTotal = formatar(valorTotal)"
                               value="<?= e(((float) ($nota['valor_total'] ?? 0)) > 0
                                            ? dinheiro_br((float) $nota['valor_total'], false) : '') ?>"
                               placeholder="17.800,00">
                    </div>
                    <?php if (isset($erros['valor_total'])): ?>
                        <p class="erro-campo"><?= Icon::svg('erro', 'w-3.5 h-3.5') ?> <?= e($erros['valor_total']) ?></p>
                    <?php endif; ?>
                </div>

                <div>
                    <label class="label" for="situacao">Situação</label>
                    <select name="situacao" id="situacao" class="select">
                        <?php foreach (OpmeModel::SITUACOES as $valor => $rotulo): ?>
                            <option value="<?= e($valor) ?>"
                                <?= ($nota['situacao'] ?? 'normal') === $valor ? 'selected' : '' ?>>
                                <?= e($rotulo) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="ajuda">Cancelada e doação ficam fora das somas a pagar.</p>
                </div>

                <div class="form-largo">
                    <label class="flex cursor-pointer items-start gap-3">
                        <input type="checkbox" name="encaminhado_hse" class="check mt-0.5"
                               <?= !empty($nota['encaminhado_hse']) ? 'checked' : '' ?>>
                        <span>
                            <span class="block text-[0.8125rem] font-medium text-slate-800 dark:text-slate-100">
                                NF encaminhada ao HSE
                            </span>
                            <span class="block text-xs text-slate-500 dark:text-slate-400">
                                A coluna que a planilha tinha e quase nunca era preenchida.
                            </span>
                        </span>
                    </label>
                </div>

                <div class="form-largo">
                    <label class="label" for="observacao">Observação</label>
                    <input type="text" name="observacao" id="observacao" maxlength="300" class="input"
                           value="<?= e($nota['observacao'] ?? '') ?>"
                           placeholder="Ex.: desconto para quitação">
                </div>
            </div>
        </div>

        <div class="form-secao">
            <div>
                <h2 class="form-secao-titulo">Parcelas</h2>
                <p class="form-secao-desc">
                    Diga em quantas vezes e quando cai a primeira: o sistema monta o
                    restante mês a mês. Cada linha continua editável, porque o
                    fornecedor nem sempre divide em valores iguais.
                </p>

                <div class="mt-3 rounded-lg bg-slate-50 p-3 text-xs dark:bg-slate-950/50">
                    <p class="flex items-center justify-between gap-2">
                        <span class="text-slate-500 dark:text-slate-400">Soma das parcelas</span>
                        <span class="num font-semibold" :class="bate ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400'"
                              x-text="somaFormatada"></span>
                    </p>
                    <p class="mt-1 flex items-center justify-between gap-2">
                        <span class="text-slate-500 dark:text-slate-400">Valor da nota</span>
                        <span class="num" x-text="totalFormatado"></span>
                    </p>
                    <p class="mt-1.5 text-[0.6875rem]" x-show="!bate" x-cloak
                       :class="'text-amber-700 dark:text-amber-400'">
                        A diferença acima de um real trava o salvamento.
                    </p>
                </div>
            </div>

            <div>
                <div class="mb-3 flex flex-wrap items-end gap-2">
                    <div class="w-28">
                        <label class="label" for="qtd_parcelas">Em quantas</label>
                        <input type="number" id="qtd_parcelas" min="1" max="60" class="input input-sm num"
                               x-model.number="quantas">
                    </div>
                    <div class="w-40">
                        <label class="label" for="primeiro_venc">Primeiro vencimento</label>
                        <input type="date" id="primeiro_venc" class="input input-sm" x-model="primeiro">
                    </div>
                    <button type="button" class="btn btn-outline btn-sm" @click="gerar">
                        <?= Icon::svg('igual', 'w-3.5 h-3.5') ?> Gerar parcelas
                    </button>
                </div>

                <input type="hidden" name="total_parcelas" :value="Math.max(quantas, linhas.length)">

                <?php foreach (['parcelas', 'soma'] as $campo): ?>
                    <?php if (isset($erros[$campo])): ?>
                        <p class="erro-campo mb-2"><?= Icon::svg('erro', 'w-3.5 h-3.5') ?> <?= e($erros[$campo]) ?></p>
                    <?php endif; ?>
                <?php endforeach; ?>

                <div class="overflow-x-auto rounded-lg border border-slate-200 dark:border-slate-800">
                    <table class="tabela">
                        <thead>
                            <tr>
                                <th class="w-12">#</th>
                                <th class="w-44">Vencimento</th>
                                <th class="w-40">Valor</th>
                                <th class="w-12"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(l, i) in linhas" :key="i">
                                <tr>
                                    <td class="mono text-xs text-slate-500" x-text="i + 1"></td>
                                    <td>
                                        <input type="date" name="vencimento[]" class="input input-sm"
                                               x-model="l.vencimento">
                                    </td>
                                    <td>
                                        <div class="campo-moeda">
                                            <span class="moeda-simbolo moeda-simbolo-sm" aria-hidden="true">R$</span>
                                            <input type="text" name="valor[]" inputmode="decimal"
                                                   class="input input-sm num" x-model="l.valor"
                                                   placeholder="0,00" @input="soDinheiro($event)"
                                                   @focus="$event.target.select()"
                                                   @blur="l.valor = formatar(l.valor)">
                                        </div>
                                    </td>
                                    <td>
                                        <button type="button" class="btn-acao btn-acao-perigo" title="Remover parcela"
                                                @click="remover(i)">
                                            <?= Icon::svg('excluir', 'w-4 h-4') ?>
                                        </button>
                                    </td>
                                </tr>
                            </template>

                            <tr x-show="!linhas.length">
                                <td colspan="4" class="text-center text-xs text-slate-500 dark:text-slate-400">
                                    Nenhuma parcela lançada. Nota à vista? Deixe uma só, com o valor cheio.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <button type="button" class="btn btn-ghost btn-sm mt-2" @click="adicionar">
                    <?= Icon::svg('novo', 'w-3.5 h-3.5') ?> Acrescentar uma parcela
                </button>
            </div>
        </div>
    </div>

    <div class="form-acoes">
        <a href="<?= $editando ? url('opme', 'ver', ['id' => (int) $nota['id']]) : url_lista('opme') ?>"
           class="btn btn-outline">Cancelar</a>
        <button type="submit" class="btn btn-primary" :disabled="enviando">
            <span x-show="!enviando"><?= Icon::svg('salvar', 'w-4 h-4') ?></span>
            <span x-show="enviando" x-cloak class="girando"></span>
            <?= $editando ? 'Salvar alterações' : 'Lançar nota' ?>
        </button>
    </div>
</form>

<datalist id="lista-medicos">
    <?php foreach ($medicos as $m): ?>
        <option value="<?= e($m) ?>"></option>
    <?php endforeach; ?>
</datalist>

<datalist id="lista-hospitais">
    <?php foreach ($hospitais as $h): ?>
        <option value="<?= e($h) ?>"></option>
    <?php endforeach; ?>
</datalist>
