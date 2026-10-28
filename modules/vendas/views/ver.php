<?php
/**
 * A proposta por dentro — e o que fazer com ela.
 *
 * É aqui que a venda vira contrato: o botão de fechar cria o contrato no
 * Cadastro com o titular dentro, e a partir daí o cadastro da família é de
 * lá.
 *
 * @var array $p, $comissao, $pessoas
 */
declare(strict_types=1);

$podeEditar  = Auth::temPermissao('vendas', 'pode_editar');
$podeExcluir = Auth::temPermissao('vendas', 'pode_excluir');

$cores = [
    'em_negociacao' => 'badge-ambar',
    'fechada'       => 'badge-verde',
    'perdida'       => 'badge-rosa',
];

$linha = function (string $rotulo, ?string $valor, bool $mono = false): string {
    $conteudo = ($valor !== null && $valor !== '')
        ? '<span class="' . ($mono ? 'mono ' : '') . 'text-slate-800 dark:text-slate-200">' . e($valor) . '</span>'
        : '<span class="text-slate-400 dark:text-slate-600">—</span>';

    return '<div class="flex flex-wrap items-baseline gap-x-2 py-1">'
         . '<span class="w-44 shrink-0 text-xs text-slate-500 dark:text-slate-400">' . e($rotulo) . '</span>'
         . '<span class="text-[0.8125rem]">' . $conteudo . '</span></div>';
};
?>

<div class="mb-5 flex flex-wrap items-start justify-between gap-3">
    <div class="flex items-start gap-3">
        <a href="<?= url_lista('vendas') ?>" class="btn-acao mt-0.5" aria-label="Voltar">
            <?= Icon::svg('anterior', 'w-4 h-4') ?>
        </a>
        <div>
            <h1 class="titulo-pagina"><?= e($p['titular_nome']) ?></h1>
            <p class="sub-pagina">
                Proposta <span class="mono"><?= e($p['numero']) ?></span> ·
                <?= e($p['vendedor']) ?> (<?= e($p['empresa']) ?>) ·
                <?= e(data_br($p['data_proposta'])) ?>
            </p>
        </div>
    </div>

    <div class="flex flex-wrap items-center gap-2">
        <span class="badge <?= $cores[$p['status']] ?? 'badge-cinza' ?>">
            <?= e(VendasModel::STATUS[$p['status']] ?? $p['status']) ?>
        </span>
        <?php if ($podeEditar && $p['status'] !== 'fechada'): ?>
            <a href="<?= url('vendas', 'editar', ['id' => (int) $p['id']]) ?>" class="btn btn-primary">
                <?= Icon::svg('editar', 'w-4 h-4') ?> Editar
            </a>
        <?php endif; ?>
    </div>
</div>

<?php if ($p['status'] === 'fechada' && $p['contrato_id']): ?>
    <div class="mb-4 flex items-start gap-2 rounded-lg border border-emerald-200 bg-emerald-50/70 px-3 py-2 text-[0.8125rem] text-emerald-800 dark:border-emerald-500/30 dark:bg-emerald-500/5 dark:text-emerald-300">
        <?= Icon::svg('check', 'w-4 h-4 mt-0.5 shrink-0') ?>
        <span>
            Venda fechada em <strong><?= e(data_br($p['data_fechamento'])) ?></strong>. O contrato
            foi criado no Cadastro com o titular —
            <a href="<?= url('cadastro', 'ver', ['id' => (int) $p['contrato_id']]) ?>"
               class="font-medium underline">abrir o contrato e incluir os dependentes →</a>
        </span>
    </div>
<?php elseif ($p['status'] === 'perdida'): ?>
    <div class="mb-4 flex items-start gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-[0.8125rem] text-slate-600 dark:border-slate-800 dark:bg-slate-900/60 dark:text-slate-400">
        <?= Icon::svg('aviso', 'w-4 h-4 mt-0.5 shrink-0') ?>
        <span>
            Proposta perdida<?= $p['motivo_perda'] ? ': ' . e($p['motivo_perda']) : '' ?>.
        </span>
    </div>
