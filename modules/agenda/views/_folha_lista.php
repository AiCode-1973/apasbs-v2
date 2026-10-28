<?php
/**
 * Folha da agenda no formato lista: agrupada por médico, depois dia a dia.
 * É o formato que mais se parece com a escala que a central recebe por
 * escrito, e por isso o melhor para conferir.
 *
 * Espera: $ano, $mes, $blocos, $porDia, $bloqueios, $porExame, $totalVagas
 */
declare(strict_types=1);
?>

<?php if (empty($blocos)): ?>
        <p class="text-sm text-slate-600">Nenhum bloco lançado nesta competência.</p>
    <?php else: ?>

        <?php
        // Agrupado por médico: é assim que a escala chega e é assim que a
        // central confere. Cada médico vira um parágrafo curto.
        $porMedicoBlocos = [];
        foreach ($blocos as $b) {
            $porMedicoBlocos[$b['medico_id']]['medico'] = $b;
            $porMedicoBlocos[$b['medico_id']]['blocos'][] = $b;
        }
        ?>

        <section class="mb-6">
            <h2 class="mb-2 text-[0.8125rem] font-semibold uppercase tracking-wide text-slate-500">Por médico</h2>

            <ul class="space-y-3">
                <?php foreach ($porMedicoBlocos as $grupo): ?>
                    <?php
                    $medico = $grupo['medico'];
                    $lista  = $grupo['blocos'];

                    // Dias, na ordem, só o número — "01, 15, 22 e 29"
                    $dias = array_map(fn($b) => (int) date('j', (int) strtotime($b['data'])), $lista);
                    sort($dias);
                    $dias = array_values(array_unique($dias));
                    $diasTexto = implode(', ', array_map(fn($d) => str_pad((string) $d, 2, '0', STR_PAD_LEFT), $dias));

                    // Turnos e dias da semana usados
                    $semana = array_unique(array_map(fn($b) => dia_semana_br($b['data']), $lista));
                    $turnosUsados = array_unique(array_map(fn($b) => nome_turno($b['turno']), $lista));

                    // Capacidade por exame, somando os blocos do médico
                    $porExameMedico = [];
                    foreach ($lista as $b) {
                        foreach ($b['vagas'] as $v) {
                            $porExameMedico[$v['codigo']] = ($porExameMedico[$v['codigo']] ?? 0) + $v['capacidade'];
                        }
                    }
                    // Capacidade por dia (a escala fala em "até N por dia")
                    $porDiaMedico = [];
                    foreach ($lista as $b) {
                        foreach ($b['vagas'] as $v) {
                            $porDiaMedico[$v['codigo']] = max($porDiaMedico[$v['codigo']] ?? 0, $v['capacidade']);
                        }
                    }
                    $regra = [];
                    foreach ($porDiaMedico as $codigo => $qtd) {
                        $regra[] = $qtd . ' ' . $codigo;
                    }
                    ?>

                    <li class="medico-<?= (int) $medico['medico_cor'] ?> flex gap-2.5 break-inside-avoid">
                        <span class="mt-1.5 med-ponto print:border print:border-black" aria-hidden="true"></span>
                        <div class="min-w-0">
                            <p class="text-[0.875rem] font-semibold text-marine-900">
                                <?= e($medico['medico_nome']) ?>
                            </p>
                            <p class="text-[0.8125rem] text-slate-700">
                                <?= e(implode(' e ', $semana)) ?>
                                <?= e(count($turnosUsados) === 1 ? mb_strtolower(reset($turnosUsados)) : implode('/', $turnosUsados)) ?>:
                                dias <?= e($diasTexto) ?>
                            </p>
                            <?php if ($regra): ?>
                                <p class="text-[0.8125rem] text-slate-600">
                                    Agendar até <?= e(implode(' e ', $regra)) ?> por dia ·
                                    total do mês:
                                    <?php
                                    $totais = [];
                                    foreach ($porExameMedico as $codigo => $qtd) {
                                        $totais[] = $qtd . ' ' . $codigo;
                                    }
                                    echo e(implode(' + ', $totais));
                                    ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>

        <?php if ($bloqueios): ?>
            <section class="mb-6 break-inside-avoid">
                <h2 class="mb-2 text-[0.8125rem] font-semibold uppercase tracking-wide text-slate-500">Não abrir</h2>
                <ul class="space-y-0.5">
                    <?php foreach ($bloqueios as $data => $b): ?>
                        <li class="text-[0.8125rem] text-slate-700">
                            <span class="font-semibold"><?= e(data_br($data)) ?></span>
                            (<?= e(dia_semana_br($data)) ?>) — <?= e($b['motivo']) ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endif; ?>

        <section class="imprimir-em-bloco">
            <h2 class="mb-2 text-[0.8125rem] font-semibold uppercase tracking-wide text-slate-500">Dia a dia</h2>

            <table class="tabela">
                <thead>
                    <tr>
                        <th class="w-36">Dia</th>
                        <th class="w-24">Turno</th>
                        <th>Médico</th>
                        <th class="w-64">Vagas</th>
                        <th>Observação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($blocos as $b): ?>
                        <?php
                        $partes = [];
                        foreach ($b['vagas'] as $v) {
                            $partes[] = $v['capacidade'] . ' ' . $v['codigo'];
                        }
                        ?>
                        <tr>
                            <td>
                                <span class="celula-principal block"><?= e(data_br($b['data'])) ?></span>
                                <span class="celula-suave text-xs"><?= e(dia_semana_br($b['data'])) ?></span>
                            </td>
                            <td class="celula-suave"><?= e(nome_turno($b['turno'])) ?></td>
                            <td class="medico-<?= (int) $b['medico_cor'] ?>">
                                <span class="flex items-center gap-2">
                                    <span class="med-ponto print:border print:border-black" aria-hidden="true"></span>
                                    <span class="celula-principal"><?= e($b['medico_nome']) ?></span>
                                </span>
                            </td>
                            <td class="celula-suave"><?= e($partes ? implode(' · ', $partes) : '—') ?></td>
                            <td class="celula-suave"><?= e($b['observacao'] ?? '') ?: '—' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>

        <section class="mt-6 break-inside-avoid">
            <h2 class="mb-2 text-[0.8125rem] font-semibold uppercase tracking-wide text-slate-500">Total do mês</h2>
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
            </p>
        </section>
    <?php endif; ?>
