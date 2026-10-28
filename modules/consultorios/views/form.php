<?php
/**
 * Lançar ou editar uma ocupação de sala.
 *
 * @var array $ocupacao
 * @var array $erros
 * @var array $salas
 * @var array $profissionais
 * @var array $especialidades
 */
declare(strict_types=1);

$editando  = !empty($ocupacao['id']);
$actionUrl = url('consultorios', $editando ? 'atualizar' : 'salvar');
$dias      = dias_semana();
?>

<div class="mb-5 flex items-center gap-3">
    <a href="<?= url('consultorios', 'mapa') ?>" class="btn btn-icon btn-outline" aria-label="Voltar ao mapa">
        <?= Icon::svg('voltar', 'w-4 h-4') ?>
    </a>
    <div>
        <h1 class="titulo-pagina"><?= $editando ? 'Editar ocupação' : 'Nova ocupação' ?></h1>
        <p class="sub-pagina">
            <?= $editando
                ? 'Mude o horário, a sala ou quem atende'
                : 'Quem passa a ocupar a sala, em que dia e em que horário' ?>
        </p>
    </div>
</div>

<form method="post" action="<?= $actionUrl ?>" class="max-w-3xl" novalidate x-data="envio" @submit="enviar">
    <?= Csrf::campo() ?>
    <?php if ($editando): ?>
        <input type="hidden" name="id" value="<?= (int) $ocupacao['id'] ?>">
    <?php endif; ?>

    <div class="card p-5 lg:p-6">

        <div class="form-secao">
            <div>
                <h2 class="form-secao-titulo">Onde e quando</h2>
                <p class="form-secao-desc">
                    A grade é semanal e fixa: vale toda semana, até alguém mudar.
                    Não é a agenda de um dia específico.
                </p>
            </div>

            <div class="form-campos">
                <div>
                    <label class="label" for="sala_id">Sala <span class="obrigatorio">*</span></label>
                    <select name="sala_id" id="sala_id" class="select <?= isset($erros['sala_id']) ? 'input-error' : '' ?>" required>
                        <?php foreach ($salas as $s): ?>
                            <option value="<?= (int) $s['id'] ?>"
                                <?= (int) ($ocupacao['sala_id'] ?? 0) === (int) $s['id'] ? 'selected' : '' ?>>
                                <?= e($s['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($erros['sala_id'])): ?>
                        <p class="erro-campo"><?= Icon::svg('erro', 'w-3.5 h-3.5') ?> <?= e($erros['sala_id']) ?></p>
                    <?php endif; ?>
                </div>

                <div>
                    <label class="label" for="dia_semana">Dia da semana <span class="obrigatorio">*</span></label>
                    <select name="dia_semana" id="dia_semana" class="select" required>
                        <?php foreach ([1, 2, 3, 4, 5, 6] as $d): ?>
                            <option value="<?= $d ?>" <?= (int) ($ocupacao['dia_semana'] ?? 1) === $d ? 'selected' : '' ?>>
                                <?= e($dias[$d]) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="label" for="turno">Turno <span class="obrigatorio">*</span></label>
                    <select name="turno" id="turno" class="select" required>
                        <?php foreach (turnos() as $valor => $rotulo): ?>
                            <option value="<?= e($valor) ?>" <?= ($ocupacao['turno'] ?? 'manha') === $valor ? 'selected' : '' ?>>
                                <?= e($rotulo) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="ajuda">Define em qual das duas grades a ocupação aparece.</p>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="label" for="hora_inicio">Início <span class="obrigatorio">*</span></label>
                        <input type="time" name="hora_inicio" id="hora_inicio" class="input" required
                               value="<?= e(hora_curta($ocupacao['hora_inicio'] ?? '08:00')) ?>">
                    </div>
                    <div>
                        <label class="label" for="hora_fim">Fim <span class="obrigatorio">*</span></label>
                        <input type="time" name="hora_fim" id="hora_fim" required
                               class="input <?= isset($erros['hora_fim']) ? 'input-error' : '' ?>"
                               value="<?= e(hora_curta($ocupacao['hora_fim'] ?? '12:00')) ?>">
                    </div>
                    <?php if (isset($erros['hora_fim'])): ?>
                        <p class="erro-campo col-span-2"><?= Icon::svg('erro', 'w-3.5 h-3.5') ?> <?= e($erros['hora_fim']) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="form-secao">
            <div>
                <h2 class="form-secao-titulo">Quem ocupa</h2>
                <p class="form-secao-desc">
                    A pessoa vem do cadastro de profissionais — assim o nome é
                    sempre escrito do mesmo jeito e o filtro do mapa encontra
                    todas as ocupações dela.
                </p>
                <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                    Falta alguém na lista?
                    <a href="<?= url('consultorios', 'profissionais') ?>" class="font-medium text-marine-600 hover:underline dark:text-marine-300">Cadastrar profissional</a>.
                </p>
            </div>

            <div class="form-campos">
                <div class="form-largo">
                    <label class="label" for="profissional_id">
                        Profissional ou atividade <span class="obrigatorio">*</span>
                    </label>
                    <select name="profissional_id" id="profissional_id" required
                            class="select <?= isset($erros['profissional_id']) ? 'input-error' : '' ?>">
                        <option value="">Escolha…</option>
                        <?php foreach ($profissionais as $p): ?>
                            <option value="<?= (int) $p['id'] ?>"
                                    data-especialidade="<?= (int) ($p['especialidade_id'] ?? 0) ?>"
                                <?= (int) ($ocupacao['profissional_id'] ?? 0) === (int) $p['id'] ? 'selected' : '' ?>>
                                <?= e($p['nome']) ?><?= $p['especialidade_nome'] ? ' — ' . e($p['especialidade_nome']) : '' ?><?= $p['atividade'] ? ' (atividade)' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($erros['profissional_id'])): ?>
                        <p class="erro-campo"><?= Icon::svg('erro', 'w-3.5 h-3.5') ?> <?= e($erros['profissional_id']) ?></p>
                    <?php endif; ?>
                </div>

                <div>
                    <label class="label" for="especialidade_id">Especialidade nesta sala</label>
                    <select name="especialidade_id" id="especialidade_id" class="select">
                        <option value="">A habitual do profissional</option>
                        <?php foreach ($especialidades as $x): ?>
                            <option value="<?= (int) $x['id'] ?>"
                                <?= (int) ($ocupacao['especialidade_id'] ?? 0) === (int) $x['id'] ? 'selected' : '' ?>>
                                <?= e($x['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="ajuda">
                        Só preencha quando for diferente: quem atende clínica num
                        dia e gastro no outro.
                    </p>
                </div>

                <div>
                    <label class="label" for="periodicidade">Frequência</label>
                    <select name="periodicidade" id="periodicidade" class="select">
                        <?php foreach (ConsultorioModel::PERIODICIDADES as $valor => $rotulo): ?>
                            <option value="<?= e($valor) ?>"
                                <?= ($ocupacao['periodicidade'] ?? 'semanal') === $valor ? 'selected' : '' ?>>
                                <?= e($rotulo) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="ajuda">Quinzenal aparece marcado com 15/15 no mapa.</p>
                </div>

                <div class="form-largo">
                    <label class="label" for="observacao">Observação</label>
                    <input type="text" name="observacao" id="observacao" maxlength="200" class="input"
                           value="<?= e($ocupacao['observacao'] ?? '') ?>"
                           placeholder="Ex.: a partir de outubro">
                    <p class="ajuda">
                        O que a planilha escrevia entre parênteses. Fica em letra
                        miúda embaixo do horário.
                    </p>
                </div>
            </div>
        </div>

        <?php if ($editando): ?>
            <div class="form-secao">
                <div>
                    <h2 class="form-secao-titulo">Situação</h2>
                    <p class="form-secao-desc">
                        Desativar tira do mapa sem apagar. Serve para quem parou
                        de atender mas pode voltar.
                    </p>
                </div>

                <div>
                    <label class="flex cursor-pointer items-start gap-3">
                        <input type="checkbox" name="ativo" class="check mt-0.5" <?= ($ocupacao['ativo'] ?? 1) ? 'checked' : '' ?>>
                        <span>
                            <span class="block text-[0.8125rem] font-medium text-slate-800 dark:text-slate-100">No mapa</span>
                            <span class="block text-xs text-slate-500 dark:text-slate-400">Aparece na grade da semana.</span>
                        </span>
                    </label>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="form-acoes">
        <a href="<?= url('consultorios', 'mapa') ?>" class="btn btn-outline">Cancelar</a>
        <button type="submit" class="btn btn-primary" :disabled="enviando">
            <span x-show="!enviando"><?= Icon::svg('salvar', 'w-4 h-4') ?></span>
            <span x-show="enviando" x-cloak class="girando"></span>
            <?= $editando ? 'Salvar alterações' : 'Lançar no mapa' ?>
        </button>
    </div>
</form>

<?php if ($editando && Auth::temPermissao('consultorios', 'pode_excluir')): ?>
    <div class="mt-4 max-w-3xl">
        <div class="card border-rose-200 p-4 dark:border-rose-500/30">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="text-[0.8125rem] font-medium text-slate-800 dark:text-slate-100">
                        Remover do mapa
                    </p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        A sala volta a aparecer como livre nesse dia e horário.
                    </p>
                </div>

                <form method="post" action="<?= url('consultorios', 'excluir') ?>"
                      data-titulo="Remover do mapa"
                      data-texto="<?= e(($ocupacao['profissional'] ?? '') . ' sai do mapa e a sala fica livre nesse horário.') ?>">
                    <?= Csrf::campo() ?>
                    <input type="hidden" name="id" value="<?= (int) $ocupacao['id'] ?>">
                    <button type="button" @click="abrir" class="btn btn-danger-outline">
                        <?= Icon::svg('excluir', 'w-4 h-4') ?> Remover
                    </button>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>
