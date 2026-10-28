<?php
/**
 * Folha do mapa de sala para imprimir e pendurar no corredor.
 *
 * Sai em paisagem: cinco (ou seis) colunas de dia com nome, especialidade
 * e horário não cabem em retrato sem virar coluna de palavra cortada.
 *
 * @var array  $salas
 * @var array  $dias
 * @var array  $grade
 * @var array  $resumo
 * @var array  $conflitos
 * @var string $turno
 */
declare(strict_types=1);

$tituloPagina = 'Mapa de sala';

// Lido pelo layout `limpo`
$paginaPaisagem = true;

$turnosFolha = $turno === 'todos' ? turnos() : [$turno => nome_turno($turno)];
?>

<div class="mb-4 flex flex-wrap items-center justify-end gap-2 nao-imprimir">
    <span class="mr-auto text-xs text-slate-500">
        A folha sai em paisagem. Cada turno começa em uma página.
    </span>

    <div class="flex items-center gap-1 rounded-lg border border-slate-300 p-0.5">
        <?php foreach (['todos' => 'Manhã e tarde'] + turnos() as $valor => $rotulo): ?>
            <a href="<?= url('consultorios', 'imprimir', ['turno' => $valor]) ?>"
               class="btn btn-sm <?= $turno === $valor ? 'btn-primary' : 'btn-ghost' ?>">
                <?= e($rotulo) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <button type="button" data-imprimir class="btn btn-primary">
        <?= Icon::svg('imprimir', 'w-4 h-4') ?> Imprimir
    </button>
</div>

<div class="card p-6 print:border-0 print:p-0 print:shadow-none">

    <header class="mb-4 flex items-start justify-between gap-4 border-b border-slate-200 pb-3">
        <div>
            <h1 class="text-lg font-semibold leading-tight text-marine-900">Mapa de sala</h1>
            <p class="mt-0.5 text-sm text-slate-600">
                Ambulatório · grade da semana<?= $turno !== 'todos' ? ' · ' . e(nome_turno($turno)) : '' ?>
            </p>
        </div>

        <img src="<?= BASE_URL ?>/image/logo_apas.png" alt="APAS" class="h-10 w-auto object-contain">
    </header>

    <?php foreach ($turnosFolha as $chave => $rotulo): ?>
        <section class="mb-5 <?= $turno === 'todos' && $chave === 'tarde' ? 'quebra-pagina' : '' ?>">
            <h2 class="mb-2 flex items-center gap-2 text-[0.9375rem] font-semibold text-marine-900">
                <span class="turno-ponto turno-ponto-<?= e($chave) ?>" aria-hidden="true"></span>
                <?= e($rotulo) ?>
            </h2>

            <?php
            $turnoFolha = $chave;
            require __DIR__ . '/_folha.php';
            ?>
        </section>
    <?php endforeach; ?>

    <section class="mt-4 break-inside-avoid text-[0.8125rem] text-slate-700">
        <p>
            <?= numero(count($salas)) ?> salas ·
            <?= numero($resumo['ocupacoes']) ?> ocupações ·
            <?= numero($resumo['ocupados']) ?> de <?= numero($resumo['espacos']) ?> espaços usados
            (<?= numero($resumo['livres']) ?> livres) ·
            <?= numero($resumo['horas']) ?> horas de consultório por semana.
            <?php if ($conflitos): ?>
                <span class="font-semibold">
                    Atenção: <?= count($conflitos) ?> choque<?= count($conflitos) === 1 ? '' : 's' ?> de horário
                    <?= count($conflitos) === 1 ? 'está marcado' : 'estão marcados' ?> no mapa.
                </span>
            <?php endif; ?>
        </p>
        <p class="mt-1 text-[0.75rem] text-slate-500">
            15/15 = atende a cada quinze dias. <span class="folha-mapa-livre" style="display:inline">Livre</span>
            = sala vaga naquele dia e turno.
        </p>
    </section>

    <footer class="mt-5 border-t border-slate-200 pt-3 text-[0.6875rem] text-slate-400">
        Emitido em <?= e(date('d/m/Y \à\s H:i')) ?> por <?= e((string) Auth::nome()) ?> · APASBS
    </footer>
</div>
