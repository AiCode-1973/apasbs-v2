<?php
/**
 * Marcações de um bloco: quem já está agendado, quem marcou e quanto sobrou.
 *
 * O número de "marcadas" não é digitado em lugar nenhum — é a contagem das
 * linhas desta tela. Por isso ele nunca discorda da lista.
 *
 * @var array $bloco
 * @var array $vagas       exame_id => ['capacidade' => int, 'ocupadas' => int]
 * @var array $exames
 * @var array $marcacoes
 * @var array $motivos
 * @var int   $ano
 * @var int   $mes
 * @var string $volta
 * @var array $erros
 * @var array $preenchido
 */
declare(strict_types=1);

$podeCriar   = Auth::temPermissao('agenda', 'pode_criar');
$podeEditar  = Auth::temPermissao('agenda', 'pode_editar');
$podeExcluir = Auth::temPermissao('agenda', 'pode_excluir');

$blocoId  = (int) $bloco['id'];
$urlVolta = url('agenda', $volta, ['ano' => $ano, 'mes' => $mes]);

$totalCap = 0;
$totalOcu = 0;
foreach ($vagas as $v) {
    $totalCap += $v['capacidade'];
    $totalOcu += $v['ocupadas'];
}

$ativas    = array_filter($marcacoes, fn($m) => $m['status'] === 'marcada');
$canceladas = array_filter($marcacoes, fn($m) => $m['status'] !== 'marcada');

// Tipos que este bloco realmente atende, com o que sobrou de cada um
$exameSel = (int) ($preenchido['exame_id'] ?? 0);
$oferta   = [];
foreach ($exames as $x) {
    $xid = (int) $x['id'];
    if (!isset($vagas[$xid]) || $vagas[$xid]['capacidade'] <= 0) {
        continue;
    }
    $oferta[$xid] = [
        'nome'   => $x['nome'],
        'codigo' => $x['codigo'],
        'cap'    => $vagas[$xid]['capacidade'],
        'ocu'    => $vagas[$xid]['ocupadas'],
        'livres' => max(0, $vagas[$xid]['capacidade'] - $vagas[$xid]['ocupadas']),
    ];
}
$temVagaLivre = false;
foreach ($oferta as $o) {
    if ($o['livres'] > 0) { $temVagaLivre = true; break; }
}
?>

<div class="mb-5 flex flex-wrap items-start justify-between gap-3">
    <div class="flex items-center gap-3">
        <a href="<?= $urlVolta ?>" class="btn btn-icon btn-outline" aria-label="Voltar">
            <?= Icon::svg('voltar', 'w-4 h-4') ?>
        </a>
        <div class="medico-<?= (int) $bloco['medico_cor'] ?>">
            <h1 class="titulo-pagina flex items-center gap-2">
                <span class="med-ponto" aria-hidden="true"></span>
                <?= e($bloco['medico_nome']) ?>
            </h1>
            <p class="sub-pagina">
                <?= e(data_br($bloco['data'])) ?> · <?= e(dia_semana_br($bloco['data'])) ?>
                · <?= e(nome_turno($bloco['turno'])) ?>
                <?php if (!empty($bloco['observacao'])): ?>
                    · <?= e($bloco['observacao']) ?>
                <?php endif; ?>
            </p>
        </div>
    </div>

    <?php if ($podeEditar): ?>
        <a href="<?= url('agenda', 'editar', ['id' => $blocoId, 'ano' => $ano, 'mes' => $mes, 'vista' => $volta]) ?>"
           class="btn btn-outline">
            <?= Icon::svg('editar', 'w-4 h-4') ?> Editar o bloco
        </a>
    <?php endif; ?>
</div>

