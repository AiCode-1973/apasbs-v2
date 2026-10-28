<?php
/**
 * Auditoria Hospitalar — as contas de um tipo, com o dinheiro à vista.
 *
 * Internação e PA/Ambulatório são a mesma tela: muda o que identifica a conta
 * (paciente de um lado, setor do outro) e a tabela no banco. Quem audita
 * trabalha uma competência por vez, então o recorte vive nos filtros e os
 * totais acompanham o recorte — total que ignora o filtro responde a uma
 * pergunta que ninguém fez.
 *
 * @var string $tipo
 * @var array  $cfg, $contas, $totais, $contagem, $competencias, $convenios, $filtro
 * @var int    $total, $pagina, $porPagina
 */
declare(strict_types=1);

$podeCriar   = Auth::temPermissao('auditoria_hosp', 'pode_criar');
$podeEditar  = Auth::temPermissao('auditoria_hosp', 'pode_editar');
$podeExcluir = Auth::temPermissao('auditoria_hosp', 'pode_excluir');

$temFiltro = $filtro['busca'] !== '' || $filtro['status'] !== '' || $filtro['convenio_id']
             || $filtro['setor'] !== '' || $filtro['competencia'] !== '' || $filtro['so_glosa'];

$base = array_filter([
    'tipo'        => $tipo,
    'q'           => $filtro['busca'] !== '' ? $filtro['busca'] : null,
    'status'      => $filtro['status'] !== '' ? $filtro['status'] : null,
    'convenio'    => $filtro['convenio_id'] ?: null,
    'setor'       => $filtro['setor'] !== '' ? $filtro['setor'] : null,
    'competencia' => $filtro['competencia'] !== '' ? $filtro['competencia'] : null,
    'glosa'       => $filtro['so_glosa'] ? 1 : null,
]);

$mesBr = static function (?string $mes): string {
    if (!$mes) {
        return '—';
    }
    [$ano, $m] = array_pad(explode('-', $mes), 2, '01');
    return $m . '/' . $ano;
};

// O percentual de glosa do recorte: é o número que a reunião pergunta.
$percGlosa = ((float) $totais['final']) > 0
    ? round((float) $totais['glosado'] / (float) $totais['final'] * 100, 2)
    : null;
?>

<div class="mb-5 flex flex-wrap items-end justify-between gap-3">
    <div>
        <h1 class="titulo-pagina">Auditoria Hospitalar</h1>
        <p class="sub-pagina">Contas auditadas, glosas e recursos com os convênios</p>
    </div>

    <div class="flex flex-wrap items-center gap-2">
        <?php /* A planilha leva o mesmo recorte da tela — inclusive a busca,
                 que o consolidado não usa. Só aparece quando há o que baixar:
                 botão que entrega arquivo vazio é botão que engana. */ ?>
        <?php if ($total > 0): ?>
            <a href="<?= url('auditoria_hosp', 'excel', $base) ?>" class="btn btn-outline">
                <?= Icon::svg('download', 'w-4 h-4') ?> Excel
            </a>
        <?php endif; ?>

        <?php /* O consolidado leva o recorte da lista: quem filtrou uma
                 competência quer o consolidado dela, não o do mês corrente. */ ?>
        <a href="<?= url('auditoria_hosp', 'consolidado', array_filter([
                'tipo'        => $tipo,
                'convenio'    => $filtro['convenio_id'] ?: null,
                'setor'       => $filtro['setor'] !== '' ? $filtro['setor'] : null,
                'status'      => $filtro['status'] !== '' ? $filtro['status'] : null,
            ]) + ($filtro['competencia'] !== '' ? ['competencia' => $filtro['competencia']] : [])) ?>"
           class="btn btn-outline">
            <?= Icon::svg('fechamentos', 'w-4 h-4') ?> Consolidado
        </a>

        <?php if ($podeCriar): ?>
            <a href="<?= url('auditoria_hosp', 'novo', ['tipo' => $tipo]) ?>" class="btn btn-primary">
                <?= Icon::svg('novo', 'w-4 h-4') ?> Nova conta
            </a>
        <?php endif; ?>
    </div>
</div>

