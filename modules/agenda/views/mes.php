<?php
/**
 * Calendário do mês. Cada bloco mostra o nome do médico escrito, a cor da
 * paleta categórica, o turno e as vagas por tipo de exame.
 *
 * @var int   $ano
 * @var int   $mes
 * @var array $porDia
 * @var array $bloqueios
 * @var array $porMedico
 * @var array $porExame
 * @var array $medicos
 * @var array $exames
 * @var int   $medicoFiltro
 * @var int   $exameFiltro
 * @var int   $diasNoMes
 * @var int   $primeiroDiaW
 * @var array $anos
 */
declare(strict_types=1);

$vista       = 'mes';
$podeCriar   = Auth::temPermissao('agenda', 'pode_criar');
$podeEditar  = Auth::temPermissao('agenda', 'pode_editar');
$podeExcluir = Auth::temPermissao('agenda', 'pode_excluir');
$hoje        = date('Y-m-d');

require __DIR__ . '/_cabecalho.php';

$totalCelulas = (int) (ceil(($primeiroDiaW + $diasNoMes) / 7) * 7);
?>

<div class="card mb-4 overflow-hidden">
    <div class="card-header nao-imprimir">
        <h2 class="card-title"><?= e(competencia_br($ano, $mes)) ?></h2>
        <span class="text-xs text-slate-500 dark:text-slate-400">
            Clique num bloco para marcar exames · use o + do dia para lançar um bloco novo
        </span>
    </div>

    <div class="overflow-x-auto p-2 sm:p-3">
        <div class="cal-grade min-w-[46rem]">
            <?php foreach (dias_semana_curto() as $abrev): ?>
                <div class="cal-cabecalho"><?= e($abrev) ?></div>
            <?php endforeach; ?>

            <?php for ($celula = 0; $celula < $totalCelulas; $celula++): ?>
                <?php
                $dia = $celula - $primeiroDiaW + 1;

                if ($dia < 1 || $dia > $diasNoMes) {
                    echo '<div class="cal-dia cal-dia-fora"></div>';
                    continue;
                }

                $data      = sprintf('%04d-%02d-%02d', $ano, $mes, $dia);
                $blocosDia = $porDia[$data] ?? [];
                $bloqueio  = $bloqueios[$data] ?? null;

                $classes = 'cal-dia';
                if ($bloqueio)      { $classes .= ' cal-dia-bloqueado'; }
                if ($data === $hoje){ $classes .= ' cal-dia-hoje'; }
                ?>

                <div class="<?= $classes ?>">
                    <div class="flex items-start justify-between gap-1">
                        <span class="cal-dia-num">
                            <?= $dia ?>
                            <?php if ($data === $hoje): ?>
                                <span class="sr-only">(hoje)</span>
                            <?php endif; ?>
                        </span>

                        <?php if ($podeCriar && !$bloqueio): ?>
                            <a href="<?= url('agenda', 'novo', ['data' => $data, 'ano' => $ano, 'mes' => $mes, 'vista' => 'mes']) ?>"
                               class="nao-imprimir rounded p-0.5 text-slate-300 transition-colors hover:bg-slate-100 hover:text-marine-600 dark:text-slate-600 dark:hover:bg-slate-800 dark:hover:text-marine-300"
                               title="Lançar bloco em <?= e(data_br($data)) ?>"
                               aria-label="Lançar bloco em <?= e(data_br($data)) ?>">
                                <?= Icon::svg('novo', 'w-3.5 h-3.5') ?>
                            </a>
                        <?php endif; ?>
                    </div>

                    <?php if ($bloqueio): ?>
                        <p class="rounded bg-white/80 px-1.5 py-1 text-[0.625rem] font-semibold leading-snug text-slate-600 dark:bg-slate-950/70 dark:text-slate-300">
                            <?= e($bloqueio['motivo']) ?>
                        </p>
                    <?php endif; ?>

                    <?php foreach ($blocosDia as $b): ?>
                        <?php
                        $resumoVagas = [];
                        foreach ($b['vagas'] as $v) {
                            $resumoVagas[] = $v['capacidade'] . ' ' . $v['codigo'];
                        }
                        $texto  = $resumoVagas ? implode(' · ', $resumoVagas) : 'sem vagas';
                        $cap    = (int) $b['capacidade'];
                        $ocu    = (int) $b['ocupadas'];
                        $lotado = $cap > 0 && $ocu >= $cap;
                        $rotulo = $b['medico_nome'] . ', ' . nome_turno($b['turno']) . ', ' . $texto
                                . ' — ' . $ocu . ' de ' . $cap . ' marcadas'
                                . (!empty($b['techsallus']) ? ' — agenda criada no TechSallus' : '');
                        ?>

                        <a href="<?= url('agenda', 'marcacoes', ['bloco' => (int) $b['id'], 'vista' => 'mes']) ?>"
                           class="cal-bloco medico-<?= (int) $b['medico_cor'] ?>"
                           title="<?= e($rotulo) ?>" aria-label="Marcações de <?= e($rotulo) ?>">

                            <span class="cal-bloco-medico"><?= e($b['medico_nome']) ?></span>
                            <span class="cal-bloco-vagas"><?= e(nome_turno($b['turno'])) ?></span>
                            <span class="cal-bloco-vagas font-medium"><?= e($texto) ?></span>
                            <span class="cal-bloco-vagas <?= $lotado ? 'font-semibold text-rose-600 dark:text-rose-400' : '' ?>">
                                <?= $ocu ?>/<?= $cap ?> marcadas<?= $lotado ? ' · lotado' : '' ?>
                            </span>
                            <?php if (!empty($b['techsallus'])): ?>
                                <span class="cal-bloco-tech">
                                    <?= Icon::svg('check', 'w-2.5 h-2.5') ?> TechSallus
                                </span>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endfor; ?>
        </div>
    </div>

    <?php if (empty($porDia) && empty($bloqueios)): ?>
        <div class="vazio border-t border-slate-200 dark:border-slate-800">
            <span class="vazio-icone"><?= Icon::svg('agenda', 'w-5 h-5') ?></span>
            <p class="vazio-titulo">Nada lançado em <?= e(competencia_br($ano, $mes)) ?></p>
            <p class="vazio-texto">
                Lance os blocos dia a dia, do jeito que a escala chegou: médico, turno
                e quantas vagas de cada exame cabem naquele período.
            </p>
            <?php if ($podeCriar): ?>
                <a href="<?= url('agenda', 'novo', ['ano' => $ano, 'mes' => $mes]) ?>" class="btn btn-primary btn-sm mt-1">
                    <?= Icon::svg('novo', 'w-3.5 h-3.5') ?> Novo bloco
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/_resumo.php'; ?>

