<?php
/**
 * Saúde Ocupacional — entregas de atestado médico.
 *
 * Uma linha por entrega, e não por pessoa: o mesmo colaborador entrega
 * vários atestados ao longo do ano, e cada um tem o seu protocolo.
 *
 * @var array $atestados, $panorama, $empresas, $filtro
 * @var int   $total, $pagina, $porPagina
 */
declare(strict_types=1);

$podeCriar   = Auth::temPermissao('ocupacional', 'pode_criar');
$podeEditar  = Auth::temPermissao('ocupacional', 'pode_editar');
$podeExcluir = Auth::temPermissao('ocupacional', 'pode_excluir');

$hoje = date('Y-m-d');

$temFiltro = $filtro['busca'] !== '' || $filtro['empresa_id'] || $filtro['forma'] !== ''
             || $filtro['de'] || $filtro['ate'] || $filtro['sem_recebimento']
             || $filtro['afastados'];

$base = array_filter([
    'q'               => $filtro['busca'] !== '' ? $filtro['busca'] : null,
    'empresa'         => $filtro['empresa_id'] ?: null,
    'forma'           => $filtro['forma'] !== '' ? $filtro['forma'] : null,
    'de'              => $filtro['de'] ?: null,
    'ate'             => $filtro['ate'] ?: null,
    'sem_recebimento' => $filtro['sem_recebimento'] ? 1 : null,
    'afastados'       => $filtro['afastados'] ? 1 : null,
]);
?>

<div class="mb-5 flex flex-wrap items-end justify-between gap-3">
    <div>
        <h1 class="titulo-pagina">Entrega de atestado médico</h1>
        <p class="sub-pagina">Protocolo de recebimento — Setor de Saúde Ocupacional</p>
    </div>

    <div class="flex flex-wrap items-center gap-2">
        <a href="<?= url('ocupacional', 'lista') ?>" class="btn btn-outline">
            <?= Icon::svg('ocupacional', 'w-4 h-4') ?> Fichas
        </a>
        <a href="<?= url('ocupacional', 'trabalhadores') ?>" class="btn btn-outline">
            <?= Icon::svg('usuarios', 'w-4 h-4') ?> Trabalhadores
        </a>
        <?php if ($podeCriar): ?>
            <a href="<?= url('ocupacional', 'novo_atestado') ?>" class="btn btn-primary">
                <?= Icon::svg('novo', 'w-4 h-4') ?> Registrar entrega
            </a>
        <?php endif; ?>
    </div>
</div>

<?php /* O que este registro é, escrito na tela: é a mesma frase do rodapé do
         comprovante, e ela evita a confusão de tratar o protocolo como
         abono já concedido. */ ?>
<div class="mb-4 flex items-start gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-[0.8125rem] text-slate-600 dark:border-slate-800 dark:bg-slate-900/60 dark:text-slate-400">
    <?= Icon::svg('permissoes', 'w-4 h-4 mt-0.5 shrink-0') ?>
    <span>
        O registro comprova <strong>a entrega e o recebimento</strong> do atestado — não
        representa, isoladamente, validação ou aprovação do afastamento. Cada impressão
        fica registrada na auditoria com o nome de quem imprimiu.
    </span>
</div>

<div class="card mb-4">
    <div class="card-corpo grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <a href="<?= url('ocupacional', 'atestados') ?>"
           class="rounded-lg p-1 transition-colors hover:bg-slate-50 dark:hover:bg-slate-800/60">
            <p class="rotulo-num">Entregas</p>
            <p class="valor-num"><?= numero((int) ($panorama['entregas'] ?? 0)) ?></p>
            <p class="ajuda">atestados protocolados</p>
        </a>

        <div class="rounded-lg p-1">
            <p class="rotulo-num">Neste mês</p>
            <p class="valor-num"><?= numero((int) ($panorama['no_mes'] ?? 0)) ?></p>
            <p class="ajuda">entregues em <?= e(nome_mes((int) date('n'))) ?></p>
        </div>

        <a href="<?= url('ocupacional', 'atestados', ['afastados' => 1]) ?>"
           class="rounded-lg p-1 transition-colors hover:bg-slate-50 dark:hover:bg-slate-800/60">
            <p class="rotulo-num">Afastados hoje</p>
            <p class="valor-num"><?= numero((int) ($panorama['afastados'] ?? 0)) ?></p>
            <p class="ajuda">com retorno previsto à frente</p>
        </a>

        <a href="<?= url('ocupacional', 'atestados', ['sem_recebimento' => 1]) ?>"
           class="rounded-lg p-1 transition-colors hover:bg-slate-50 dark:hover:bg-slate-800/60">
            <p class="rotulo-num">Sem recebimento</p>
            <p class="valor-num <?= ($panorama['sem_recebimento'] ?? 0) > 0 ? 'text-amber-600 dark:text-amber-400' : '' ?>">
                <?= numero((int) ($panorama['sem_recebimento'] ?? 0)) ?>
            </p>
            <p class="ajuda">falta dizer quem recebeu</p>
        </a>
    </div>