<?php /* Os dois tipos são a mesma tela; a troca fica no alto, como aba. */ ?>
<div class="mb-4 flex flex-wrap items-center gap-1.5">
    <?php foreach (AuditoriaHospModel::TIPOS as $chave => $t): ?>
        <a href="<?= url('auditoria_hosp', 'lista', ['tipo' => $chave]) ?>"
           class="btn btn-sm <?= $tipo === $chave ? 'btn-primary' : 'btn-outline' ?>">
            <?= e($t['plural']) ?>
        </a>
    <?php endforeach; ?>
</div>

<!-- O dinheiro do recorte -->
<div class="card mb-4">
    <div class="card-corpo grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
        <div>
            <p class="rotulo-num">Contas</p>
            <p class="valor-num"><?= numero((int) $totais['contas']) ?></p>
            <p class="ajuda">no recorte atual</p>
        </div>
        <div>
            <p class="rotulo-num">Valor inicial</p>
            <p class="valor-num"><?= e(dinheiro_br((float) $totais['inicial'])) ?></p>
            <p class="ajuda">como as contas chegaram</p>
        </div>
        <div>
            <p class="rotulo-num">Valor final</p>
            <p class="valor-num"><?= e(dinheiro_br((float) $totais['final'])) ?></p>
            <p class="ajuda">depois da auditoria</p>
        </div>
        <div>
            <p class="rotulo-num">Glosado</p>
            <p class="valor-num text-rose-600 dark:text-rose-400">
                <?= e(dinheiro_br((float) $totais['glosado'])) ?>
            </p>
            <p class="ajuda">
                <?= $percGlosa === null ? 'sem base para o percentual'
                    : e(number_format($percGlosa, 2, ',', '.')) . '% do valor final' ?>
            </p>
        </div>
        <div>
            <p class="rotulo-num">Aceito no recurso</p>
            <p class="valor-num text-emerald-600 dark:text-emerald-400">
                <?= e(dinheiro_br((float) $totais['aceito'])) ?>
            </p>
            <p class="ajuda">glosa revertida</p>
        </div>
    </div>
</div>

