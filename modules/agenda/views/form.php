<?php
/**
 * Lançamento de um bloco: um médico, num dia, num turno, com a capacidade
 * de cada tipo de exame contada separadamente.
 *
 * @var array|null $bloco
 * @var array $vagas    exame_id => ['capacidade' => int, 'ocupadas' => int]
 * @var array $medicos
 * @var array $exames
 * @var array $erros
 * @var string $volta
 */
declare(strict_types=1);

$editando  = $bloco && !empty($bloco['id']);
$titulo    = $editando ? 'Editar bloco' : 'Novo bloco na agenda';
$actionUrl = url('agenda', $editando ? 'atualizar' : 'salvar');

$dataSel   = (string) ($bloco['data'] ?? date('Y-m-d'));
$turnoSel  = (string) ($bloco['turno'] ?? 'manha');
$medicoSel = (int) ($bloco['medico_id'] ?? 0);

$ts        = (int) strtotime($dataSel);
$anoVolta  = $ts ? (int) date('Y', $ts) : (int) date('Y');
$mesVolta  = $ts ? (int) date('n', $ts) : (int) date('n');
$urlVolta  = url('agenda', $volta, ['ano' => $anoVolta, 'mes' => $mesVolta]);
?>

<div class="mb-5 flex items-center gap-3">
    <a href="<?= $urlVolta ?>" class="btn btn-icon btn-outline" aria-label="Voltar">
        <?= Icon::svg('voltar', 'w-4 h-4') ?>
    </a>
    <div>
        <h1 class="titulo-pagina"><?= e($titulo) ?></h1>
        <p class="sub-pagina">
            <?= $editando
                ? 'Ajuste o dia, o turno ou as vagas deste bloco'
                : 'Um bloco é um médico atendendo num dia e num turno' ?>
        </p>
    </div>
</div>

<?php if (empty($medicos)): ?>
    <div class="alerta alerta-aviso mb-4">
        <?= Icon::svg('aviso', 'w-4 h-4') ?>
        <span>
            Nenhum médico ativo cadastrado. Cadastre os médicos em
            <a href="<?= url('medicos', 'lista') ?>" class="font-semibold underline">Médicos do Ambulatório</a>
            antes de montar a escala.
        </span>
    </div>
<?php endif; ?>

