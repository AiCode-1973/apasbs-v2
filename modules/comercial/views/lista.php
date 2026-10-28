<?php
/**
 * Comercial — o que o convênio paga por cada coisa.
 *
 * Uma lista só para tudo: diária, taxa, procedimento, honorário, pacote,
 * OPME, material e dieta. Quem pergunta "quanto é isto?" não sabe em qual
 * anexo do edital a linha mora — sabe o código ou o nome.
 *
 * @var array $itens, $tabelas, $grupos, $panorama, $filtro
 * @var array|false $tabela
 * @var int   $total, $pagina, $porPagina
 */
declare(strict_types=1);

$podeCriar   = Auth::temPermissao('comercial', 'pode_criar');
$podeEditar  = Auth::temPermissao('comercial', 'pode_editar');
$podeExcluir = Auth::temPermissao('comercial', 'pode_excluir');

$cores = [
    'procedimento' => 'badge-ceu',
    'honorario'    => 'badge-verde',
    'pacote'       => 'badge-ambar',
    'opme'         => 'badge-rosa',
    'material'     => 'badge-cinza',
    'dieta'        => 'badge-cinza',
    'diaria'       => 'badge-ceu',
    'taxa'         => 'badge-cinza',
    'porte'        => 'badge-cinza',
];
?>

<div class="mb-5 flex flex-wrap items-end justify-between gap-3">
    <div>
        <h1 class="titulo-pagina">Comercial</h1>
        <p class="sub-pagina">
            <?php if ($tabela): ?>
                <?= e($tabela['convenio']) ?> · <?= e($tabela['referencia']) ?> ·
                vigência <?= e(data_br($tabela['vigencia'])) ?>
            <?php else: ?>
                Tabelas de valores dos convênios
            <?php endif; ?>
        </p>
    </div>

    <div class="flex flex-wrap items-center gap-2">
        <a href="<?= url('comercial', 'tabelas') ?>" class="btn btn-outline">
            <?= Icon::svg('setores', 'w-4 h-4') ?> Tabelas
        </a>
        <?php if ($podeCriar && $tabela): ?>
            <a href="<?= url('comercial', 'novo', ['tabela' => (int) $tabela['id']]) ?>" class="btn btn-primary">
                <?= Icon::svg('novo', 'w-4 h-4') ?> Novo item
            </a>
        <?php endif; ?>
    </div>
</div>

<?php if (!$tabela): ?>
    <div class="card">
        <div class="vazio">
            <span class="vazio-icone"><?= Icon::svg('comercial', 'w-5 h-5') ?></span>
            <p class="vazio-titulo">Nenhuma tabela de valores cadastrada</p>
            <p class="vazio-texto">
                Uma tabela é um edital de um convênio, com a data em que passou a
                valer. Cadastre a primeira e depois carregue os itens — a carga do
                edital da CAPEP-SAÚDE está em <span class="mono">sql/com_capep_2026.sql</span>.
            </p>
            <a href="<?= url('comercial', 'tabelas') ?>" class="btn btn-primary btn-sm mt-1">
                <?= Icon::svg('novo', 'w-3.5 h-3.5') ?> Cadastrar tabela
            </a>
        </div>
    </div>
<?php else: ?>

<?php /* O que a tabela tem dentro, por tipo. Cada cartão é um filtro — é o
         caminho mais curto para "me mostra só os OPME". */ ?>
