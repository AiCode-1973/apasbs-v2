<?php
/**
 * Levar os dependentes para o plano do titular.
 *
 * A tela existe para o "quanto isso custa" aparecer ANTES do sim: mudar o
 * plano de uma família muda a mensalidade dela, e esse é o tipo de conta que
 * ninguém quer descobrir na fatura.
 *
 * @var array $c, $s
 */
declare(strict_types=1);

$diferenca = $s['depois'] - $s['antes'];
$ativos    = array_filter($s['linhas'], fn($l) => $l['status'] === 'ativo');
$inativos  = array_filter($s['linhas'], fn($l) => $l['status'] !== 'ativo');
?>

<div class="mb-5 flex items-start gap-3">
    <a href="<?= url('cadastro', 'ver', ['id' => (int) $c['id']]) ?>" class="btn-acao mt-0.5" aria-label="Voltar">
        <?= Icon::svg('anterior', 'w-4 h-4') ?>
    </a>
    <div>
        <h1 class="titulo-pagina">Aplicar o plano do titular à família</h1>
        <p class="sub-pagina">
            Contrato <?= e($c['numero_proposta'] ?: '#' . (int) $c['id']) ?> ·
            titular no <strong><?= e((string) $s['plano']) ?></strong>
        </p>
    </div>
</div>

<div class="mb-4 flex items-start gap-2 rounded-lg border border-amber-200 bg-amber-50/70 px-3 py-2 text-[0.8125rem] text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/5 dark:text-amber-300">
    <?= Icon::svg('aviso', 'w-4 h-4 mt-0.5 shrink-0') ?>
    <span>
        O plano é do contrato: quando o titular muda, a família acompanha —
        inclusive quem a regra de elegibilidade indicaria em outro plano. A
        mensalidade de cada pessoa passa a ser a da faixa etária dela
        <strong>no plano novo</strong>.
    </span>
</div>

