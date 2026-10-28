<?php
/**
 * Auditoria Hospitalar — o consolidado de uma competência.
 *
 * O número desta tela é somado na hora, a partir das contas. O AuditorHosp
 * guarda tabelas consolidadas à parte, atualizadas por um botão, e elas já
 * divergem das contas em mais de uma competência — aqui não existe o estado
 * "esqueci de consolidar": abriu a tela, somou.
 *
 * Os percentuais são os mesmos de uma conta e sobre as mesmas bases: retirado
 * e acrescentado sobre o inicial, glosado sobre o final, aceito sobre o
 * glosado. Cada um calculado sobre as somas, nunca pela média dos percentuais
 * das contas — média de percentual não é percentual do total.
 *
 * @var string $tipo
 * @var array  $cfg, $linhas, $total, $setores, $evolucao, $competencias, $convenios, $filtro
 * @var bool   $porSetor
 */
declare(strict_types=1);

require __DIR__ . '/_consolidado_apoio.php';

$temFiltro = $filtro['status'] !== '' || $filtro['convenio_id'] || $filtro['setor'] !== '';

$base = array_filter([
    'tipo'     => $tipo,
    'status'   => $filtro['status'] !== '' ? $filtro['status'] : null,
    'convenio' => $filtro['convenio_id'] ?: null,
    'setor'    => $filtro['setor'] !== '' ? $filtro['setor'] : null,
    'setores'  => $porSetor ? 1 : null,
]) + ['competencia' => $filtro['competencia']];

$pct  = AuditoriaHospModel::percentuais($total);

// O ranking da taxa de glosa: só entra quem glosou e tem base para o
// percentual. Uma lista de barras em 0% não é informação, é ruído — e esconde
// as duas linhas que realmente importam quando elas existem.
$ranking = [];
foreach ($linhas as $l) {
    $p = AuditoriaHospModel::percentuais($l);
    if ($p['glosado'] !== null && (float) $l['valor_glosado'] > 0) {
        $ranking[] = ['rotulo' => $rotuloLinha($l), 'pct' => $p['glosado'],
                      'valor'  => (float) $l['valor_glosado']];
    }
}
usort($ranking, static fn ($a, $b) => $b['pct'] <=> $a['pct']);
$ranking   = array_slice($ranking, 0, 8);
$maiorPct  = $ranking ? $ranking[0]['pct'] : 0.0;

// Escala do gráfico: o maior valor da série manda, e as duas séries dividem a
// mesma escala — barra de glosa comparada com barra de glosa de outro mês só
// significa algo se a régua for a mesma.
$teto = 0.0;
foreach ($evolucao as $m) {
    $teto = max($teto, (float) $m['valor_total'], (float) $m['valor_glosado']);
}
?>

<div class="mb-5 flex flex-wrap items-end justify-between gap-3">
    <div>
        <h1 class="titulo-pagina">Consolidado da auditoria</h1>
        <p class="sub-pagina">
            <?= e($cfg['plural']) ?> ·
            <?= $filtro['competencia'] !== ''
                ? 'competência ' . e($mesBr($filtro['competencia']))
                : 'todas as competências' ?>
        </p>
    </div>

    <div class="flex flex-wrap items-center gap-2">
        <a href="<?= url('auditoria_hosp', 'lista', ['tipo' => $tipo]) ?>" class="btn btn-outline">
            <?= Icon::svg('auditoria_hosp', 'w-4 h-4') ?> Contas
        </a>
        <a href="<?= url('auditoria_hosp', 'imprimir', $base) ?>" target="_blank" class="btn btn-primary">
            <?= Icon::svg('imprimir', 'w-4 h-4') ?> Imprimir
        </a>
    </div>
</div>

<?php /* Os dois tipos são a mesma tela, como na lista. */ ?>
<div class="mb-4 flex flex-wrap items-center gap-1.5">
    <?php foreach (AuditoriaHospModel::TIPOS as $chave => $t): ?>
        <a href="<?= url('auditoria_hosp', 'consolidado', ['tipo' => $chave]) ?>"
           class="btn btn-sm <?= $tipo === $chave ? 'btn-primary' : 'btn-outline' ?>">
            <?= e($t['plural']) ?>
        </a>
    <?php endforeach; ?>
</div>

