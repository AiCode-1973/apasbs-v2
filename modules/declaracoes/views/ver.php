<?php
/**
 * A declaração por dentro: quem já respondeu, o que foi declarado e até
 * quando corre a Cobertura Parcial Temporária.
 *
 * @var array $d, $pessoas, $resumo
 * @var string|null $fimCpt
 */
declare(strict_types=1);

$podeEditar  = Auth::temPermissao('declaracoes', 'pode_editar');
$podeExcluir = Auth::temPermissao('declaracoes', 'pode_excluir');

$hoje       = date('Y-m-d');
$cptVigente = $fimCpt !== null && $fimCpt >= $hoje;

$comDlp      = array_filter($resumo, fn($r) => $r['tem_dlp']);
$incompletos = array_filter($resumo, fn($r) => $r['faltam'] > 0);
$titular     = null;
foreach ($pessoas as $p) {
    if ($p['tipo'] === 'titular') {
        $titular = $p;
    }
}
?>

<div class="mb-5 flex flex-wrap items-start justify-between gap-3">
    <div class="flex items-start gap-3">
        <a href="<?= url_lista('declaracoes') ?>" class="btn-acao mt-0.5" aria-label="Voltar">
            <?= Icon::svg('anterior', 'w-4 h-4') ?>
        </a>
        <div>
            <h1 class="titulo-pagina">
                <?= $titular ? e($titular['nome']) : 'Declaração de saúde' ?>
            </h1>
            <p class="sub-pagina">
                Contrato <?= e($d['numero_proposta'] ?: '#' . (int) $d['contrato_id']) ?> ·
                preenchida em <?= e(data_br($d['data_preenchimento'])) ?>
            </p>
        </div>
    </div>

    <div class="flex flex-wrap items-center gap-2">
        <span class="badge <?= $d['status'] === 'concluida' ? 'badge-verde' : 'badge-ambar' ?>">
            <?= e(DeclaracaoModel::STATUS[$d['status']] ?? $d['status']) ?>
        </span>

        <a href="<?= url('declaracoes', 'imprimir', ['id' => (int) $d['id']]) ?>"
           class="btn btn-outline" target="_blank">
            <?= Icon::svg('imprimir', 'w-4 h-4') ?> Imprimir
        </a>

        <?php if ($podeEditar): ?>
            <a href="<?= url('declaracoes', 'editar', ['id' => (int) $d['id']]) ?>" class="btn btn-primary">
                <?= Icon::svg('editar', 'w-4 h-4') ?> Editar
            </a>
        <?php endif; ?>
    </div>
</div>

<?php if ($incompletos && $d['status'] === 'concluida'): ?>
    <div class="mb-4 flex items-start gap-2 rounded-lg border border-amber-200 bg-amber-50/60 px-3 py-2 text-[0.8125rem] text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/5 dark:text-amber-300">
        <?= Icon::svg('aviso', 'w-4 h-4 mt-0.5 shrink-0') ?>
        <span>
            Declaração concluída com questionário incompleto em
            <strong><?= numero(count($incompletos)) ?></strong>
            pessoa<?= count($incompletos) === 1 ? '' : 's' ?>. Confira antes de
            colher a assinatura — item em branco não é a mesma coisa que "não".
        </span>
    </div>
<?php endif; ?>