<!-- Situação das vagas ------------------------------------------------- -->
<div class="card mb-4">
    <div class="card-header">
        <h2 class="card-title">Vagas deste bloco</h2>
        <span class="text-xs text-slate-500 dark:text-slate-400">
            <?= numero($totalOcu) ?> de <?= numero($totalCap) ?> marcadas
        </span>
    </div>

    <div class="card-corpo grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
        <?php foreach ($oferta as $xid => $o): ?>
            <?php $pct = $o['cap'] > 0 ? (int) round($o['ocu'] / $o['cap'] * 100) : 0; ?>
            <div class="rounded-lg border border-slate-200 px-3 py-2.5 dark:border-slate-800">
                <div class="flex items-baseline justify-between gap-2">
                    <span class="text-[0.8125rem] font-semibold text-marine-900 dark:text-white"><?= e($o['nome']) ?></span>
                    <span class="num text-xs text-slate-500 dark:text-slate-400"><?= $o['ocu'] ?>/<?= $o['cap'] ?></span>
                </div>
                <span class="barra <?= $o['livres'] === 0 ? 'barra-cheia' : '' ?> mt-2">
                    <span style="width: <?= min(100, $pct) ?>%"></span>
                </span>
                <span class="mt-1.5 block text-xs <?= $o['livres'] === 0 ? 'font-semibold text-rose-600 dark:text-rose-400' : 'text-slate-500 dark:text-slate-400' ?>">
                    <?= $o['livres'] === 0
                        ? 'Sem vaga — lotado'
                        : $o['livres'] . ' vaga' . ($o['livres'] === 1 ? '' : 's') . ' livre' . ($o['livres'] === 1 ? '' : 's') ?>
                </span>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Marcar -------------------------------------------------------------- -->
<?php if ($podeCriar): ?>
    <div class="card mb-4">
        <div class="card-header">
            <h2 class="card-title">Marcar exame</h2>
            <span class="text-xs text-slate-500 dark:text-slate-400">
                Fica registrado no seu nome: <?= e((string) Auth::nome()) ?>
            </span>
        </div>

        <?php if (!$temVagaLivre): ?>
            <div class="card-corpo">
                <p class="text-[0.8125rem] text-slate-500 dark:text-slate-400">
                    Este bloco está lotado. Para abrir mais vagas, aumente a capacidade
                    em <strong>Editar o bloco</strong> — ou cancele uma marcação abaixo.
                </p>
            </div>
        <?php else: ?>
            <form method="post" action="<?= url('agenda', 'marcar') ?>" class="card-corpo" novalidate
                  x-data="envio" @submit="enviar">
                <?= Csrf::campo() ?>
                <input type="hidden" name="bloco" value="<?= $blocoId ?>">
                <input type="hidden" name="vista" value="<?= e($volta) ?>">

                <?php /* Largura travada: sem o max-w o campo de nome estica até o fim
                         do card e vira uma faixa de mil pixels para digitar 30 caracteres. */ ?>
                <div class="grid max-w-3xl gap-3 md:grid-cols-[11rem_10rem_1fr_auto] md:items-start">
                    <div>
                        <label class="label" for="exame_id">Tipo de exame <span class="obrigatorio">*</span></label>
                        <select name="exame_id" id="exame_id" required
                                class="select <?= isset($erros['exame_id']) ? 'input-error' : '' ?>">
                            <option value="">Selecione…</option>
                            <?php foreach ($oferta as $xid => $o): ?>
                                <option value="<?= $xid ?>"
                                        <?= $exameSel === $xid ? 'selected' : '' ?>
                                        <?= $o['livres'] === 0 ? 'disabled' : '' ?>>
                                    <?= e($o['nome']) ?>
                                    <?= $o['livres'] === 0 ? '— lotado' : '— ' . $o['livres'] . ' livre' . ($o['livres'] === 1 ? '' : 's') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($erros['exame_id'])): ?>
                            <p class="erro-campo"><?= Icon::svg('erro', 'w-3.5 h-3.5') ?> <?= e($erros['exame_id']) ?></p>
                        <?php endif; ?>
                    </div>

                    <div x-data="cpf">
                        <label class="label" for="paciente_cpf">CPF <span class="obrigatorio">*</span></label>
                        <input type="text" name="paciente_cpf" id="paciente_cpf" inputmode="numeric"
                               maxlength="14" required @input="formatar"
                               class="input mono <?= isset($erros['paciente_cpf']) ? 'input-error' : '' ?>"
                               value="<?= e(formatar_cpf((string) ($preenchido['paciente_cpf'] ?? ''))) ?>"
                               placeholder="000.000.000-00">
                        <?php if (isset($erros['paciente_cpf'])): ?>
                            <p class="erro-campo"><?= Icon::svg('erro', 'w-3.5 h-3.5') ?> <?= e($erros['paciente_cpf']) ?></p>
                        <?php endif; ?>
                    </div>

                    <div>
                        <label class="label" for="paciente_nome">Nome da paciente <span class="obrigatorio">*</span></label>
                        <input type="text" name="paciente_nome" id="paciente_nome" maxlength="150" required
                               class="input <?= isset($erros['paciente_nome']) ? 'input-error' : '' ?>"
                               value="<?= e($preenchido['paciente_nome'] ?? '') ?>"
                               placeholder="Nome completo, como está no cartão">
                        <?php if (isset($erros['paciente_nome'])): ?>
                            <p class="erro-campo"><?= Icon::svg('erro', 'w-3.5 h-3.5') ?> <?= e($erros['paciente_nome']) ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="md:pt-[1.4rem]">
                        <button type="submit" class="btn btn-primary w-full md:w-auto" :disabled="enviando">
                            <span x-show="!enviando"><?= Icon::svg('novo', 'w-4 h-4') ?></span>
                            <span x-show="enviando" x-cloak class="girando"></span>
                            Marcar
                        </button>
                    </div>
                </div>

                <div class="mt-3 max-w-3xl">
                    <label class="label" for="observacao">Observação</label>
                    <input type="text" name="observacao" id="observacao" maxlength="300" class="input"
                           value="<?= e($preenchido['observacao'] ?? '') ?>"
                           placeholder="Opcional. Ex.: preparo entregue, paciente prefere ligar de manhã.">
                </div>
            </form>
        <?php endif; ?>
    </div>