<form method="get" class="card card-corpo mb-4 flex flex-wrap items-center gap-2">
    <input type="hidden" name="mod" value="auditoria_hosp">
    <input type="hidden" name="action" value="consolidado">
    <input type="hidden" name="tipo" value="<?= e($tipo) ?>">

    <select name="competencia" class="select w-auto" aria-label="Competência">
        <option value="">Todas as competências</option>
        <?php foreach ($competencias as $mes): ?>
            <option value="<?= e($mes) ?>" <?= $filtro['competencia'] === $mes ? 'selected' : '' ?>>
                <?= e($mesBr($mes)) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <select name="convenio" class="select w-auto" aria-label="Convênio">
        <option value="">Todos os convênios</option>
        <?php foreach ($convenios as $c): ?>
            <option value="<?= (int) $c['id'] ?>" <?= $filtro['convenio_id'] === (int) $c['id'] ? 'selected' : '' ?>>
                <?= e($c['nome_convenio']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <select name="status" class="select w-auto" aria-label="Situação">
        <option value="">Todas as situações</option>
        <?php foreach (AuditoriaHospModel::SITUACOES as $st): ?>
            <option value="<?= e($st) ?>" <?= $filtro['status'] === $st ? 'selected' : '' ?>><?= e($st) ?></option>
        <?php endforeach; ?>
    </select>

    <?php if ($cfg['setor']): ?>
        <select name="setor" class="select w-auto" aria-label="Setor">
            <option value="">Todos os setores</option>
            <?php foreach (AuditoriaHospModel::SETORES as $s): ?>
                <option value="<?= e($s) ?>" <?= $filtro['setor'] === $s ? 'selected' : '' ?>><?= e($s) ?></option>
            <?php endforeach; ?>
        </select>

        <label class="flex cursor-pointer items-center gap-1.5 text-xs text-slate-600 dark:text-slate-400">
            <input type="checkbox" name="setores" value="1" class="check" <?= $porSetor ? 'checked' : '' ?>>
            Abrir cada convênio por setor
        </label>
    <?php endif; ?>

    <button type="submit" class="btn btn-outline">Filtrar</button>

    <?php if ($temFiltro): ?>
        <a href="<?= url('auditoria_hosp', 'consolidado', ['tipo' => $tipo, 'competencia' => $filtro['competencia']]) ?>"
           class="btn btn-ghost">Limpar</a>
    <?php endif; ?>
</form>

<!-- Os números da competência -->
<div class="card mb-4">
    <div class="card-corpo grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
        <div>
            <p class="rotulo-num">Contas</p>
            <p class="valor-num whitespace-nowrap text-xl"><?= numero((int) $total['contas']) ?></p>
            <p class="ajuda"><?= numero(count($linhas)) ?> <?= $porSetor ? 'linhas' : 'convênio' . (count($linhas) === 1 ? '' : 's') ?></p>
        </div>
        <div>
            <p class="rotulo-num">Valor inicial</p>
            <p class="valor-num whitespace-nowrap text-xl"><?= e(dinheiro_br((float) $total['valor_inicial'])) ?></p>
            <p class="ajuda">como as contas chegaram</p>
        </div>
        <div>
            <p class="rotulo-num">Retirado</p>
            <p class="valor-num whitespace-nowrap text-xl text-amber-600 dark:text-amber-400">
                <?= e(dinheiro_br((float) $total['valor_retirado'])) ?>
            </p>
            <p class="ajuda"><?= $percTexto($pct['retirado'], 'do inicial') ?></p>
        </div>
        <div>
            <p class="rotulo-num">Valor final</p>
            <p class="valor-num whitespace-nowrap text-xl"><?= e(dinheiro_br((float) $total['valor_total'])) ?></p>
            <p class="ajuda">
                <?php if ((float) $total['valor_acrescentado'] > 0): ?>
                    com <?= e(dinheiro_br((float) $total['valor_acrescentado'], false)) ?> acrescentado
                <?php elseif ((float) $total['valor_acrescentado'] < 0): ?>
                    com <?= e(dinheiro_br(abs((float) $total['valor_acrescentado']), false)) ?>
                    de acréscimo negativo
                <?php else: ?>
                    depois da auditoria
                <?php endif; ?>
            </p>
        </div>
        <div>
            <p class="rotulo-num">Glosado</p>
            <p class="valor-num whitespace-nowrap text-xl text-rose-600 dark:text-rose-400">
                <?= e(dinheiro_br((float) $total['valor_glosado'])) ?>
            </p>
            <p class="ajuda"><?= $percTexto($pct['glosado'], 'do valor final') ?></p>
        </div>
        <div>
            <p class="rotulo-num">Aceito no recurso</p>
            <p class="valor-num whitespace-nowrap text-xl text-emerald-600 dark:text-emerald-400">
                <?= e(dinheiro_br((float) $total['valor_aceito'])) ?>
            </p>
            <p class="ajuda"><?= $percTexto($pct['aceito'], 'da glosa revertida') ?></p>
        </div>
    </div>
</div>

<?php /* Conta sem valor final entra no inicial e não entra no final, então
         empurra o percentual de glosa para cima. Dizer isso é mais útil do
         que deixar o número grande sem explicação. */ ?>
<?php if ((int) $total['sem_final'] > 0): ?>
    <div class="alerta alerta-aviso mb-4">
        <?= Icon::svg('aviso', 'w-4 h-4 shrink-0') ?>
        <span>
            <?php if ((int) $total['sem_final'] === 1): ?>
                Uma conta deste recorte chegou com valor e ainda está sem valor final:
                ela soma no inicial e não soma no final,
            <?php else: ?>
                <?= numero((int) $total['sem_final']) ?> contas deste recorte chegaram com valor
                e ainda estão sem valor final: elas somam no inicial e não somam no final,
            <?php endif; ?>
            então o percentual de glosa fica maior do que vai ficar quando a auditoria terminar.
        </span>
    </div>
<?php endif; ?>

<div class="grid gap-4 lg:grid-cols-3">

    <!-- Evolução dos últimos meses -------------------------------------------
         Duas séries na mesma régua: valor final e glosado, mês a mês. Sem
         biblioteca de gráfico — é barra de CSS, que imprime e não depende de
         script nenhum. -->
    <section class="card lg:col-span-2">
        <div class="card-header">
            <h2 class="card-title">Valor final e glosa, mês a mês</h2>
            <div class="flex items-center gap-3 text-[0.6875rem] text-slate-500 dark:text-slate-400">
                <span class="flex items-center gap-1.5">
                    <span class="h-2 w-2 rounded-sm bg-marine-600 dark:bg-marine-400"></span> Final
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="h-2 w-2 rounded-sm bg-rose-500 dark:bg-rose-400"></span> Glosado
                </span>
            </div>
        </div>

        <?php if (count($evolucao) < 2): ?>
            <div class="vazio py-10">
                <span class="vazio-icone"><?= Icon::svg('fechamentos', 'w-5 h-5') ?></span>
                <p class="vazio-texto">Uma competência só não desenha evolução.</p>
            </div>
        <?php else: ?>
            <div class="p-4">
                <?php /* As colunas esticam com o container (items-stretch), e a
                         área das barras é que flexiona: sem isso a barra herda
                         altura zero e o gráfico sai em branco. */ ?>
                <div class="flex h-44 items-stretch gap-1 sm:gap-2">
                    <?php foreach ($evolucao as $m): ?>
                        <?php
                        $final   = (float) $m['valor_total'];
                        $glosado = (float) $m['valor_glosado'];
                        $pm      = AuditoriaHospModel::percentuais($m);
                        $atual   = $filtro['competencia'] === $m['mes'];
                        ?>
                        <div class="flex min-w-0 flex-1 flex-col items-center gap-1.5">
                            <div class="flex w-full flex-1 items-end justify-center gap-0.5"
                                 title="<?= e($mesBr((string) $m['mes'])) ?>: final <?= e(dinheiro_br($final)) ?>, glosado <?= e(dinheiro_br($glosado)) ?><?= $pm['glosado'] !== null ? ' (' . e(number_format($pm['glosado'], 2, ',', '.')) . '%)' : '' ?>">
                                <div class="w-1/2 rounded-t-sm bg-marine-600 dark:bg-marine-400"
                                     style="height: <?= $altura($final, $teto) ?>%"></div>
                                <div class="w-1/2 rounded-t-sm bg-rose-500 dark:bg-rose-400"
                                     style="height: <?= $altura($glosado, $teto) ?>%"></div>
                            </div>
                            <a href="<?= url('auditoria_hosp', 'consolidado', ['tipo' => $tipo, 'competencia' => $m['mes']]) ?>"
                               class="block w-full truncate text-center text-[0.625rem] <?= $atual
                                    ? 'font-semibold text-marine-700 dark:text-marine-300'
                                    : 'text-slate-500 hover:text-marine-700 dark:text-slate-400 dark:hover:text-marine-300' ?>">
                                <?= e($mesBr((string) $m['mes'])) ?>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </section>

    <!-- Taxa de glosa por convênio ------------------------------------------- -->
    <section class="card self-start">
        <div class="card-header">
            <h2 class="card-title">Maiores taxas de glosa</h2>
        </div>

        <?php if (!$ranking): ?>
            <div class="vazio py-10">
                <span class="vazio-icone"><?= Icon::svg('fechamentos', 'w-5 h-5') ?></span>
                <p class="vazio-texto">Nenhuma glosa registrada neste recorte.</p>
            </div>
        <?php else: ?>
            <ul class="space-y-3 p-4">
                <?php foreach ($ranking as $r): ?>
                    <li>
                        <div class="mb-1 flex items-baseline justify-between gap-3">
                            <span class="min-w-0 truncate text-xs text-slate-600 dark:text-slate-300"
                                  title="<?= e($r['rotulo']) ?> · <?= e(dinheiro_br($r['valor'])) ?> glosados">
                                <?= e($r['rotulo']) ?>
                            </span>
                            <span class="num shrink-0 text-xs font-semibold text-rose-600 dark:text-rose-400">
                                <?= e(number_format($r['pct'], 2, ',', '.')) ?>%
                            </span>
                        </div>
                        <div class="h-1.5 w-full overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800"
                             role="img"
                             aria-label="<?= e($r['rotulo']) ?>: <?= e(number_format($r['pct'], 2, ',', '.')) ?>% de glosa">
                            <div class="h-full rounded-full bg-rose-500 dark:bg-rose-400"
                                 style="width: <?= $altura($r['pct'], $maiorPct) ?>%"></div>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</div>

<!-- O consolidado em números --------------------------------------------- -->
<div class="card mt-4 overflow-hidden">
    <div class="card-header">
        <h2 class="card-title">
            Por convênio<?= $porSetor ? ' e setor' : '' ?>
        </h2>
        <span class="text-[0.6875rem] text-slate-500 dark:text-slate-400">somado das contas agora</span>
    </div>

    <?php if ((float) $total['valor_acrescentado'] < 0): ?>
        <p class="border-b border-slate-200 px-4 pb-3 text-[0.6875rem] text-slate-500 dark:border-slate-800 dark:text-slate-400">
            A coluna de acréscimo fecha negativa porque o AuditorHosp aceita acréscimo
            com sinal negativo — é como o sistema antigo registra uma segunda retirada
            na conta. O valor final de cada conta já considera isso.
        </p>
    <?php endif; ?>

    <?php if (!$linhas): ?>
        <div class="vazio">
            <span class="vazio-icone"><?= Icon::svg('auditoria_hosp', 'w-5 h-5') ?></span>
            <p class="vazio-titulo">Nada nesta competência</p>
            <p class="vazio-texto">Escolha outra competência ou limpe o filtro.</p>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <?= $tabela($linhas, $total, $porSetor, $tipo, $filtro) ?>
        </div>
    <?php endif; ?>
</div>

<?php /* No PA a leitura por setor é a pergunta seguinte: o PA glosa mais que o
         ambulatório? Com os convênios abertos por setor a tabela de cima já
         responde, então esta não repete. */ ?>
<?php if ($setores && !$porSetor && count($setores) > 1): ?>
    <div class="card mt-4 overflow-hidden">
        <div class="card-header">
            <h2 class="card-title">Por setor</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="tabela">
                <thead>
                    <tr>
                        <th class="w-24">Setor</th>
                        <th class="w-20 text-right">Contas</th>
                        <th class="w-32 text-right">Inicial</th>
                        <th class="w-32 text-right">Final</th>
                        <th class="w-36 text-right">Glosado</th>
                        <th class="w-32 text-right">Aceito</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($setores as $s): ?>
                        <?php $ps = AuditoriaHospModel::percentuais($s); ?>
                        <tr>
                            <td class="celula-principal"><?= e((string) $s['setor']) ?></td>
                            <td class="text-right text-xs"><?= numero((int) $s['contas']) ?></td>
                            <td class="text-right text-xs"><?= e(dinheiro_br((float) $s['valor_inicial'], false)) ?></td>
                            <td class="text-right text-xs"><?= e(dinheiro_br((float) $s['valor_total'], false)) ?></td>
                            <td class="text-right text-xs">
                                <span class="font-semibold text-rose-600 dark:text-rose-400">
                                    <?= e(dinheiro_br((float) $s['valor_glosado'], false)) ?>
                                </span>
                                <?php if ($ps['glosado'] !== null): ?>
                                    <span class="block text-[0.625rem] text-slate-400">
                                        <?= e(number_format($ps['glosado'], 2, ',', '.')) ?>%
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="text-right text-xs text-emerald-600 dark:text-emerald-400">
                                <?= e(dinheiro_br((float) $s['valor_aceito'], false)) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>
