<?php
/**
 * A conferência: o que o robô trouxe, o que já está lançado, e o de/para
 * dos motivos antes de virar fechamento.
 *
 * @var array      $coleta
 * @var array      $motivos
 * @var array      $itensMotivo
 * @var array      $itensFila
 * @var array      $itensAtendente
 * @var array      $sugestoes
 * @var array|false $fechamentoAtual
 */
declare(strict_types=1);

$podeEditar  = Auth::temPermissao('coletas', 'pode_editar');
$competencia = competencia_br((int) $coleta['ano'], (int) $coleta['mes']);
$pendente    = $coleta['status'] === 'pendente';
$bate        = (int) $coleta['soma_motivos'] === (int) $coleta['total_atendimentos'];

$minutosBr = function (?int $m): string {
    if ($m === null) {
        return '—';
    }
    $h = intdiv($m, 60);
    $r = $m % 60;
    return $h > 0 ? $h . 'h ' . $r . 'min' : $r . 'min';
};
?>

<div class="mb-5 flex flex-wrap items-start justify-between gap-3">
    <div class="flex items-start gap-3">
        <a href="<?= url_lista('coletas') ?>" class="btn btn-icon btn-outline" aria-label="Voltar">
            <?= Icon::svg('voltar', 'w-4 h-4') ?>
        </a>
        <div>
            <p class="text-xs text-slate-500 dark:text-slate-400">
                Coleta #<?= (int) $coleta['id'] ?> · <?= e($coleta['origem']) ?> ·
                <?= e(datahora_br($coleta['coletado_em'])) ?>
            </p>
            <h1 class="titulo-pagina"><?= e($competencia) ?></h1>
            <p class="sub-pagina">
                <?= e(ColetaModel::STATUS[$coleta['status']] ?? $coleta['status']) ?>
                <?= $coleta['usuario_nome'] ? ' · por ' . e($coleta['usuario_nome']) : '' ?>
            </p>
        </div>
    </div>

    <?php if ($podeEditar && in_array($coleta['status'], ['pendente', 'erro'], true)): ?>
        <form method="post" action="<?= url('coletas', 'descartar') ?>"
              data-titulo="Descartar coleta"
              data-texto="A coleta de <?= e($competencia) ?> sai da fila. O fechamento não é tocado.">
            <?= Csrf::campo() ?>
            <input type="hidden" name="id" value="<?= (int) $coleta['id'] ?>">
            <button type="button" @click="abrir" class="btn btn-danger-outline">
                <?= Icon::svg('cancelar', 'w-4 h-4') ?> Descartar
            </button>
        </form>
    <?php endif; ?>
</div>

<?php if ($coleta['status'] === 'erro'): ?>
    <div class="card mb-4 border-rose-300 dark:border-rose-500/40">
        <div class="card-corpo flex items-start gap-3 text-[0.8125rem] text-rose-800 dark:text-rose-300">
            <?= Icon::svg('erro', 'w-5 h-5 shrink-0') ?>
            <span>
                <strong class="block">O robô não conseguiu buscar esta competência.</strong>
                <?= e((string) $coleta['mensagem']) ?>
            </span>
        </div>
    </div>
<?php elseif (!empty($coleta['mensagem'])): ?>
    <div class="card mb-4 border-amber-300 dark:border-amber-500/40">
        <div class="card-corpo flex items-start gap-3 text-[0.8125rem] text-amber-800 dark:text-amber-300">
            <?= Icon::svg('aviso', 'w-5 h-5 shrink-0') ?>
            <span><?= e((string) $coleta['mensagem']) ?></span>
        </div>
    </div>
<?php endif; ?>

