<?php
/**
 * A entrega por dentro — e as entregas anteriores da mesma pessoa ao lado.
 *
 * A coluna da direita é o motivo de abrir aqui em vez de só imprimir: é nela
 * que aparece o colaborador que entrega atestado todo mês.
 *
 * @var array $a, $anteriores
 */
declare(strict_types=1);

$podeEditar  = Auth::temPermissao('ocupacional', 'pode_editar');
$podeExcluir = Auth::temPermissao('ocupacional', 'pode_excluir');

$hoje     = date('Y-m-d');
$afastado = $a['retorno'] && $a['retorno'] >= $hoje;

/** Uma linha "rótulo: valor", com travessão quando não há valor. */
$linha = function (string $rotulo, ?string $valor, bool $mono = false): string {
    $conteudo = ($valor !== null && $valor !== '')
        ? '<span class="' . ($mono ? 'mono ' : '') . 'text-slate-800 dark:text-slate-200">' . e($valor) . '</span>'
        : '<span class="text-slate-400 dark:text-slate-600">—</span>';

    return '<div class="flex flex-wrap items-baseline gap-x-2 py-1">'
         . '<span class="w-44 shrink-0 text-xs text-slate-500 dark:text-slate-400">' . e($rotulo) . '</span>'
         . '<span class="text-[0.8125rem]">' . $conteudo . '</span></div>';
};

$forma = OcupacionalModel::FORMAS[$a['forma']] ?? $a['forma'];
if ($a['forma'] === 'outro' && $a['forma_outro']) {
    $forma .= ' — ' . $a['forma_outro'];
}
?>

<div class="mb-5 flex flex-wrap items-start justify-between gap-3">
    <div class="flex items-start gap-3">
        <a href="<?= url('ocupacional', 'atestados') ?>" class="btn-acao mt-0.5" aria-label="Voltar">
            <?= Icon::svg('anterior', 'w-4 h-4') ?>
        </a>
        <div>
            <h1 class="titulo-pagina"><?= e($a['trabalhador']) ?></h1>
            <p class="sub-pagina">
                Protocolo <span class="mono"><?= e($a['protocolo']) ?></span> ·
                entregue em <?= e(data_br($a['entrega_data'])) ?>
                <?= $a['entrega_hora'] ? ' às ' . e(hora_curta((string) $a['entrega_hora'])) : '' ?>
            </p>
        </div>
    </div>

    <div class="flex flex-wrap items-center gap-2">
        <a href="<?= url('ocupacional', 'imprimir_atestado', ['id' => (int) $a['id']]) ?>"
           class="btn btn-outline" target="_blank">
            <?= Icon::svg('imprimir', 'w-4 h-4') ?> Imprimir comprovante
        </a>
        <?php if ($podeEditar): ?>
            <a href="<?= url('ocupacional', 'editar_atestado', ['id' => (int) $a['id']]) ?>" class="btn btn-primary">
                <?= Icon::svg('editar', 'w-4 h-4') ?> Editar
            </a>
        <?php endif; ?>
    </div>
</div>

<?php if ($afastado): ?>
    <div class="mb-4 flex items-center gap-2 rounded-lg border border-amber-200 bg-amber-50/70 px-3 py-2 text-[0.8125rem] text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/5 dark:text-amber-300">
        <?= Icon::svg('aviso', 'w-4 h-4 shrink-0') ?>
        <span>Afastamento em curso — retorno previsto para <strong><?= e(data_br($a['retorno'])) ?></strong>.</span>
    </div>
<?php endif; ?>

<?php if (!$a['recebido_por']): ?>
    <div class="mb-4 flex items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-[0.8125rem] text-slate-600 dark:border-slate-800 dark:bg-slate-900/60 dark:text-slate-400">
        <?= Icon::svg('aviso', 'w-4 h-4 shrink-0') ?>
        <span>
            Ninguém consta como quem recebeu. O comprovante sai com a linha em branco
            para assinar à mão<?= $podeEditar ? ' — dá para completar pela edição' : '' ?>.
        </span>
    </div>
<?php endif; ?>

