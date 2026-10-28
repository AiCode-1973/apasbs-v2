<?php
/**
 * Auditoria Hospitalar — o que a tela e a folha do consolidado compartilham.
 *
 * A tabela de números é a mesma nos dois lugares, e um consolidado impresso que
 * não bate com o da tela é pior do que não imprimir nada. Então ela vive aqui,
 * uma vez, e cada lado só decide se as linhas levam link.
 */
declare(strict_types=1);

/** "2026-04" → "04/2026". Competência vazia é o recorte inteiro. */
$mesBr = static function (?string $mes): string {
    if (!$mes) {
        return '—';
    }
    [$ano, $m] = array_pad(explode('-', $mes), 2, '01');

    return $m . '/' . $ano;
};

/** O nome da linha: o convênio e, quando a tabela abre por setor, o setor. */
$rotuloLinha = static function (array $l): string {
    $nome = (string) ($l['convenio'] ?? '');
    $nome = $nome !== '' ? $nome : 'Sem convênio';

    return ($l['setor'] ?? '') !== '' ? $nome . ' · ' . $l['setor'] : $nome;
};

/** Percentual com a base dita por extenso — ou a frase de quando não há base. */
$percTexto = static function (?float $p, string $base): string {
    return $p === null
        ? 'sem base para o percentual'
        : number_format($p, 2, ',', '.') . '% ' . $base;
};

/**
 * Altura (ou largura) de barra em porcentagem da régua.
 *
 * O mínimo de 2% é proposital: valor pequeno e valor nenhum precisam parecer
 * diferentes, senão a barra inexistente conta a mentira de que não houve nada.
 */
$altura = static function (float $valor, float $teto): string {
    if ($teto <= 0 || $valor <= 0) {
        return '0';
    }

    return (string) max(2, (int) round($valor / $teto * 100));
};

/**
 * A tabela do consolidado. $comLink liga o nome do convênio à lista de contas
 * daquele recorte — na folha impressa não há onde clicar.
 */