<?php if ($podeEditar): ?>
    <div class="card mt-4 nao-imprimir">
        <div class="card-header">
            <h2 class="card-title">Dias bloqueados</h2>
            <span class="text-xs text-slate-500 dark:text-slate-400">
                Aparecem hachurados no calendário, com o motivo escrito
            </span>
        </div>

        <div class="card-corpo grid gap-4 lg:grid-cols-2">
            <form method="post" action="<?= url('agenda', 'bloquear') ?>"
                  class="flex flex-wrap items-end gap-2" x-data="envio" @submit="enviar">
                <?= Csrf::campo() ?>
                <input type="hidden" name="ano" value="<?= $ano ?>">
                <input type="hidden" name="mes" value="<?= $mes ?>">

                <div class="w-40">
                    <label class="label" for="data_bloqueio">Dia</label>
                    <input type="date" name="data" id="data_bloqueio" class="input" required
                           min="<?= sprintf('%04d-%02d-01', $ano, $mes) ?>"
                           max="<?= sprintf('%04d-%02d-%02d', $ano, $mes, $diasNoMes) ?>"
                           value="<?= sprintf('%04d-%02d-01', $ano, $mes) ?>">
                </div>

                <div class="min-w-[12rem] flex-1">
                    <label class="label" for="motivo_bloqueio">Motivo</label>
                    <input type="text" name="motivo" id="motivo_bloqueio" class="input" maxlength="150"
                           placeholder="Ex.: Feriado — Independência do Brasil">
                </div>

                <button type="submit" class="btn btn-outline" :disabled="enviando">
                    <?= Icon::svg('bloqueio', 'w-4 h-4') ?> Bloquear
                </button>
            </form>

            <div>
                <?php if (empty($bloqueios)): ?>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        Nenhum dia bloqueado em <?= e(nome_mes($mes)) ?>.
                    </p>
                <?php else: ?>
                    <ul class="divide-y divide-slate-200 dark:divide-slate-800">
                        <?php foreach ($bloqueios as $data => $b): ?>
                            <li class="flex items-center justify-between gap-3 py-1.5">
                                <span class="min-w-0">
                                    <span class="block text-[0.8125rem] font-medium text-slate-800 dark:text-slate-100">
                                        <?= e(data_br($data)) ?> ·
                                        <span class="font-normal text-slate-500 dark:text-slate-400"><?= e(dia_semana_br($data)) ?></span>
                                    </span>
                                    <span class="block truncate text-xs text-slate-500 dark:text-slate-400"><?= e($b['motivo']) ?></span>
                                </span>

                                <form method="post" action="<?= url('agenda', 'desbloquear') ?>">
                                    <?= Csrf::campo() ?>
                                    <input type="hidden" name="data" value="<?= e($data) ?>">
                                    <input type="hidden" name="ano" value="<?= $ano ?>">
                                    <input type="hidden" name="mes" value="<?= $mes ?>">
                                    <button type="submit" class="btn btn-ghost btn-sm">
                                        <?= Icon::svg('liberar', 'w-3.5 h-3.5') ?> Liberar
                                    </button>
                                </form>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php endif; ?>
