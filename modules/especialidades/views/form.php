<?php
/** @var array|null $especialidade */
/** @var array $erros */
declare(strict_types=1);

$editando  = $especialidade && !empty($especialidade['id']);
$titulo    = $editando ? 'Editar especialidade' : 'Nova especialidade';
$actionUrl = url('especialidades', $editando ? 'atualizar' : 'salvar');
?>

<div class="mb-5 flex items-center gap-3">
    <a href="<?= url_lista('especialidades') ?>" class="btn btn-icon btn-outline" aria-label="Voltar">
        <?= Icon::svg('voltar', 'w-4 h-4') ?>
    </a>
    <div>
        <h1 class="titulo-pagina"><?= e($titulo) ?></h1>
        <p class="sub-pagina"><?= $editando ? 'Atualize os dados da especialidade' : 'Cadastre uma nova especialidade médica' ?></p>
    </div>
</div>

<form method="post" action="<?= $actionUrl ?>" class="max-w-4xl" novalidate
      x-data="envio" @submit="enviar">
    <?= Csrf::campo() ?>
    <?php if ($editando): ?>
        <input type="hidden" name="id" value="<?= (int) $especialidade['id'] ?>">
    <?php endif; ?>

    <div class="card p-5 lg:p-6">
        <div class="form-secao">
            <div>
                <h2 class="form-secao-titulo">Dados da especialidade</h2>
                <p class="form-secao-desc">
                    Use a nomenclatura oficial do CFM. O nome é único e aparece no cadastro de cirurgias.
                </p>
            </div>

            <div class="form-campos">
                <div class="form-largo">
                    <label class="label" for="nome">Nome da especialidade <span class="obrigatorio">*</span></label>
                    <input type="text" name="nome" id="nome" maxlength="150" required autofocus
                           class="input <?= isset($erros['nome']) ? 'input-error' : '' ?>"
                           value="<?= e($especialidade['nome'] ?? '') ?>" placeholder="Ex.: Cirurgia vascular">
                    <?php if (isset($erros['nome'])): ?>
                        <p class="erro-campo"><?= Icon::svg('erro', 'w-3.5 h-3.5') ?> <?= e($erros['nome']) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if ($editando): ?>
            <div class="form-secao">
                <div>
                    <h2 class="form-secao-titulo">Situação</h2>
                    <p class="form-secao-desc">
                        Especialidades inativas somem do formulário de cirurgias, mas os registros antigos continuam intactos.
                    </p>
                </div>

                <div>
                    <label class="flex cursor-pointer items-start gap-3">
                        <input type="checkbox" name="ativo" class="check mt-0.5" <?= ($especialidade['ativo'] ?? 1) ? 'checked' : '' ?>>
                        <span>
                            <span class="block text-[0.8125rem] font-medium text-slate-800 dark:text-slate-100">Especialidade ativa</span>
                            <span class="block text-xs text-slate-500 dark:text-slate-400">Disponível para novos registros de cirurgia.</span>
                        </span>
                    </label>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="form-acoes">
        <a href="<?= url_lista('especialidades') ?>" class="btn btn-outline">Cancelar</a>
        <button type="submit" class="btn btn-primary" :disabled="enviando">
            <span x-show="!enviando"><?= Icon::svg('salvar', 'w-4 h-4') ?></span>
            <span x-show="enviando" x-cloak class="girando"></span>
            <?= $editando ? 'Salvar alterações' : 'Cadastrar especialidade' ?>
        </button>
    </div>
</form>