$tabela = static function (
    array $linhas,
    array $total,
    bool $porSetor,
    string $tipo,
    array $filtro,
    bool $comLink = true
) use ($rotuloLinha): string {
    $moeda = static fn ($v): string => e(dinheiro_br((float) $v, false));

    ob_start();
    ?>
    <table class="tabela">
        <thead>
            <tr>
                <th>Convênio<?= $porSetor ? ' e setor' : '' ?></th>
                <th class="w-16 text-right">Contas</th>
                <th class="w-28 text-right">Inicial</th>
                <th class="w-28 text-right">Retirado</th>
                <th class="w-28 text-right">Acrescido</th>
                <th class="w-28 text-right">Final</th>
                <th class="w-32 text-right">Glosado</th>
                <th class="w-28 text-right">Aceito</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($linhas as $l): ?>
                <?php
                $p    = AuditoriaHospModel::percentuais($l);
                $alvo = array_filter([
                    'tipo'        => $tipo,
                    'convenio'    => (int) $l['convenio_id'] ?: null,
                    'setor'       => ($l['setor'] ?? '') !== '' ? $l['setor'] : ($filtro['setor'] ?: null),
                    'status'      => $filtro['status'] !== '' ? $filtro['status'] : null,
                    'competencia' => $filtro['competencia'] !== '' ? $filtro['competencia'] : null,
                ]);
                ?>
                <tr>
                    <td>
                        <?php if ($comLink): ?>
                            <a href="<?= url('auditoria_hosp', 'lista', $alvo) ?>"
                               class="celula-principal hover:text-marine-700 hover:underline dark:hover:text-marine-300">
                                <?= e($rotuloLinha($l)) ?>
                            </a>
                        <?php else: ?>
                            <span class="celula-principal"><?= e($rotuloLinha($l)) ?></span>
                        <?php endif; ?>

                        <?php if ((int) $l['sem_final'] > 0): ?>
                            <span class="block text-[0.625rem] text-amber-600 dark:text-amber-400">
                                <?= numero((int) $l['sem_final']) ?> sem valor final
                            </span>
                        <?php endif; ?>
                    </td>

                    <td class="text-right text-xs"><?= numero((int) $l['contas']) ?></td>
                    <td class="text-right text-xs"><?= $moeda($l['valor_inicial']) ?></td>

                    <td class="text-right text-xs">
                        <?php if ((float) $l['valor_retirado'] > 0): ?>
                            <?= $moeda($l['valor_retirado']) ?>
                            <?php if ($p['retirado'] !== null): ?>
                                <span class="block text-[0.625rem] text-slate-400">
                                    <?= e(number_format($p['retirado'], 2, ',', '.')) ?>%
                                </span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="text-slate-400 dark:text-slate-600">—</span>
                        <?php endif; ?>
                    </td>

                    <?php /* O AuditorHosp aceita acréscimo negativo, e a base
                             real está cheia deles: é como o sistema antigo
                             registra uma segunda retirada. Mostrar o sinal é
                             mais honesto do que esconder a linha no travessão
                             e deixar o total negativo sem explicação. */ ?>
                    <td class="text-right text-xs">
                        <?php if ((float) $l['valor_acrescentado'] != 0): ?>
                            <span class="<?= (float) $l['valor_acrescentado'] < 0
                                ? 'text-amber-600 dark:text-amber-400' : '' ?>">
                                <?= $moeda($l['valor_acrescentado']) ?>
                            </span>
                        <?php else: ?>
                            <span class="text-slate-400 dark:text-slate-600">—</span>
                        <?php endif; ?>
                    </td>

                    <td class="text-right text-xs font-medium"><?= $moeda($l['valor_total']) ?></td>

                    <td class="text-right text-xs">
                        <?php if ((float) $l['valor_glosado'] > 0): ?>
                            <span class="font-semibold text-rose-600 dark:text-rose-400">
                                <?= $moeda($l['valor_glosado']) ?>
                            </span>
                            <?php if ($p['glosado'] !== null): ?>
                                <span class="block text-[0.625rem] text-slate-400">
                                    <?= e(number_format($p['glosado'], 2, ',', '.')) ?>% do final
                                </span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="text-slate-400 dark:text-slate-600">—</span>
                        <?php endif; ?>
                    </td>

                    <td class="text-right text-xs">
                        <?php if ((float) $l['valor_aceito'] > 0): ?>
                            <span class="text-emerald-600 dark:text-emerald-400">
                                <?= $moeda($l['valor_aceito']) ?>
                            </span>
                            <?php if ($p['aceito'] !== null): ?>
                                <span class="block text-[0.625rem] text-slate-400">
                                    <?= e(number_format($p['aceito'], 2, ',', '.')) ?>% da glosa
                                </span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="text-slate-400 dark:text-slate-600">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>

        <?php /* O rodapé soma as linhas que estão acima dele, não o banco de
                 novo: a tabela fecha com ela mesma. */ ?>
        <tfoot>
            <?php $pt = AuditoriaHospModel::percentuais($total); ?>
            <tr>
                <td>Total<?= count($linhas) > 1 ? ' — ' . numero(count($linhas)) . ' linhas' : '' ?></td>
                <td class="text-right"><?= numero((int) $total['contas']) ?></td>
                <td class="text-right"><?= $moeda($total['valor_inicial']) ?></td>
                <td class="text-right"><?= $moeda($total['valor_retirado']) ?></td>
                <td class="text-right <?= (float) $total['valor_acrescentado'] < 0
                    ? 'text-amber-600 dark:text-amber-400' : '' ?>">
                    <?= $moeda($total['valor_acrescentado']) ?>
                </td>
                <td class="text-right"><?= $moeda($total['valor_total']) ?></td>
                <td class="text-right text-rose-600 dark:text-rose-400">
                    <?= $moeda($total['valor_glosado']) ?>
                    <?php if ($pt['glosado'] !== null): ?>
                        <span class="block text-[0.625rem] font-normal text-slate-500 dark:text-slate-400">
                            <?= e(number_format($pt['glosado'], 2, ',', '.')) ?>% do final
                        </span>
                    <?php endif; ?>
                </td>
                <td class="text-right text-emerald-600 dark:text-emerald-400">
                    <?= $moeda($total['valor_aceito']) ?>
                </td>
            </tr>
        </tfoot>
    </table>
    <?php
    return (string) ob_get_clean();
};
