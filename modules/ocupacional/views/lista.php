<?php
/**
 * Saúde Ocupacional — as fichas.
 *
 * Uma linha por exame. O histórico de uma pessoa fica na tela dela; aqui o
 * recorte é do PCMSO: o que está esperando o médico e o que está vencendo.
 *
 * @var array $fichas, $panorama, $empresas, $filtro
 * @var int   $total, $pagina, $porPagina
 */
declare(strict_types=1);

$podeCriar   = Auth::temPermissao('ocupacional', 'pode_criar');
$podeEditar  = Auth::temPermissao('ocupacional', 'pode_editar');
$podeExcluir = Auth::temPermissao('ocupacional', 'pode_excluir');

$cores = [
    'pendente'       => 'badge-ambar',
    'apto'           => 'badge-verde',
    'apto_restricao' => 'badge-ceu',
    'inapto'         => 'badge-rosa',
];

$hoje = new DateTimeImmutable('today');

$temFiltro = $filtro['busca'] !== '' || $filtro['empresa_id'] || $filtro['tipo'] !== ''
             || $filtro['conclusao'] !== '' || $filtro['de'] || $filtro['ate']
             || $filtro['vencendo'] || $filtro['pendentes'];

// O que sobrevive à troca de página
$base = array_filter([
    'q'         => $filtro['busca'] !== '' ? $filtro['busca'] : null,
    'empresa'   => $filtro['empresa_id'] ?: null,
    'tipo'      => $filtro['tipo'] !== '' ? $filtro['tipo'] : null,
    'conclusao' => $filtro['conclusao'] !== '' ? $filtro['conclusao'] : null,
    'de'        => $filtro['de'] ?: null,
    'ate'       => $filtro['ate'] ?: null,
    'vencendo'  => $filtro['vencendo'] ? 1 : null,
    'pendentes' => $filtro['pendentes'] ? 1 : null,
]);
?>

<div class="mb-5 flex flex-wrap items-end justify-between gap-3">
    <div>
        <h1 class="titulo-pagina">Saúde Ocupacional</h1>
        <p class="sub-pagina">Prontuários de exame ocupacional — NR-7 / PCMSO</p>
    </div>

    <div class="flex flex-wrap items-center gap-2">
        <a href="<?= url('ocupacional', 'atestados') ?>" class="btn btn-outline">
            <?= Icon::svg('imprimir', 'w-4 h-4') ?> Atestados
        </a>
        <a href="<?= url('ocupacional', 'trabalhadores') ?>" class="btn btn-outline">
            <?= Icon::svg('usuarios', 'w-4 h-4') ?> Trabalhadores
        </a>
        <a href="<?= url('ocupacional', 'empresas') ?>" class="btn btn-outline">
            <?= Icon::svg('setores', 'w-4 h-4') ?> Empresas
        </a>
        <?php if ($podeCriar): ?>
            <a href="<?= url('ocupacional', 'novo') ?>" class="btn btn-primary">
                <?= Icon::svg('novo', 'w-4 h-4') ?> Nova ficha
            </a>
        <?php endif; ?>
    </div>
</div>

<?php /* Dado de saúde, sob sigilo. O aviso fica na tela porque quem abre
         esta lista costuma estar num balcão, com gente por perto. */ ?>
<div class="mb-4 flex items-start gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-[0.8125rem] text-slate-600 dark:border-slate-800 dark:bg-slate-900/60 dark:text-slate-400">
    <?= Icon::svg('permissoes', 'w-4 h-4 mt-0.5 shrink-0') ?>
    <span>
        <strong>Confidencial.</strong> Prontuário de saúde ocupacional é dado sensível
        (LGPD) e deve ser guardado por 20 anos. Cada impressão fica registrada na
        auditoria com o nome de quem imprimiu.
    </span>
</div>

