<?php
/** @var array|null $setor */
/** @var array $erros */
declare(strict_types=1);

$editando  = $setor && !empty($setor['id']);
$titulo    = $editando ? 'Editar setor' : 'Novo setor';
$actionUrl = url('setores', $editando ? 'atualizar' : 'salvar');
?>

<div class="mb-5 flex items-center gap-3">
    <a href="<?= url_lista('setores') ?>" class="btn btn-icon btn-outline" aria-label="Voltar">
        <?= Icon::svg('voltar', 'w-4 h-4') ?>
    </a>
    <div>
        <h1 class="titulo-pagina"><?= e($titulo) ?></h1>
        <p class="sub-pagina"><?= $editando ? 'Atualize os dados do setor' : 'Cadastre um novo setor da organização' ?></p>
    </div>
</div>

<form method="post" action="<?= $actionUrl ?>" class="max-w-4xl" novalidate
      x-data="envio" @submit="enviar">
    <?= Csrf::campo() ?>
    <?php if ($editando): ?>
        <input type="hidden" name="id" value="<?= (int) $setor['id'] ?>">
    <?php endif; ?>

    <div class="card p-5 lg:p-6">
        <div class="form-secao">
            <div>
                <h2 class="form-secao-titulo">Dados do setor</h2>
                <p class="form-secao-desc">
                    Cada profissional é vinculado a um setor. Não pode haver dois setores com o mesmo nome.
                </p>
            </div>

            <div class="form-campos">
                <div class="form-largo">
                    <label class="label" for="nome">Nome do setor <span class="obrigatorio">*</span></label>
                    <input type="text" name="nome" id="nome" maxlength="100" required autofocus
                           class="input <?= isset($erros['nome']) ? 'input-error' : '' ?>"
                           value="<?= e($setor['nome'] ?? '') ?>" placeholder="Ex.: Centro Cirúrgico">
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
                        Setores inativos deixam de aparecer no cadastro de usuários, mas os vínculos existentes continuam válidos.
                    </p>
                </div>

                <div>
                    <label class="flex cursor-pointer items-start gap-3">
                        <input type="checkbox" name="ativo" class="check mt-0.5" <?= ($setor['ativo'] ?? 1) ? 'checked' : '' ?>>
                        <span>
                            <span class="block text-[0.8125rem] font-medium text-slate-800 dark:text-slate-100">Setor ativo</span>
                            <span class="block text-xs text-slate-500 dark:text-slate-400">Disponível para vincular novos profissionais.</span>
                        </span>
                    </label>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="form-acoes">
        <a href="<?= url_lista('setores') ?>" class="btn btn-outline">Cancelar</a>
        <button type="submit" class="btn btn-primary" :disabled="enviando">
            <span x-show="!enviando"><?= Icon::svg('salvar', 'w-4 h-4') ?></span>
            <span x-show="enviando" x-cloak class="girando"></span>
            <?= $editando ? 'Salvar alterações' : 'Cadastrar setor' ?>
        </button>
    </div>
</form>
