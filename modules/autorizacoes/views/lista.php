<?php
/**
 * Autorizações de exame.
 *
 * A planilha começava um bloco novo a cada dia, com o cabeçalho repetido no
 * meio da folha. Aqui a data é uma coluna e os filtros fazem o recorte.
 *
 * @var array $autorizacoes
 * @var int   $total, $pagina, $porPagina, $paradas
 * @var array $contagem, $convenios, $procedimentos, $filtro, $documentos
 */
declare(strict_types=1);

$podeCriar   = Auth::temPermissao('autorizacoes', 'pode_criar');
$podeEditar  = Auth::temPermissao('autorizacoes', 'pode_editar');
$podeExcluir = Auth::temPermissao('autorizacoes', 'pode_excluir');

$cores = [
    'aguardando' => 'badge-ambar',
    'pendente'   => 'badge-violeta',
    'autorizado' => 'badge-verde',
    'negado'     => 'badge-rosa',
    'agendado'   => 'badge-ceu',
];

$temFiltro = $filtro['busca'] !== '' || $filtro['status'] !== '' || $filtro['convenio_id']
             || $filtro['procedimento_id'] || $filtro['de'] || $filtro['ate'] || $filtro['em_aberto']
             || ($filtro['parado'] ?? false);

// O que sobrevive à troca de página
$base = array_filter([
    'q'            => $filtro['busca'] !== '' ? $filtro['busca'] : null,
    'status'       => $filtro['status'] !== '' ? $filtro['status'] : null,
    'convenio'     => $filtro['convenio_id'] ?: null,
    'procedimento' => $filtro['procedimento_id'] ?: null,
    'de'           => $filtro['de'] ?: null,
    'ate'          => $filtro['ate'] ?: null,
    'aberto'       => $filtro['em_aberto'] ? 1 : null,
    'parado'       => ($filtro['parado'] ?? false) ? 1 : null,
]);
?>

<div class="mb-5 flex flex-wrap items-end justify-between gap-3">
    <div>
        <h1 class="titulo-pagina">Autorização de exames</h1>
        <p class="sub-pagina">Pedidos esperando liberação do convênio</p>
    </div>

    <div class="flex flex-wrap items-center gap-2">
        <a href="<?= url('autorizacoes', 'convenios') ?>" class="btn btn-outline">
            <?= Icon::svg('setores', 'w-4 h-4') ?> Convênios
        </a>
        <a href="<?= url('autorizacoes', 'procedimentos') ?>" class="btn btn-outline">
            <?= Icon::svg('exames', 'w-4 h-4') ?> Procedimentos
        </a>
        <?php if ($podeCriar): ?>
            <a href="<?= url('autorizacoes', 'novo') ?>" class="btn btn-primary">
                <?= Icon::svg('novo', 'w-4 h-4') ?> Nova autorização
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- Onde está cada pedido -->
<div class="card mb-4">
    <div class="card-corpo grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
        <?php foreach (AutorizacaoModel::STATUS as $chave => $rotulo): ?>
            <a href="<?= url('autorizacoes', 'lista', ['status' => $chave]) ?>"
               class="rounded-lg p-1 transition-colors hover:bg-slate-50 dark:hover:bg-slate-800/60">
                <p class="rotulo-num"><?= e($rotulo) ?></p>
                <p class="valor-num <?= match (true) {
                        $chave === 'aguardando' && ($contagem[$chave] ?? 0) > 0
                            => 'text-amber-600 dark:text-amber-400',
                        $chave === 'pendente' && ($contagem[$chave] ?? 0) > 0
                            => 'text-violet-600 dark:text-violet-400',
                        default => '',
                    } ?>">
                    <?= numero((int) ($contagem[$chave] ?? 0)) ?>
                </p>
                <p class="ajuda">
                    <?= match ($chave) {
                        'aguardando' => 'o convênio ainda não respondeu',
                        'pendente'   => 'falta informação para seguir',
                        'autorizado' => 'liberado, falta marcar',
                        'negado'     => 'o convênio recusou',
                        'agendado'   => 'na fila, falta a data',
                    } ?>
                </p>
            </a>
        <?php endforeach; ?>
    </div>

    <?php /* Autorização tem prazo. A que ninguém marcou vence e o pedido
             inteiro precisa ser refeito — este é o aviso que a planilha
             não sabia dar. */ ?>
    <?php if ($paradas > 0): ?>
        <div class="border-t border-amber-200 bg-amber-50/60 px-4 py-2.5 text-[0.8125rem] dark:border-amber-500/30 dark:bg-amber-500/5">
            <?php /* Abre exatamente o que a faixa contou — não o filtro
                     parecido de "autorizado", que traz também o que já tem dia. */ ?>
            <a href="<?= url('autorizacoes', 'lista', ['parado' => 1]) ?>"
               class="flex items-center gap-2 text-amber-800 hover:underline dark:text-amber-300">
                <?= Icon::svg('aviso', 'w-4 h-4') ?>
                <span>
                    <strong><?= numero($paradas) ?></strong>
                    autoriza<?= $paradas === 1 ? 'ção parada' : 'ções paradas' ?> há mais de uma semana
                    sem data agendada
                </span>
            </a>
        </div>
    <?php endif; ?>
