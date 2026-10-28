<?php
/** @var array|null $motivo */
/** @var array $erros */
declare(strict_types=1);

$editando  = $motivo && !empty($motivo['id']);
$titulo    = $editando ? 'Editar motivo' : 'Novo motivo';
$actionUrl = url('motivos', $editando ? 'atualizar' : 'salvar');
?>

<div class="mb-5 flex items-center gap-3">
    <a href="<?= url_lista('motivos') ?>" class="btn btn-icon btn-outline" aria-label="Voltar">
        <?= Icon::svg('voltar', 'w-4 h-4') ?>
    </a>
    <div>
        <h1 class="titulo-pagina"><?= e($titulo) ?></h1>
        <p class="sub-pagina">
            <?= $editando ? 'Atualize os dados do motivo' : 'Cadastre um novo motivo de fechamento de atendimento' ?>
        </p>
    </div>
</div>

<form method="post" action="<?= $actionUrl ?>" class="max-w-4xl" novalidate
      x-data="envio" @submit="enviar">
    <?= Csrf::campo() ?>
    <?php if ($editando): ?>
        <input type="hidden" name="id" value="<?= (int) $motivo['id'] ?>">
    <?php endif; ?>

    <div class="card p-5 lg:p-6">

        <div class="form-secao">
            <div>
                <h2 class="form-secao-titulo">Identificação</h2>
                <p class="form-secao-desc">
                    O código é a referência curta usada em relatório e planilha. Fica
                    sempre em caixa alta e não pode se repetir.
                </p>
            </div>

            <div class="form-campos">
                <div>
                    <label class="label" for="codigo">Código interno <span class="obrigatorio">*</span></label>
                    <input type="text" name="codigo" id="codigo" maxlength="30" required autofocus
                           class="input font-mono uppercase <?= isset($erros['codigo']) ? 'input-error' : '' ?>"
                           value="<?= e($motivo['codigo'] ?? '') ?>" placeholder="Ex.: FALTA">
                    <?php if (isset($erros['codigo'])): ?>
                        <p class="erro-campo"><?= Icon::svg('erro', 'w-3.5 h-3.5') ?> <?= e($erros['codigo']) ?></p>
                    <?php else: ?>
                        <p class="ajuda">Letras, números, ponto, hífen e underscore.</p>
                    <?php endif; ?>
                </div>

                <div>
                    <label class="label" for="nome">Nome do motivo <span class="obrigatorio">*</span></label>
                    <input type="text" name="nome" id="nome" maxlength="150" required
                           class="input <?= isset($erros['nome']) ? 'input-error' : '' ?>"
                           value="<?= e($motivo['nome'] ?? '') ?>" placeholder="Ex.: Falta do paciente">
                    <?php if (isset($erros['nome'])): ?>
                        <p class="erro-campo"><?= Icon::svg('erro', 'w-3.5 h-3.5') ?> <?= e($erros['nome']) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="form-secao">
            <div>
                <h2 class="form-secao-titulo">Descrição</h2>
                <p class="form-secao-desc">
                    Opcional, mas ajuda a padronizar: explique em que situação a
                    central deve escolher este motivo.
                </p>
            </div>

            <div>
                <label class="label" for="descricao">Quando usar este motivo</label>
                <textarea name="descricao" id="descricao" rows="3" maxlength="500"
                          class="textarea <?= isset($erros['descricao']) ? 'input-error' : '' ?>"
                          placeholder="Ex.: usar quando o paciente não comparece e não avisa com antecedência."><?= e($motivo['descricao'] ?? '') ?></textarea>
                <?php if (isset($erros['descricao'])): ?>
                    <p class="erro-campo"><?= Icon::svg('erro', 'w-3.5 h-3.5') ?> <?= e($erros['descricao']) ?></p>
                <?php else: ?>
                    <p class="ajuda">Até 500 caracteres.</p>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($editando): ?>
            <div class="form-secao">
                <div>
                    <h2 class="form-secao-titulo">Situação</h2>
                    <p class="form-secao-desc">
                        Motivos inativos saem das listas de escolha, mas continuam
                        aparecendo no histórico já registrado.
                    </p>
                </div>

                <div>
                    <label class="flex cursor-pointer items-start gap-3">
                        <input type="checkbox" name="ativo" class="check mt-0.5" <?= ($motivo['ativo'] ?? 1) ? 'checked' : '' ?>>
                        <span>
                            <span class="block text-[0.8125rem] font-medium text-slate-800 dark:text-slate-100">Motivo ativo</span>
                            <span class="block text-xs text-slate-500 dark:text-slate-400">Disponível para uso pela central.</span>
                        </span>
                    </label>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="form-acoes">
        <a href="<?= url_lista('motivos') ?>" class="btn btn-outline">Cancelar</a>
        <button type="submit" class="btn btn-primary" :disabled="enviando">
            <span x-show="!enviando"><?= Icon::svg('salvar', 'w-4 h-4') ?></span>
            <span x-show="enviando" x-cloak class="girando"></span>
            <?= $editando ? 'Salvar alterações' : 'Cadastrar motivo' ?>
        </button>
    </div>
</form>
