<?php
/** @var array  $motivos */
/** @var string $busca */
declare(strict_types=1);

$podeCriar   = Auth::temPermissao('motivos', 'pode_criar');
$podeEditar  = Auth::temPermissao('motivos', 'pode_editar');
$podeExcluir = Auth::temPermissao('motivos', 'pode_excluir');
?>

<div class="mb-5 flex flex-wrap items-end justify-between gap-3">
    <div>
        <h1 class="titulo-pagina">Motivos de fechamento</h1>
        <p class="sub-pagina">Razões usadas para encerrar um atendimento na Central de Agendamento</p>
    </div>

    <?php if ($podeCriar): ?>
        <a href="<?= url('motivos', 'novo') ?>" class="btn btn-primary">
            <?= Icon::svg('novo', 'w-4 h-4') ?> Novo motivo
        </a>
    <?php endif; ?>
</div>

<div class="card overflow-hidden">
    <form method="get" action="<?= BASE_URL ?>/" class="toolbar">
        <input type="hidden" name="mod" value="motivos">
        <input type="hidden" name="action" value="lista">

        <div class="campo-icone w-full sm:w-80">
            <?= Icon::svg('buscar') ?>
            <input type="search" name="q" value="<?= e($busca) ?>"
                   class="input" placeholder="Buscar por código, nome ou descrição…" aria-label="Buscar motivos">
        </div>

        <button type="submit" class="btn btn-outline">Buscar</button>

        <?php if ($busca !== ''): ?>
            <a href="<?= url('motivos', 'lista') ?>" class="btn btn-ghost">Limpar</a>
        <?php endif; ?>

        <span class="ml-auto text-xs text-slate-500 dark:text-slate-400">
            <?= count($motivos) ?> registro<?= count($motivos) === 1 ? '' : 's' ?>
        </span>
    </form>

    <?php if (empty($motivos)): ?>
        <div class="vazio">
            <span class="vazio-icone"><?= Icon::svg('motivos', 'w-5 h-5') ?></span>
            <p class="vazio-titulo">
                <?= $busca !== '' ? 'Nenhum motivo encontrado' : 'Nenhum motivo cadastrado' ?>
            </p>
            <p class="vazio-texto">
                <?= $busca !== ''
                    ? 'Nenhum resultado para “' . e($busca) . '”. Tente outro termo.'
                    : 'Cadastre os motivos que a central usa para encerrar um atendimento, como falta do paciente ou cancelamento.' ?>
            </p>
            <?php if ($podeCriar && $busca === ''): ?>
                <a href="<?= url('motivos', 'novo') ?>" class="btn btn-primary btn-sm mt-1">
                    <?= Icon::svg('novo', 'w-3.5 h-3.5') ?> Novo motivo
                </a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="tabela">
                <thead>
                    <tr>
                        <th class="w-36">Código</th>
                        <th class="w-72">Motivo</th>
                        <th>Descrição</th>
                        <th class="w-28">Situação</th>
                        <th class="w-24 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($motivos as $m): ?>
                        <tr>
                            <td>
                                <span class="mono rounded-md bg-slate-100 px-2 py-0.5 font-semibold text-marine-700 dark:bg-slate-800 dark:text-marine-300">
                                    <?= e($m['codigo']) ?>
                                </span>
                            </td>
                            <td class="celula-principal"><?= e($m['nome']) ?></td>
                            <td class="celula-suave max-w-lg">
                                <span class="block truncate" title="<?= e($m['descricao'] ?? '') ?>">
                                    <?= $m['descricao'] !== null && $m['descricao'] !== '' ? e($m['descricao']) : '—' ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-ponto <?= $m['ativo'] ? 'badge-verde' : 'badge-cinza' ?>">
                                    <?= $m['ativo'] ? 'Ativo' : 'Inativo' ?>
                                </span>
                            </td>
                            <td>
                                <div class="flex items-center justify-end gap-0.5">
                                    <?php if ($podeEditar): ?>
                                        <a href="<?= url('motivos', 'editar', ['id' => (int) $m['id']]) ?>"
                                           class="btn-acao" title="Editar" aria-label="Editar <?= e($m['nome']) ?>">
                                            <?= Icon::svg('editar', 'w-4 h-4') ?>
                                        </a>
                                    <?php endif; ?>

                                    <?php if ($podeExcluir): ?>
                                        <form method="post" action="<?= url('motivos', 'excluir') ?>" class="inline"
                                              data-titulo="Excluir motivo"
                                              data-texto="O motivo &quot;<?= e($m['nome']) ?>&quot; será removido permanentemente.">
                                            <?= Csrf::campo() ?>
                                            <input type="hidden" name="id" value="<?= (int) $m['id'] ?>">
                                            <button type="button" @click="abrir" class="btn-acao btn-acao-perigo"
                                                    title="Excluir" aria-label="Excluir <?= e($m['nome']) ?>">
                                                <?= Icon::svg('excluir', 'w-4 h-4') ?>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
