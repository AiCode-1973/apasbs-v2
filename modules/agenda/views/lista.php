<?php
/**
 * Mesma competência do calendário, em lista — é a visão boa para conferir
 * linha a linha contra a planilha que a central recebe.
 *
 * @var int   $ano
 * @var int   $mes
 * @var array $blocos
 * @var array $bloqueios
 * @var array $porMedico
 * @var array $porExame
 * @var array $medicos
 * @var array $exames
 * @var int   $medicoFiltro
 * @var int   $exameFiltro
 * @var int   $diasNoMes
 * @var array $anos
 */
declare(strict_types=1);

$vista       = 'lista';
$podeEditar  = Auth::temPermissao('agenda', 'pode_editar');
$podeExcluir = Auth::temPermissao('agenda', 'pode_excluir');
$podeCriar   = Auth::temPermissao('agenda', 'pode_criar');

require __DIR__ . '/_cabecalho.php';

$diaAnterior = null;
?>

<div class="card mb-4 overflow-hidden">
    <?php if (empty($blocos)): ?>
        <div class="vazio">
            <span class="vazio-icone"><?= Icon::svg('agenda', 'w-5 h-5') ?></span>
            <p class="vazio-titulo">Nenhum bloco em <?= e(competencia_br($ano, $mes)) ?></p>
            <p class="vazio-texto">
                <?= $medicoFiltro || $exameFiltro
                    ? 'Nada encontrado com esse filtro. Limpe o filtro para ver o mês inteiro.'
                    : 'Lance os blocos da escala para eles aparecerem aqui e no calendário.' ?>
            </p>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="tabela">
                <thead>
                    <tr>
                        <th class="w-44">Dia</th>
                        <th class="w-24">Turno</th>
                        <th>Médico</th>
                        <th class="w-72">Vagas por exame</th>
                        <th class="w-40">Ocupação</th>
                        <th>Observação</th>
                        <th class="w-24 text-right nao-imprimir">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($blocos as $b): ?>
                        <?php
                        $data      = $b['data'];
                        $novoDia   = $data !== $diaAnterior;
                        $diaAnterior = $data;
                        $bloqueio  = $bloqueios[$data] ?? null;
                        $cap       = (int) $b['capacidade'];
                        $ocu       = (int) $b['ocupadas'];
                        $pct       = $cap > 0 ? (int) round($ocu / $cap * 100) : 0;
                        ?>
                        <tr class="<?= $novoDia ? 'border-t-2 border-slate-200 dark:border-slate-800' : '' ?>">
                            <td>
                                <?php if ($novoDia): ?>
                                    <span class="celula-principal block"><?= e(data_br($data)) ?></span>
                                    <span class="celula-suave text-xs"><?= e(dia_semana_br($data)) ?></span>
                                    <?php if ($bloqueio): ?>
                                        <span class="badge badge-ambar mt-1" title="<?= e($bloqueio['motivo']) ?>">
                                            <?= Icon::svg('bloqueio', 'w-3 h-3') ?> Dia bloqueado
                                        </span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="celula-suave text-xs">idem</span>
                                <?php endif; ?>
                            </td>

                            <td class="celula-suave"><?= e(nome_turno($b['turno'])) ?></td>

                            <td>
                                <span class="flex items-center gap-2 medico-<?= (int) $b['medico_cor'] ?>">
                                    <span class="med-ponto" aria-hidden="true"></span>
                                    <span class="celula-principal"><?= e($b['medico_nome']) ?></span>
                                </span>
                                <?php if (!$b['medico_ativo']): ?>
                                    <span class="badge badge-cinza mt-1">Médico inativo</span>
                                <?php endif; ?>
                                <?php if (!empty($b['techsallus'])): ?>
                                    <span class="badge badge-verde mt-1" title="Agenda criada no TechSallus">
                                        <?= Icon::svg('check', 'w-3 h-3') ?> TechSallus
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php if (empty($b['vagas'])): ?>
                                    <span class="celula-suave text-xs">—</span>
                                <?php else: ?>
                                    <span class="flex flex-wrap gap-1">
                                        <?php foreach ($b['vagas'] as $v): ?>
                                            <span class="badge <?= $v['livres'] === 0 ? 'badge-rosa' : 'badge-cinza' ?>"
                                                  title="<?= e($v['nome']) ?>: <?= $v['ocupadas'] ?> de <?= $v['capacidade'] ?> marcados">
                                                <?= $v['capacidade'] ?> <?= e($v['codigo']) ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <span class="flex items-center gap-2 medico-<?= (int) $b['medico_cor'] ?>">
                                    <span class="barra <?= $pct >= 100 ? 'barra-cheia' : '' ?> w-16">
                                        <span style="width: <?= min(100, $pct) ?>%"></span>
                                    </span>
                                    <span class="celula-suave text-xs"><?= $ocu ?>/<?= $cap ?></span>
                                </span>
                            </td>

                            <td class="celula-suave max-w-xs">
                                <span class="block truncate" title="<?= e($b['observacao'] ?? '') ?>">
                                    <?= $b['observacao'] !== null && $b['observacao'] !== '' ? e($b['observacao']) : '—' ?>
                                </span>
                            </td>

                            <td class="nao-imprimir">
                                <div class="flex items-center justify-end gap-0.5">
                                    <a href="<?= url('agenda', 'marcacoes', ['bloco' => (int) $b['id'], 'vista' => 'lista']) ?>"
                                       class="btn-acao" title="Marcações"
                                       aria-label="Marcações do bloco de <?= e($b['medico_nome']) ?> em <?= e(data_br($data)) ?>">
                                        <?= Icon::svg('marcacoes', 'w-4 h-4') ?>
                                    </a>

                                    <?php if ($podeEditar): ?>
                                        <a href="<?= url('agenda', 'editar', ['id' => (int) $b['id'], 'ano' => $ano, 'mes' => $mes, 'vista' => 'lista']) ?>"
                                           class="btn-acao" title="Editar"
                                           aria-label="Editar bloco de <?= e($b['medico_nome']) ?> em <?= e(data_br($data)) ?>">
                                            <?= Icon::svg('editar', 'w-4 h-4') ?>
                                        </a>
                                    <?php endif; ?>

                                    <?php if ($podeExcluir && $ocu > 0): ?>
                                        <span class="btn-acao-off"
                                              title="Não pode excluir: <?= $ocu ?> paciente<?= $ocu === 1 ? '' : 's' ?> com exame marcado">
                                            <?= Icon::svg('excluir', 'w-4 h-4') ?>
                                        </span>
                                    <?php elseif ($podeExcluir): ?>
                                        <form method="post" action="<?= url('agenda', 'excluir') ?>" class="inline"
                                              data-titulo="Excluir bloco"
                                              data-texto="O bloco de <?= e($b['medico_nome']) ?> em <?= e(data_br($data)) ?> (<?= e(nome_turno($b['turno'])) ?>) será removido da agenda.">
                                            <?= Csrf::campo() ?>
                                            <input type="hidden" name="id" value="<?= (int) $b['id'] ?>">
                                            <input type="hidden" name="vista" value="lista">
                                            <button type="button" @click="abrir" class="btn-acao btn-acao-perigo"
                                                    title="Excluir"
                                                    aria-label="Excluir bloco de <?= e($b['medico_nome']) ?> em <?= e(data_br($data)) ?>">
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
            <span><?= numero(count($blocos)) ?> bloco<?= count($blocos) === 1 ? '' : 's' ?> em <?= e(competencia_br($ano, $mes)) ?></span>
            <?php if ($bloqueios): ?>
                <span><?= count($bloqueios) ?> dia<?= count($bloqueios) === 1 ? '' : 's' ?> bloqueado<?= count($bloqueios) === 1 ? '' : 's' ?></span>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/_resumo.php'; ?>
