<?php
/**
 * Fila das coletas do ReciclaLead.
 *
 * @var array  $coletas
 * @var array  $contagem
 * @var string $status
 */
declare(strict_types=1);

$podeEditar = Auth::temPermissao('coletas', 'pode_editar');
$pendentes  = (int) ($contagem['pendente'] ?? 0);
$comErro    = (int) ($contagem['erro'] ?? 0);

$cores = [
    'pendente'   => 'badge-ambar',
    'confirmada' => 'badge-verde',
    'descartada' => 'badge-cinza',
    'erro'       => 'badge-rosa',
];
?>

<div class="mb-5 flex flex-wrap items-end justify-between gap-3">
    <div>
        <h1 class="titulo-pagina">Coletas</h1>
        <p class="sub-pagina">O que o robô do ReciclaLead trouxe, esperando conferência</p>
    </div>

    <a href="<?= url('fechamentos', 'lista') ?>" class="btn btn-outline">
        <?= Icon::svg('fechamentos', 'w-4 h-4') ?> Fechamentos
    </a>
</div>

<?php if ($pendentes > 0 || $comErro > 0): ?>
    <div class="card mb-4 <?= $comErro > 0 ? 'border-rose-300 dark:border-rose-500/40' : 'border-amber-300 dark:border-amber-500/40' ?>">
        <div class="card-corpo flex flex-wrap items-center gap-x-6 gap-y-2 text-[0.8125rem]">
            <?php if ($pendentes > 0): ?>
                <span class="flex items-center gap-2 text-amber-700 dark:text-amber-400">
                    <?= Icon::svg('aviso', 'w-4 h-4') ?>
                    <strong><?= $pendentes ?></strong>
                    coleta<?= $pendentes === 1 ? '' : 's' ?> esperando conferência
                </span>
            <?php endif; ?>

            <?php if ($comErro > 0): ?>
                <span class="flex items-center gap-2 text-rose-700 dark:text-rose-400">
                    <?= Icon::svg('erro', 'w-4 h-4') ?>
                    <strong><?= $comErro ?></strong>
                    coleta<?= $comErro === 1 ? '' : 's' ?> falhou — o robô não conseguiu buscar
                </span>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<div class="card overflow-hidden">
    <div class="toolbar">
        <a href="<?= url('coletas', 'lista') ?>"
           class="btn btn-sm <?= $status === '' ? 'btn-primary' : 'btn-ghost' ?>">Todas</a>

        <?php foreach (ColetaModel::STATUS as $chave => $rotulo): ?>
            <a href="<?= url('coletas', 'lista', ['status' => $chave]) ?>"
               class="btn btn-sm <?= $status === $chave ? 'btn-primary' : 'btn-ghost' ?>">
                <?= e($rotulo) ?>
                <span class="text-[0.6875rem] opacity-70">(<?= (int) ($contagem[$chave] ?? 0) ?>)</span>
            </a>
        <?php endforeach; ?>

        <span class="ml-auto text-xs text-slate-500 dark:text-slate-400">
            <?= count($coletas) ?> na tela
        </span>
    </div>

    <?php if (empty($coletas)): ?>
        <div class="vazio">
            <span class="vazio-icone"><?= Icon::svg('coletas', 'w-5 h-5') ?></span>
            <p class="vazio-titulo">Nenhuma coleta <?= $status !== '' ? mb_strtolower(ColetaModel::STATUS[$status]) : 'registrada' ?></p>
            <p class="vazio-texto">
                O robô roda pela tarefa agendada do servidor. Quando ele trouxer os
                números de uma competência, eles aparecem aqui para conferência.
            </p>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="tabela">
                <thead>
                    <tr>
                        <th class="w-32">Competência</th>
                        <th class="w-36 text-right">Atendimentos</th>
                        <th>Conferência</th>
                        <th class="w-52">Situação</th>
                        <th class="w-36">Coletado em</th>
                        <th class="w-24 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($coletas as $c): ?>
                        <?php
                        $bate = (int) $c['soma_motivos'] === (int) $c['total_atendimentos'];
                        $erro = $c['status'] === 'erro';
                        ?>
                        <tr>
                            <td>
                                <a href="<?= url('coletas', 'ver', ['id' => (int) $c['id']]) ?>"
                                   class="celula-principal text-marine-700 hover:underline dark:text-marine-300">
                                    <?= e(competencia_br((int) $c['ano'], (int) $c['mes'])) ?>
                                </a>
                            </td>

                            <td class="num text-right">
                                <?= $erro ? '—' : '<span class="celula-principal">' . numero((int) $c['total_atendimentos']) . '</span>' ?>
                            </td>

                            <td class="text-xs">
                                <?php if ($erro): ?>
                                    <span class="text-rose-600 dark:text-rose-400"><?= e((string) $c['mensagem']) ?></span>
                                <?php elseif (!$bate): ?>
                                    <span class="text-amber-700 dark:text-amber-400">
                                        soma dos motivos: <?= numero((int) $c['soma_motivos']) ?> — não bate
                                    </span>
                                <?php elseif (!empty($c['mensagem'])): ?>
                                    <span class="text-amber-700 dark:text-amber-400"><?= e((string) $c['mensagem']) ?></span>
                                <?php else: ?>
                                    <span class="text-emerald-600 dark:text-emerald-400">
                                        soma dos motivos confere
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <span class="badge whitespace-nowrap <?= $cores[$c['status']] ?? 'badge-cinza' ?>">
                                    <?= e(ColetaModel::STATUS[$c['status']] ?? $c['status']) ?>
                                </span>
                                <?php if ($c['usuario_nome']): ?>
                                    <span class="block text-[0.6875rem] text-slate-500 dark:text-slate-400">
                                        por <?= e($c['usuario_nome']) ?>
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td class="celula-suave text-xs"><?= e(datahora_br($c['coletado_em'])) ?></td>

                            <td>
                                <div class="flex items-center justify-end gap-0.5">
                                    <a href="<?= url('coletas', 'ver', ['id' => (int) $c['id']]) ?>"
                                       class="btn-acao" title="Conferir" aria-label="Conferir a coleta de <?= e(competencia_br((int) $c['ano'], (int) $c['mes'])) ?>">
                                        <?= Icon::svg('ver', 'w-4 h-4') ?>
                                    </a>

                                    <?php if ($podeEditar && in_array($c['status'], ['pendente', 'erro'], true)): ?>
                                        <form method="post" action="<?= url('coletas', 'descartar') ?>" class="inline"
                                              data-titulo="Descartar coleta"
                                              data-texto="A coleta de <?= e(competencia_br((int) $c['ano'], (int) $c['mes'])) ?> sai da fila. O fechamento não é tocado.">
                                            <?= Csrf::campo() ?>
                                            <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                                            <button type="button" @click="abrir" class="btn-acao btn-acao-perigo"
                                                    title="Descartar" aria-label="Descartar">
                                                <?= Icon::svg('cancelar', 'w-4 h-4') ?>
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
