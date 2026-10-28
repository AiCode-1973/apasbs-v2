<?php
/**
 * A ficha por dentro — e o histórico do trabalhador ao lado.
 *
 * O valor de abrir aqui em vez de imprimir é a coluna da direita: dá para
 * comparar esta ficha com os exames anteriores da mesma pessoa, que é o que
 * o acompanhamento do PCMSO pede.
 *
 * @var array $f, $historico
 */
declare(strict_types=1);

$podeEditar  = Auth::temPermissao('ocupacional', 'pode_editar');
$podeExcluir = Auth::temPermissao('ocupacional', 'pode_excluir');

$cores = [
    'pendente'       => 'badge-ambar',
    'apto'           => 'badge-verde',
    'apto_restricao' => 'badge-ceu',
    'inapto'         => 'badge-rosa',
];

$hoje   = date('Y-m-d');
$venceu = $f['validade'] && $f['validade'] < $hoje;
$valor  = imc($f['peso'], $f['altura']);

/** Uma linha "rótulo: valor", com travessão quando não há valor. */
$linha = function (string $rotulo, ?string $valor, bool $mono = false): string {
    $conteudo = ($valor !== null && $valor !== '')
        ? '<span class="' . ($mono ? 'mono ' : '') . 'text-slate-800 dark:text-slate-200">' . e($valor) . '</span>'
        : '<span class="text-slate-400 dark:text-slate-600">—</span>';

    return '<div class="flex flex-wrap items-baseline gap-x-2 py-1">'
         . '<span class="w-44 shrink-0 text-xs text-slate-500 dark:text-slate-400">' . e($rotulo) . '</span>'
         . '<span class="text-[0.8125rem]">' . $conteudo . '</span></div>';
};

$riscos = [];
foreach (OcupacionalModel::RISCOS as $campo => $rotulo) {
    if (!empty($f[$campo])) {
        $riscos[] = $rotulo;
    }
}
?>

<div class="mb-5 flex flex-wrap items-start justify-between gap-3">
    <div class="flex items-start gap-3">
        <a href="<?= url_lista('ocupacional') ?>" class="btn-acao mt-0.5" aria-label="Voltar">
            <?= Icon::svg('anterior', 'w-4 h-4') ?>
        </a>
        <div>
            <h1 class="titulo-pagina"><?= e($f['trabalhador']) ?></h1>
            <p class="sub-pagina">
                <?= e(OcupacionalModel::TIPOS[$f['tipo']] ?? $f['tipo']) ?>
                em <?= e(data_br($f['data_exame'])) ?> · <?= e($f['empresa']) ?>
            </p>
        </div>
    </div>

    <div class="flex flex-wrap items-center gap-2">
        <a href="<?= url('ocupacional', 'imprimir', ['id' => (int) $f['id']]) ?>"
           class="btn btn-outline" target="_blank">
            <?= Icon::svg('imprimir', 'w-4 h-4') ?> Imprimir
        </a>
        <?php if ($podeEditar): ?>
            <a href="<?= url('ocupacional', 'editar', ['id' => (int) $f['id']]) ?>" class="btn btn-primary">
                <?= Icon::svg('editar', 'w-4 h-4') ?> Editar
            </a>
        <?php endif; ?>
    </div>
</div>

<?php if ($venceu): ?>
    <div class="mb-4 flex items-center gap-2 rounded-lg border border-rose-200 bg-rose-50/60 px-3 py-2 text-[0.8125rem] text-rose-800 dark:border-rose-500/30 dark:bg-rose-500/5 dark:text-rose-300">
        <?= Icon::svg('aviso', 'w-4 h-4 shrink-0') ?>
        <span>Validade vencida em <strong><?= e(data_br($f['validade'])) ?></strong> — o exame precisa ser refeito.</span>
    </div>
<?php endif; ?>

