<?php
/**
 * Cabeçalho comum ao calendário e à lista: título, navegação de mês,
 * filtros e troca de visão.
 *
 * Espera: $ano, $mes, $vista, $medicos, $exames, $medicoFiltro, $exameFiltro,
 *         $anos, $blocos
 */
declare(strict_types=1);

$podeCriar = Auth::temPermissao('agenda', 'pode_criar');

$tsMes  = (int) strtotime(sprintf('%04d-%02d-01', $ano, $mes));
$ant    = ['ano' => (int) date('Y', strtotime('-1 month', $tsMes)), 'mes' => (int) date('n', strtotime('-1 month', $tsMes))];
$prox   = ['ano' => (int) date('Y', strtotime('+1 month', $tsMes)), 'mes' => (int) date('n', strtotime('+1 month', $tsMes))];
$filtro = array_filter(['medico' => $medicoFiltro, 'exame' => $exameFiltro]);

// Anos oferecidos no seletor: os que já têm agenda + os dois próximos
$anosOpcoes = array_unique(array_merge($anos, [$ano, (int) date('Y'), (int) date('Y') + 1]));
rsort($anosOpcoes);
?>

<div class="mb-5 flex flex-wrap items-end justify-between gap-3">
    <div>
        <h1 class="titulo-pagina">Agenda do Ambulatório</h1>
        <p class="sub-pagina">
            Escala de <?= e(competencia_br($ano, $mes)) ?> — cada médico com sua cor e seu nome escrito
        </p>
    </div>

    <div class="flex flex-wrap items-center gap-2 nao-imprimir">
        <?php /* Imprime no formato da visão em que a pessoa está; a folha
                 tem um botão para trocar de formato sem voltar aqui. */ ?>
        <a href="<?= url('agenda', 'imprimir', array_merge(
                    ['ano' => $ano, 'mes' => $mes, 'formato' => $vista === 'mes' ? 'calendario' : 'lista'],
                    $filtro)) ?>"
           class="btn btn-outline" target="_blank" rel="noopener">
            <?= Icon::svg('imprimir', 'w-4 h-4') ?> Imprimir
        </a>

        <?php if ($podeCriar): ?>
            <a href="<?= url('agenda', 'novo', ['ano' => $ano, 'mes' => $mes, 'vista' => $vista]) ?>"
               class="btn btn-primary">
                <?= Icon::svg('novo', 'w-4 h-4') ?> Novo bloco
            </a>
        <?php endif; ?>
    </div>
</div>

<div class="card mb-4 nao-imprimir">
    <form method="get" action="<?= BASE_URL ?>/" class="toolbar border-b-0">
        <input type="hidden" name="mod" value="agenda">
        <input type="hidden" name="action" value="<?= e($vista) ?>">

        <div class="flex items-center gap-1">
            <a href="<?= url('agenda', $vista, array_merge($ant, $filtro)) ?>"
               class="btn btn-icon btn-outline" aria-label="Mês anterior" title="Mês anterior">
                <?= Icon::svg('anterior', 'w-4 h-4') ?>
            </a>
            <a href="<?= url('agenda', $vista, array_merge($prox, $filtro)) ?>"
               class="btn btn-icon btn-outline" aria-label="Próximo mês" title="Próximo mês">
                <?= Icon::svg('proximo', 'w-4 h-4') ?>
            </a>
        </div>

        <label class="sr-only" for="mes">Mês</label>
        <select name="mes" id="mes" class="select w-36">
            <?php foreach (meses() as $numero => $nome): ?>
                <option value="<?= $numero ?>" <?= $mes === $numero ? 'selected' : '' ?>><?= e($nome) ?></option>
            <?php endforeach; ?>
        </select>

        <label class="sr-only" for="ano">Ano</label>
        <select name="ano" id="ano" class="select w-28">
            <?php foreach ($anosOpcoes as $a): ?>
                <option value="<?= $a ?>" <?= $ano === $a ? 'selected' : '' ?>><?= $a ?></option>
            <?php endforeach; ?>
        </select>

        <label class="sr-only" for="medico">Médico</label>
        <select name="medico" id="medico" class="select w-full sm:w-52">
            <option value="">Todos os médicos</option>
            <?php foreach ($medicos as $m): ?>
                <option value="<?= (int) $m['id'] ?>" <?= $medicoFiltro === (int) $m['id'] ? 'selected' : '' ?>>
                    <?= e($m['nome']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label class="sr-only" for="exame">Tipo de exame</label>
        <select name="exame" id="exame" class="select w-full sm:w-44">
            <option value="">Todos os exames</option>
            <?php foreach ($exames as $x): ?>
                <option value="<?= (int) $x['id'] ?>" <?= $exameFiltro === (int) $x['id'] ? 'selected' : '' ?>>
                    <?= e($x['nome']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button type="submit" class="btn btn-outline">Ver</button>

        <?php if ($filtro): ?>
            <a href="<?= url('agenda', $vista, ['ano' => $ano, 'mes' => $mes]) ?>" class="btn btn-ghost">Limpar filtro</a>
        <?php endif; ?>

        <div class="ml-auto flex items-center gap-1 rounded-lg border border-slate-300 p-0.5 dark:border-slate-700">
            <a href="<?= url('agenda', 'mes', array_merge(['ano' => $ano, 'mes' => $mes], $filtro)) ?>"
               class="btn btn-sm <?= $vista === 'mes' ? 'btn-primary' : 'btn-ghost' ?>">
                <?= Icon::svg('calendario', 'w-3.5 h-3.5') ?> Calendário
            </a>
            <a href="<?= url('agenda', 'lista', array_merge(['ano' => $ano, 'mes' => $mes], $filtro)) ?>"
               class="btn btn-sm <?= $vista === 'lista' ? 'btn-primary' : 'btn-ghost' ?>">
                <?= Icon::svg('motivos', 'w-3.5 h-3.5') ?> Lista
            </a>
        </div>
    </form>
</div>
