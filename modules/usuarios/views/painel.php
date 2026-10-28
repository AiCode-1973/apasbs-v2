<?php
/** @var array $modulos */
/** @var array $contadores */
/** @var int   $cirurgiasMes */
/** @var array $ultimas */
/** @var array $porEspec */
/** @var array $atividade */
declare(strict_types=1);

$n = fn(int $v): string => number_format($v, 0, ',', '.');

$cartoes = [
    'usuarios'       => ['Usuários ativos', 'usuarios',       null],
    'setores'        => ['Setores',         'setores',        null],
    'especialidades' => ['Especialidades',  'especialidades', null],
    'cirurgias'      => ['Cirurgias',       'cirurgias',      $cirurgiasMes],
];

$indicadores = array_filter(
    $cartoes,
    fn($chave) => isset($contadores[$chave]) && Auth::temPermissao($chave),
    ARRAY_FILTER_USE_KEY
);

// Escala do gráfico de barras: proporcional ao maior valor da série
$maiorEspec = $porEspec ? max(array_map(fn($r) => (int) $r['total'], $porEspec)) : 0;

$badgesAuditoria = [
    'emerald' => 'badge-verde', 'sky'    => 'badge-ceu',   'rose'  => 'badge-rosa',
    'slate'   => 'badge-cinza', 'violet' => 'badge-violeta', 'amber' => 'badge-ambar',
];
?>

<div class="mb-5">
    <h1 class="titulo-pagina">Olá, <?= e(explode(' ', trim((string) Auth::nome()))[0]) ?>.</h1>
    <p class="sub-pagina">
        <?= Auth::isAdmin()
            ? 'Você tem acesso irrestrito a todos os módulos.'
            : 'Estes são os módulos liberados para o seu usuário.' ?>
    </p>
</div>

