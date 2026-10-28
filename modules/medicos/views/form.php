<?php
/** @var array|null $medico */
/** @var array $erros */
/** @var array $coresEmUso */
declare(strict_types=1);

$editando  = $medico && !empty($medico['id']);
$titulo    = $editando ? 'Editar médico' : 'Novo médico';
$actionUrl = url('medicos', $editando ? 'atualizar' : 'salvar');
$corSel    = (int) ($medico['cor'] ?? 1);
?>

<div class="mb-5 flex items-center gap-3">
    <a href="<?= url_lista('medicos') ?>" class="btn btn-icon btn-outline" aria-label="Voltar">
        <?= Icon::svg('voltar', 'w-4 h-4') ?>
    </a>
    <div>
        <h1 class="titulo-pagina"><?= e($titulo) ?></h1>
        <p class="sub-pagina">
            <?= $editando ? 'Atualize os dados do médico' : 'Cadastre um médico que atende na agenda da central' ?>
        </p>
    </div>
</div>

<form method="post" action="<?= $actionUrl ?>" class="max-w-4xl" novalidate
      x-data="envio" @submit="enviar">
    <?= Csrf::campo() ?>
    <?php if ($editando): ?>
        <input type="hidden" name="id" value="<?= (int) $medico['id'] ?>">
    <?php endif; ?>

    <div class="card p-5 lg:p-6">

        <div class="form-secao">
            <div>
                <h2 class="form-secao-titulo">Identificação</h2>
                <p class="form-secao-desc">
                    O nome aparece escrito em todos os blocos da agenda. Escreva do
                    jeito que a central já usa na escala, com o Dr. ou Dra. na frente.
                </p>
            </div>

            <div class="form-campos">
                <div class="form-largo">
                    <label class="label" for="nome">Nome do médico <span class="obrigatorio">*</span></label>
                    <input type="text" name="nome" id="nome" maxlength="150" required autofocus
                           class="input <?= isset($erros['nome']) ? 'input-error' : '' ?>"
                           value="<?= e($medico['nome'] ?? '') ?>" placeholder="Ex.: Dr. Ettore">
                    <?php if (isset($erros['nome'])): ?>
                        <p class="erro-campo"><?= Icon::svg('erro', 'w-3.5 h-3.5') ?> <?= e($erros['nome']) ?></p>
                    <?php endif; ?>
                </div>

                <div>
                    <label class="label" for="crm">CRM</label>
                    <input type="text" name="crm" id="crm" maxlength="20"
                           class="input mono <?= isset($erros['crm']) ? 'input-error' : '' ?>"
                           value="<?= e($medico['crm'] ?? '') ?>" placeholder="Ex.: 123456/SP">
                    <?php if (isset($erros['crm'])): ?>
                        <p class="erro-campo"><?= Icon::svg('erro', 'w-3.5 h-3.5') ?> <?= e($erros['crm']) ?></p>
                    <?php endif; ?>
                </div>

                <div>
                    <label class="label" for="especialidade">Especialidade</label>
                    <input type="text" name="especialidade" id="especialidade" maxlength="120"
                           class="input <?= isset($erros['especialidade']) ? 'input-error' : '' ?>"
                           value="<?= e($medico['especialidade'] ?? '') ?>" placeholder="Ex.: Endoscopia">
                    <?php if (isset($erros['especialidade'])): ?>
                        <p class="erro-campo"><?= Icon::svg('erro', 'w-3.5 h-3.5') ?> <?= e($erros['especialidade']) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="form-secao">
            <div>
                <h2 class="form-secao-titulo">Cor na agenda</h2>
                <p class="form-secao-desc">
                    Serve para bater o olho no calendário e achar o médico rápido. O
                    nome sempre aparece escrito junto, então quem não distingue cores
                    não perde nada. Repetir cor é permitido, mas atrapalha a leitura.
                </p>
            </div>

            <div>
                <div class="flex flex-wrap gap-2" role="radiogroup" aria-label="Cor do médico na agenda">
                    <?php for ($c = 1; $c <= MedicoModel::CORES; $c++): ?>
                        <?php $ocupada = $coresEmUso[$c] ?? []; ?>
                        <label class="medico-<?= $c ?> group relative cursor-pointer">
                            <input type="radio" name="cor" value="<?= $c ?>" class="peer sr-only"
                                   <?= $corSel === $c ? 'checked' : '' ?>>
                            <span class="flex items-center gap-2 rounded-lg border border-slate-300 px-2.5 py-1.5
                                         text-[0.75rem] font-medium text-slate-600 transition-colors
                                         peer-checked:border-[color:var(--medico)] peer-checked:bg-[color-mix(in_oklab,var(--medico)_12%,transparent)]
                                         peer-checked:text-[color:var(--medico)]
                                         peer-focus-visible:ring-2 peer-focus-visible:ring-marine-500/70
                                         dark:border-slate-700 dark:text-slate-400">
                                <span class="med-ponto" aria-hidden="true"></span>
                                Cor <?= $c ?>
                                <?php if ($ocupada): ?>
                                    <span class="text-[0.625rem] font-normal text-slate-400"
                                          title="Já usada por: <?= e(implode(', ', $ocupada)) ?>">em uso</span>
                                <?php endif; ?>
                            </span>
                        </label>
                    <?php endfor; ?>
                </div>
                <p class="ajuda mt-2">
                    “Em uso” só avisa que outro médico ativo já escolheu esta cor.
                </p>
            </div>
        </div>

        <?php if ($editando): ?>
            <div class="form-secao">
                <div>
                    <h2 class="form-secao-titulo">Situação</h2>
                    <p class="form-secao-desc">
                        Médico inativo some das listas ao montar a escala, mas os
                        blocos já lançados continuam aparecendo no calendário.
                    </p>
                </div>

                <div>
                    <label class="flex cursor-pointer items-start gap-3">
                        <input type="checkbox" name="ativo" class="check mt-0.5" <?= ($medico['ativo'] ?? 1) ? 'checked' : '' ?>>
                        <span>
                            <span class="block text-[0.8125rem] font-medium text-slate-800 dark:text-slate-100">Médico ativo</span>
                            <span class="block text-xs text-slate-500 dark:text-slate-400">Disponível para entrar na escala do mês.</span>
                        </span>
                    </label>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="form-acoes">
        <a href="<?= url_lista('medicos') ?>" class="btn btn-outline">Cancelar</a>
        <button type="submit" class="btn btn-primary" :disabled="enviando">
            <span x-show="!enviando"><?= Icon::svg('salvar', 'w-4 h-4') ?></span>
            <span x-show="enviando" x-cloak class="girando"></span>
            <?= $editando ? 'Salvar alterações' : 'Cadastrar médico' ?>
        </button>
    </div>
</form>
