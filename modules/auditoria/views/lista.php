<?php
/** @var array $registros */
/** @var array $filtros */
/** @var array $acoes */
/** @var int   $pagina, $totalPags, $total, $porPagina */
/** @var bool  $indisponivel */
declare(strict_types=1);

$linkPagina = function (int $p) use ($filtros): string {
    $params = array_filter($filtros, fn($v) => $v !== '');
    $params['p'] = $p;
    return url('auditoria', 'lista', $params);
};

$badges = [
    'emerald' => 'badge-verde',
    'sky'     => 'badge-ceu',
    'rose'    => 'badge-rosa',
    'slate'   => 'badge-cinza',
    'violet'  => 'badge-violeta',
    'amber'   => 'badge-ambar',
];

$temFiltro = (bool) array_filter($filtros, fn($v) => $v !== '');
?>

<div class="mb-5 flex flex-wrap items-end justify-between gap-3">
    <div>
        <h1 class="titulo-pagina">Log de auditoria</h1>
        <p class="sub-pagina">Registro de tudo que foi criado, editado e excluído no sistema</p>
    </div>

    <?php if (Auth::isAdmin() && Auth::temPermissao('auditoria', 'pode_excluir') && !$indisponivel): ?>
        <form method="post" action="<?= url('auditoria', 'limpar') ?>"
              data-titulo="Limpar log antigo"
              data-texto="Todos os registros com mais de 180 dias serão removidos permanentemente.">
            <?= Csrf::campo() ?>
            <input type="hidden" name="dias" value="180">
            <button type="button" @click="abrir" class="btn btn-danger-outline">
                <?= Icon::svg('excluir', 'w-4 h-4') ?> Limpar registros antigos
            </button>
        </form>
    <?php endif; ?>
</div>

<?php if ($indisponivel): ?>
    <div class="card">
        <div class="vazio">
            <span class="vazio-icone"><?= Icon::svg('aviso', 'w-5 h-5') ?></span>
            <p class="vazio-titulo">Tabela de auditoria não encontrada</p>
            <p class="vazio-texto">
                Execute o script <code class="rounded bg-slate-100 px-1 py-0.5 text-[0.7rem] dark:bg-slate-800">sql/auditoria.sql</code>
                no banco para ativar o registro de auditoria. O restante do sistema funciona normalmente sem ele.
            </p>
        </div>
    </div>
<?php else: ?>

<div class="card overflow-hidden">
    <form method="get" action="<?= BASE_URL ?>/" class="toolbar">
        <input type="hidden" name="mod" value="auditoria">
        <input type="hidden" name="action" value="lista">

        <div class="campo-icone w-full sm:w-64">
            <?= Icon::svg('buscar') ?>
            <input type="search" name="q" value="<?= e($filtros['q']) ?>"
                   class="input" placeholder="Descrição ou usuário…" aria-label="Buscar no log">
        </div>

        <select name="modulo" class="select w-full sm:w-40" aria-label="Filtrar por módulo">
            <option value="">Todos os módulos</option>
            <?php foreach (Modules::todos() as $m): ?>
                <option value="<?= e($m['slug']) ?>" <?= $filtros['modulo'] === $m['slug'] ? 'selected' : '' ?>>
                    <?= e($m['nome']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select name="acao" class="select w-full sm:w-36" aria-label="Filtrar por ação">
            <option value="">Todas as ações</option>
            <?php foreach ($acoes as $a): ?>
                <option value="<?= e($a) ?>" <?= $filtros['acao'] === $a ? 'selected' : '' ?>>
                    <?= e(Audit::estilo((string) $a)[0]) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <div class="flex items-center gap-1.5">
            <input type="date" name="de" value="<?= e($filtros['de']) ?>" class="input w-auto" aria-label="Data inicial">
            <span class="text-xs text-slate-400">até</span>
            <input type="date" name="ate" value="<?= e($filtros['ate']) ?>" class="input w-auto" aria-label="Data final">
        </div>

        <button type="submit" class="btn btn-outline">Filtrar</button>

        <?php if ($temFiltro): ?>
            <a href="<?= url('auditoria', 'lista') ?>" class="btn btn-ghost">Limpar</a>
        <?php endif; ?>

        <span class="ml-auto text-xs text-slate-500 dark:text-slate-400">
            <?= number_format($total, 0, ',', '.') ?> registro<?= $total === 1 ? '' : 's' ?>
        </span>
    </form>

    <?php if (empty($registros)): ?>
        <div class="vazio">
            <span class="vazio-icone"><?= Icon::svg('auditoria', 'w-5 h-5') ?></span>
            <p class="vazio-titulo">Nenhum registro encontrado</p>
            <p class="vazio-texto">As ações feitas no sistema aparecerão aqui.</p>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="tabela">
                <thead>
                    <tr>
                        <th class="w-36">Quando</th>
                        <th class="w-44">Quem</th>
                        <th class="w-24">Ação</th>
                        <th class="w-28">Módulo</th>
                        <th>Descrição</th>
                        <th class="w-32">Origem</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($registros as $r): ?>
                        <?php [$rotulo, $cor] = Audit::estilo((string) $r['acao']); ?>
                        <tr>
                            <td class="celula-suave whitespace-nowrap text-xs"><?= datahora_br($r['created_at']) ?></td>
                            <td class="celula-principal"><?= e($r['usuario_nome']) ?></td>
                            <td><span class="badge <?= $badges[$cor] ?? 'badge-cinza' ?>"><?= e($rotulo) ?></span></td>
                            <td class="celula-suave text-xs"><?= e(Modules::get((string) $r['modulo'])['nome'] ?? $r['modulo']) ?></td>
                            <td class="celula-suave"><?= e($r['descricao']) ?></td>
                            <td class="mono celula-suave"><?= e($r['ip']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?= Paginacao::barra([
            'modulo'    => 'auditoria',
            'param'     => 'p',
            'pagina'    => $pagina,
            'total'     => $total,
            'porPagina' => $porPagina,
            'padrao'    => 50,
            'rotulo'    => ['registro', 'registros'],
            'base'      => array_filter($filtros, fn($v) => $v !== ''),
        ]) ?>
    <?php endif; ?>
</div>
<?php endif; ?>