</div>

<div class="card overflow-hidden">
    <form method="get" action="<?= BASE_URL ?>/" class="toolbar">
        <input type="hidden" name="mod" value="autorizacoes">
        <input type="hidden" name="action" value="lista">

        <div class="campo-icone w-full sm:w-72">
            <?= Icon::svg('buscar') ?>
            <input type="search" name="q" value="<?= e($filtro['busca']) ?>" class="input"
                   placeholder="Nome, CPF, telefone, carteirinha ou senha…" aria-label="Buscar autorizações">
        </div>

        <select name="status" class="select w-auto" aria-label="Situação">
            <option value="">Todas as situações</option>
            <?php foreach (AutorizacaoModel::STATUS as $chave => $rotulo): ?>
                <option value="<?= e($chave) ?>" <?= $filtro['status'] === $chave ? 'selected' : '' ?>>
                    <?= e($rotulo) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select name="convenio" class="select w-auto" aria-label="Convênio">
            <option value="">Todos os convênios</option>
            <?php foreach ($convenios as $c): ?>
                <option value="<?= (int) $c['id'] ?>" <?= $filtro['convenio_id'] === (int) $c['id'] ? 'selected' : '' ?>>
                    <?= e($c['nome']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select name="procedimento" class="select w-auto" aria-label="Procedimento">
            <option value="">Todos os procedimentos</option>
            <?php foreach ($procedimentos as $p): ?>
                <option value="<?= (int) $p['id'] ?>" <?= $filtro['procedimento_id'] === (int) $p['id'] ? 'selected' : '' ?>>
                    <?= e($p['nome']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label class="flex items-center gap-1.5 text-xs text-slate-600 dark:text-slate-400">
            <span class="whitespace-nowrap">Solicitadas de</span>
            <input type="date" name="de" value="<?= e((string) $filtro['de']) ?>" class="input input-sm w-auto"
                   aria-label="Solicitadas a partir de">
            <span>até</span>
            <input type="date" name="ate" value="<?= e((string) $filtro['ate']) ?>" class="input input-sm w-auto"
                   aria-label="Solicitadas até">
        </label>

        <label class="flex cursor-pointer items-center gap-1.5 text-xs text-slate-600 dark:text-slate-400">
            <input type="checkbox" name="aberto" value="1" class="check" <?= $filtro['em_aberto'] ? 'checked' : '' ?>>
            Só o que está em aberto
        </label>

        <?php /* Quem chegou pela faixa âmbar continua nela ao filtrar por
                 convênio ou período — o recorte do aviso não se perde no meio
                 do caminho. Para sair dele, Limpar. */ ?>
        <?php if ($filtro['parado'] ?? false): ?>
            <label class="flex cursor-pointer items-center gap-1.5 text-xs text-amber-700 dark:text-amber-400">
                <input type="checkbox" name="parado" value="1" class="check" checked>
                Só as paradas há mais de <?= AutorizacaoModel::DIAS_PARADA ?> dias
            </label>
        <?php endif; ?>

        <button type="submit" class="btn btn-outline">Filtrar</button>

        <?php if ($temFiltro): ?>
            <a href="<?= url('autorizacoes', 'lista') ?>" class="btn btn-ghost">Limpar</a>
        <?php endif; ?>
    </form>

    <?php if (empty($autorizacoes)): ?>
        <div class="vazio">
            <span class="vazio-icone"><?= Icon::svg('autorizacoes', 'w-5 h-5') ?></span>
            <p class="vazio-titulo"><?= $temFiltro ? 'Nenhuma autorização encontrada' : 'Nenhuma autorização registrada' ?></p>
            <p class="vazio-texto">
                <?= $temFiltro
                    ? 'Nenhum resultado com esses filtros. Tente limpar e buscar de novo.'
                    : 'Registre o primeiro pedido: paciente, convênio e o procedimento que precisa de liberação.' ?>
            </p>
            <?php if ($podeCriar && !$temFiltro): ?>
                <a href="<?= url('autorizacoes', 'novo') ?>" class="btn btn-primary btn-sm mt-1">
                    <?= Icon::svg('novo', 'w-3.5 h-3.5') ?> Nova autorização
                </a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="tabela">
                <thead>
                    <tr>
                        <th class="w-28">Solicitação</th>
                        <th>Paciente</th>
                        <th class="w-32">Convênio</th>
                        <th class="w-44">Procedimento</th>
                        <th class="w-28">Pedido</th>
                        <th class="w-28">Agendada</th>
                        <th class="w-52">Situação</th>
                        <th class="w-24 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($autorizacoes as $a): ?>
                        <tr>
                            <td class="celula-suave text-xs"><?= e(data_br($a['data_solicitacao'])) ?></td>

                            <td>
                                <span class="celula-principal block"><?= e($a['paciente']) ?></span>
                                <?php /* Telefone e CPF na mesma linha de baixo: são o que
                                         a atendente lê em voz alta quando liga para o
                                         convênio, e ler os dois de uma vez poupa um clique. */ ?>
                                <span class="mono block text-xs text-slate-500 dark:text-slate-400">
                                    <?php if ($a['contato']): ?>
                                        <a href="tel:+55<?= e($a['contato']) ?>"
                                           class="hover:text-marine-700 dark:hover:text-marine-300"><?= e(telefone_br($a['contato'])) ?></a>
                                    <?php endif; ?>
                                    <?php if ($a['cpf']): ?>
                                        <?= $a['contato'] ? ' · ' : '' ?><?= e(formatar_cpf((string) $a['cpf'])) ?>
                                    <?php endif; ?>
                                </span>

                                <?php /* Os documentos. Só aparece o que tem notícia: o que
                                         já chegou, em cinza, e a guia que falta quando o
                                         convênio já autorizou — aí é cobrança, em âmbar.
                                         Linha sem nada a dizer não ganha enfeite. */ ?>
                                <?php
                                $doc = $documentos[(int) $a['id']] ?? ['guia' => false, 'pedido' => false];
                                $faltaGuia = !$doc['guia']
                                    && in_array($a['status'], ['autorizado', 'agendado'], true);
                                ?>
                                <?php if ($doc['guia'] || $doc['pedido'] || $faltaGuia): ?>
                                    <span class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-0.5 text-[0.6875rem]">
                                        <?php foreach (['guia' => 'guia', 'pedido' => 'pedido'] as $tipo => $rotulo): ?>
                                            <?php if ($doc[$tipo]): ?>
                                                <span class="inline-flex items-center gap-1 text-slate-500 dark:text-slate-400"
                                                      title="<?= e(AutorizacaoModel::TIPOS_ANEXO[$tipo]) ?> anexada">
                                                    <?= Icon::svg('anexo', 'w-3 h-3') ?><?= $rotulo ?>
                                                </span>
                                            <?php endif; ?>
                                        <?php endforeach; ?>

                                        <?php if ($faltaGuia): ?>
                                            <?php if ($podeEditar): ?>
                                                <a href="<?= url('autorizacoes', 'editar', ['id' => (int) $a['id']]) ?>"
                                                   class="inline-flex items-center gap-1 text-amber-600 underline decoration-dotted hover:text-amber-700 dark:text-amber-400">
                                                    <?= Icon::svg('anexo', 'w-3 h-3') ?>sem a guia
                                                </a>
                                            <?php else: ?>
                                                <span class="inline-flex items-center gap-1 text-amber-600 dark:text-amber-400">
                                                    <?= Icon::svg('anexo', 'w-3 h-3') ?>sem a guia
                                                </span>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td class="celula-suave">
                                <?= e($a['convenio']) ?>
                                <?php if ($a['carteirinha']): ?>
                                    <span class="mono block text-[0.6875rem] text-slate-400 dark:text-slate-500">
                                        <?= e($a['carteirinha']) ?>
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td class="celula-suave">
                                <?= e($a['procedimento']) ?>
                                <?php if ($a['senha']): ?>
                                    <span class="mono block text-[0.6875rem] text-slate-400 dark:text-slate-500">
                                        senha <?= e($a['senha']) ?>
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td class="celula-suave text-xs">
                                <?= $a['data_pedido']
                                    ? e(data_br($a['data_pedido']))
                                    : '<span class="text-slate-400 dark:text-slate-600">sem data</span>' ?>
                            </td>

                            <?php /* A data aparece em qualquer situação, e não só no que
                                     está Para Autorizar: o campo é livre, e um dia combinado
                                     antes da resposta do convênio é informação útil na
                                     lista. Em negrito quando a situação é Para Autorizar —
                                     dia combinado; em cinza nas demais, onde ainda é
                                     data prevista. */ ?>
                            <td class="text-xs">
                                <?php if ($a['data_agendada']): ?>
                                    <?php if ($a['status'] === 'agendado'): ?>
                                        <span class="celula-principal"><?= e(data_br($a['data_agendada'])) ?></span>
                                    <?php else: ?>
                                        <span class="text-slate-500 dark:text-slate-400"
                                              title="Data prevista: a situação ainda não é Para Autorizar">
                                            <?= e(data_br($a['data_agendada'])) ?>
                                        </span>
                                        <span class="block text-[0.625rem] text-slate-400 dark:text-slate-600">prevista</span>
                                    <?php endif; ?>
                                <?php elseif ($a['status'] === 'agendado'): ?>
                                    <?php /* Para Autorizar sem data não trava a gravação —
                                             então a lista precisa apontar a falta. */ ?>
                                    <?php if ($podeEditar): ?>
                                        <a href="<?= url('autorizacoes', 'editar', ['id' => (int) $a['id']]) ?>"
                                           class="text-amber-600 underline decoration-dotted hover:text-amber-700 dark:text-amber-400">
                                            sem data
                                        </a>
                                    <?php else: ?>
                                        <span class="text-amber-600 dark:text-amber-400">sem data</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-slate-400 dark:text-slate-600">—</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <span class="badge whitespace-nowrap <?= $cores[$a['status']] ?? 'badge-cinza' ?>">
                                    <?= e(AutorizacaoModel::STATUS[$a['status']] ?? $a['status']) ?>
                                </span>

                                <?php /* O que está pendente aparece na própria linha, logo
                                         abaixo do selo: uma situação que diz "falta alguma
                                         coisa" sem dizer o quê obriga a abrir o registro
                                         para saber, e isso é exatamente o que a lista
                                         existe para evitar. Em violeta quando é a própria
                                         situação; em âmbar quando o convênio já respondeu
                                         e o texto é só um lembrete. */ ?>
                                <?php $pendencia = trim((string) ($a['info_pendente'] ?? '')); ?>
                                <?php if ($pendencia !== ''): ?>
                                    <span class="mt-0.5 block text-[0.6875rem] <?= $a['status'] === 'pendente'
                                            ? 'text-violet-600 dark:text-violet-400'
                                            : 'text-amber-600 dark:text-amber-400' ?>">
                                        <?= $a['status'] === 'pendente' ? '' : 'pendente: ' ?><?= e($pendencia) ?>
                                    </span>
                                <?php endif; ?>

                                <?php /* Quando o convênio liberou. Fica sob o selo em vez
                                         de virar coluna: a tabela já tem oito, e esta data
                                         só existe em parte das linhas. Faltando, vira um
                                         link — é o empurrão para completar o registro sem
                                         travar quem autorizou pelo atalho da lista. */ ?>
                                <?php if (in_array($a['status'], ['autorizado', 'agendado'], true)): ?>
                                    <span class="mt-0.5 block text-[0.6875rem] text-slate-500 dark:text-slate-400">
                                        <?php if ($a['data_autorizacao']): ?>
                                            autorizado em <?= e(data_br($a['data_autorizacao'])) ?>
                                        <?php elseif ($podeEditar): ?>
                                            <a href="<?= url('autorizacoes', 'editar', ['id' => (int) $a['id']]) ?>"
                                               class="text-slate-400 underline decoration-dotted hover:text-marine-700 dark:text-slate-500 dark:hover:text-marine-300">
                                                sem data da autorização
                                            </a>
                                        <?php else: ?>
                                            <span class="text-slate-400 dark:text-slate-600">sem data da autorização</span>
                                        <?php endif; ?>
                                    </span>
                                <?php endif; ?>

                                <?php if ($podeEditar && $a['status'] === 'pendente'): ?>
                                    <?php /* Resolver é apagar o que falta e escolher a
                                             situação — dois gestos que não cabem num
                                             botão de um clique só. */ ?>
                                    <a href="<?= url('autorizacoes', 'editar', ['id' => (int) $a['id']]) ?>"
                                       class="mt-1 block text-[0.6875rem] text-marine-700 hover:underline dark:text-marine-300">
                                        resolver a pendência →
                                    </a>
                                <?php endif; ?>

                                <?php /* O gesto mais repetido do dia é responder ao convênio.
                                         Dois botões na própria linha evitam abrir o formulário
                                         para mudar uma palavra. */ ?>
                                <?php if ($podeEditar && $a['status'] === 'aguardando'): ?>
                                    <span class="mt-1 flex items-center gap-1">
                                        <?php foreach ([['autorizado', 'Autorizar'], ['negado', 'Negar']] as [$novo, $rotulo]): ?>
                                            <form method="post" action="<?= url('autorizacoes', 'situacao') ?>" class="inline">
                                                <?= Csrf::campo() ?>
                                                <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
                                                <input type="hidden" name="status" value="<?= $novo ?>">
                                                <button type="submit" class="btn-pag"
                                                        title="Marcar como <?= mb_strtolower($rotulo) ?>"><?= $rotulo ?></button>
                                            </form>
                                        <?php endforeach; ?>
                                    </span>
                                <?php elseif ($a['status'] === 'negado' && $a['motivo_negativa']): ?>
                                    <span class="mt-1 block text-[0.6875rem] text-rose-600 dark:text-rose-400">
                                        <?= e($a['motivo_negativa']) ?>
                                    </span>
                                <?php elseif ($podeEditar && $a['status'] === 'autorizado'): ?>
                                    <?php /* Só o atalho para a data. Mudar para "Para
                                             Autorizar" é escolha de situação, e situação se
                                             escolhe no formulário ou pelo filtro — não num
                                             botão que muda o registro com um clique só. */ ?>
                                    <a href="<?= url('autorizacoes', 'editar', ['id' => (int) $a['id']]) ?>"
                                       class="mt-1 block text-[0.6875rem] text-marine-700 hover:underline dark:text-marine-300">
                                        <?= $a['data_agendada'] ? 'confirmar a data →' : 'marcar a data →' ?>
                                    </a>
                                <?php endif; ?>
                            </td>

                            <td>
                                <div class="flex items-center justify-end gap-0.5">
                                    <?php /* Só aparece onde há o que imprimir: ícone
                                             que não faz nada é ícone que atrapalha. */ ?>
                                    <?php if ($doc['guia'] || $doc['pedido']): ?>
                                        <a href="<?= url('autorizacoes', 'imprimir', ['id' => (int) $a['id']]) ?>"
                                           target="_blank" rel="noopener"
                                           class="btn-acao" title="Imprimir a guia e o pedido"
                                           aria-label="Imprimir os documentos de <?= e($a['paciente']) ?>">
                                            <?= Icon::svg('imprimir', 'w-4 h-4') ?>
                                        </a>
                                    <?php endif; ?>

                                    <?php if ($podeEditar): ?>
                                        <a href="<?= url('autorizacoes', 'editar', ['id' => (int) $a['id']]) ?>"
                                           class="btn-acao" title="Editar"
                                           aria-label="Editar a autorização de <?= e($a['paciente']) ?>">
                                            <?= Icon::svg('editar', 'w-4 h-4') ?>
                                        </a>
                                    <?php endif; ?>

                                    <?php if ($podeExcluir): ?>
                                        <form method="post" action="<?= url('autorizacoes', 'excluir') ?>" class="inline"
                                              data-titulo="Excluir autorização"
                                              data-texto="O pedido de <?= e($a['procedimento']) ?> de <?= e($a['paciente']) ?> será removido. Esta ação não pode ser desfeita.">
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
            'modulo'    => 'autorizacoes',
            'pagina'    => $pagina,
            'total'     => $total,
            'porPagina' => $porPagina,
            'padrao'    => 50,
            'rotulo'    => ['autorização', 'autorizações'],
            'base'      => $base,
        ]) ?>
    <?php endif; ?>
</div>