</div>

<div class="card overflow-hidden">
    <form method="get" action="<?= BASE_URL ?>/" class="toolbar">
        <input type="hidden" name="mod" value="ocupacional">
        <input type="hidden" name="action" value="atestados">

        <div class="campo-icone w-full sm:w-72">
            <?= Icon::svg('buscar') ?>
            <input type="search" name="q" value="<?= e($filtro['busca']) ?>" class="input"
                   placeholder="Protocolo, nome, matrícula ou CPF…" aria-label="Buscar entregas">
        </div>

        <select name="empresa" class="select w-auto" aria-label="Empresa">
            <option value="">Todas as empresas</option>
            <?php foreach ($empresas as $emp): ?>
                <option value="<?= (int) $emp['id'] ?>" <?= $filtro['empresa_id'] === (int) $emp['id'] ? 'selected' : '' ?>>
                    <?= e($emp['nome']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select name="forma" class="select w-auto" aria-label="Forma de entrega">
            <option value="">Todas as formas</option>
            <?php foreach (OcupacionalModel::FORMAS as $chave => $rotulo): ?>
                <option value="<?= e($chave) ?>" <?= $filtro['forma'] === $chave ? 'selected' : '' ?>>
                    <?= e($rotulo) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label class="flex items-center gap-1.5 text-xs text-slate-600 dark:text-slate-400">
            <span class="whitespace-nowrap">Entregues de</span>
            <input type="date" name="de" value="<?= e((string) $filtro['de']) ?>" class="input input-sm w-auto"
                   aria-label="Entregues a partir de">
            <span>até</span>
            <input type="date" name="ate" value="<?= e((string) $filtro['ate']) ?>" class="input input-sm w-auto"
                   aria-label="Entregues até">
        </label>

        <label class="flex cursor-pointer items-center gap-1.5 text-xs text-slate-600 dark:text-slate-400">
            <input type="checkbox" name="sem_recebimento" value="1" class="check"
                   <?= $filtro['sem_recebimento'] ? 'checked' : '' ?>>
            Sem recebimento
        </label>

        <button type="submit" class="btn btn-outline">Filtrar</button>

        <?php if ($temFiltro): ?>
            <a href="<?= url('ocupacional', 'atestados') ?>" class="btn btn-ghost">Limpar</a>
        <?php endif; ?>
    </form>

    <?php if (empty($atestados)): ?>
        <div class="vazio">
            <span class="vazio-icone"><?= Icon::svg('ocupacional', 'w-5 h-5') ?></span>
            <p class="vazio-titulo"><?= $temFiltro ? 'Nenhuma entrega encontrada' : 'Nenhuma entrega registrada' ?></p>
            <p class="vazio-texto">
                <?= $temFiltro
                    ? 'Nenhum resultado com esses filtros. Tente limpar e buscar de novo.'
                    : 'Quando alguém trouxer um atestado ao setor, registre aqui: o sistema dá o número do protocolo e imprime o comprovante com a 2ª via do colaborador.' ?>
            </p>
            <?php if ($podeCriar && !$temFiltro): ?>
                <a href="<?= url('ocupacional', 'novo_atestado') ?>" class="btn btn-primary btn-sm mt-1">
                    <?= Icon::svg('novo', 'w-3.5 h-3.5') ?> Registrar a primeira entrega
                </a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="tabela">
                <thead>
                    <tr>
                        <th class="w-28">Protocolo</th>
                        <th>Colaborador</th>
                        <th class="w-32">Entrega</th>
                        <th class="w-40">Afastamento</th>
                        <th class="w-40">Forma</th>
                        <th class="w-40">Recebido por</th>
                        <th class="w-32 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($atestados as $a): ?>
                        <?php $afastado = $a['retorno'] && $a['retorno'] >= $hoje; ?>
                        <tr>
                            <td>
                                <a href="<?= url('ocupacional', 'ver_atestado', ['id' => (int) $a['id']]) ?>"
                                   class="mono text-xs font-medium text-marine-700 hover:underline dark:text-marine-300">
                                    <?= e($a['protocolo']) ?>
                                </a>
                            </td>

                            <td>
                                <a href="<?= url('ocupacional', 'ver_atestado', ['id' => (int) $a['id']]) ?>"
                                   class="celula-principal block hover:underline"><?= e($a['trabalhador']) ?></a>
                                <span class="block text-xs text-slate-500 dark:text-slate-400">
                                    <?php if ($a['matricula']): ?>
                                        mat. <?= e($a['matricula']) ?>
                                    <?php endif; ?>
                                    <?php if ($a['setor']): ?>
                                        <?= $a['matricula'] ? ' · ' : '' ?><?= e($a['setor']) ?>
                                    <?php endif; ?>
                                </span>
                            </td>

                            <td class="celula-suave text-xs">
                                <?= e(data_br($a['entrega_data'])) ?>
                                <?php if ($a['entrega_hora']): ?>
                                    <span class="block text-[0.6875rem] text-slate-400 dark:text-slate-500">
                                        às <?= e(substr((string) $a['entrega_hora'], 0, 5)) ?>
                                    </span>
                                <?php endif; ?>
                            </td>

                            <?php /* O que a chefia pergunta primeiro: quantos dias, e se a
                                     pessoa ainda está fora. Quem já voltou fica em cinza. */ ?>
                            <td class="text-xs">
                                <?php if (!$a['dias'] && !$a['inicio']): ?>
                                    <span class="text-slate-400 dark:text-slate-600">—</span>
                                <?php else: ?>
                                    <span class="<?= $afastado ? 'font-medium text-amber-600 dark:text-amber-400' : 'celula-suave' ?>">
                                        <?= $a['dias'] ? numero((int) $a['dias']) . ' dia' . ((int) $a['dias'] === 1 ? '' : 's') : 'sem dias' ?>
                                    </span>
                                    <?php if ($a['retorno']): ?>
                                        <span class="block text-[0.6875rem] <?= $afastado ? 'text-amber-500' : 'text-slate-400 dark:text-slate-500' ?>">
                                            <?= $afastado ? 'volta em ' : 'voltou em ' ?><?= e(data_br($a['retorno'])) ?>
                                        </span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>

                            <td class="celula-suave text-xs">
                                <?= e(OcupacionalModel::FORMAS[$a['forma']] ?? $a['forma']) ?>
                                <?php if ($a['forma'] === 'outro' && $a['forma_outro']): ?>
                                    <span class="block text-[0.6875rem] text-slate-400 dark:text-slate-500">
                                        <?= e($a['forma_outro']) ?>
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td class="text-xs">
                                <?php if ($a['recebido_por']): ?>
                                    <span class="celula-suave"><?= e($a['recebido_por']) ?></span>
                                <?php elseif ($podeEditar): ?>
                                    <a href="<?= url('ocupacional', 'editar_atestado', ['id' => (int) $a['id']]) ?>"
                                       class="text-amber-600 hover:underline dark:text-amber-400">
                                        informar quem recebeu →
                                    </a>
                                <?php else: ?>
                                    <span class="text-amber-600 dark:text-amber-400">em branco</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <div class="flex items-center justify-end gap-0.5">
                                    <a href="<?= url('ocupacional', 'imprimir_atestado', ['id' => (int) $a['id']]) ?>"
                                       class="btn-acao" title="Imprimir o comprovante" target="_blank"
                                       aria-label="Imprimir o comprovante de <?= e($a['trabalhador']) ?>">
                                        <?= Icon::svg('imprimir', 'w-4 h-4') ?>
                                    </a>

                                    <?php if ($podeEditar): ?>
                                        <a href="<?= url('ocupacional', 'editar_atestado', ['id' => (int) $a['id']]) ?>"
                                           class="btn-acao" title="Editar"
                                           aria-label="Editar a entrega <?= e($a['protocolo']) ?>">
                                            <?= Icon::svg('editar', 'w-4 h-4') ?>
                                        </a>
                                    <?php endif; ?>

                                    <?php if ($podeExcluir): ?>
                                        <form method="post" action="<?= url('ocupacional', 'excluir_atestado') ?>" class="inline"
                                              data-titulo="Excluir entrega de atestado"
                                              data-texto="A entrega <?= e($a['protocolo']) ?>, de <?= e($a['trabalhador']) ?>, será removida. O comprovante já pode estar impresso na mão do colaborador, e o número não será reaproveitado. Exclua apenas se foi lançada por engano.">
                                            <?= Csrf::campo() ?>
                                            <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
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
            'modulo'    => 'ocupacional',
            'action'    => 'atestados',
            'pagina'    => $pagina,
            'total'     => $total,
            'porPagina' => $porPagina,
            'padrao'    => 50,
            'rotulo'    => ['entrega', 'entregas'],
            'base'      => $base,
        ]) ?>
    <?php endif; ?>
</div>