<?php endif; ?>

<div class="grid gap-4 lg:grid-cols-[1.6fr_1fr] lg:items-start">
    <div class="space-y-4">
        <div class="card">
            <div class="card-header"><h2 class="card-title">Quem será titular</h2></div>
            <div class="card-corpo">
                <?= $linha('Nome', $p['titular_nome']) ?>
                <?= $linha('CPF', $p['titular_cpf'] ? formatar_cpf((string) $p['titular_cpf']) : '', true) ?>
                <?= $linha('Nascimento', data_br($p['titular_nascimento'], '')) ?>
                <?= $linha('Categoria', $p['titular_categoria']
                    ? (Elegibilidade::CATEGORIAS[$p['titular_categoria']] ?? '') : '') ?>
                <?= $linha('Telefone', telefone_br($p['contato'])) ?>
                <?= $linha('E-mail', $p['email']) ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h2 class="card-title">A venda</h2></div>
            <div class="card-corpo">
                <?= $linha('Tipo de contrato', Elegibilidade::TIPOS_CONTRATO[$p['tipo']] ?? $p['tipo']) ?>
                <?= $linha('Vidas previstas', numero((int) $p['vidas'])
                    . ' · ' . numero(count($pessoas) + 1) . ' cadastrada'
                    . (count($pessoas) === 0 ? '' : 's')) ?>
                <?= $linha('Vendedor', $p['vendedor'] . ' — ' . $p['empresa']) ?>
                <?= $linha('Data da proposta', data_br($p['data_proposta'])) ?>
                <?= $linha('Fechamento', data_br($p['data_fechamento'], '')) ?>
                <?= $linha('Observação', $p['observacao']) ?>
            </div>

            <div class="border-t border-slate-100 px-4 py-2.5 text-[0.6875rem] text-slate-400 dark:border-slate-800 dark:text-slate-500">
                Registrada por <?= e($p['usuario_nome'] ?: '—') ?> em <?= e(datahora_br($p['created_at'])) ?>.
            </div>
        </div>

        <?php /* A família que a venda levou. Enquanto a proposta está aberta, é
                 aqui que o vendedor monta o grupo; fechada, isto vira o contrato
                 e o Cadastro assume. */ ?>
        <div class="card overflow-hidden">
            <div class="card-header">
                <h2 class="card-title">
                    As vidas da família
                    <span class="ml-1 text-xs font-normal text-slate-500 dark:text-slate-400">
                        <?= numero(count($pessoas) + 1) ?> no total, com o titular
                    </span>
                </h2>
                <?php if ($podeEditar && $p['status'] === 'em_negociacao'): ?>
                    <a href="<?= url('vendas', 'pessoa', ['proposta' => (int) $p['id']]) ?>"
                       class="btn btn-outline btn-sm">
                        <?= Icon::svg('novo', 'w-3.5 h-3.5') ?> Incluir
                    </a>
                <?php endif; ?>
            </div>

            <div class="overflow-x-auto">
                <table class="tabela">
                    <thead>
                        <tr>
                            <th>Pessoa</th>
                            <th class="w-44">Parentesco</th>
                            <th class="w-28">Nascimento</th>
                            <th class="w-20 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <span class="celula-principal block"><?= e($p['titular_nome']) ?></span>
                                <?php if ($p['titular_cpf']): ?>
                                    <span class="mono block text-xs text-slate-500 dark:text-slate-400">
                                        <?= e(formatar_cpf((string) $p['titular_cpf'])) ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="celula-suave text-xs">Titular</td>
                            <td class="celula-suave text-xs"><?= e(data_br($p['titular_nascimento'], '—')) ?></td>
                            <td></td>
                        </tr>

                        <?php foreach ($pessoas as $pessoa): ?>
                            <?php
                            $avaliacao = Elegibilidade::avaliar(
                                ['tipo' => 'dependente', 'parentesco' => $pessoa['parentesco'],
                                 'nascimento' => $pessoa['nascimento']]
                                + array_map('intval', array_intersect_key($pessoa, Elegibilidade::CONDICOES)),
                                $p['tipo']
                            );
                            ?>
                            <tr>
                                <td>
                                    <span class="celula-principal block"><?= e($pessoa['nome']) ?></span>
                                    <span class="block text-xs text-slate-500 dark:text-slate-400">
                                        <?php if ($pessoa['cpf']): ?>
                                            <span class="mono"><?= e(formatar_cpf((string) $pessoa['cpf'])) ?></span>
                                        <?php endif; ?>
                                        <?php if ($avaliacao['elegivel'] && $avaliacao['plano']): ?>
                                            <?= $pessoa['cpf'] ? ' · ' : '' ?><?= e($avaliacao['plano']) ?>
                                        <?php elseif (!$avaliacao['elegivel']): ?>
                                            <?= $pessoa['cpf'] ? ' · ' : '' ?><span class="text-amber-600 dark:text-amber-400">confira a regra</span>
                                        <?php endif; ?>
                                    </span>
                                </td>

                                <td class="celula-suave text-xs">
                                    <?= e(Elegibilidade::rotuloParentesco($pessoa['parentesco'])) ?>
                                </td>

                                <td class="celula-suave text-xs">
                                    <?= e(data_br($pessoa['nascimento'], '—')) ?>
                                </td>

                                <td>
                                    <div class="flex items-center justify-end gap-0.5">
                                        <?php if ($podeEditar && $p['status'] === 'em_negociacao'): ?>
                                            <a href="<?= url('vendas', 'pessoa', ['id' => (int) $pessoa['id']]) ?>"
                                               class="btn-acao" title="Editar" aria-label="Editar <?= e($pessoa['nome']) ?>">
                                                <?= Icon::svg('editar', 'w-4 h-4') ?>
                                            </a>

                                            <form method="post" action="<?= url('vendas', 'excluir_pessoa') ?>" class="inline"
                                                  data-titulo="Tirar da proposta"
                                                  data-texto="<?= e($pessoa['nome']) ?> sai desta proposta. Isso não mexe em nada no Cadastro.">
                                                <?= Csrf::campo() ?>
                                                <input type="hidden" name="id" value="<?= (int) $pessoa['id'] ?>">
                                                <button type="button" @click="abrir" class="btn-acao btn-acao-perigo"
                                                        title="Tirar" aria-label="Tirar da proposta">
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
                <?php if ($p['status'] === 'fechada'): ?>
                    Esta lista é o registro do que foi vendido. As pessoas já estão no
                    contrato, e é lá que elas se corrigem.
                <?php else: ?>
                    O plano ao lado do nome é o que a regra indica hoje. Ao fechar, todos
                    entram no contrato no plano do titular — a regra da casa — e a
                    indicação fica gravada para conferência.
                <?php endif; ?>
            </div>
        </div>

        <?php if ($podeExcluir && $p['status'] !== 'fechada'): ?>
            <div class="card">
                <div class="px-4 py-3">
                    <form method="post" action="<?= url('vendas', 'excluir') ?>"
                          data-titulo="Excluir proposta"
                          data-texto="A proposta <?= e($p['numero']) ?> será removida. Proposta perdida costuma valer mais guardada que apagada — o motivo ensina a corrigir o discurso de venda.">
                        <?= Csrf::campo() ?>
                        <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                        <button type="button" @click="abrir" class="btn btn-outline btn-sm text-rose-600 dark:text-rose-400">
                            <?= Icon::svg('excluir', 'w-3.5 h-3.5') ?> Excluir proposta
                        </button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="space-y-4">
        <?php if ($p['status'] === 'em_negociacao' && $podeEditar): ?>
            <div class="card">
                <div class="card-header"><h2 class="card-title">Desfecho</h2></div>

                <div class="card-corpo space-y-4">
                    <form method="post" action="<?= url('vendas', 'fechar') ?>" class="space-y-2"
                          data-titulo="Fechar a venda"
                          data-acao="Fechar a venda" data-tom="normal"
                          data-texto="O contrato de <?= e($p['titular_nome']) ?> será criado no Cadastro com <?= e(numero(count($pessoas) + 1)) ?> pessoa<?= count($pessoas) === 0 ? '' : 's' ?>: o titular<?= count($pessoas) > 0 ? ' e ' . e(numero(count($pessoas))) . ' dependente' . (count($pessoas) === 1 ? '' : 's') : '' ?>. Quem faltar entra depois, pelo Cadastro.">
                        <?= Csrf::campo() ?>
                        <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">

                        <label class="label" for="data_fechamento">Data do fechamento</label>
                        <input type="date" name="data_fechamento" id="data_fechamento" class="input"
                               value="<?= e(date('Y-m-d')) ?>">

                        <button type="button" @click="abrir" class="btn btn-primary w-full">
                            <?= Icon::svg('check', 'w-4 h-4') ?> Fechar e criar o contrato
                        </button>

                        <?php if (($p['titular_categoria'] ?? '') === ''): ?>
                            <p class="ajuda text-amber-600 dark:text-amber-400">
                                Falta a categoria do titular — sem ela o contrato não nasce.
                            </p>
                        <?php endif; ?>
                    </form>

                    <form method="post" action="<?= url('vendas', 'perder') ?>"
                          class="space-y-2 border-t border-slate-100 pt-4 dark:border-slate-800">
                        <?= Csrf::campo() ?>
                        <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">

                        <label class="label" for="motivo_perda">Motivo, se a venda caiu</label>
                        <input type="text" name="motivo_perda" id="motivo_perda" maxlength="200" class="input"
                               placeholder="Ex.: achou caro, foi para outro plano">

                        <button type="submit" class="btn btn-outline btn-sm w-full">
                            Marcar como perdida
                        </button>
                    </form>
                </div>
            </div>
        <?php elseif ($p['status'] === 'perdida' && $podeEditar): ?>
            <div class="card">
                <div class="card-header"><h2 class="card-title">Desfecho</h2></div>
                <div class="card-corpo">
                    <form method="post" action="<?= url('vendas', 'reabrir') ?>">
                        <?= Csrf::campo() ?>
                        <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                        <button type="submit" class="btn btn-outline btn-sm w-full">
                            <?= Icon::svg('anterior', 'w-3.5 h-3.5') ?> Voltar para negociação
                        </button>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header"><h2 class="card-title">Comissão</h2></div>

            <?php if ($p['status'] !== 'fechada'): ?>
                <div class="card-corpo">
                    <p class="text-[0.8125rem] text-slate-600 dark:text-slate-400">
                        A comissão só existe depois do fechamento. O percentual de
                        <strong><?= e(number_format((float) $p['percent_atual'], 2, ',', '')) ?>%</strong>
                        combinado com <?= e($p['vendedor']) ?> fica congelado na proposta no dia
                        em que ela fechar.
                    </p>
                </div>
            <?php else: ?>
                <div class="card-corpo space-y-3">
                    <div class="flex items-baseline justify-between">
                        <span class="text-xs text-slate-500 dark:text-slate-400">Mensalidade de hoje</span>
                        <span class="text-sm text-slate-700 dark:text-slate-300">
                            <?= e(dinheiro_br($comissao['mensalidade'])) ?>
                        </span>
                    </div>

                    <div class="flex items-baseline justify-between">
                        <span class="text-xs text-slate-500 dark:text-slate-400">
                            Percentual (<?= e(number_format($comissao['percent'], 2, ',', '')) ?>%)
                        </span>
                        <span class="text-lg font-semibold text-slate-900 dark:text-slate-100">
                            <?= e(dinheiro_br($comissao['valor'])) ?>
                        </span>
                    </div>

                    <p class="ajuda border-t border-slate-100 pt-3 dark:border-slate-800">
                        A comissão acompanha a mensalidade do contrato: a família cresce, a
                        faixa etária muda no aniversário, e a conta sobe junto. O percentual
                        é o que estava combinado no dia do fechamento.
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
