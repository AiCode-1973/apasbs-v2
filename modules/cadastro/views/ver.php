<?php
/**
 * O contrato por dentro: as pessoas e o que a família paga.
 *
 * A classificação é reavaliada ao abrir, e não só ao gravar: idade muda
 * sozinha, e um filho que passou dos 18 desde o cadastro mudou de plano
 * sem ninguém mexer em nada. É aqui que isso aparece.
 *
 * @var array $c, $pessoas, $mensalidade, $desalinhados
 * @var int   $declaracoes
 */
declare(strict_types=1);

$podeCriar   = Auth::temPermissao('cadastro', 'pode_criar');
$podeEditar  = Auth::temPermissao('cadastro', 'pode_editar');
$podeExcluir = Auth::temPermissao('cadastro', 'pode_excluir');

$cores = [
    'em_analise' => 'badge-ambar',
    'ativo'      => 'badge-verde',
    'suspenso'   => 'badge-ceu',
    'cancelado'  => 'badge-rosa',
];

$titular = null;
foreach ($pessoas as $p) {
    if ($p['tipo'] === 'titular') {
        $titular = $p;
    }
}

// Papel faltando e plano do titular diferente do que a regra indica: os
// avisos que a tela precisa dar de cara.
//
// O dependente NÃO entra nessa contagem: pela regra da casa ele segue o
// plano do titular, então divergir da sugestão da elegibilidade é o normal
// e virar aviso seria ruído em toda família com filho maior.
$divergentes = 0;
$comPendencia = 0;
foreach ($pessoas as $p) {
    if ($p['status'] !== 'ativo') {
        continue;
    }
    $a = $p['avaliacao'];
    if ($p['tipo'] === 'titular' && $a['elegivel'] && $p['plano'] && $a['plano'] !== $p['plano']) {
        $divergentes++;
    }
    if ($a['pendencias']) {
        $comPendencia++;
    }
}

$valores = [];
foreach ($mensalidade['linhas'] as $l) {
    $valores[$l['id']] = $l;
}
?>

<div class="mb-5 flex flex-wrap items-start justify-between gap-3">
    <div class="flex items-start gap-3">
        <a href="<?= url_lista('cadastro') ?>" class="btn-acao mt-0.5" aria-label="Voltar">
            <?= Icon::svg('anterior', 'w-4 h-4') ?>
        </a>
        <div>
            <h1 class="titulo-pagina">
                <?= $titular ? e($titular['nome']) : 'Contrato sem titular' ?>
            </h1>
            <p class="sub-pagina">
                Contrato <?= e($c['numero_proposta'] ?: '#' . (int) $c['id']) ?> ·
                <?= e(Elegibilidade::TIPOS_CONTRATO[$c['tipo']] ?? $c['tipo']) ?>
                <?php if ($titular && $titular['categoria']): ?>
                    · <?= e(Elegibilidade::CATEGORIAS[$titular['categoria']] ?? '') ?>
                <?php endif; ?>
            </p>
        </div>
    </div>

    <div class="flex flex-wrap items-center gap-2">
        <span class="badge <?= $cores[$c['status']] ?? 'badge-cinza' ?>">
            <?= e(CadastroModel::STATUS[$c['status']] ?? $c['status']) ?>
        </span>

        <?php if ($podeEditar): ?>
            <a href="<?= url('cadastro', 'editar', ['id' => (int) $c['id']]) ?>" class="btn btn-outline">
                <?= Icon::svg('editar', 'w-4 h-4') ?> Editar contrato
            </a>
        <?php endif; ?>

        <?php if ($podeCriar): ?>
            <?php if (!$titular): ?>
                <a href="<?= url('cadastro', 'pessoa', ['contrato' => (int) $c['id'], 'tipo' => 'titular']) ?>"
                   class="btn btn-primary">
                    <?= Icon::svg('novo', 'w-4 h-4') ?> Cadastrar titular
                </a>
            <?php else: ?>
                <a href="<?= url('cadastro', 'pessoa', ['contrato' => (int) $c['id']]) ?>"
                   class="btn btn-primary">
                    <?= Icon::svg('novo', 'w-4 h-4') ?> Incluir dependente
                </a>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php if ($divergentes > 0): ?>
    <div class="mb-4 flex items-start gap-2 rounded-lg border border-amber-200 bg-amber-50/60 px-3 py-2 text-[0.8125rem] text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/5 dark:text-amber-300">
        <?= Icon::svg('aviso', 'w-4 h-4 mt-0.5 shrink-0') ?>
        <span>
            O titular está num plano diferente do que a regra indica hoje. Pode ser
            escolha de vocês — a coluna <em>Plano</em> mostra qual é a diferença.
        </span>
    </div>
