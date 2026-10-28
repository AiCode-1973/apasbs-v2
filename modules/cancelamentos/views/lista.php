<?php
/** @var array  $motivos */
/** @var string $busca */
declare(strict_types=1);

$podeCriar   = Auth::temPermissao('cancelamentos', 'pode_criar');
$podeEditar  = Auth::temPermissao('cancelamentos', 'pode_editar');
$podeExcluir = Auth::temPermissao('cancelamentos', 'pode_excluir');

$ativas = 0;
foreach ($motivos as $m) {
    if ($m['ativo']) { $ativas++; }
}
?>

<div class="mb-5 flex flex-wrap items-end justify-between gap-3">
    <div>
        <h1 class="titulo-pagina">Motivos de cancelamento</h1>
        <p class="sub-pagina">
            As mensagens que a atendente escolhe ao desmarcar um exame na agenda
        </p>
    </div>

    <?php if ($podeCriar): ?>
        <a href="<?= url('cancelamentos', 'novo') ?>" class="btn btn-primary">
            <?= Icon::svg('novo', 'w-4 h-4') ?> Nova mensagem
        </a>
    <?php endif; ?>
</div>

<div class="card overflow-hidden">
    <form method="get" action="<?= BASE_URL ?>/" class="toolbar">
        <input type="hidden" name="mod" value="cancelamentos">
        <input type="hidden" name="action" value="lista">

        <div class="campo-icone w-full sm:w-80">
            <?= Icon::svg('buscar') ?>
            <input type="search" name="q" value="<?= e($busca) ?>"
                   class="input" placeholder="Buscar mensagem…" aria-label="Buscar mensagens">
        </div>

        <button type="submit" class="btn btn-outline">Buscar</button>

        <?php if ($busca !== ''): ?>
            <a href="<?= url('cancelamentos', 'lista') ?>" class="btn btn-ghost">Limpar</a>
        <?php endif; ?>

        <span class="ml-auto text-xs text-slate-500 dark:text-slate-400">
            <?= numero($ativas) ?> na lista da atendente
            <?php if (count($motivos) > $ativas): ?>
                · <?= numero(count($motivos) - $ativas) ?> inativa<?= count($motivos) - $ativas === 1 ? '' : 's' ?>
            <?php endif; ?>
        </span>
    </form>

    <?php if (empty($motivos)): ?>
        <div class="vazio">
            <span class="vazio-icone"><?= Icon::svg('cancelamentos', 'w-5 h-5') ?></span>
            <p class="vazio-titulo">
                <?= $busca !== '' ? 'Nenhuma mensagem encontrada' : 'Nenhuma mensagem cadastrada' ?>
            </p>
            <p class="vazio-texto">
                <?= $busca !== ''
                    ? 'Nenhum resultado para “' . e($busca) . '”. Tente outro termo.'
                    : 'Cadastre as razões mais comuns — paciente desistiu, remarcou, não conseguiu fazer o preparo. A atendente escolhe da lista em vez de digitar tudo de novo.' ?>
            </p>
            <?php if ($podeCriar && $busca === ''): ?>
                <a href="<?= url('cancelamentos', 'novo') ?>" class="btn btn-primary btn-sm mt-1">
                    <?= Icon::svg('novo', 'w-3.5 h-3.5') ?> Nova mensagem
                </a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="tabela">
                <thead>
                    <tr>
                        <th class="w-20 text-right">Ordem</th>
                        <th>Mensagem</th>
                        <th class="w-40">Já usada</th>
                        <th class="w-28">Situação</th>
                        <th class="w-24 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($motivos as $m): ?>
                        <?php $usos = (int) $m['qtd_usos']; ?>
                        <tr class="<?= $m['ativo'] ? '' : 'opacity-60' ?>">
                            <td class="num celula-suave text-right text-xs"><?= (int) $m['ordem'] ?></td>

                            <td><span class="celula-principal"><?= e($m['nome']) ?></span></td>

                            <td class="celula-suave text-xs">
                                <?= $usos > 0
                                    ? numero($usos) . ' cancelamento' . ($usos === 1 ? '' : 's')
                                    : 'Nunca usada' ?>
                            </td>

                            <td>
                                <span class="badge <?= $m['ativo'] ? 'badge-verde' : 'badge-cinza' ?>">
                                    <?= $m['ativo'] ? 'Na lista' : 'Fora da lista' ?>
                                </span>
                            </td>

                            <td>
                                <div class="flex items-center justify-end gap-0.5">
                                    <?php if ($podeEditar): ?>
                                        <a href="<?= url('cancelamentos', 'editar', ['id' => (int) $m['id']]) ?>"
                                           class="btn-acao" title="Editar"
                                           aria-label="Editar mensagem “<?= e($m['nome']) ?>”">
                                            <?= Icon::svg('editar', 'w-4 h-4') ?>
                                        </a>
                                    <?php endif; ?>

                                    <?php if ($podeExcluir && $usos > 0): ?>
                                        <span class="btn-acao-off"
                                              title="Não pode excluir: já usada em <?= $usos ?> cancelamento<?= $usos === 1 ? '' : 's' ?>. Marque como inativa.">
                                            <?= Icon::svg('excluir', 'w-4 h-4') ?>
                                        </span>
                                    <?php elseif ($podeExcluir): ?>
                                        <form method="post" action="<?= url('cancelamentos', 'excluir') ?>" class="inline"
                                              data-titulo="Excluir mensagem"
                                              data-texto="“<?= e($m['nome']) ?>” será removida da lista permanentemente.">
                                            <?= Csrf::campo() ?>
                                            <input type="hidden" name="id" value="<?= (int) $m['id'] ?>">
                                            <button type="button" @click="abrir" class="btn-acao btn-acao-perigo"
                                                    title="Excluir" aria-label="Excluir mensagem “<?= e($m['nome']) ?>”">
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

        <div class="toolbar-rodape">
            <span>
                Menor ordem aparece primeiro. Deixe espaço entre os números
                (10, 20, 30…) para encaixar mensagens novas no meio depois.
            </span>
        </div>
    <?php endif; ?>
</div>
