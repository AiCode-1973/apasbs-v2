<?php
/**
 * O trabalhador por dentro: o cadastro e toda a vida ocupacional dele.
 *
 * É a tela que a pasta de papel nunca deu — admissional, periódicos e
 * demissional na mesma ordem em que aconteceram.
 *
 * @var array $t, $historico
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

$hoje = date('Y-m-d');

// A validade que vale é a da ficha mais recente que tem uma.
$validade = null;
foreach ($historico as $h) {
    if ($h['validade']) {
        $validade = $h['validade'];
        break;
    }
}
$venceu = $validade !== null && $validade < $hoje;
?>

<div class="mb-5 flex flex-wrap items-start justify-between gap-3">
    <div class="flex items-start gap-3">
        <a href="<?= url('ocupacional', 'trabalhadores') ?>" class="btn-acao mt-0.5" aria-label="Voltar">
            <?= Icon::svg('anterior', 'w-4 h-4') ?>
        </a>
        <div>
            <h1 class="titulo-pagina"><?= e($t['nome']) ?></h1>
            <p class="sub-pagina">
                <?= e($t['empresa']) ?>
                <?= $t['funcao'] ? ' · ' . e($t['funcao']) : '' ?>
                <?= !$t['ativo'] ? ' · desligado' : '' ?>
            </p>
        </div>
    </div>

    <div class="flex flex-wrap items-center gap-2">
        <?php if ($podeEditar): ?>
            <a href="<?= url('ocupacional', 'trabalhadores', ['id' => (int) $t['id']]) ?>" class="btn btn-outline">
                <?= Icon::svg('editar', 'w-4 h-4') ?> Editar cadastro
            </a>
        <?php endif; ?>
        <?php if ($podeCriar): ?>
            <a href="<?= url('ocupacional', 'novo_atestado', ['trabalhador' => (int) $t['id']]) ?>"
               class="btn btn-outline">
                <?= Icon::svg('novo', 'w-4 h-4') ?> Entregar atestado
            </a>
            <a href="<?= url('ocupacional', 'novo', ['trabalhador' => (int) $t['id']]) ?>" class="btn btn-primary">
                <?= Icon::svg('novo', 'w-4 h-4') ?> Nova ficha
            </a>
        <?php endif; ?>
    </div>
</div>

<?php if ($venceu): ?>
    <div class="mb-4 flex items-center gap-2 rounded-lg border border-rose-200 bg-rose-50/60 px-3 py-2 text-[0.8125rem] text-rose-800 dark:border-rose-500/30 dark:bg-rose-500/5 dark:text-rose-300">
        <?= Icon::svg('aviso', 'w-4 h-4 shrink-0') ?>
        <span>Exame vencido em <strong><?= e(data_br($validade)) ?></strong> — precisa ser refeito.</span>
    </div>
<?php endif; ?>

<div class="grid gap-4 lg:grid-cols-[22rem_minmax(0,1fr)] lg:items-start">

    <div class="card">
        <div class="card-header"><h2 class="card-title">Cadastro</h2></div>
        <div class="card-corpo space-y-1">
            <?php
            $campos = [
                'CPF'        => $t['cpf'] ? formatar_cpf((string) $t['cpf']) : null,
                'Matrícula'  => $t['matricula'] ?? null,
                'Nascimento' => $t['nascimento'] ? data_br($t['nascimento']) : null,
                'Empresa'    => $t['empresa'],
                'Função'     => $t['funcao'],
                'Setor'      => $t['setor'],
                'Admissão'   => $t['admissao'] ? data_br($t['admissao']) : null,
                'Observação' => $t['observacao'],
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

        <?php /* A exclusão só aparece para quem não tem ficha. Com prontuário,
                 o controller recusa e explica o porquê — mas oferecer o botão
                 seria prometer uma ação que vai ser negada. */ ?>
        <?php if ($podeExcluir && empty($historico)): ?>
            <div class="border-t border-slate-100 px-4 py-3 dark:border-slate-800">
                <form method="post" action="<?= url('ocupacional', 'excluir_trabalhador') ?>"
                      data-titulo="Excluir trabalhador"
                      data-texto="<?= e($t['nome']) ?> será removido do cadastro. Esta ação não pode ser desfeita.">
                    <?= Csrf::campo() ?>
                    <input type="hidden" name="id" value="<?= (int) $t['id'] ?>">
                    <button type="button" @click="abrir" class="btn btn-outline btn-sm text-rose-600 dark:text-rose-400">
                        <?= Icon::svg('excluir', 'w-3.5 h-3.5') ?> Excluir cadastro
                    </button>
                </form>
            </div>
        <?php elseif ($podeExcluir): ?>
            <div class="border-t border-slate-100 px-4 py-2.5 text-xs text-slate-500 dark:border-slate-800 dark:text-slate-400">
                Com ficha lançada, o cadastro não pode ser excluído — o prontuário
                precisa ser guardado por 20 anos. Para tirar de circulação, desmarque
                <strong>Ativo</strong> no cadastro.
            </div>
        <?php endif; ?>
    </div>

    <div class="card overflow-hidden">
        <div class="card-header">
            <h2 class="card-title">Vida ocupacional</h2>
            <span class="text-xs text-slate-500 dark:text-slate-400">
                <?= numero(count($historico)) ?> ficha<?= count($historico) === 1 ? '' : 's' ?>
            </span>
        </div>

        <?php if (empty($historico)): ?>
            <div class="vazio">
                <span class="vazio-icone"><?= Icon::svg('ocupacional', 'w-5 h-5') ?></span>
                <p class="vazio-titulo">Nenhuma ficha ainda</p>
                <p class="vazio-texto">O primeiro exame costuma ser o admissional.</p>
                <?php if ($podeCriar): ?>
                    <a href="<?= url('ocupacional', 'novo', ['trabalhador' => (int) $t['id']]) ?>"
                       class="btn btn-primary btn-sm mt-1">
                        <?= Icon::svg('novo', 'w-3.5 h-3.5') ?> Registrar o admissional
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="tabela">
                    <thead>
                        <tr>
                            <th class="w-28">Exame em</th>
                            <th class="w-40">Tipo</th>
                            <th class="w-28">Validade</th>
                            <th>Conclusão</th>
                            <th class="w-20 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($historico as $h): ?>
                            <tr>
                                <td class="celula-suave text-xs"><?= e(data_br($h['data_exame'])) ?></td>
                                <td>
                                    <a href="<?= url('ocupacional', 'ver', ['id' => (int) $h['id']]) ?>"
                                       class="celula-principal hover:underline">
                                        <?= e(OcupacionalModel::TIPOS[$h['tipo']] ?? $h['tipo']) ?>
                                    </a>
                                </td>
                                <td class="text-xs">
                                    <?php if (!$h['validade']): ?>
                                        <span class="text-slate-400 dark:text-slate-600">—</span>
                                    <?php elseif ($h['validade'] < $hoje): ?>
                                        <span class="text-rose-600 dark:text-rose-400"><?= e(data_br($h['validade'])) ?></span>
                                    <?php else: ?>
                                        <span class="celula-suave"><?= e(data_br($h['validade'])) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge <?= $cores[$h['conclusao']] ?? 'badge-cinza' ?>">
                                        <?= e(OcupacionalModel::CONCLUSOES[$h['conclusao']] ?? $h['conclusao']) ?>
                                    </span>
                                    <?php if ($h['conclusao'] === 'apto_restricao' && $h['restricoes']): ?>
                                        <span class="mt-0.5 block text-[0.6875rem] text-slate-500 dark:text-slate-400">
                                            <?= e($h['restricoes']) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="flex items-center justify-end gap-0.5">
                                        <a href="<?= url('ocupacional', 'imprimir', ['id' => (int) $h['id']]) ?>"
                                           class="btn-acao" title="Imprimir" target="_blank"
                                           aria-label="Imprimir a ficha de <?= e(data_br($h['data_exame'])) ?>">
                                            <?= Icon::svg('imprimir', 'w-4 h-4') ?>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
