<?php
/**
 * Folha da agenda no formato calendário: o mês inteiro numa página, para
 * pendurar na parede da central.
 *
 * Sai em paisagem — sete colunas de dia não cabem em retrato sem virar
 * uma coluna de palavras cortadas. É uma <table>, não grid: tabela quebra
 * de página de um jeito previsível, grid não.
 *
 * Espera: $ano, $mes, $porDia, $bloqueios, $porMedico, $porExame,
 *         $diasNoMes, $primeiroDiaW, $blocos, $totalVagas
 */
declare(strict_types=1);

$hoje         = date('Y-m-d');
$totalCelulas = (int) (ceil(($primeiroDiaW + $diasNoMes) / 7) * 7);
$semanas      = (int) ($totalCelulas / 7);
?>

<?php if (empty($blocos) && empty($bloqueios)): ?>
    <p class="text-sm text-slate-600">Nenhum bloco lançado nesta competência.</p>
<?php else: ?>

    <!-- Legenda: é aqui que a cor ganha nome. Numa impressora preto e branco
         os pontos somem, e o calendário continua legível porque o nome do
         médico está escrito dentro de cada bloco. -->
    <?php if ($porMedico): ?>
        <div class="mb-3 flex flex-wrap items-center gap-x-4 gap-y-1.5 break-inside-avoid">
            <?php foreach ($porMedico as $m): ?>
                <span class="medico-<?= (int) $m['cor'] ?> flex items-center gap-1.5">
                    <span class="med-ponto folha-tinta" aria-hidden="true"></span>
                    <span class="text-[0.75rem] font-semibold text-marine-900"><?= e($m['nome']) ?></span>
                    <span class="text-[0.6875rem] text-slate-500">
                        <?= (int) $m['dias'] ?> dia<?= (int) $m['dias'] === 1 ? '' : 's' ?>
                        · <?= numero((int) $m['capacidade']) ?> vagas
                    </span>
                </span>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <table class="folha-cal">
        <thead>
            <tr>
                <?php foreach (dias_semana_curto() as $abrev): ?>
                    <th><?= e($abrev) ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php for ($semana = 0; $semana < $semanas; $semana++): ?>
                <tr>
                    <?php for ($coluna = 0; $coluna < 7; $coluna++): ?>
                        <?php
                        $celula = $semana * 7 + $coluna;
                        $dia    = $celula - $primeiroDiaW + 1;

                        if ($dia < 1 || $dia > $diasNoMes) {
                            echo '<td class="folha-cal-fora"></td>';
                            continue;
                        }

                        $data      = sprintf('%04d-%02d-%02d', $ano, $mes, $dia);
                        $blocosDia = $porDia[$data] ?? [];
                        $bloqueio  = $bloqueios[$data] ?? null;
                        ?>
                        <td class="<?= $bloqueio ? 'folha-cal-bloqueado' : '' ?>">
                            <span class="folha-cal-dia"><?= str_pad((string) $dia, 2, '0', STR_PAD_LEFT) ?></span>

                            <?php if ($bloqueio): ?>
                                <span class="folha-cal-aviso"><?= e($bloqueio['motivo']) ?></span>
                            <?php endif; ?>

                            <?php foreach ($blocosDia as $b): ?>
                                <?php
                                $partes = [];
                                foreach ($b['vagas'] as $v) {
                                    $partes[] = $v['capacidade'] . ' ' . $v['codigo'];
                                }
                                $cap = (int) $b['capacidade'];
                                $ocu = (int) $b['ocupadas'];
                                ?>
                                <span class="folha-cal-bloco medico-<?= (int) $b['medico_cor'] ?>">
                                    <span class="folha-cal-medico"><?= e($b['medico_nome']) ?></span>
                                    <span class="folha-cal-vagas">
                                        <?= e(mb_strtolower(nome_turno($b['turno']))) ?> ·
                                        <?= e($partes ? implode(' · ', $partes) : 'sem vagas') ?>
                                        <?php if ($ocu > 0): ?>
                                            <span class="folha-cal-ocupacao">— <?= $ocu ?>/<?= $cap ?> marcadas</span>
                                        <?php endif; ?>
                                    </span>
                                    <?php if (!empty($b['techsallus'])): ?>
                                        <span class="folha-cal-tech">&#10003; TechSallus</span>
                                    <?php endif; ?>
                                </span>
                            <?php endforeach; ?>
                        </td>
                    <?php endfor; ?>
                </tr>
            <?php endfor; ?>
        </tbody>
    </table>

    <section class="mt-4 break-inside-avoid">
        <p class="text-[0.8125rem] text-slate-700">
            <?php
            $resumo = [];
            foreach ($porExame as $x) {
                $resumo[] = numero((int) $x['capacidade']) . ' ' . $x['nome'];
            }
            echo e(implode(' · ', $resumo));
            ?>
            <span class="font-semibold"> = <?= numero($totalVagas) ?> vagas</span>
            em <?= numero(count($blocos)) ?> bloco<?= count($blocos) === 1 ? '' : 's' ?>,
            <?= numero(count($porDia)) ?> dia<?= count($porDia) === 1 ? '' : 's' ?> de atendimento.
            <?php if ($bloqueios): ?>
                <?php
                $naoAbrir = [];
                foreach ($bloqueios as $data => $b) {
                    $naoAbrir[] = date('d/m', (int) strtotime($data));
                }
                ?>
                Não abrir: <span class="font-semibold"><?= e(implode(', ', $naoAbrir)) ?></span>.
            <?php endif; ?>
        </p>
    </section>
<?php endif; ?>
