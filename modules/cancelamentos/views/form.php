<?php
/** @var array|null $motivo */
/** @var array $erros */
declare(strict_types=1);

$editando  = $motivo && !empty($motivo['id']);
$titulo    = $editando ? 'Editar mensagem' : 'Nova mensagem de cancelamento';
$actionUrl = url('cancelamentos', $editando ? 'atualizar' : 'salvar');
?>

<div class="mb-5 flex items-center gap-3">
    <a href="<?= url_lista('cancelamentos') ?>" class="btn btn-icon btn-outline" aria-label="Voltar">
        <?= Icon::svg('voltar', 'w-4 h-4') ?>
    </a>
    <div>
        <h1 class="titulo-pagina"><?= e($titulo) ?></h1>
        <p class="sub-pagina">
            <?= $editando
                ? 'Ajuste o texto, a ordem ou tire a mensagem da lista'
                : 'Uma razão pronta para a atendente escolher ao desmarcar um exame' ?>
        </p>
    </div>
</div>

<form method="post" action="<?= $actionUrl ?>" class="max-w-3xl" novalidate
      x-data="envio" @submit="enviar">
    <?= Csrf::campo() ?>
    <?php if ($editando): ?>
        <input type="hidden" name="id" value="<?= (int) $motivo['id'] ?>">
    <?php endif; ?>

    <div class="card p-5 lg:p-6">

        <div class="form-secao">
            <div>
                <h2 class="form-secao-titulo">A mensagem</h2>
                <p class="form-secao-desc">
                    É o texto que aparece na lista para a atendente e depois fica
                    escrito no histórico da paciente. Escreva curto e direto, do
                    jeito que você quer ler num relatório daqui a seis meses.
                </p>
            </div>

            <div class="form-campos">
                <div class="form-largo">
                    <label class="label" for="nome">Mensagem <span class="obrigatorio">*</span></label>
                    <input type="text" name="nome" id="nome" maxlength="150" required autofocus
                           class="input <?= isset($erros['nome']) ? 'input-error' : '' ?>"
                           value="<?= e($motivo['nome'] ?? '') ?>"
                           placeholder="Ex.: Paciente desistiu do exame">
                    <?php if (isset($erros['nome'])): ?>
                        <p class="erro-campo"><?= Icon::svg('erro', 'w-3.5 h-3.5') ?> <?= e($erros['nome']) ?></p>
                    <?php else: ?>
                        <p class="ajuda">Até 150 caracteres.</p>
                    <?php endif; ?>
                </div>

                <div>
                    <label class="label" for="ordem">Ordem na lista</label>
                    <input type="number" name="ordem" id="ordem" min="0" max="9999"
                           class="input num" value="<?= (int) ($motivo['ordem'] ?? 0) ?>">
                    <p class="ajuda">Menor número aparece primeiro.</p>
                </div>
            </div>
        </div>

        <?php if ($editando): ?>
            <div class="form-secao">
                <div>
                    <h2 class="form-secao-titulo">Situação</h2>
                    <p class="form-secao-desc">
                        Tirar da lista não apaga nada: os cancelamentos que já usaram
                        esta mensagem continuam mostrando ela no histórico.
                    </p>
                </div>

                <div>
                    <label class="flex cursor-pointer items-start gap-3">
                        <input type="checkbox" name="ativo" class="check mt-0.5" <?= ($motivo['ativo'] ?? 1) ? 'checked' : '' ?>>
                        <span>
                            <span class="block text-[0.8125rem] font-medium text-slate-800 dark:text-slate-100">Mostrar na lista</span>
                            <span class="block text-xs text-slate-500 dark:text-slate-400">A atendente pode escolher esta mensagem ao cancelar.</span>
                        </span>
                    </label>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="form-acoes">
        <a href="<?= url_lista('cancelamentos') ?>" class="btn btn-outline">Cancelar</a>
        <button type="submit" class="btn btn-primary" :disabled="enviando">
            <span x-show="!enviando"><?= Icon::svg('salvar', 'w-4 h-4') ?></span>
            <span x-show="enviando" x-cloak class="girando"></span>
            <?= $editando ? 'Salvar alterações' : 'Cadastrar mensagem' ?>
        </button>
    </div>
</form>