<!-- Onde está cada ficha -->
<div class="card mb-4">
    <div class="card-corpo grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <a href="<?= url('ocupacional', 'lista') ?>"
           class="rounded-lg p-1 transition-colors hover:bg-slate-50 dark:hover:bg-slate-800/60">
            <p class="rotulo-num">Fichas</p>
            <p class="valor-num"><?= numero((int) ($panorama['fichas'] ?? 0)) ?></p>
            <p class="ajuda">exames registrados</p>
        </a>

        <a href="<?= url('ocupacional', 'lista', ['pendentes' => 1]) ?>"
           class="rounded-lg p-1 transition-colors hover:bg-slate-50 dark:hover:bg-slate-800/60">
            <p class="rotulo-num">Aguardando médico</p>
            <p class="valor-num <?= ($panorama['pendentes'] ?? 0) > 0 ? 'text-amber-600 dark:text-amber-400' : '' ?>">
                <?= numero((int) ($panorama['pendentes'] ?? 0)) ?>
            </p>
            <p class="ajuda">triagem feita, falta a conclusão</p>
        </a>

        <a href="<?= url('ocupacional', 'lista', ['vencendo' => 1]) ?>"
           class="rounded-lg p-1 transition-colors hover:bg-slate-50 dark:hover:bg-slate-800/60">
            <p class="rotulo-num">Vencidas</p>
            <p class="valor-num <?= ($panorama['vencidas'] ?? 0) > 0 ? 'text-rose-600 dark:text-rose-400' : '' ?>">
                <?= numero((int) ($panorama['vencidas'] ?? 0)) ?>
            </p>
            <p class="ajuda">o exame precisa ser refeito</p>
        </a>

        <a href="<?= url('ocupacional', 'lista', ['vencendo' => 1]) ?>"
           class="rounded-lg p-1 transition-colors hover:bg-slate-50 dark:hover:bg-slate-800/60">
            <p class="rotulo-num">Vencem em 30 dias</p>
            <p class="valor-num"><?= numero((int) ($panorama['a_vencer'] ?? 0)) ?></p>
            <p class="ajuda">dá tempo de agendar</p>
        </a>
    </div>

    <?php if ((int) ($panorama['vencidas'] ?? 0) > 0): ?>
        <div class="border-t border-rose-200 bg-rose-50/60 px-4 py-2.5 text-[0.8125rem] dark:border-rose-500/30 dark:bg-rose-500/5">
            <a href="<?= url('ocupacional', 'lista', ['vencendo' => 1]) ?>"
               class="flex items-center gap-2 text-rose-800 hover:underline dark:text-rose-300">
                <?= Icon::svg('aviso', 'w-4 h-4') ?>
                <span>
                    <strong><?= numero((int) $panorama['vencidas']) ?></strong>
                    ficha<?= (int) $panorama['vencidas'] === 1 ? '' : 's' ?> com validade vencida —
                    trabalhador sem exame em dia é irregularidade em fiscalização
                </span>
            </a>
        </div>
    <?php endif; ?>
</div>