<form method="post" action="<?= $actionUrl ?>" class="max-w-4xl" novalidate
      x-data="blocoForm" @submit="enviar">
    <?= Csrf::campo() ?>
    <input type="hidden" name="vista" value="<?= e($volta) ?>">
    <?php if ($editando): ?>
        <input type="hidden" name="id" value="<?= (int) $bloco['id'] ?>">
    <?php endif; ?>

    <div class="card p-5 lg:p-6">

        <div class="form-secao">
            <div>
                <h2 class="form-secao-titulo">Quando e com quem</h2>
                <p class="form-secao-desc">
                    O mesmo médico não pode ter dois blocos no mesmo dia e turno —
                    se precisar de mais vagas, aumente a capacidade do bloco que já existe.
                </p>
            </div>

            <div class="form-campos">
                <div class="form-largo">
                    <label class="label" for="medico_id">Médico <span class="obrigatorio">*</span></label>
                    <select name="medico_id" id="medico_id" required
                            class="select <?= isset($erros['medico_id']) ? 'input-error' : '' ?>">
                        <option value="">Selecione…</option>
                        <?php foreach ($medicos as $m): ?>
                            <option value="<?= (int) $m['id'] ?>" <?= $medicoSel === (int) $m['id'] ? 'selected' : '' ?>>
                                <?= e($m['nome']) ?><?= !empty($m['especialidade']) ? ' — ' . e($m['especialidade']) : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($erros['medico_id'])): ?>
                        <p class="erro-campo"><?= Icon::svg('erro', 'w-3.5 h-3.5') ?> <?= e($erros['medico_id']) ?></p>
                    <?php endif; ?>
                </div>

                <div>
                    <label class="label" for="data">Dia <span class="obrigatorio">*</span></label>
                    <input type="date" name="data" id="data" required
                           class="input <?= isset($erros['data']) ? 'input-error' : '' ?>"
                           value="<?= e($dataSel) ?>">
                    <?php if (isset($erros['data'])): ?>
                        <p class="erro-campo"><?= Icon::svg('erro', 'w-3.5 h-3.5') ?> <?= e($erros['data']) ?></p>
                    <?php elseif ($ts): ?>
                        <p class="ajuda"><?= e(dia_semana_br($dataSel)) ?></p>
                    <?php endif; ?>
                </div>

                <div>
                    <label class="label" for="turno">Turno <span class="obrigatorio">*</span></label>
                    <select name="turno" id="turno" class="select" required>
                        <?php foreach (turnos() as $valor => $rotulo): ?>
                            <option value="<?= e($valor) ?>" <?= $turnoSel === $valor ? 'selected' : '' ?>>
                                <?= e($rotulo) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="form-secao">
            <div>
                <h2 class="form-secao-titulo">Vagas por tipo de exame</h2>
                <p class="form-secao-desc">
                    Cada tipo tem contagem própria: um bloco pode ter 2 combos e 4
                    colonos ao mesmo tempo. Deixe em zero o que não é atendido neste
                    bloco. Aqui você define quantas vagas existem — quem marca cada
                    uma é a central, na tela de marcações.
                </p>
            </div>

            <div>
                <?php if (isset($erros['vagas'])): ?>
                    <p class="erro-campo mb-2"><?= Icon::svg('erro', 'w-3.5 h-3.5') ?> <?= e($erros['vagas']) ?></p>
                <?php endif; ?>

                <div class="overflow-x-auto rounded-lg border border-slate-200 dark:border-slate-800">
                    <table class="tabela tabela-fixa">
                        <thead>
                            <tr>
                                <th>Tipo de exame</th>
                                <th class="w-36 text-right">Vagas</th>
                                <th class="w-32 text-right">Já marcadas</th>
                                <th class="w-24 text-right">Livres</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($exames as $x): ?>
                                <?php
                                $xid = (int) $x['id'];
                                $cap = (int) ($vagas[$xid]['capacidade'] ?? 0);
                                $ocu = (int) ($vagas[$xid]['ocupadas'] ?? 0);
                                $inativo = isset($x['ativo']) && !$x['ativo'];
                                ?>
                                <tr>
                                    <td>
                                        <label class="celula-principal" for="cap_<?= $xid ?>"><?= e($x['nome']) ?></label>
                                        <span class="mono celula-suave block text-[0.6875rem]">
                                            <?= e($x['codigo']) ?><?= $inativo ? ' · tipo inativo' : '' ?>
                                        </span>
                                    </td>
                                    <td class="text-right">
                                        <input type="text" inputmode="numeric" maxlength="3"
                                               name="capacidade[<?= $xid ?>]" id="cap_<?= $xid ?>"
                                               data-vaga="<?= $xid ?>" data-papel="capacidade"
                                               class="input input-sm num w-20 text-right"
                                               value="<?= $cap ?>" @input="recalcular">
                                    </td>
                                    <td class="num text-right text-xs text-slate-500 dark:text-slate-400">
                                        <?= $ocu ?>
                                        <input type="hidden" data-vaga="<?= $xid ?>" data-papel="ocupadas" value="<?= $ocu ?>">
                                    </td>
                                    <td class="num text-right">
                                        <span class="text-xs" :class="livres(<?= $xid ?>) < 0 ? 'font-semibold text-rose-600 dark:text-rose-400' : 'text-slate-500 dark:text-slate-400'"
                                              x-text="livres(<?= $xid ?>)"><?= $cap - $ocu ?></span>
                                    </td>
                                </tr>
                                <?php if (isset($erros['vaga_' . $xid])): ?>
                                    <tr>
                                        <td colspan="4" class="pt-0">
                                            <p class="erro-campo"><?= Icon::svg('erro', 'w-3.5 h-3.5') ?> <?= e($erros['vaga_' . $xid]) ?></p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td>Total do bloco</td>
                                <td class="num text-right" x-text="totalCapacidade">—</td>
                                <td class="num celula-suave text-right" x-text="totalOcupadas">—</td>
                                <td class="num celula-suave text-right" x-text="totalCapacidade - totalOcupadas">—</td>
                            </tr>
                            <tr>
                                <td colspan="4" class="celula-suave border-t-0 pt-0 text-xs">
                                    “Já marcadas” vem da tela de marcações e não se digita aqui.
                                    Reduzir as vagas abaixo desse número não é permitido.
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <p class="ajuda mt-2" x-show="totalOcupadas > totalCapacidade" x-cloak>
                    Há mais exames marcados do que vagas — cancele marcações antes de
                    reduzir, ou aumente a capacidade.
                </p>
            </div>
        </div>

        <div class="form-secao">
            <div>
                <h2 class="form-secao-titulo">TechSallus</h2>
                <p class="form-secao-desc">
                    Marque quando a agenda deste bloco já tiver sido criada lá.
                    A marca aparece no bloco do calendário, então dá para bater
                    o olho no mês e ver o que ainda falta lançar.
                </p>
            </div>

            <div>
                <label class="flex cursor-pointer items-start gap-3">
                    <input type="checkbox" name="techsallus" class="check mt-0.5"
                           <?= !empty($bloco['techsallus']) ? 'checked' : '' ?>>
                    <span>
                        <span class="block text-[0.8125rem] font-medium text-slate-800 dark:text-slate-100">
                            Agenda criada no TechSallus
                        </span>
                        <span class="block text-xs text-slate-500 dark:text-slate-400">
                            Deixe desmarcado enquanto o lançamento no TechSallus estiver pendente.
                        </span>
                    </span>
                </label>
            </div>
        </div>

        <div class="form-secao">
            <div>
                <h2 class="form-secao-titulo">Observação</h2>
                <p class="form-secao-desc">
                    Opcional. Use para o que a escala traz por escrito: horário
                    diferente, encaixe combinado, sala específica.
                </p>
            </div>

            <div>
                <label class="label" for="observacao">Observação do bloco</label>
                <textarea name="observacao" id="observacao" rows="2" maxlength="300"
                          class="textarea" placeholder="Ex.: começar 13h30, sala 2."><?= e($bloco['observacao'] ?? '') ?></textarea>
                <p class="ajuda">Até 300 caracteres.</p>
            </div>
        </div>
    </div>

    <div class="form-acoes">
        <a href="<?= $urlVolta ?>" class="btn btn-outline">Cancelar</a>
        <button type="submit" class="btn btn-primary" :disabled="enviando">
            <span x-show="!enviando"><?= Icon::svg('salvar', 'w-4 h-4') ?></span>
            <span x-show="enviando" x-cloak class="girando"></span>
            <?= $editando ? 'Salvar alterações' : 'Lançar bloco' ?>
        </button>
    </div>
</form>