<div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_20rem] lg:items-start">

    <div class="space-y-4">
        <div class="card overflow-hidden">
            <div class="card-header">
                <h2 class="card-title">Pessoas na declaração</h2>
                <span class="text-xs text-slate-500 dark:text-slate-400">
                    <?= numero(count($resumo)) ?> no contrato
                </span>
            </div>

            <?php if (empty($resumo)): ?>
                <div class="vazio">
                    <span class="vazio-icone"><?= Icon::svg('usuarios', 'w-5 h-5') ?></span>
                    <p class="vazio-titulo">Nenhuma pessoa ativa no contrato</p>
                    <p class="vazio-texto">
                        A declaração é preenchida pelas pessoas do contrato. Cadastre-as
                        primeiro em Cadastro de Beneficiários.
                    </p>
                </div>
            <?php else: ?>
                <div class="divide-y divide-slate-100 dark:divide-slate-800">
                    <?php foreach ($resumo as $r): ?>
                        <div class="px-4 py-3">
                            <div class="flex flex-wrap items-start justify-between gap-2">
                                <div>
                                    <span class="celula-principal block"><?= e($r['nome']) ?></span>
                                    <span class="text-xs text-slate-500 dark:text-slate-400">
                                        <?= $r['tipo'] === 'titular' ? 'Titular' : 'Dependente' ?>
                                        ·
                                        <?php if ($r['faltam'] === 0): ?>
                                            <span class="text-emerald-600 dark:text-emerald-400">questionário completo</span>
                                        <?php else: ?>
                                            <span class="text-amber-600 dark:text-amber-400">
                                                faltam <?= numero($r['faltam']) ?> de <?= count(DeclaracaoModel::ITENS) ?>
                                            </span>
                                        <?php endif; ?>
                                    </span>
                                </div>

                                <div class="flex items-center gap-2">
                                    <?php if ($r['tem_dlp']): ?>
                                        <span class="badge badge-ambar">
                                            <?= numero(count($r['declarados'])) ?>
                                            declarada<?= count($r['declarados']) === 1 ? '' : 's' ?>
                                        </span>
                                    <?php elseif ($r['faltam'] === 0): ?>
                                        <span class="badge badge-verde">Nada a declarar</span>
                                    <?php endif; ?>

                                    <?php if ($podeEditar): ?>
                                        <a href="<?= url('declaracoes', 'responder', [
                                                'declaracao' => (int) $d['id'], 'pessoa' => $r['id'],
                                           ]) ?>" class="btn btn-outline btn-sm">
                                            <?= $r['faltam'] === 0 ? 'Revisar' : 'Responder' ?>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <?php /* O que foi declarado fica à vista: é a informação que
                                     decide a CPT, e abrir pessoa por pessoa para vê-la
                                     seria esconder justamente o que importa. */ ?>
                            <?php if ($r['declarados']): ?>
                                <ul class="mt-2 space-y-1 border-l-2 border-amber-200 pl-3 dark:border-amber-500/30">
                                    <?php foreach ($r['declarados'] as $dec): ?>
                                        <li class="text-[0.8125rem]">
                                            <span class="font-medium text-slate-700 dark:text-slate-300">
                                                <?= e($dec['titulo']) ?>
                                            </span>
                                            <?php if ($dec['descricao']): ?>
                                                <span class="block text-xs text-slate-500 dark:text-slate-400">
                                                    <?= e($dec['descricao']) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="block text-xs text-amber-600 dark:text-amber-400">
                                                    sem descrição — item <?= (int) $dec['item'] ?>
                                                </span>
                                            <?php endif; ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>

                                <?php if ($r['cpt_ate']): ?>
                                    <p class="mt-2 text-xs <?= $r['cpt_vigente']
                                            ? 'text-amber-700 dark:text-amber-400'
                                            : 'text-slate-500 dark:text-slate-400' ?>">
                                        Cobertura parcial temporária
                                        <?= $r['cpt_vigente'] ? 'até' : 'encerrada em' ?>
                                        <strong><?= e(data_br($r['cpt_ate'])) ?></strong>
                                    </p>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($podeExcluir && $d['status'] !== 'concluida'): ?>
            <form method="post" action="<?= url('declaracoes', 'excluir') ?>"
                  data-titulo="Excluir declaração de saúde"
                  data-texto="A declaração e todas as respostas serão removidas. Esta ação não pode ser desfeita.">
                <?= Csrf::campo() ?>
                <input type="hidden" name="id" value="<?= (int) $d['id'] ?>">
                <button type="button" @click="abrir" class="btn btn-outline btn-sm text-rose-600 dark:text-rose-400">
                    <?= Icon::svg('excluir', 'w-3.5 h-3.5') ?> Excluir declaração
                </button>
            </form>
        <?php endif; ?>
    </div>

    <div class="space-y-4">
        <!-- CPT -->
        <div class="card">
            <div class="card-header"><h2 class="card-title">Cobertura parcial temporária</h2></div>
            <div class="card-corpo">
                <?php if (empty($comDlp)): ?>
                    <p class="text-[0.8125rem] text-slate-600 dark:text-slate-400">
                        Ninguém declarou doença ou lesão preexistente
                        <?= $incompletos ? 'entre o que já foi respondido' : '' ?>.
                        Sem DLP declarada não há CPT.
                    </p>
                <?php else: ?>
                    <p class="rotulo-num">Pessoas em CPT</p>
                    <p class="valor-num <?= $cptVigente ? 'text-amber-600 dark:text-amber-400' : '' ?>">
                        <?= numero(count($comDlp)) ?>
                    </p>

                    <?php if ($fimCpt): ?>
                        <p class="mt-1 text-[0.8125rem] text-slate-600 dark:text-slate-400">
                            <?= $cptVigente ? 'Corre até' : 'Encerrou em' ?>
                            <strong><?= e(data_br($fimCpt)) ?></strong>
                        </p>
                    <?php else: ?>
                        <p class="mt-1 text-[0.8125rem] text-amber-700 dark:text-amber-400">
                            Sem data de contratação, o sistema não calcula o fim da CPT.
                        </p>
                    <?php endif; ?>
                <?php endif; ?>

                <div class="mt-3 border-t border-slate-100 pt-3 text-xs text-slate-500 dark:border-slate-800 dark:text-slate-400">
                    A CPT suspende, por até <?= DeclaracaoModel::MESES_CPT ?> meses da
                    contratação, apenas procedimentos de alta complexidade, leitos de alta
                    tecnologia e cirurgias <strong>ligados à doença declarada</strong>. O
                    resto da cobertura segue normal.
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h2 class="card-title">O documento</h2></div>
            <div class="card-corpo space-y-1">
                <?php
                $campos = [
                    'Contrato'      => $d['numero_proposta'] ?: '#' . (int) $d['contrato_id'],
                    'Tipo'          => Elegibilidade::TIPOS_CONTRATO[$d['tipo_contrato']] ?? null,
                    'Preenchimento' => data_br($d['data_preenchimento']),
                    'Contratação'   => $d['data_contratacao'] ? data_br($d['data_contratacao']) : null,
                    'Orientação'    => DeclaracaoModel::ORIENTACOES[$d['orientacao']] ?? null,
                    'Médico'        => $d['medico_nome'],
                    'CRM'           => $d['medico_crm'],
                    'Assinada por'  => $d['assinante_nome'],
                    'Assinada em'   => $d['data_assinatura'] ? data_br($d['data_assinatura']) : null,
                    'Observação'    => $d['observacao'],
                ];
                foreach ($campos as $rotulo => $valor): ?>
                    <div class="flex flex-wrap items-baseline gap-x-2 py-1">
                        <span class="w-28 shrink-0 text-xs text-slate-500 dark:text-slate-400"><?= e($rotulo) ?></span>
                        <span class="text-[0.8125rem]">
                            <?php if ($valor !== null && $valor !== ''): ?>
                                <span class="text-slate-800 dark:text-slate-200"><?= e((string) $valor) ?></span>
                            <?php else: ?>
                                <span class="text-slate-400 dark:text-slate-600">—</span>
                            <?php endif; ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="border-t border-slate-100 px-4 py-2.5 text-xs text-slate-500 dark:border-slate-800 dark:text-slate-400">
                <a href="<?= url('cadastro', 'ver', ['id' => (int) $d['contrato_id']]) ?>"
                   class="text-marine-700 hover:underline dark:text-marine-300">Abrir o contrato</a>
                · lançada por <?= e($d['usuario_nome'] ?: '—') ?>
            </div>
        </div>
    </div>
</div>
