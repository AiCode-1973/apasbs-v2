<?php
/** @var array|null $exame */
/** @var array $erros */
declare(strict_types=1);

$editando  = $exame && !empty($exame['id']);
$titulo    = $editando ? 'Editar tipo de exame' : 'Novo tipo de exame';
$actionUrl = url('exames', $editando ? 'atualizar' : 'salvar');
?>

<div class="mb-5 flex items-center gap-3">
    <a href="<?= url_lista('exames') ?>" class="btn btn-icon btn-outline" aria-label="Voltar">
        <?= Icon::svg('voltar', 'w-4 h-4') ?>
    </a>
    <div>
        <h1 class="titulo-pagina"><?= e($titulo) ?></h1>
        <p class="sub-pagina">
            <?= $editando ? 'Atualize os dados do tipo de exame' : 'Cadastre um tipo de exame agendável pela central' ?>
        </p>
    </div>
</div>

<form method="post" action="<?= $actionUrl ?>" class="max-w-4xl" novalidate
      x-data="envio" @submit="enviar">
    <?= Csrf::campo() ?>
    <?php if ($editando): ?>
        <input type="hidden" name="id" value="<?= (int) $exame['id'] ?>">
    <?php endif; ?>

    <div class="card p-5 lg:p-6">

        <div class="form-secao">
            <div>
                <h2 class="form-secao-titulo">Identificação</h2>
                <p class="form-secao-desc">
                    O código é a sigla curta que aparece nos blocos do calendário —
                    fica em caixa alta e não pode se repetir.
                </p>
            </div>

            <div class="form-campos">
                <div>
                    <label class="label" for="codigo">Código <span class="obrigatorio">*</span></label>
                    <input type="text" name="codigo" id="codigo" maxlength="20" required autofocus
                           class="input mono uppercase <?= isset($erros['codigo']) ? 'input-error' : '' ?>"
                           value="<?= e($exame['codigo'] ?? '') ?>" placeholder="Ex.: EDA">
                    <?php if (isset($erros['codigo'])): ?>
                        <p class="erro-campo"><?= Icon::svg('erro', 'w-3.5 h-3.5') ?> <?= e($erros['codigo']) ?></p>
                    <?php else: ?>
                        <p class="ajuda">Letras, números, ponto, hífen e underscore.</p>
                    <?php endif; ?>
                </div>

                <div>
                    <label class="label" for="nome">Nome <span class="obrigatorio">*</span></label>
                    <input type="text" name="nome" id="nome" maxlength="120" required
                           class="input <?= isset($erros['nome']) ? 'input-error' : '' ?>"
                           value="<?= e($exame['nome'] ?? '') ?>" placeholder="Ex.: Endoscopia digestiva alta">
                    <?php if (isset($erros['nome'])): ?>
                        <p class="erro-campo"><?= Icon::svg('erro', 'w-3.5 h-3.5') ?> <?= e($erros['nome']) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="form-secao">
            <div>
                <h2 class="form-secao-titulo">Exibição</h2>
                <p class="form-secao-desc">
                    A ordem define a sequência em que os tipos aparecem no bloco e no
                    resumo do mês. Menor número vem primeiro.
                </p>
            </div>

            <div class="form-campos">
                <div>
                    <label class="label" for="ordem">Ordem</label>
                    <input type="number" name="ordem" id="ordem" min="0" max="999"
                           class="input" value="<?= (int) ($exame['ordem'] ?? 0) ?>">
                    <p class="ajuda">0 a 999.</p>
                </div>

                <div class="form-largo">
                    <label class="label" for="descricao">Descrição</label>
                    <textarea name="descricao" id="descricao" rows="2" maxlength="300"
                              class="textarea <?= isset($erros['descricao']) ? 'input-error' : '' ?>"
                              placeholder="Ex.: colonoscopia e endoscopia na mesma sessão."><?= e($exame['descricao'] ?? '') ?></textarea>
                    <?php if (isset($erros['descricao'])): ?>
                        <p class="erro-campo"><?= Icon::svg('erro', 'w-3.5 h-3.5') ?> <?= e($erros['descricao']) ?></p>
                    <?php else: ?>
                        <p class="ajuda">Até 300 caracteres.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if ($editando): ?>
            <div class="form-secao">
                <div>
                    <h2 class="form-secao-titulo">Situação</h2>
                    <p class="form-secao-desc">
                        Tipos inativos somem do formulário de lançamento, mas continuam
                        visíveis nos blocos já preenchidos.
                    </p>
                </div>

                <div>
                    <label class="flex cursor-pointer items-start gap-3">
                        <input type="checkbox" name="ativo" class="check mt-0.5" <?= ($exame['ativo'] ?? 1) ? 'checked' : '' ?>>
                        <span>
                            <span class="block text-[0.8125rem] font-medium text-slate-800 dark:text-slate-100">Tipo ativo</span>
                            <span class="block text-xs text-slate-500 dark:text-slate-400">Disponível para lançamento na agenda.</span>
                        </span>
                    </label>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="form-acoes">
        <a href="<?= url_lista('exames') ?>" class="btn btn-outline">Cancelar</a>
        <button type="submit" class="btn btn-primary" :disabled="enviando">
            <span x-show="!enviando"><?= Icon::svg('salvar', 'w-4 h-4') ?></span>
            <span x-show="enviando" x-cloak class="girando"></span>
            <?= $editando ? 'Salvar alterações' : 'Cadastrar tipo' ?>
        </button>
    </div>
</form>