<div class="grid gap-4 lg:grid-cols-[1.6fr_1fr] lg:items-start">
    <div class="card overflow-hidden">
        <div class="card-header">
            <h2 class="card-title">
                Quem muda de plano
                <span class="ml-1 text-xs font-normal text-slate-500 dark:text-slate-400">
                    <?= numero(count($s['linhas'])) ?> pessoa<?= count($s['linhas']) === 1 ? '' : 's' ?>
                </span>
            </h2>
        </div>

        <div class="overflow-x-auto">
            <table class="tabela">
                <thead>
                    <tr>
                        <th>Pessoa</th>
                        <th class="w-40">De</th>
                        <th class="w-40">Para</th>
                        <th class="w-32 text-right">Mensalidade</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($s['linhas'] as $l): ?>
                        <?php
                        $sobe = $l['valor_de'] !== null && $l['valor_para'] !== null
                                && $l['valor_para'] > $l['valor_de'];
                        $desce = $l['valor_de'] !== null && $l['valor_para'] !== null
                                 && $l['valor_para'] < $l['valor_de'];
                        ?>
                        <tr>
                            <td>
                                <span class="celula-principal block"><?= e($l['nome']) ?></span>
                                <span class="block text-xs text-slate-500 dark:text-slate-400">
                                    <?= e(Elegibilidade::rotuloParentesco($l['parentesco'])) ?>
                                    <?php if ($l['idade'] !== null): ?>
                                        · <?= (int) $l['idade'] ?> anos
                                    <?php endif; ?>
                                    <?php if ($l['status'] !== 'ativo'): ?>
                                        · <span class="text-slate-400 dark:text-slate-500">inativo</span>
                                    <?php endif; ?>
                                </span>
                            </td>

                            <td class="celula-suave text-xs">
                                <?= $l['de'] !== null ? e($l['de']) : '<span class="text-slate-400 dark:text-slate-600">sem plano</span>' ?>
                            </td>

                            <td class="text-xs font-medium text-slate-700 dark:text-slate-300">
                                <?= e($l['para']) ?>
                            </td>

                            <td class="whitespace-nowrap text-right text-xs">
                                <?php if ($l['status'] !== 'ativo'): ?>
                                    <span class="text-slate-400 dark:text-slate-600">não paga</span>
                                <?php elseif ($l['valor_de'] === null || $l['valor_para'] === null): ?>
                                    <span class="text-amber-600 dark:text-amber-400">sem preço na tabela</span>
                                <?php else: ?>
                                    <span class="text-slate-400 line-through dark:text-slate-600">
                                        <?= e(dinheiro_br($l['valor_de'])) ?>
                                    </span>
                                    <span class="ml-1 font-medium <?= $sobe ? 'text-rose-600 dark:text-rose-400' : ($desce ? 'text-emerald-600 dark:text-emerald-400' : '') ?>">
                                        <?= e(dinheiro_br($l['valor_para'])) ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($inativos): ?>
            <div class="border-t border-slate-100 px-4 py-2.5 text-xs text-slate-500 dark:border-slate-800 dark:text-slate-400">
                <?= numero(count($inativos)) ?> pessoa<?= count($inativos) === 1 ? '' : 's' ?>
                inativa<?= count($inativos) === 1 ? '' : 's' ?> também
                muda<?= count($inativos) === 1 ? '' : 'm' ?> de plano. Não entra na conta hoje,
                mas se voltar ao plano volta no plano certo.
            </div>
        <?php endif; ?>
    </div>

    <div class="card self-start">
        <div class="card-header">
            <h2 class="card-title">A mensalidade da família</h2>
        </div>

        <div class="card-corpo space-y-3">
            <div class="flex items-baseline justify-between">
                <span class="text-xs text-slate-500 dark:text-slate-400">Hoje</span>
                <span class="text-sm text-slate-600 dark:text-slate-300"><?= e(dinheiro_br($s['antes'])) ?></span>
            </div>

            <div class="flex items-baseline justify-between">
                <span class="text-xs text-slate-500 dark:text-slate-400">Depois</span>
                <span class="text-lg font-semibold text-slate-900 dark:text-slate-100">
                    <?= e(dinheiro_br($s['depois'])) ?>
                </span>
            </div>

            <div class="border-t border-slate-100 pt-3 dark:border-slate-800">
                <?php if (abs($diferenca) < 0.005): ?>
                    <p class="text-[0.8125rem] text-slate-600 dark:text-slate-400">
                        O total não muda.
                    </p>
                <?php else: ?>
                    <p class="text-[0.8125rem] <?= $diferenca > 0 ? 'text-rose-700 dark:text-rose-300' : 'text-emerald-700 dark:text-emerald-300' ?>">
                        <?= $diferenca > 0 ? 'Aumento' : 'Redução' ?> de
                        <strong><?= e(dinheiro_br(abs($diferenca))) ?></strong> por mês.
                    </p>
                <?php endif; ?>

                <p class="ajuda">
                    Valores da tabela vigente hoje, pela faixa etária de cada pessoa.
                    Só entra quem está ativo.
                </p>
            </div>
        </div>

        <div class="border-t border-slate-100 px-4 py-3 dark:border-slate-800">
            <form method="post" action="<?= url('cadastro', 'alinhar_confirmar') ?>" class="flex flex-wrap gap-2">
                <?= Csrf::campo() ?>
                <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                <button type="submit" class="btn btn-primary btn-sm">
                    <?= Icon::svg('salvar', 'w-3.5 h-3.5') ?> Aplicar a todos
                </button>
                <a href="<?= url('cadastro', 'ver', ['id' => (int) $c['id']]) ?>" class="btn btn-ghost btn-sm">
                    Agora não
                </a>
            </form>

            <p class="ajuda">
                Recusando, o titular fica no plano novo e os dependentes ficam como
                estão — o contrato mostra o aviso até alguém resolver.
            </p>
        </div>
    </div>
</div>