<div class="card overflow-hidden">
    <form method="get" class="flex flex-wrap items-center gap-2 border-b border-slate-200 p-4 dark:border-slate-800">
        <input type="hidden" name="mod" value="auditoria_hosp">
        <input type="hidden" name="action" value="lista">
        <input type="hidden" name="tipo" value="<?= e($tipo) ?>">

        <div class="campo-busca min-w-56 flex-1">
            <?= Icon::svg('buscar') ?>
            <input type="search" name="q" value="<?= e($filtro['busca']) ?>" class="input"
                   placeholder="<?= $cfg['paciente'] ? 'Paciente ou guia…' : 'Número da guia…' ?>"
                   aria-label="Buscar contas">
        </div>

        <select name="competencia" class="select w-auto" aria-label="Competência">
            <option value="">Todas as competências</option>
            <?php foreach ($competencias as $mes): ?>
                <option value="<?= e($mes) ?>" <?= $filtro['competencia'] === $mes ? 'selected' : '' ?>>
                    <?= e($mesBr($mes)) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select name="convenio" class="select w-auto" aria-label="Convênio">
            <option value="">Todos os convênios</option>
            <?php foreach ($convenios as $c): ?>
                <option value="<?= (int) $c['id'] ?>" <?= $filtro['convenio_id'] === (int) $c['id'] ? 'selected' : '' ?>>
                    <?= e($c['nome_convenio']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select name="status" class="select w-auto" aria-label="Situação">
            <option value="">Todas as situações</option>
            <?php foreach (array_keys($contagem) as $st): ?>
                <option value="<?= e($st) ?>" <?= $filtro['status'] === $st ? 'selected' : '' ?>>
                    <?= e($st) ?> (<?= numero((int) $contagem[$st]) ?>)
                </option>
            <?php endforeach; ?>
        </select>

        <?php if ($cfg['setor']): ?>
            <select name="setor" class="select w-auto" aria-label="Setor">
                <option value="">Todos os setores</option>
                <?php foreach (AuditoriaHospModel::SETORES as $s): ?>
                    <option value="<?= e($s) ?>" <?= $filtro['setor'] === $s ? 'selected' : '' ?>>
                        <?= e($s) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        <?php endif; ?>

        <label class="flex cursor-pointer items-center gap-1.5 text-xs text-slate-600 dark:text-slate-400">
            <input type="checkbox" name="glosa" value="1" class="check" <?= $filtro['so_glosa'] ? 'checked' : '' ?>>
            Só o que teve glosa
        </label>

        <button type="submit" class="btn btn-outline">Filtrar</button>

        <?php if ($temFiltro): ?>
            <a href="<?= url('auditoria_hosp', 'lista', ['tipo' => $tipo]) ?>" class="btn btn-ghost">Limpar</a>
        <?php endif; ?>
    </form>

    <?php if (empty($contas)): ?>
        <div class="vazio">
            <span class="vazio-icone"><?= Icon::svg('auditoria_hosp', 'w-5 h-5') ?></span>
            <p class="vazio-titulo">
                <?= $temFiltro ? 'Nada com esse recorte' : 'Nenhuma conta registrada' ?>
            </p>
            <p class="vazio-texto">
                <?= $temFiltro
                    ? 'Tente outra competência, outro convênio, ou limpe o filtro.'
                    : 'As contas auditadas desta fila aparecem aqui.' ?>
            </p>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="tabela">
                <thead>
                    <tr>
                        <?php /* As colunas de dinheiro seguem a ordem da conta —
                                 inicial − retirado + acrescentado = final —, porque é
                                 assim que quem audita lê a linha. */ ?>
                        <th class="w-24">Competência</th>
                        <th><?= $cfg['paciente'] ? 'Paciente' : 'Setor e guia' ?></th>
                        <th class="w-28">Convênio</th>
                        <th class="w-24 text-right">Inicial</th>
                        <th class="w-24 text-right">Retirado</th>
                        <th class="w-24 text-right">Acrescido</th>
                        <th class="w-24 text-right">Final</th>
                        <th class="w-28 text-right">Glosado</th>
                        <th class="w-24 text-right">Aceito</th>
                        <th class="w-28">Situação</th>
                        <th class="w-20 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($contas as $c): ?>
                        <?php $p = AuditoriaHospModel::percentuais($c); ?>
                        <tr>
                            <td class="celula-suave text-xs"><?= e($mesBr(substr((string) $c['competencia'], 0, 7))) ?></td>

                            <td>
                                <?php if ($cfg['paciente']): ?>
                                    <span class="celula-principal block"><?= e($c['paciente']) ?></span>
                                <?php else: ?>
                                    <span class="celula-principal block"><?= e($c['setor']) ?></span>
                                <?php endif; ?>

                                <span class="mono block text-xs text-slate-500 dark:text-slate-400">
                                    <?= $c['guia_paciente'] !== null && $c['guia_paciente'] !== ''
                                        ? 'guia ' . e($c['guia_paciente'])
                                        : '<span class="text-slate-400 dark:text-slate-600">sem guia</span>' ?>
                                    <?php if ((int) $c['motivos'] > 0): ?>
                                        · <?= numero((int) $c['motivos']) ?> motivo<?= (int) $c['motivos'] === 1 ? '' : 's' ?>
                                    <?php endif; ?>
                                </span>
                            </td>

                            <td class="celula-suave"><?= e($c['convenio'] ?? '—') ?></td>

                            <td class="text-right text-xs"><?= e(dinheiro_br((float) $c['valor_inicial'], false)) ?></td>

                            <?php /* O retirado com o percentual sobre o inicial embaixo,
                                     como no consolidado: 400,00 retirados de uma conta de
                                     500 e de uma conta de 50.000 são auditorias de
                                     tamanhos diferentes, e é o percentual que diz isso. */ ?>
                            <td class="text-right text-xs">
                                <?php if ((float) $c['valor_retirado'] > 0): ?>
                                    <span class="text-amber-600 dark:text-amber-400">
                                        <?= e(dinheiro_br((float) $c['valor_retirado'], false)) ?>
                                    </span>
                                    <?php if ($p['retirado'] !== null): ?>
                                        <span class="block text-[0.625rem] text-slate-400">
                                            <?= e(number_format($p['retirado'], 2, ',', '.')) ?>%
                                        </span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-slate-400 dark:text-slate-600">—</span>
                                <?php endif; ?>
                            </td>

                            <?php /* O AuditorHosp aceita acréscimo negativo — é como o
                                     sistema antigo registra uma segunda retirada. Mostrar
                                     o sinal é mais honesto do que esconder a linha no
                                     travessão e deixar o final sem explicação. */ ?>
                            <td class="text-right text-xs">
                                <?php if ((float) $c['valor_acrescentado'] != 0): ?>
                                    <span class="<?= (float) $c['valor_acrescentado'] < 0
                                        ? 'text-amber-600 dark:text-amber-400' : '' ?>">
                                        <?= e(dinheiro_br((float) $c['valor_acrescentado'], false)) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-slate-400 dark:text-slate-600">—</span>
                                <?php endif; ?>
                            </td>

                            <?php /* Conta que ainda não passou pela auditoria tem final
                                     zerado no banco. Mostrar "0,00" ao lado de um inicial
                                     cheio parece defeito; o travessão diz o que é. */ ?>
                            <td class="text-right text-xs">
                                <?php if ((float) $c['valor_total'] > 0 || (float) $c['valor_inicial'] <= 0): ?>
                                    <?= e(dinheiro_br((float) $c['valor_total'], false)) ?>
                                <?php else: ?>
                                    <span class="text-slate-400 dark:text-slate-600"
                                          title="A conta ainda não foi auditada">—</span>
                                <?php endif; ?>
                            </td>

                            <td class="text-right text-xs">
                                <?php if ((float) $c['valor_glosado'] > 0): ?>
                                    <span class="font-semibold text-rose-600 dark:text-rose-400">
                                        <?= e(dinheiro_br((float) $c['valor_glosado'], false)) ?>
                                    </span>
                                    <?php if ($p['glosado'] !== null): ?>
                                        <span class="block text-[0.625rem] text-slate-400">
                                            <?= e(number_format($p['glosado'], 2, ',', '.')) ?>%
                                        </span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-slate-400 dark:text-slate-600">—</span>
                                <?php endif; ?>
                            </td>

                            <td class="text-right text-xs">
                                <?= (float) $c['valor_aceito'] > 0
                                    ? '<span class="text-emerald-600 dark:text-emerald-400">'
                                      . e(dinheiro_br((float) $c['valor_aceito'], false)) . '</span>'
                                    : '<span class="text-slate-400 dark:text-slate-600">—</span>' ?>
                            </td>

                            <td>
                                <span class="badge whitespace-nowrap <?= $c['status'] === 'Auditado' ? 'badge-verde' : 'badge-ambar' ?>">
                                    <?= e((string) $c['status']) ?>
                                </span>
                                <?php if (($c['falta_nf'] ?? 'Não') === 'Sim'): ?>
                                    <span class="mt-0.5 block text-[0.625rem] text-amber-600 dark:text-amber-400">falta NF</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <div class="flex items-center justify-end gap-0.5">
                                    <?php if ($podeEditar): ?>
                                        <a href="<?= url('auditoria_hosp', 'editar', ['tipo' => $tipo, 'id' => (int) $c['id']]) ?>"
                                           class="btn-acao" title="Abrir" aria-label="Abrir a conta">
                                            <?= Icon::svg('editar', 'w-4 h-4') ?>
                                        </a>
                                    <?php endif; ?>

                                    <?php if ($podeExcluir): ?>
                                        <form method="post" action="<?= url('auditoria_hosp', 'excluir') ?>" class="inline"
                                              data-titulo="Excluir conta"
                                              data-texto="A conta e os motivos de glosa dela saem do banco do AuditorHosp. Esta ação não pode ser desfeita.">
                                            <?= Csrf::campo() ?>
                                            <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                                            <input type="hidden" name="tipo" value="<?= e($tipo) ?>">
                                            <button type="button" @click="abrir" class="btn-acao btn-acao-perigo"
                                                    title="Excluir" aria-label="Excluir">
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

        <?= Paginacao::barra([
            'modulo'    => 'auditoria_hosp',
            'pagina'    => $pagina,
            'total'     => $total,
            'porPagina' => $porPagina,
            'padrao'    => 25,
            'rotulo'    => ['conta', 'contas'],
            'base'      => $base,
        ]) ?>
    <?php endif; ?>
</div>