<?php if ($coleta['status'] !== 'erro'): ?>

    <div class="card mb-4">
        <div class="card-corpo grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <p class="rotulo-num">Total de atendimentos</p>
                <p class="valor-num"><?= numero((int) $coleta['total_atendimentos']) ?></p>
                <p class="ajuda <?= $bate ? '' : 'text-amber-700 dark:text-amber-400' ?>">
                    soma dos motivos: <?= numero((int) $coleta['soma_motivos']) ?>
                    <?= $bate ? '· confere' : '· NÃO bate' ?>
                </p>
            </div>
            <div>
                <p class="rotulo-num">Tempo médio de atendimento</p>
                <p class="valor-num text-2xl"><?= e($minutosBr($coleta['tma_minutos'] !== null ? (int) $coleta['tma_minutos'] : null)) ?></p>
                <p class="ajuda">espera: <?= e($minutosBr($coleta['tme_minutos'] !== null ? (int) $coleta['tme_minutos'] : null)) ?></p>
            </div>
            <div>
                <p class="rotulo-num">Contatos</p>
                <p class="valor-num text-2xl"><?= $coleta['contatos_total'] !== null ? numero((int) $coleta['contatos_total']) : '—' ?></p>
                <p class="ajuda"><?= $coleta['contatos_novos'] !== null ? numero((int) $coleta['contatos_novos']) . ' novos no mês' : '' ?></p>
            </div>
            <div>
                <p class="rotulo-num">Demanda</p>
                <p class="valor-num text-2xl"><?= $coleta['demanda_receptiva'] !== null ? numero((int) $coleta['demanda_receptiva']) : '—' ?></p>
                <p class="ajuda">
                    receptiva · <?= $coleta['demanda_ativa'] !== null ? numero((int) $coleta['demanda_ativa']) : '—' ?> ativa
                    <?= $coleta['pendentes'] !== null ? ' · ' . numero((int) $coleta['pendentes']) . ' pendentes' : '' ?>
                </p>
            </div>
        </div>
    </div>

    <?php if ($fechamentoAtual): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h2 class="card-title">Já existe fechamento para <?= e($competencia) ?></h2>
                <span class="text-xs text-slate-500 dark:text-slate-400">
                    hoje: <?= numero((int) $fechamentoAtual['total_atendimentos']) ?> atendimentos
                    <?php if ((int) $fechamentoAtual['total_atendimentos'] !== (int) $coleta['total_atendimentos']): ?>
                        · a coleta traz <?= numero((int) $coleta['total_atendimentos']) ?>
                    <?php endif; ?>
                </span>
            </div>
            <div class="card-corpo text-[0.8125rem] text-slate-600 dark:text-slate-400">
                Confirmar <strong>substitui</strong> o total e as quantidades por motivo desta
                competência pelos números da coleta.
                <?php if (!empty($fechamentoAtual['motivos'])): ?>
                    O que está lançado hoje:
                    <?php
                    $partes = [];
                    foreach ($fechamentoAtual['motivos'] as $m) {
                        $partes[] = e($m['nome']) . ' ' . numero((int) $m['quantidade']);
                    }
                    echo implode(' · ', $partes);
                    ?>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= url('coletas', 'confirmar') ?>" x-data="envio" @submit="enviar">
        <?= Csrf::campo() ?>
        <input type="hidden" name="id" value="<?= (int) $coleta['id'] ?>">

        <div class="card mb-4 overflow-hidden">
            <div class="card-header">
                <h2 class="card-title">Motivos de fechamento</h2>
                <span class="text-xs text-slate-500 dark:text-slate-400">
                    <?= count($itensMotivo) ?> vindos do ReciclaLead · escolha em qual motivo do APASBS cada um entra
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="tabela">
                    <thead>
                        <tr>
                            <th>Rótulo no ReciclaLead</th>
                            <th class="w-28 text-right">Quantidade</th>
                            <th class="w-20 text-right">%</th>
                            <th class="w-80">Entra como</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($itensMotivo as $item): ?>
                            <?php
                            $itemId    = (int) $item['id'];
                            $sugestao  = $sugestoes[$itemId] ?? null;
                            // Só vem marcado o que já foi decidido por alguém antes (o de/para
                            // guardado). A semelhança de nome é apenas um aviso na tela: quem
                            // confere é que escolhe, senão "Agendamento não Realizado" entraria
                            // sozinho em "Atendimento realizado".
                            $escolhido = (int) ($item['motivo_id'] ?? 0);
                            ?>
                            <tr>
                                <td>
                                    <span class="celula-principal"><?= e($item['rotulo']) ?></span>
                                    <?php if ($sugestao && !$item['motivo_id']): ?>
                                        <span class="block text-[0.6875rem] text-slate-500 dark:text-slate-400">
                                            parecido com “<?= e($sugestao['nome']) ?>” (<?= (int) $sugestao['confianca'] ?>%) — confira
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td class="num text-right celula-principal"><?= numero((int) $item['quantidade']) ?></td>

                                <td class="num celula-suave text-right text-xs">
                                    <?= $item['percentual'] !== null ? number_format((float) $item['percentual'], 1, ',', '.') . '%' : '—' ?>
                                </td>

                                <td>
                                    <?php if ($pendente && $podeEditar): ?>
                                        <select name="motivo[<?= $itemId ?>]" class="select input-sm">
                                            <option value="novo" <?= $escolhido === 0 ? 'selected' : '' ?>>
                                                ➕ Criar o motivo “<?= e(mb_substr($item['rotulo'], 0, 40)) ?>”
                                            </option>
                                            <?php foreach ($motivos as $m): ?>
                                                <option value="<?= (int) $m['id'] ?>" <?= $escolhido === (int) $m['id'] ? 'selected' : '' ?>>
                                                    <?= e($m['nome']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                            <option value="ignorar">✕ Não trazer este</option>
                                        </select>
                                    <?php else: ?>
                                        <span class="text-[0.8125rem] text-slate-600 dark:text-slate-400">
                                            <?= $item['motivo_nome'] ? e($item['motivo_nome']) : '—' ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td class="font-semibold">Soma</td>
                            <td class="num text-right font-semibold"><?= numero((int) $coleta['soma_motivos']) ?></td>
                            <td colspan="2" class="text-xs <?= $bate ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-700 dark:text-amber-400' ?>">
                                <?= $bate
                                    ? 'igual ao total de atendimentos'
                                    : 'diferente do total (' . numero((int) $coleta['total_atendimentos']) . ')' ?>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <?php if ($pendente && $podeEditar): ?>
            <div class="form-acoes mb-4">
                <span class="mr-auto text-xs text-slate-500 dark:text-slate-400">
                    Confirmar grava o total e os motivos no fechamento de <?= e($competencia) ?>.
                    As filas e os atendentes ficam guardados aqui, para consulta.
                </span>
                <button type="submit" class="btn btn-primary" :disabled="enviando">
                    <span x-show="!enviando"><?= Icon::svg('check', 'w-4 h-4') ?></span>
                    <span x-show="enviando" x-cloak class="girando"></span>
                    Confirmar e gravar no fechamento
                </button>
            </div>
        <?php endif; ?>
    </form>

    <div class="grid gap-4 lg:grid-cols-2">
        <?php foreach ([['Por fila', $itensFila, 'Fila'], ['Por atendente', $itensAtendente, 'Atendente']] as [$titulo, $lista, $coluna]): ?>
            <div class="card overflow-hidden">
                <div class="card-header">
                    <h2 class="card-title"><?= e($titulo) ?></h2>
                    <span class="text-xs text-slate-500 dark:text-slate-400">só para consulta</span>
                </div>

                <?php if (empty($lista)): ?>
                    <div class="card-corpo text-xs text-slate-500 dark:text-slate-400">
                        O ReciclaLead não devolveu esse recorte para a competência.
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="tabela">
                            <thead>
                                <tr>
                                    <th><?= e($coluna) ?></th>
                                    <th class="w-28 text-right">Quantidade</th>
                                    <th class="w-20 text-right">%</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($lista as $item): ?>
                                    <tr>
                                        <td class="celula-principal"><?= e($item['rotulo']) ?></td>
                                        <td class="num text-right"><?= numero((int) $item['quantidade']) ?></td>
                                        <td class="num celula-suave text-right text-xs">
                                            <?= $item['percentual'] !== null
                                                ? number_format((float) $item['percentual'], 1, ',', '.') . '%' : '—' ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

<?php endif; ?>