<div class="grid gap-4 lg:grid-cols-[1.6fr_1fr]">
    <div class="space-y-4">

        <!-- Conclusão primeiro: é o que quase todo mundo vem ver -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Conclusão médica</h2>
                <span class="badge <?= $cores[$f['conclusao']] ?? 'badge-cinza' ?>">
                    <?= e(OcupacionalModel::CONCLUSOES[$f['conclusao']] ?? $f['conclusao']) ?>
                </span>
            </div>
            <div class="card-corpo">
                <?= $linha('Encaminhado ao médico', $f['encaminhado_em'] ? data_br($f['encaminhado_em']) : null) ?>
                <?= $linha('Validade', $f['validade'] ? data_br($f['validade']) : null) ?>
                <?php if ($f['conclusao'] === 'apto_restricao'): ?>
                    <?= $linha('Restrição', $f['restricoes']) ?>
                <?php endif; ?>
                <?= $linha('Médico do trabalho', $f['medico_nome']) ?>
                <?= $linha('CRM', $f['medico_crm'], true) ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h2 class="card-title">Identificação</h2></div>
            <div class="card-corpo">
                <?= $linha('Nome', $f['trabalhador']) ?>
                <?= $linha('CPF', $f['cpf'] ? formatar_cpf((string) $f['cpf']) : null, true) ?>
                <?= $linha('Nascimento', $f['nascimento'] ? data_br($f['nascimento']) : null) ?>
                <?= $linha('Empresa', $f['empresa']) ?>
                <?= $linha('Função', $f['funcao']) ?>
                <?= $linha('Setor', $f['setor']) ?>
                <?= $linha('Admissão', $f['admissao'] ? data_br($f['admissao']) : null) ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h2 class="card-title">Anamnese ocupacional / riscos (PGR)</h2></div>
            <div class="card-corpo">
                <?= $linha('Histórico profissional', $f['hist_profissional']) ?>
                <?= $linha('Riscos', $riscos ? implode(' · ', $riscos) : null) ?>
                <?= $linha('Usa EPI', $f['epi'] === null ? null : ((int) $f['epi'] === 1 ? 'Sim' : 'Não')) ?>
                <?= $linha('Antecedentes', $f['antecedentes']) ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h2 class="card-title">Exame físico — triagem de enfermagem</h2></div>
            <div class="card-corpo">
                <?= $linha('Pressão arterial', $f['pa_sistolica'] && $f['pa_diastolica']
                        ? $f['pa_sistolica'] . ' × ' . $f['pa_diastolica'] . ' mmHg' : null) ?>
                <?= $linha('Frequência cardíaca', $f['fc'] ? $f['fc'] . ' bpm' : null) ?>
                <?= $linha('Peso', $f['peso'] ? number_format((float) $f['peso'], 1, ',', '') . ' kg' : null) ?>
                <?= $linha('Altura', $f['altura'] ? number_format((float) $f['altura'], 2, ',', '') . ' m' : null) ?>
                <?= $linha('IMC', $valor !== null
                        ? number_format($valor, 1, ',', '') . ' — ' . imc_faixa($valor) : null) ?>
                <?= $linha('Acuidade visual', ($f['acuidade_od'] || $f['acuidade_oe'])
                        ? 'OD ' . ($f['acuidade_od'] ?: '—') . ' · OE ' . ($f['acuidade_oe'] ?: '—') : null) ?>
                <?= $linha('Queixas / exame', $f['queixas']) ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h2 class="card-title">Exames complementares</h2></div>
            <div class="card-corpo">
                <?= $linha('Audiometria', $f['audiometria_em'] ? data_br($f['audiometria_em']) : null) ?>
                <?= $linha('Espirometria', $f['espirometria_em'] ? data_br($f['espirometria_em']) : null) ?>
                <?= $linha('Laboratorial', $f['laboratorial_em'] ? data_br($f['laboratorial_em']) : null) ?>
                <?= $linha('Outros', $f['outros_exames']) ?>
                <?= $linha('Observações da enfermagem', $f['obs_enfermagem']) ?>
                <?= $linha('Enfermeiro', $f['enfermeiro_nome']) ?>
                <?= $linha('COREN', $f['enfermeiro_coren'], true) ?>
                <?= $linha('Ciência do trabalhador', $f['ciencia_em'] ? data_br($f['ciencia_em']) : null) ?>
            </div>
        </div>

        <?php if ($podeExcluir): ?>
            <form method="post" action="<?= url('ocupacional', 'excluir') ?>"
                  data-titulo="Excluir ficha de saúde ocupacional"
                  data-texto="A ficha de <?= e($f['trabalhador']) ?> será removida. Prontuário deve ser guardado por 20 anos — exclua apenas se foi lançado por engano. Esta ação não pode ser desfeita.">
                <?= Csrf::campo() ?>
                <input type="hidden" name="id" value="<?= (int) $f['id'] ?>">
                <button type="button" @click="abrir" class="btn btn-outline btn-sm text-rose-600 dark:text-rose-400">
                    <?= Icon::svg('excluir', 'w-3.5 h-3.5') ?> Excluir esta ficha
                </button>
            </form>
        <?php endif; ?>
    </div>

    <!-- O histórico da pessoa: o que a ficha de papel nunca mostrou junto -->
    <div class="card self-start">
        <div class="card-header">
            <h2 class="card-title">Histórico do trabalhador</h2>
            <a href="<?= url('ocupacional', 'ver_trabalhador', ['id' => (int) $f['trabalhador_id']]) ?>"
               class="text-xs text-marine-700 hover:underline dark:text-marine-300">Abrir cadastro</a>
        </div>

        <div class="divide-y divide-slate-100 dark:divide-slate-800">
            <?php foreach ($historico as $h): ?>
                <?php $atual = (int) $h['id'] === (int) $f['id']; ?>
                <a href="<?= url('ocupacional', 'ver', ['id' => (int) $h['id']]) ?>"
                   class="flex items-center justify-between gap-2 px-4 py-2.5 text-[0.8125rem] transition-colors
                          <?= $atual ? 'bg-marine-50 dark:bg-marine-500/10' : 'hover:bg-slate-50 dark:hover:bg-slate-800/60' ?>">
                    <span>
                        <span class="<?= $atual ? 'font-semibold' : '' ?>">
                            <?= e(OcupacionalModel::TIPOS[$h['tipo']] ?? $h['tipo']) ?>
                        </span>
                        <span class="block text-xs text-slate-500 dark:text-slate-400">
                            <?= e(data_br($h['data_exame'])) ?>
                            <?= $atual ? ' · esta ficha' : '' ?>
                        </span>
                    </span>
                    <span class="badge shrink-0 <?= $cores[$h['conclusao']] ?? 'badge-cinza' ?>">
                        <?= e(OcupacionalModel::CONCLUSOES[$h['conclusao']] ?? $h['conclusao']) ?>
                    </span>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="border-t border-slate-100 px-4 py-2 text-xs text-slate-500 dark:border-slate-800 dark:text-slate-400">
            Lançada por <?= e($f['usuario_nome'] ?: '—') ?>
            em <?= e(data_br(substr((string) $f['created_at'], 0, 10))) ?>
        </div>
    </div>
</div>
