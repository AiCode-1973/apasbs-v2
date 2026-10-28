<?php
/**
 * Mapa de sala — a planilha da parede, viva.
 *
 * Duas grades, manhã e tarde, com as salas nas linhas e os dias da semana
 * nas colunas. Sala livre não é registro: é o buraco na grade, e é ele que
 * a central procura quando chega um médico novo.
 *
 * @var array  $salas
 * @var array  $dias
 * @var array  $grade
 * @var array  $conflitos
 * @var array  $emConflito
 * @var array  $resumo
 * @var int    $encontradas
 * @var string $busca
 * @var array  $profissionais
 */
declare(strict_types=1);

$podeCriar   = Auth::temPermissao('consultorios', 'pode_criar');
$podeEditar  = Auth::temPermissao('consultorios', 'pode_editar');
$longo       = dias_semana();
?>

<div class="mb-5 flex flex-wrap items-end justify-between gap-3">
    <div>
        <h1 class="titulo-pagina">Mapa de sala</h1>
        <p class="sub-pagina">Quem ocupa cada consultório, em cada dia e turno da semana</p>
    </div>

    <div class="flex flex-wrap items-center gap-2">
        <a href="<?= url('consultorios', 'imprimir') ?>" class="btn btn-outline">
            <?= Icon::svg('imprimir', 'w-4 h-4') ?> Imprimir
        </a>
        <a href="<?= url('consultorios', 'salas') ?>" class="btn btn-outline">
            <?= Icon::svg('setores', 'w-4 h-4') ?> Salas
        </a>
        <a href="<?= url('consultorios', 'profissionais') ?>" class="btn btn-outline">
            <?= Icon::svg('usuarios', 'w-4 h-4') ?> Profissionais
        </a>
        <a href="<?= url('consultorios', 'especialidades') ?>" class="btn btn-outline">
            <?= Icon::svg('especialidades', 'w-4 h-4') ?> Especialidades
        </a>
        <?php if ($podeCriar): ?>
            <a href="<?= url('consultorios', 'novo') ?>" class="btn btn-primary">
                <?= Icon::svg('novo', 'w-4 h-4') ?> Nova ocupação
            </a>
        <?php endif; ?>
    </div>
</div>

<?php if (empty($salas)): ?>

    <div class="card">
        <div class="vazio">
            <span class="vazio-icone"><?= Icon::svg('consultorios', 'w-5 h-5') ?></span>
            <p class="vazio-titulo">Nenhuma sala cadastrada</p>
            <p class="vazio-texto">
                O mapa começa pelas salas. Cadastre as salas do ambulatório e
                depois lance quem ocupa cada uma.
            </p>
            <a href="<?= url('consultorios', 'salas') ?>" class="btn btn-primary btn-sm mt-1">
                <?= Icon::svg('setores', 'w-3.5 h-3.5') ?> Cadastrar salas
            </a>
        </div>
    </div>