<?php endif; ?>

<!-- Lista --------------------------------------------------------------- -->
<div class="card overflow-hidden">
    <div class="card-header">
        <h2 class="card-title">Quem está marcado</h2>
        <span class="text-xs text-slate-500 dark:text-slate-400">
            <?= numero(count($ativas)) ?> ativa<?= count($ativas) === 1 ? '' : 's' ?>
            <?php if ($canceladas): ?>
                · <?= numero(count($canceladas)) ?> cancelada<?= count($canceladas) === 1 ? '' : 's' ?>
            <?php endif; ?>
        </span>
    </div>

    <?php if (empty($marcacoes)): ?>
        <div class="vazio">
            <span class="vazio-icone"><?= Icon::svg('marcacoes', 'w-5 h-5') ?></span>
            <p class="vazio-titulo">Nenhum exame marcado ainda</p>
            <p class="vazio-texto">
                Conforme a central for marcando, cada paciente aparece aqui com o nome
                de quem marcou e a hora. As vagas acima descem sozinhas.
            </p>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="tabela">
                <thead>
                    <tr>
                        <th>Paciente e CPF</th>
                        <th class="w-40">Exame</th>
                        <th class="w-52">Marcado por</th>
                        <th class="w-64">Situação</th>
                        <th class="w-28 text-right">Ações</th>
                    </tr>
                </thead>

                <?php foreach ($marcacoes as $m): ?>
                    <?php
                    $ativa = $m['status'] === 'marcada';
                    $mid   = (int) $m['id'];
                    ?>
                    <tbody x-data="{ cancelando: false, motivo: '<?= $motivos ? '' : 'outro' ?>' }">
                        <tr class="<?= $ativa ? '' : 'opacity-60' ?>">
                            <td>
                                <span class="celula-principal block <?= $ativa ? '' : 'line-through' ?>">
                                    <?= e($m['paciente_nome']) ?>
                                </span>
                                <?php if (!empty($m['paciente_cpf'])): ?>
                                    <span class="mono celula-suave block text-[0.6875rem]">
                                        <?= e(formatar_cpf((string) $m['paciente_cpf'])) ?>
                                    </span>
                                <?php endif; ?>
                                <?php if (!empty($m['observacao'])): ?>
                                    <span class="celula-suave block truncate text-xs" title="<?= e($m['observacao']) ?>">
                                        <?= e($m['observacao']) ?>
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <span class="badge badge-cinza"><?= e($m['exame_codigo']) ?></span>
                            </td>

                            <td class="celula-suave text-xs">
                                <span class="block font-medium text-slate-700 dark:text-slate-300">
                                    <?= e($m['atendente_nome']) ?>
                                </span>
                                <span class="block"><?= datahora_br($m['created_at']) ?></span>
                            </td>

                            <td>
                                <?php if ($ativa): ?>
                                    <span class="badge badge-verde">Marcada</span>
                                <?php else: ?>
                                    <span class="badge badge-rosa">Cancelada</span>
                                    <span class="celula-suave mt-1 block text-xs">
                                        <?= e($m['motivo_nome'] ?? 'Sem motivo registrado') ?>
                                    </span>
                                    <span class="celula-suave block text-[0.6875rem]">
                                        por <?= e($m['cancelado_nome'] ?? '—') ?>
                                        em <?= datahora_br($m['cancelado_em']) ?>
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <div class="flex items-center justify-end gap-0.5">
                                    <?php if ($ativa && $podeEditar): ?>
                                        <button type="button" @click="cancelando = !cancelando"
                                                class="btn-acao btn-acao-perigo" title="Cancelar marcação"
                                                aria-label="Cancelar marcação de <?= e($m['paciente_nome']) ?>">
                                            <?= Icon::svg('cancelar', 'w-4 h-4') ?>
                                        </button>
                                    <?php endif; ?>

                                    <?php if ($podeExcluir): ?>
                                        <form method="post" action="<?= url('agenda', 'excluir_marcacao') ?>" class="inline"
                                              data-titulo="Apagar marcação"
                                              data-texto="A marcação de <?= e($m['paciente_nome']) ?> some do sistema sem deixar histórico. Para desmarcar sem perder o registro, use Cancelar.">
                                            <?= Csrf::campo() ?>
                                            <input type="hidden" name="id" value="<?= $mid ?>">
                                            <input type="hidden" name="vista" value="<?= e($volta) ?>">
                                            <button type="button" @click="abrir" class="btn-acao btn-acao-perigo"
                                                    title="Apagar (erro de digitação)"
                                                    aria-label="Apagar marcação de <?= e($m['paciente_nome']) ?>">
                                                <?= Icon::svg('excluir', 'w-4 h-4') ?>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>

                        <?php if ($ativa && $podeEditar): ?>
                            <tr x-show="cancelando" x-cloak>
                                <td colspan="5" class="bg-slate-50 dark:bg-slate-950/60">
                                    <form method="post" action="<?= url('agenda', 'cancelar') ?>"
                                          class="flex flex-wrap items-start gap-2">
                                        <?= Csrf::campo() ?>
                                        <input type="hidden" name="id" value="<?= $mid ?>">
                                        <input type="hidden" name="vista" value="<?= e($volta) ?>">

                                        <div class="min-w-[16rem] flex-1">
                                            <label class="label" for="motivo_id_<?= $mid ?>">
                                                Por que <?= e($m['paciente_nome']) ?> está sendo desmarcada?
                                            </label>

                                            <?php if ($motivos): ?>
                                                <select name="motivo_id" id="motivo_id_<?= $mid ?>" required
                                                        class="select" x-model="motivo">
                                                    <option value="">Selecione o motivo…</option>
                                                    <?php foreach ($motivos as $mo): ?>
                                                        <option value="<?= (int) $mo['id'] ?>"><?= e($mo['nome']) ?></option>
                                                    <?php endforeach; ?>
                                                    <option value="outro">Outro motivo…</option>
                                                </select>
                                            <?php else: ?>
                                                <input type="hidden" name="motivo_id" value="outro">
                                            <?php endif; ?>

                                            <?php /* Só aparece em "Outro motivo" — e o que for escrito
                                                     entra no cadastro para as próximas vezes. */ ?>
                                            <div x-show="motivo === 'outro'" x-cloak
                                                 <?= $motivos ? 'class="mt-2"' : '' ?>>
                                                <label class="sr-only" for="motivo_<?= $mid ?>">Escreva o motivo</label>
                                                <input type="text" name="motivo" id="motivo_<?= $mid ?>"
                                                       maxlength="150" class="input"
                                                       :required="motivo === 'outro'"
                                                       placeholder="Escreva o motivo — ele entra na lista para as próximas vezes">
                                            </div>
                                        </div>

                                        <div class="flex items-center gap-2 pt-[1.4rem]">
                                            <button type="submit" class="btn btn-danger">
                                                <?= Icon::svg('cancelar', 'w-4 h-4') ?> Cancelar marcação
                                            </button>
                                            <button type="button" @click="cancelando = false" class="btn btn-ghost">
                                                Voltar
                                            </button>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                <?php endforeach; ?>
            </table>
        </div>
    <?php endif; ?>
</div>