<div class="card overflow-hidden">
    <form method="get" action="<?= BASE_URL ?>/" class="toolbar">
        <input type="hidden" name="mod" value="ocupacional">
        <input type="hidden" name="action" value="lista">

        <div class="campo-icone w-full sm:w-72">
            <?= Icon::svg('buscar') ?>
            <input type="search" name="q" value="<?= e($filtro['busca']) ?>" class="input"
                   placeholder="Nome, CPF, função ou setor…" aria-label="Buscar fichas">
        </div>

        <select name="empresa" class="select w-auto" aria-label="Empresa">
            <option value="">Todas as empresas</option>
            <?php foreach ($empresas as $emp): ?>
                <option value="<?= (int) $emp['id'] ?>" <?= $filtro['empresa_id'] === (int) $emp['id'] ? 'selected' : '' ?>>
                    <?= e($emp['nome']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select name="tipo" class="select w-auto" aria-label="Tipo de exame">
            <option value="">Todos os tipos</option>
            <?php foreach (OcupacionalModel::TIPOS as $chave => $rotulo): ?>
                <option value="<?= e($chave) ?>" <?= $filtro['tipo'] === $chave ? 'selected' : '' ?>>
                    <?= e($rotulo) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select name="conclusao" class="select w-auto" aria-label="Conclusão médica">
            <option value="">Todas as conclusões</option>
            <?php foreach (OcupacionalModel::CONCLUSOES as $chave => $rotulo): ?>
                <option value="<?= e($chave) ?>" <?= $filtro['conclusao'] === $chave ? 'selected' : '' ?>>
                    <?= e($rotulo) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label class="flex items-center gap-1.5 text-xs text-slate-600 dark:text-slate-400">
            <span class="whitespace-nowrap">Exames de</span>
            <input type="date" name="de" value="<?= e((string) $filtro['de']) ?>" class="input input-sm w-auto"
                   aria-label="Exames a partir de">
            <span>até</span>
            <input type="date" name="ate" value="<?= e((string) $filtro['ate']) ?>" class="input input-sm w-auto"
                   aria-label="Exames até">
        </label>

        <label class="flex cursor-pointer items-center gap-1.5 text-xs text-slate-600 dark:text-slate-400">
            <input type="checkbox" name="vencendo" value="1" class="check" <?= $filtro['vencendo'] ? 'checked' : '' ?>>
            Só o que está vencendo
        </label>

        <button type="submit" class="btn btn-outline">Filtrar</button>

        <?php if ($temFiltro): ?>
            <a href="<?= url('ocupacional', 'lista') ?>" class="btn btn-ghost">Limpar</a>
        <?php endif; ?>
    </form>

    <?php if (empty($fichas)): ?>
        <div class="vazio">
            <span class="vazio-icone"><?= Icon::svg('ocupacional', 'w-5 h-5') ?></span>
            <p class="vazio-titulo"><?= $temFiltro ? 'Nenhuma ficha encontrada' : 'Nenhuma ficha registrada' ?></p>
            <p class="vazio-texto">
                <?= $temFiltro
                    ? 'Nenhum resultado com esses filtros. Tente limpar e buscar de novo.'
                    : 'Comece cadastrando a empresa e o trabalhador; depois registre o exame admissional.' ?>
            </p>
            <?php if ($podeCriar && !$temFiltro): ?>
                <a href="<?= url('ocupacional', 'empresas') ?>" class="btn btn-primary btn-sm mt-1">
                    <?= Icon::svg('novo', 'w-3.5 h-3.5') ?> Cadastrar a primeira empresa
                </a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="tabela">
                <thead>
                    <tr>
                        <th class="w-28">Exame em</th>
                        <th>Trabalhador</th>
                        <th class="w-36">Empresa</th>
                        <th class="w-36">Tipo</th>
                        <th class="w-28">Validade</th>
                        <th class="w-44">Conclusão</th>
                        <th class="w-28 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($fichas as $f): ?>
                        <?php
                        $venceu = $f['validade'] && $f['validade'] < $hoje->format('Y-m-d');
                        $perto  = $f['validade'] && !$venceu
                                  && $f['validade'] <= $hoje->modify('+30 days')->format('Y-m-d');
                        ?>
                        <tr>
                            <td class="celula-suave text-xs"><?= e(data_br($f['data_exame'])) ?></td>

                            <td>
                                <a href="<?= url('ocupacional', 'ver', ['id' => (int) $f['id']]) ?>"
                                   class="celula-principal block hover:underline"><?= e($f['trabalhador']) ?></a>
                                <span class="block text-xs text-slate-500 dark:text-slate-400">
                                    <?php if ($f['cpf']): ?>
                                        <span class="mono"><?= e(formatar_cpf((string) $f['cpf'])) ?></span>
                                    <?php endif; ?>
                                    <?php if ($f['funcao']): ?>
                                        <?= $f['cpf'] ? ' · ' : '' ?><?= e($f['funcao']) ?>
                                    <?php endif; ?>
                                </span>
                            </td>

                            <td class="celula-suave"><?= e($f['empresa']) ?></td>

                            <td class="celula-suave text-xs">
                                <?= e(OcupacionalModel::TIPOS[$f['tipo']] ?? $f['tipo']) ?>
                            </td>

                            <?php /* A validade é o que faz o módulo valer: é ela que
                                     diz quem precisa refazer o exame. Vencida em
                                     vermelho, perto de vencer em âmbar. */ ?>
                            <td class="text-xs">
                                <?php if (!$f['validade']): ?>
                                    <span class="text-slate-400 dark:text-slate-600">—</span>
                                <?php elseif ($venceu): ?>
                                    <span class="font-medium text-rose-600 dark:text-rose-400"><?= e(data_br($f['validade'])) ?></span>
                                    <span class="block text-[0.625rem] text-rose-500">vencida</span>
                                <?php elseif ($perto): ?>
                                    <span class="font-medium text-amber-600 dark:text-amber-400"><?= e(data_br($f['validade'])) ?></span>
                                    <span class="block text-[0.625rem] text-amber-500">vence em breve</span>
                                <?php else: ?>
                                    <span class="celula-suave"><?= e(data_br($f['validade'])) ?></span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <span class="badge whitespace-nowrap <?= $cores[$f['conclusao']] ?? 'badge-cinza' ?>">
                                    <?= e(OcupacionalModel::CONCLUSOES[$f['conclusao']] ?? $f['conclusao']) ?>
                                </span>
                                <?php if ($f['conclusao'] === 'apto_restricao' && $f['restricoes']): ?>
                                    <span class="mt-0.5 block text-[0.6875rem] text-slate-500 dark:text-slate-400">
                                        <?= e($f['restricoes']) ?>
                                    </span>
                                <?php elseif ($f['conclusao'] === 'pendente' && $podeEditar): ?>
                                    <a href="<?= url('ocupacional', 'editar', ['id' => (int) $f['id']]) ?>"
                                       class="mt-0.5 block text-[0.6875rem] text-marine-700 hover:underline dark:text-marine-300">
                                        lançar a conclusão →
                                    </a>
                                <?php endif; ?>
                            </td>

                            <td>
                                <div class="flex items-center justify-end gap-0.5">
                                    <a href="<?= url('ocupacional', 'imprimir', ['id' => (int) $f['id']]) ?>"
                                       class="btn-acao" title="Imprimir a ficha" target="_blank"
                                       aria-label="Imprimir a ficha de <?= e($f['trabalhador']) ?>">
                                        <?= Icon::svg('imprimir', 'w-4 h-4') ?>
                                    </a>

                                    <?php if ($podeEditar): ?>
                                        <a href="<?= url('ocupacional', 'editar', ['id' => (int) $f['id']]) ?>"
                                           class="btn-acao" title="Editar"
                                           aria-label="Editar a ficha de <?= e($f['trabalhador']) ?>">
                                            <?= Icon::svg('editar', 'w-4 h-4') ?>
                                        </a>
                                    <?php endif; ?>

                                    <?php if ($podeExcluir): ?>
                                        <form method="post" action="<?= url('ocupacional', 'excluir') ?>" class="inline"
                                              data-titulo="Excluir ficha de saúde ocupacional"
                                              data-texto="A ficha de <?= e($f['trabalhador']) ?> (<?= e(OcupacionalModel::TIPOS[$f['tipo']] ?? '') ?>, <?= e(data_br($f['data_exame'])) ?>) será removida. Prontuário deve ser guardado por 20 anos — exclua apenas se foi lançado por engano. Esta ação não pode ser desfeita.">
                                            <?= Csrf::campo() ?>
                                            <input type="hidden" name="id" value="<?= (int) $f['id'] ?>">
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
            'pagina'    => $pagina,
            'total'     => $total,
            'porPagina' => $porPagina,
            'padrao'    => 50,
            'rotulo'    => ['ficha', 'fichas'],
            'base'      => $base,
        ]) ?>
    <?php endif; ?>
</div>