<?php else: ?>

    <?php if ($conflitos): ?>
        <div class="card mb-4 border-rose-300 nao-imprimir dark:border-rose-500/40">
            <div class="card-header">
                <h2 class="card-title flex items-center gap-2 text-rose-700 dark:text-rose-400">
                    <?= Icon::svg('aviso', 'w-4 h-4') ?>
                    <?= count($conflitos) ?> choque<?= count($conflitos) === 1 ? '' : 's' ?> de horário
                </h2>
                <span class="text-xs text-slate-500 dark:text-slate-400">
                    Duas ocupações no mesmo lugar e horário, ou a mesma pessoa em duas salas
                </span>
            </div>

            <div class="card-corpo">
                <ul class="space-y-2">
                    <?php foreach ($conflitos as $c): ?>
                        <?php $a = $c['a']; $b = $c['b']; ?>
                        <li class="flex flex-wrap items-baseline gap-x-2 gap-y-0.5 text-[0.8125rem] text-slate-700 dark:text-slate-300">
                            <span class="badge badge-rosa">
                                <?= $c['tipo'] === 'sala' ? 'Mesma sala' : 'Mesma pessoa' ?>
                            </span>

                            <?php /* O turno vai em cada lado: um choque pode ser
                                      justamente uma ocupação lançada na grade
                                      errada, com horário da manhã na tarde. */ ?>
                            <span>
                                <strong><?= e($longo[(int) $a['dia_semana']] ?? '') ?></strong>:
                                <?= e($a['profissional']) ?>
                                (<?= e($a['sala_nome']) ?>, <?= e(mb_strtolower(nome_turno($a['turno']))) ?>,
                                <?= e(hora_curta($a['hora_inicio'])) ?>–<?= e(hora_curta($a['hora_fim'])) ?>)
                                cruza com
                                <?= e($b['profissional']) ?>
                                (<?= e($b['sala_nome']) ?>, <?= e(mb_strtolower(nome_turno($b['turno']))) ?>,
                                <?= e(hora_curta($b['hora_inicio'])) ?>–<?= e(hora_curta($b['hora_fim'])) ?>)
                            </span>

                            <?php if ($podeEditar): ?>
                                <span class="ml-auto flex gap-1">
                                    <a href="<?= url('consultorios', 'editar', ['id' => (int) $a['id']]) ?>"
                                       class="btn btn-ghost btn-sm">Abrir a 1ª</a>
                                    <a href="<?= url('consultorios', 'editar', ['id' => (int) $b['id']]) ?>"
                                       class="btn btn-ghost btn-sm">Abrir a 2ª</a>
                                </span>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    <?php endif; ?>

    <div class="card mb-4 nao-imprimir">
        <form method="get" action="<?= BASE_URL ?>/" class="toolbar">
            <input type="hidden" name="mod" value="consultorios">
            <input type="hidden" name="action" value="mapa">

            <div class="campo-icone w-full sm:w-80">
                <?= Icon::svg('buscar') ?>
                <input type="search" name="q" value="<?= e($busca) ?>" class="input" list="profissionais-mapa"
                       placeholder="Ver só um profissional ou especialidade…"
                       aria-label="Filtrar o mapa por profissional ou especialidade">
            </div>

            <button type="submit" class="btn btn-outline">Filtrar</button>

            <?php if ($busca !== ''): ?>
                <a href="<?= url('consultorios', 'mapa') ?>" class="btn btn-ghost">Ver o mapa inteiro</a>
            <?php endif; ?>

            <span class="ml-auto text-xs text-slate-500 dark:text-slate-400">
                <?php if ($busca !== ''): ?>
                    <?= numero($encontradas) ?> ocupação<?= $encontradas === 1 ? '' : 'ões' ?> para “<?= e($busca) ?>”
                <?php else: ?>
                    <?= numero($resumo['ocupacoes']) ?> ocupações ·
                    <?= numero($resumo['ocupados']) ?> de <?= numero($resumo['espacos']) ?> espaços usados ·
                    <?= numero($resumo['livres']) ?> livre<?= $resumo['livres'] === 1 ? '' : 's' ?>
                <?php endif; ?>
            </span>
        </form>
    </div>

    <?php if ($busca !== '' && $encontradas === 0): ?>
        <div class="card">
            <div class="vazio">
                <span class="vazio-icone"><?= Icon::svg('buscar', 'w-5 h-5') ?></span>
                <p class="vazio-titulo">Nada encontrado para “<?= e($busca) ?>”</p>
                <p class="vazio-texto">
                    O filtro procura pelo nome do profissional e pela especialidade.
                    Tente só o primeiro nome.
                </p>
                <a href="<?= url('consultorios', 'mapa') ?>" class="btn btn-outline btn-sm mt-1">Ver o mapa inteiro</a>
            </div>
        </div>
    <?php else: ?>

        <?php foreach (turnos() as $chave => $nomeTurno): ?>
            <div class="card mb-4 overflow-hidden">
                <div class="card-header">
                    <h2 class="card-title flex items-center gap-2">
                        <span class="turno-ponto turno-ponto-<?= e($chave) ?>" aria-hidden="true"></span>
                        <?= e($nomeTurno) ?>
                    </h2>
                    <span class="text-xs text-slate-500 dark:text-slate-400">
                        <?php if ($podeEditar): ?>
                            Clique numa ocupação para editar<?= $podeCriar ? ' · use o + para lançar' : '' ?>
                        <?php else: ?>
                            <?= numero($resumo['por_turno'][$chave] ?? 0) ?> ocupações
                        <?php endif; ?>
                    </span>
                </div>

                <?php
                $turno = $chave;
                require __DIR__ . '/_grade.php';
                ?>
            </div>
        <?php endforeach; ?>

        <div class="card">
            <div class="card-corpo grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <p class="rotulo-num">Salas</p>
                    <p class="valor-num"><?= numero(count($salas)) ?></p>
                    <p class="ajuda"><?= numero(count($dias)) ?> dias × 2 turnos = <?= numero($resumo['espacos']) ?> espaços</p>
                </div>
                <div>
                    <p class="rotulo-num">Espaços ocupados</p>
                    <p class="valor-num"><?= numero($resumo['ocupados']) ?></p>
                    <p class="ajuda"><?= numero($resumo['livres']) ?> ainda livres</p>
                </div>
                <div>
                    <p class="rotulo-num">Profissionais</p>
                    <p class="valor-num"><?= numero($resumo['profissionais']) ?></p>
                    <p class="ajuda">contando as atividades fixas</p>
                </div>
                <div>
                    <p class="rotulo-num">Horas de consultório</p>
                    <p class="valor-num"><?= numero($resumo['horas']) ?></p>
                    <p class="ajuda">por semana, somando as salas</p>
                </div>
            </div>
        </div>

    <?php endif; ?>
<?php endif; ?>

<datalist id="profissionais-mapa">
    <?php foreach ($profissionais as $p): ?>
        <option value="<?= e($p) ?>"></option>
    <?php endforeach; ?>
</datalist>