<?php if ($indicadores): ?>
    <div class="card mb-4 grid divide-y divide-slate-200 sm:grid-cols-2 sm:divide-x sm:divide-y-0 lg:grid-cols-4 dark:divide-slate-800">
        <?php foreach ($indicadores as $chave => [$rotulo, $icone, $extra]): ?>
            <a href="<?= url($chave, Modules::get($chave)['action'] ?? 'lista') ?>"
               class="group flex items-center gap-3 px-4 py-3.5 transition-colors hover:bg-slate-50 dark:hover:bg-slate-800/40">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-500 transition-colors group-hover:bg-marine-600 group-hover:text-white dark:bg-slate-800 dark:text-slate-400">
                    <?= Icon::svg($icone, 'w-[18px] h-[18px]') ?>
                </span>
                <span class="min-w-0">
                    <span class="block text-xl font-semibold leading-none text-marine-900 dark:text-white">
                        <?= $n((int) $contadores[$chave]) ?>
                    </span>
                    <span class="mt-1 block truncate text-xs text-slate-500 dark:text-slate-400">
                        <?= e($rotulo) ?><?php if ($extra !== null && $extra > 0): ?>
                            · <span class="text-marine-600 dark:text-marine-300"><?= $n($extra) ?> este mês</span>
                        <?php endif; ?>
                    </span>
                </span>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="grid gap-4 lg:grid-cols-3">

    <?php if (Auth::temPermissao('cirurgias')): ?>
        <!-- Últimas cirurgias ------------------------------------------------ -->
        <section class="card overflow-hidden lg:col-span-2">
            <div class="card-header">
                <h2 class="card-title">Últimas cirurgias</h2>
                <a href="<?= url('cirurgias', 'lista') ?>" class="btn btn-sm btn-ghost">
                    Ver todas <?= Icon::svg('proximo', 'w-3.5 h-3.5') ?>
                </a>
            </div>

            <?php if (empty($ultimas)): ?>
                <div class="vazio py-10">
                    <span class="vazio-icone"><?= Icon::svg('cirurgias', 'w-5 h-5') ?></span>
                    <p class="vazio-texto">Nenhum procedimento registrado ainda.</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="tabela">
                        <thead>
                            <tr>
                                <th class="w-32">Protocolo</th>
                                <th>Procedimento</th>
                                <th class="w-44">Médico</th>
                                <th class="w-32">Registro</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ultimas as $c): ?>
                                <tr>
                                    <td>
                                        <a href="<?= url('cirurgias', 'detalhe', ['id' => (int) $c['id']]) ?>"
                                           class="mono font-semibold text-marine-700 hover:underline dark:text-marine-300">
                                            <?= e($c['protocolo']) ?>
                                        </a>
                                    </td>
                                    <td class="max-w-xs">
                                        <span class="celula-principal block truncate" title="<?= e($c['tuss_termo']) ?>">
                                            <?= e($c['tuss_termo']) ?>
                                        </span>
                                        <span class="celula-suave text-xs"><?= e($c['especialidade_nome']) ?></span>
                                    </td>
                                    <td class="celula-suave truncate"><?= e($c['medico']) ?></td>
                                    <td class="celula-suave whitespace-nowrap text-xs"><?= datahora_br($c['created_at']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <!-- Cirurgias por especialidade --------------------------------------
             Série única: um só matiz, barras finas com ponta arredondada,
             valor rotulado ao lado. Sem legenda — o título já nomeia a série. -->
        <section class="card self-start">
            <div class="card-header">
                <h2 class="card-title">Cirurgias por especialidade</h2>
            </div>

            <?php if (empty($porEspec)): ?>
                <div class="vazio py-10">
                    <span class="vazio-icone"><?= Icon::svg('especialidades', 'w-5 h-5') ?></span>
                    <p class="vazio-texto">Sem dados para exibir.</p>
                </div>
            <?php else: ?>
                <ul class="space-y-3 p-4">
                    <?php foreach ($porEspec as $r): ?>
                        <?php
                        $total = (int) $r['total'];
                        $pct   = $maiorEspec > 0 ? max(4, (int) round($total / $maiorEspec * 100)) : 0;
                        ?>
                        <li>
                            <div class="mb-1 flex items-baseline justify-between gap-3">
                                <span class="min-w-0 truncate text-xs text-slate-600 dark:text-slate-300" title="<?= e($r['nome']) ?>">
                                    <?= e($r['nome']) ?>
                                </span>
                                <span class="num shrink-0 text-xs font-semibold text-marine-900 dark:text-white"><?= $n($total) ?></span>
                            </div>
                            <div class="h-1.5 w-full overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800"
                                 role="img" aria-label="<?= e($r['nome']) ?>: <?= $n($total) ?> cirurgia<?= $total === 1 ? '' : 's' ?>">
                                <div class="h-full rounded-full bg-marine-600 dark:bg-marine-400" style="width: <?= $pct ?>%"></div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <?php if (Auth::temPermissao('auditoria')): ?>
        <!-- Atividade recente ------------------------------------------------ -->
        <section class="card overflow-hidden lg:col-span-2">
            <div class="card-header">
                <h2 class="card-title">Atividade recente</h2>
                <a href="<?= url('auditoria', 'lista') ?>" class="btn btn-sm btn-ghost">
                    Ver log completo <?= Icon::svg('proximo', 'w-3.5 h-3.5') ?>
                </a>
            </div>

            <?php if (empty($atividade)): ?>
                <div class="vazio py-10">
                    <span class="vazio-icone"><?= Icon::svg('auditoria', 'w-5 h-5') ?></span>
                    <p class="vazio-texto">Nenhuma atividade registrada ainda.</p>
                </div>
            <?php else: ?>
                <ul class="divide-y divide-slate-100 dark:divide-slate-800">
                    <?php foreach ($atividade as $a): ?>
                        <?php [$rotulo, $cor] = Audit::estilo((string) $a['acao']); ?>
                        <li class="flex flex-wrap items-center gap-2 px-4 py-2.5 text-[0.8125rem]">
                            <span class="badge <?= $badgesAuditoria[$cor] ?? 'badge-cinza' ?> shrink-0"><?= e($rotulo) ?></span>
                            <span class="min-w-0 flex-1 truncate text-slate-600 dark:text-slate-300"><?= e($a['descricao']) ?></span>
                            <span class="shrink-0 text-xs text-slate-400"><?= e($a['usuario_nome']) ?></span>
                            <span class="shrink-0 text-xs text-slate-400"><?= datahora_br($a['created_at']) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <!-- Acesso rápido -------------------------------------------------------- -->
    <section class="card overflow-hidden <?= empty($atividade) && !Auth::temPermissao('cirurgias') ? 'lg:col-span-3' : '' ?>">
        <div class="card-header">
            <h2 class="card-title">Módulos</h2>
        </div>

        <?php if (empty($modulos)): ?>
            <div class="vazio py-10">
                <span class="vazio-icone"><?= Icon::svg('aviso', 'w-5 h-5') ?></span>
                <p class="vazio-titulo">Nenhum módulo liberado</p>
                <p class="vazio-texto">Solicite a liberação ao administrador do sistema.</p>
            </div>
        <?php else: ?>
            <ul class="divide-y divide-slate-100 dark:divide-slate-800">
                <?php foreach ($modulos as $m): ?>
                    <li>
                        <a href="<?= url($m['slug'], $m['action']) ?>"
                           class="group flex items-center gap-3 px-4 py-2.5 transition-colors hover:bg-slate-50 dark:hover:bg-slate-800/40">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-marine-50 text-marine-600 transition-colors group-hover:bg-marine-600 group-hover:text-white dark:bg-marine-500/10 dark:text-marine-300">
                                <?= Icon::svg($m['icone'], 'w-4 h-4') ?>
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block text-[0.8125rem] font-medium text-slate-800 dark:text-slate-100"><?= e($m['nome']) ?></span>
                                <span class="block truncate text-xs text-slate-500 dark:text-slate-400"><?= e($m['desc']) ?></span>
                            </span>
                            <span class="shrink-0 text-slate-300 transition-colors group-hover:text-marine-600 dark:text-slate-600">
                                <?= Icon::svg('proximo', 'w-4 h-4') ?>
                            </span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</div>