<?php endif; ?>

<?php /* O plano é do contrato: dependente fora do plano do titular é
         pendência, e a tela oferece o conserto num clique. */ ?>
<?php if (!empty($desalinhados)): ?>
    <div class="mb-4 flex items-start gap-2 rounded-lg border border-amber-200 bg-amber-50/60 px-3 py-2 text-[0.8125rem] text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/5 dark:text-amber-300">
        <?= Icon::svg('aviso', 'w-4 h-4 mt-0.5 shrink-0') ?>
        <span>
            <strong><?= numero(count($desalinhados)) ?></strong>
            dependente<?= count($desalinhados) === 1 ? '' : 's' ?> fora do plano do titular
            <?php if ($titular && $titular['plano']): ?>
                (<?= e((string) $titular['plano']) ?>)
            <?php endif; ?>.
            <?php if ($podeEditar): ?>
                <a href="<?= url('cadastro', 'alinhar', ['id' => (int) $c['id']]) ?>"
                   class="font-medium underline">Ver o que muda e aplicar a todos →</a>
            <?php endif; ?>
        </span>
    </div>
<?php endif; ?>

<div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_20rem] lg:items-start">

    <div class="card overflow-hidden">
        <div class="card-header">
            <h2 class="card-title">Pessoas no contrato</h2>
            <span class="text-xs text-slate-500 dark:text-slate-400">
                <?= numero(count($pessoas)) ?> cadastrada<?= count($pessoas) === 1 ? '' : 's' ?>
                <?php if ($comPendencia > 0): ?>
                    · <span class="text-amber-600 dark:text-amber-400"><?= numero($comPendencia) ?> com pendência</span>
                <?php endif; ?>
            </span>
        </div>

        <?php if (empty($pessoas)): ?>
            <div class="vazio">
                <span class="vazio-icone"><?= Icon::svg('usuarios', 'w-5 h-5') ?></span>
                <p class="vazio-titulo">Nenhuma pessoa no contrato</p>
                <p class="vazio-texto">
                    Comece pelo titular — só Policial Militar ativo, inativo ou
                    pensionista pode ocupar esse lugar. Os dependentes vêm depois dele.
                </p>
                <?php if ($podeCriar): ?>
                    <a href="<?= url('cadastro', 'pessoa', ['contrato' => (int) $c['id'], 'tipo' => 'titular']) ?>"
                       class="btn btn-primary btn-sm mt-1">
                        <?= Icon::svg('novo', 'w-3.5 h-3.5') ?> Cadastrar titular
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="tabela">
                    <thead>
                        <tr>
                            <th>Pessoa</th>
                            <th class="w-40">Vínculo</th>
                            <th class="w-24">Faixa</th>
                            <th class="w-44">Plano</th>
                            <th class="w-28 text-right">Mensalidade</th>
                            <th class="w-20 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pessoas as $p): ?>
                            <?php
                            $a       = $p['avaliacao'];
                            $linha   = $valores[(int) $p['id']] ?? null;
                            $inativo = $p['status'] !== 'ativo';
                            $difere  = $a['elegivel'] && $p['plano'] && $a['plano'] !== $p['plano'];
                            ?>
                            <tr class="<?= $inativo ? 'opacity-60' : '' ?>">
                                <td>
                                    <span class="celula-principal block"><?= e($p['nome']) ?></span>
                                    <span class="block text-xs text-slate-500 dark:text-slate-400">
                                        <?php if ($p['cpf']): ?>
                                            <span class="mono"><?= e(formatar_cpf((string) $p['cpf'])) ?></span>
                                        <?php endif; ?>
                                        <?php if ($inativo): ?>
                                            <span class="badge badge-cinza ml-1">Inativo</span>
                                        <?php endif; ?>
                                    </span>

                                    <?php /* A pendência fica sob o nome, onde quem confere
                                             a pasta está olhando. */ ?>
                                    <?php if ($a['pendencias'] && !$inativo): ?>
                                        <span class="mt-0.5 block text-[0.6875rem] text-amber-600 dark:text-amber-400">
                                            falta: <?= e(implode(', ', $a['pendencias'])) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td class="celula-suave text-xs">
                                    <?php if ($p['tipo'] === 'titular'): ?>
                                        <span class="font-medium text-slate-700 dark:text-slate-300">Titular</span>
                                        <span class="block text-[0.6875rem] text-slate-400 dark:text-slate-500">
                                            <?= e(Elegibilidade::CATEGORIAS[$p['categoria']] ?? '—') ?>
                                        </span>
                                    <?php else: ?>
                                        <?= e(Elegibilidade::rotuloParentesco($p['parentesco'])) ?>
                                    <?php endif; ?>
                                </td>

                                <td class="celula-suave text-xs">
                                    <?php if ($linha && $linha['faixa'] !== null): ?>
                                        <?= e(Elegibilidade::rotuloFaixa($linha['faixa'])) ?>
                                        <span class="block text-[0.625rem] text-slate-400 dark:text-slate-500">
                                            <?= (int) $linha['idade'] ?> anos
                                        </span>
                                    <?php else: ?>
                                        <span class="text-slate-400 dark:text-slate-600">—</span>
                                    <?php endif; ?>
                                </td>

                                <td class="text-xs">
                                    <?php if ($p['plano']): ?>
                                        <span class="celula-principal"><?= e($p['plano']) ?></span>
                                    <?php else: ?>
                                        <span class="text-amber-600 dark:text-amber-400">sem plano</span>
                                    <?php endif; ?>

                                    <?php if ($difere): ?>
                                        <span class="mt-0.5 block text-[0.625rem] text-amber-600 dark:text-amber-400">
                                            a regra indica <?= e($a['plano']) ?>
                                        </span>
                                    <?php elseif (!$a['elegivel'] && $a['impedimento'] && !$inativo): ?>
                                        <span class="mt-0.5 block text-[0.625rem] text-rose-600 dark:text-rose-400">
                                            fora da regra atual
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td class="text-right text-xs">
                                    <?php if ($inativo): ?>
                                        <span class="text-slate-400 dark:text-slate-600">—</span>
                                    <?php elseif ($linha && $linha['valor'] !== null): ?>
                                        <span class="mono celula-principal"><?= e(dinheiro_br($linha['valor'])) ?></span>
                                    <?php else: ?>
                                        <span class="text-[0.6875rem] text-amber-600 dark:text-amber-400">
                                            <?= e($linha['falta'] ?? 'sem valor') ?>
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <div class="flex items-center justify-end gap-0.5">
                                        <?php if ($podeEditar): ?>
                                            <a href="<?= url('cadastro', 'pessoa', ['id' => (int) $p['id']]) ?>"
                                               class="btn-acao" title="Editar"
                                               aria-label="Editar <?= e($p['nome']) ?>">
                                                <?= Icon::svg('editar', 'w-4 h-4') ?>
                                            </a>
                                        <?php endif; ?>

                                        <?php if ($podeExcluir): ?>
                                            <form method="post" action="<?= url('cadastro', 'excluir_pessoa') ?>" class="inline"
                                                  data-titulo="Excluir do contrato"
                                                  data-texto="<?= e($p['nome']) ?> será removido do cadastro. Para encerrar a participação sem perder o histórico, marque como inativo em vez de excluir.">
                                                <?= Csrf::campo() ?>
                                                <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
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

    <div class="space-y-4">
        <!-- O que a família paga -->
        <div class="card">
            <div class="card-header"><h2 class="card-title">Mensalidade do grupo</h2></div>
            <div class="card-corpo">
                <p class="rotulo-num">Total por mês</p>
                <p class="text-2xl font-semibold text-marine-800 dark:text-marine-200">
                    <?= e(dinheiro_br($mensalidade['total'])) ?>
                </p>
                <p class="ajuda mt-1">
                    Soma das faixas etárias de hoje, pela tabela de preços em vigor.
                </p>

                <?php
                $semValor = array_filter($mensalidade['linhas'], fn($l) => $l['valor'] === null);
                ?>
                <?php if ($semValor): ?>
                    <div class="mt-3 border-t border-slate-100 pt-3 dark:border-slate-800">
                        <p class="text-[0.8125rem] text-amber-700 dark:text-amber-400">
                            <?= numero(count($semValor)) ?>
                            pessoa<?= count($semValor) === 1 ? '' : 's' ?> sem valor calculado —
                            o total está incompleto.
                        </p>
                    </div>
                <?php endif; ?>

                <div class="mt-3 border-t border-slate-100 pt-3 text-xs text-slate-500 dark:border-slate-800 dark:text-slate-400">
                    A faixa etária muda no aniversário, e a mensalidade sobe junto sem
                    ninguém mexer no cadastro.
                    <a href="<?= url('cadastro', 'precos') ?>" class="text-marine-700 hover:underline dark:text-marine-300">
                        Ver a tabela
                    </a>.
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h2 class="card-title">Contrato</h2></div>
            <div class="card-corpo space-y-1">
                <?php
                $campos = [
                    'Nº da proposta' => $c['numero_proposta'],
                    'Tipo'           => Elegibilidade::TIPOS_CONTRATO[$c['tipo']] ?? $c['tipo'],
                    'Adesão'         => $c['data_adesao'] ? data_br($c['data_adesao']) : null,
                    'Situação'       => CadastroModel::STATUS[$c['status']] ?? $c['status'],
                    'Observação'     => $c['observacao'],
                ];
                foreach ($campos as $rotulo => $valor): ?>
                    <div class="flex flex-wrap items-baseline gap-x-2 py-1">
                        <span class="w-28 shrink-0 text-xs text-slate-500 dark:text-slate-400"><?= e($rotulo) ?></span>
                        <span class="text-[0.8125rem]">
                            <?php if ($valor !== null && $valor !== ''): ?>
                                <span class="text-slate-800 dark:text-slate-200"><?= e((string) $valor) ?></span>
                            <?php else: ?>
                                <span class="text-slate-400 dark:text-slate-600">—</span>
                            <?php endif; ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if ($podeExcluir && !empty($declaracoes)): ?>
                <div class="border-t border-slate-100 px-4 py-2.5 text-xs text-slate-500 dark:border-slate-800 dark:text-slate-400">
                    Com declaração de saúde (documento assinado), o contrato não pode ser
                    excluído. Para encerrá-lo, mude a situação para <strong>Cancelado</strong>.
                </div>
            <?php elseif ($podeExcluir):
                $qtd = count($pessoas);
            ?>
                <div class="border-t border-slate-100 px-4 py-3 dark:border-slate-800">
                    <form method="post" action="<?= url('cadastro', 'excluir') ?>"
                          data-titulo="Excluir contrato"
                          data-texto="<?= e($qtd > 0
                              ? sprintf('O contrato será removido junto com %d pessoa%s cadastrada%s. Esta ação não pode ser desfeita — para encerrar sem perder o histórico, mude a situação para Cancelado.',
                                  $qtd, $qtd === 1 ? '' : 's', $qtd === 1 ? '' : 's')
                              : 'O contrato será removido. Esta ação não pode ser desfeita.') ?>">
                        <?= Csrf::campo() ?>
                        <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                        <button type="button" @click="abrir" class="btn btn-outline btn-sm text-rose-600 dark:text-rose-400">
                            <?= Icon::svg('excluir', 'w-3.5 h-3.5') ?> Excluir contrato
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