<div class="grid gap-4 lg:grid-cols-[1.6fr_1fr]">
    <div class="space-y-4">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Colaborador</h2>
            </div>
            <div class="card-corpo">
                <?= $linha('Nome', $a['trabalhador']) ?>
                <?= $linha('CPF', $a['cpf'] ? formatar_cpf((string) $a['cpf']) : '', true) ?>
                <?= $linha('Matrícula', $a['matricula']) ?>
                <?= $linha('Setor', $a['setor']) ?>
                <?= $linha('Cargo / função', $a['funcao']) ?>
                <?= $linha('Empresa', $a['empresa']) ?>
            </div>
            <div class="border-t border-slate-100 px-4 py-2.5 text-xs dark:border-slate-800">
                <a href="<?= url('ocupacional', 'ver_trabalhador', ['id' => (int) $a['trabalhador_id']]) ?>"
                   class="text-marine-700 hover:underline dark:text-marine-300">
                    Abrir o cadastro do colaborador →
                </a>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Atestado</h2>
            </div>
            <div class="card-corpo">
                <?= $linha('Data de emissão', data_br($a['emissao'], '')) ?>
                <?= $linha('Início do afastamento', data_br($a['inicio'], '')) ?>
                <?= $linha('Quantidade de dias', $a['dias'] ? numero((int) $a['dias']) : '') ?>
                <?= $linha('Retorno previsto', data_br($a['retorno'], '')) ?>
                <?= $linha('Médico / profissional', $a['medico_nome']) ?>
                <?= $linha('CRM / registro', $a['medico_registro']) ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Entrega e recebimento</h2>
            </div>
            <div class="card-corpo">
                <?= $linha('Data da entrega', data_br($a['entrega_data'], '')) ?>
                <?= $linha('Horário da entrega', $a['entrega_hora'] ? hora_curta((string) $a['entrega_hora']) : '') ?>
                <?= $linha('Forma de entrega', $forma) ?>
                <?= $linha('Recebido por', $a['recebido_por']) ?>
                <?= $linha('Data do recebimento', data_br($a['recebido_data'], '')) ?>
                <?= $linha('Horário do recebimento', $a['recebido_hora'] ? hora_curta((string) $a['recebido_hora']) : '') ?>
                <?= $linha('Observação', $a['observacao']) ?>
            </div>

            <div class="border-t border-slate-100 px-4 py-2.5 text-[0.6875rem] text-slate-400 dark:border-slate-800 dark:text-slate-500">
                Registrado por <?= e($a['usuario_nome'] ?: '—') ?> em <?= e(datahora_br($a['created_at'])) ?>.
                O documento comprova a entrega e o recebimento — não, isoladamente, a
                validação do afastamento.
            </div>

            <?php if ($podeExcluir): ?>
                <div class="border-t border-slate-100 px-4 py-3 dark:border-slate-800">
                    <form method="post" action="<?= url('ocupacional', 'excluir_atestado') ?>"
                          data-titulo="Excluir entrega de atestado"
                          data-texto="A entrega <?= e($a['protocolo']) ?>, de <?= e($a['trabalhador']) ?>, será removida. O comprovante já pode estar impresso na mão do colaborador, e o número não será reaproveitado. Exclua apenas se foi lançada por engano.">
                        <?= Csrf::campo() ?>
                        <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
                        <button type="button" @click="abrir" class="btn btn-outline btn-sm text-rose-600 dark:text-rose-400">
                            <?= Icon::svg('excluir', 'w-3.5 h-3.5') ?> Excluir entrega
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card self-start">
        <div class="card-header">
            <h2 class="card-title">Entregas deste colaborador</h2>
        </div>

        <?php if (count($anteriores) <= 1): ?>
            <div class="px-4 py-6 text-center text-[0.8125rem] text-slate-500 dark:text-slate-400">
                Primeira entrega registrada.
            </div>
        <?php else: ?>
            <ul class="divide-y divide-slate-100 dark:divide-slate-800">
                <?php foreach ($anteriores as $o): ?>
                    <?php $atual = (int) $o['id'] === (int) $a['id']; ?>
                    <li class="px-4 py-2.5 <?= $atual ? 'bg-slate-50 dark:bg-slate-800/50' : '' ?>">
                        <a href="<?= url('ocupacional', 'ver_atestado', ['id' => (int) $o['id']]) ?>"
                           class="flex items-baseline justify-between gap-2 text-[0.8125rem] <?= $atual ? '' : 'hover:underline' ?>">
                            <span class="mono text-xs text-slate-500 dark:text-slate-400"><?= e($o['protocolo']) ?></span>
                            <span class="text-slate-700 dark:text-slate-300"><?= e(data_br($o['entrega_data'])) ?></span>
                            <span class="text-xs text-slate-500 dark:text-slate-400">
                                <?= $o['dias'] ? numero((int) $o['dias']) . ' d' : '—' ?>
                            </span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>
