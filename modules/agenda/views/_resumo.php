<?php
/**
 * Legenda dos médicos + totais do mês. É também a legenda de cores do
 * calendário — por isso cada cor vem sempre com o nome escrito ao lado.
 *
 * Espera: $porMedico, $porExame, $ano, $mes
 */
declare(strict_types=1);

$totalCap = 0;
$totalOcu = 0;
foreach ($porExame as $x) {
    $totalCap += (int) $x['capacidade'];
    $totalOcu += (int) $x['ocupadas'];
}
$totalBlocos = 0;
$totalDias   = [];
foreach ($porMedico as $m) {
    $totalBlocos += (int) $m['blocos'];
}
?>

<?php if ($porMedico): ?>
    <div class="grid gap-4 lg:grid-cols-[1.6fr_1fr]">

        <div class="card imprimir-em-bloco">
            <div class="card-header">
                <h2 class="card-title">Quem atende em <?= e(nome_mes($mes)) ?></h2>
                <span class="text-xs text-slate-500 dark:text-slate-400">
                    <?= numero($totalBlocos) ?> bloco<?= $totalBlocos === 1 ? '' : 's' ?>
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="tabela">
                    <thead>
                        <tr>
                            <th>Médico</th>
                            <th class="w-20 text-right">Dias</th>
                            <th class="w-24 text-right">Blocos</th>
                            <th class="w-24 text-right">Vagas</th>
                            <th class="w-40">Ocupação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($porMedico as $m): ?>
                            <?php
                            $cap = (int) $m['capacidade'];
                            $ocu = (int) $m['ocupadas'];
                            $pct = $cap > 0 ? (int) round($ocu / $cap * 100) : 0;
                            ?>
                            <tr class="medico-<?= (int) $m['cor'] ?>">
                                <td>
                                    <span class="flex items-center gap-2">
                                        <span class="med-ponto" aria-hidden="true"></span>
                                        <span class="celula-principal"><?= e($m['nome']) ?></span>
                                    </span>
                                </td>
                                <td class="num celula-suave text-right"><?= (int) $m['dias'] ?></td>
                                <td class="num celula-suave text-right"><?= (int) $m['blocos'] ?></td>
                                <td class="num text-right font-semibold text-marine-900 dark:text-white"><?= numero($cap) ?></td>
                                <td>
                                    <span class="flex items-center gap-2">
                                        <span class="barra <?= $pct >= 100 ? 'barra-cheia' : '' ?> w-20">
                                            <span style="width: <?= min(100, $pct) ?>%"></span>
                                        </span>
                                        <span class="celula-suave text-xs"><?= numero($ocu) ?>/<?= numero($cap) ?></span>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card imprimir-em-bloco">
            <div class="card-header">
                <h2 class="card-title">Vagas por tipo de exame</h2>
            </div>

            <div class="overflow-x-auto">
                <table class="tabela">
                    <thead>
                        <tr>
                            <th>Exame</th>
                            <th class="w-24 text-right">Vagas</th>
                            <th class="w-24 text-right">Marcadas</th>
                            <th class="w-24 text-right">Livres</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($porExame as $x): ?>
                            <?php
                            $cap = (int) $x['capacidade'];
                            $ocu = (int) $x['ocupadas'];
                            ?>
                            <tr>
                                <td>
                                    <span class="celula-principal"><?= e($x['nome']) ?></span>
                                    <span class="mono celula-suave block text-[0.6875rem]"><?= e($x['codigo']) ?></span>
                                </td>
                                <td class="num text-right font-semibold text-marine-900 dark:text-white"><?= numero($cap) ?></td>
                                <td class="num celula-suave text-right"><?= numero($ocu) ?></td>
                                <td class="num text-right <?= $cap - $ocu <= 0 ? 'font-semibold text-rose-600 dark:text-rose-400' : 'celula-suave' ?>">
                                    <?= numero(max(0, $cap - $ocu)) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td>Total</td>
                            <td class="num text-right"><?= numero($totalCap) ?></td>
                            <td class="num celula-suave text-right"><?= numero($totalOcu) ?></td>
                            <td class="num celula-suave text-right"><?= numero(max(0, $totalCap - $totalOcu)) ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>