<div class="card mb-4">
    <div class="card-corpo grid gap-3 sm:grid-cols-3 lg:grid-cols-5">
        <?php foreach (ComercialModel::TIPOS as $chave => $rotulo): ?>
            <?php if (empty($panorama[$chave])) { continue; } ?>
            <a href="<?= url('comercial', 'lista', ['tabela' => (int) $tabela['id'], 'tipo' => $chave]) ?>"
               class="rounded-lg p-1 transition-colors hover:bg-slate-50 dark:hover:bg-slate-800/60">
                <p class="rotulo-num"><?= e($rotulo) ?></p>
                <p class="valor-num <?= $filtro['tipo'] === $chave ? 'text-marine-700 dark:text-marine-300' : '' ?>">
                    <?= numero($panorama[$chave]) ?>
                </p>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<div class="card overflow-hidden">
    <form method="get" action="<?= BASE_URL ?>/" class="toolbar">
        <input type="hidden" name="mod" value="comercial">
        <input type="hidden" name="action" value="lista">

        <div class="campo-icone w-full sm:w-80">
            <?= Icon::svg('buscar') ?>
            <input type="search" name="q" value="<?= e($filtro['busca']) ?>" class="input"
                   placeholder="Código ou descrição…" aria-label="Buscar na tabela de valores">
        </div>

        <select name="tabela" class="select w-auto" aria-label="Tabela de valores">
            <?php foreach ($tabelas as $t): ?>
                <option value="<?= (int) $t['id'] ?>" <?= (int) $tabela['id'] === (int) $t['id'] ? 'selected' : '' ?>>
                    <?= e($t['convenio']) ?> — <?= e(data_br($t['vigencia'])) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select name="tipo" class="select w-auto" aria-label="Tipo de item">
            <option value="">Todos os tipos</option>
            <?php foreach (ComercialModel::TIPOS as $chave => $rotulo): ?>
                <option value="<?= e($chave) ?>" <?= $filtro['tipo'] === $chave ? 'selected' : '' ?>>
                    <?= e($rotulo) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select name="grupo" class="select w-auto max-w-xs" aria-label="Grupo do edital">
            <option value="">Todos os grupos</option>
            <?php foreach ($grupos as $g): ?>
                <option value="<?= e($g['grupo']) ?>" <?= $filtro['grupo'] === $g['grupo'] ? 'selected' : '' ?>>
                    <?= e($g['grupo']) ?> (<?= numero((int) $g['itens']) ?>)
                </option>
            <?php endforeach; ?>
        </select>

        <label class="flex items-center gap-1.5 text-xs text-slate-600 dark:text-slate-400">
            <span class="whitespace-nowrap">Valor de</span>
            <input type="text" name="de" value="<?= e($filtro['de'] !== null ? (string) $filtro['de'] : '') ?>"
                   class="input input-sm w-24" inputmode="decimal" aria-label="Valor mínimo">
            <span>até</span>
            <input type="text" name="ate" value="<?= e($filtro['ate'] !== null ? (string) $filtro['ate'] : '') ?>"
                   class="input input-sm w-24" inputmode="decimal" aria-label="Valor máximo">
        </label>

        <label class="flex cursor-pointer items-center gap-1.5 text-xs text-slate-600 dark:text-slate-400">
            <input type="checkbox" name="manuais" value="1" class="check" <?= $filtro['so_manuais'] ? 'checked' : '' ?>>
            Só os incluídos à mão
        </label>

        <button type="submit" class="btn btn-outline">Filtrar</button>

        <?php if ($filtro['busca'] !== '' || $filtro['tipo'] !== '' || $filtro['grupo'] !== ''
                  || $filtro['de'] !== null || $filtro['ate'] !== null || $filtro['so_manuais']): ?>
            <a href="<?= url('comercial', 'lista', ['tabela' => (int) $tabela['id']]) ?>" class="btn btn-ghost">Limpar</a>
        <?php endif; ?>
    </form>

    <?php if (empty($itens)): ?>
        <div class="vazio">
            <span class="vazio-icone"><?= Icon::svg('comercial', 'w-5 h-5') ?></span>
            <p class="vazio-titulo">Nenhum item encontrado</p>
            <p class="vazio-texto">
                Esta tabela ainda não tem itens, ou nenhum bate com os filtros.
                A carga do edital é feita pelo arquivo SQL; o botão
                <strong>Novo item</strong> serve para o que o edital não trouxe.
            </p>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="tabela">
                <thead>
                    <tr>
                        <th class="w-28">Código</th>
                        <th>Descrição</th>
                        <th class="w-40">Tipo</th>
                        <th class="w-28">Variante</th>
                        <th class="w-32 text-right">Valor</th>
                        <th class="w-24 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($itens as $i): ?>
                        <tr>
                            <td class="celula-suave">
                                <?php if ($i['codigo']): ?>
                                    <a href="<?= url('comercial', 'ver', ['id' => (int) $i['id']]) ?>"
                                       class="mono text-xs hover:underline"><?= e($i['codigo']) ?></a>
                                <?php else: ?>
                                    <span class="text-slate-400 dark:text-slate-600">—</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <a href="<?= url('comercial', 'ver', ['id' => (int) $i['id']]) ?>"
                                   class="celula-principal block hover:underline"><?= e($i['descricao']) ?></a>
                                <?php if ($i['grupo']): ?>
                                    <span class="block text-xs text-slate-500 dark:text-slate-400">
                                        <?= e($i['grupo']) ?>
                                        <?php if ($i['origem'] === 'manual'): ?>
                                            · <span class="text-marine-600 dark:text-marine-300">incluído à mão</span>
                                        <?php endif; ?>
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <span class="badge whitespace-nowrap <?= $cores[$i['tipo']] ?? 'badge-cinza' ?>">
                                    <?= e(ComercialModel::TIPOS[$i['tipo']] ?? $i['tipo']) ?>
                                </span>
                            </td>

                            <td class="celula-suave text-xs">
                                <?= $i['variante'] ? e($i['variante']) : '<span class="text-slate-400 dark:text-slate-600">—</span>' ?>
                            </td>

                            <td class="whitespace-nowrap text-right">
                                <?php if ($i['valor'] === null): ?>
                                    <span class="text-xs text-amber-600 dark:text-amber-400">sem valor</span>
                                <?php else: ?>
                                    <span class="font-medium text-slate-800 dark:text-slate-200">
                                        <?= e(dinheiro_br((float) $i['valor'])) ?>
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <div class="flex items-center justify-end gap-0.5">
                                    <?php if ($podeEditar): ?>
                                        <a href="<?= url('comercial', 'editar', ['id' => (int) $i['id']]) ?>"
                                           class="btn-acao" title="Editar" aria-label="Editar <?= e($i['descricao']) ?>">
                                            <?= Icon::svg('editar', 'w-4 h-4') ?>
                                        </a>
                                    <?php endif; ?>

                                    <?php if ($podeExcluir): ?>
                                        <form method="post" action="<?= url('comercial', 'excluir') ?>" class="inline"
                                              data-titulo="Excluir item da tabela"
                                              data-texto="<?= e($i['descricao']) ?> será removido desta tabela de valores.<?= $i['origem'] === 'edital' ? ' Atenção: este item veio da carga do edital e volta na próxima recarga do arquivo.' : '' ?>">
                                            <?= Csrf::campo() ?>
                                            <input type="hidden" name="id" value="<?= (int) $i['id'] ?>">
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
            'modulo'    => 'comercial',
            'pagina'    => $pagina,
            'total'     => $total,
            'porPagina' => $porPagina,
            'padrao'    => 50,
            'rotulo'    => ['item', 'itens'],
            'base'      => array_filter([
                'tabela'  => (int) $tabela['id'],
                'q'       => $filtro['busca'] !== '' ? $filtro['busca'] : null,
                'tipo'    => $filtro['tipo'] !== '' ? $filtro['tipo'] : null,
                'grupo'   => $filtro['grupo'] !== '' ? $filtro['grupo'] : null,
                'de'      => $filtro['de'],
                'ate'     => $filtro['ate'],
                'manuais' => $filtro['so_manuais'] ? 1 : null,
            ]),
        ]) ?>
    <?php endif; ?>
</div>
<?php endif; ?>
