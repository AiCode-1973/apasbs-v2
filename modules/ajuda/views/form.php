<?php
/**
 * @var array|null $manual
 * @var array $erros
 * @var array $categorias
 * @var int   $limite
 */
declare(strict_types=1);

$editando  = $manual && !empty($manual['id']);
$titulo    = $editando ? 'Editar manual' : 'Publicar manual';
$actionUrl = url('ajuda', $editando ? 'atualizar' : 'salvar');
?>

<div class="mb-5 flex items-center gap-3">
    <a href="<?= url_lista('ajuda') ?>" class="btn btn-icon btn-outline" aria-label="Voltar">
        <?= Icon::svg('voltar', 'w-4 h-4') ?>
    </a>
    <div>
        <h1 class="titulo-pagina"><?= e($titulo) ?></h1>
        <p class="sub-pagina">
            <?= $editando
                ? 'Ajuste a ficha ou substitua o arquivo'
                : 'Envie o arquivo e diga do que ele trata' ?>
        </p>
    </div>
</div>

<form method="post" action="<?= $actionUrl ?>" class="max-w-4xl" novalidate
      enctype="multipart/form-data" x-data="envio" @submit="enviar">
    <?= Csrf::campo() ?>
    <?php if ($editando): ?>
        <input type="hidden" name="id" value="<?= (int) $manual['id'] ?>">
    <?php endif; ?>

    <div class="card p-5 lg:p-6">

        <div class="form-secao">
            <div>
                <h2 class="form-secao-titulo">O arquivo</h2>
                <p class="form-secao-desc">
                    PDF ou imagem, até <?= e(formatar_tamanho($limite)) ?>. Documentos do Word e do Excel não são
                    aceitos: eles chegam ao servidor com a mesma assinatura de um
                    arquivo compactado qualquer, e não há como conferir o conteúdo.
                    Exporte para PDF antes de enviar.
                </p>
            </div>

            <div>
                <?php if ($editando): ?>
                    <div class="mb-3 flex items-center gap-3 rounded-lg border border-slate-200 px-3 py-2.5 dark:border-slate-800">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg
                                     bg-marine-50 text-marine-600 dark:bg-marine-600/15 dark:text-marine-300">
                            <?= Icon::svg('arquivo', 'w-4 h-4') ?>
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-[0.8125rem] font-medium text-slate-800 dark:text-slate-100">
                                <?= e($manual['nome_orig'] ?? '') ?>
                            </span>
                            <span class="block text-xs text-slate-500 dark:text-slate-400">
                                <?= e(formatar_tamanho((int) ($manual['tamanho'] ?? 0))) ?> · arquivo atual
                            </span>
                        </span>
                        <?php if (!empty($manual['id'])): ?>
                            <a href="<?= url('ajuda', 'arquivo', ['id' => (int) $manual['id']]) ?>"
                               target="_blank" rel="noopener" class="btn btn-outline btn-sm">
                                <?= Icon::svg('ver', 'w-3.5 h-3.5') ?> Ver
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <label class="label" for="arquivo">
                    <?= $editando ? 'Substituir por outro arquivo' : 'Arquivo' ?>
                    <?php if (!$editando): ?><span class="obrigatorio">*</span><?php endif; ?>
                </label>
                <input type="file" name="arquivo" id="arquivo"
                       accept="application/pdf,image/png,image/jpeg,image/webp"
                       class="input <?= isset($erros['arquivo']) ? 'input-error' : '' ?> file:mr-3 file:rounded-md
                              file:border-0 file:bg-marine-600 file:px-3 file:py-1.5 file:text-xs
                              file:font-medium file:text-white hover:file:bg-marine-700"
                       <?= $editando ? '' : 'required' ?>>

                <?php if (isset($erros['arquivo'])): ?>
                    <p class="erro-campo"><?= Icon::svg('erro', 'w-3.5 h-3.5') ?> <?= e($erros['arquivo']) ?></p>
                <?php elseif ($editando): ?>
                    <p class="ajuda">Deixe em branco para manter o arquivo atual.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="form-secao">
            <div>
                <h2 class="form-secao-titulo">A ficha</h2>
                <p class="form-secao-desc">
                    É o que a pessoa lê antes de decidir abrir. Um título claro e uma
                    linha de descrição evitam que ela baixe o manual errado.
                </p>
            </div>

            <div class="form-campos">
                <div class="form-largo">
                    <label class="label" for="titulo">Título <span class="obrigatorio">*</span></label>
                    <input type="text" name="titulo" id="titulo" maxlength="150" required
                           class="input <?= isset($erros['titulo']) ? 'input-error' : '' ?>"
                           value="<?= e($manual['titulo'] ?? '') ?>"
                           placeholder="Ex.: Manual da Agenda do Ambulatório">
                    <?php if (isset($erros['titulo'])): ?>
                        <p class="erro-campo"><?= Icon::svg('erro', 'w-3.5 h-3.5') ?> <?= e($erros['titulo']) ?></p>
                    <?php endif; ?>
                </div>

                <div class="form-largo">
                    <label class="label" for="descricao">Descrição</label>
                    <textarea name="descricao" id="descricao" rows="2" maxlength="400" class="textarea"
                              placeholder="Uma ou duas linhas dizendo o que a pessoa encontra dentro."><?= e($manual['descricao'] ?? '') ?></textarea>
                    <p class="ajuda">Até 400 caracteres.</p>
                </div>

                <div>
                    <label class="label" for="categoria">Categoria</label>
                    <input type="text" name="categoria" id="categoria" maxlength="80"
                           class="input" list="categorias-ajuda"
                           value="<?= e($manual['categoria'] ?? 'Geral') ?>"
                           placeholder="Ex.: Ambulatório">
                    <p class="ajuda">Agrupa os manuais na tela. Escreva uma nova ou escolha uma já usada.</p>
                </div>

                <div>
                    <label class="label" for="ordem">Ordem</label>
                    <input type="number" name="ordem" id="ordem" min="0" max="9999" class="input num"
                           value="<?= (int) ($manual['ordem'] ?? 0) ?>">
                    <p class="ajuda">Menor número primeiro. Deixe 0 para entrar no fim.</p>
                </div>
            </div>
        </div>

        <?php if ($editando): ?>
            <div class="form-secao">
                <div>
                    <h2 class="form-secao-titulo">Publicação</h2>
                    <p class="form-secao-desc">
                        Despublicar tira o manual da vista de quem só lê, sem apagar o
                        arquivo. Serve para uma versão que ficou desatualizada.
                    </p>
                </div>

                <div>
                    <label class="flex cursor-pointer items-start gap-3">
                        <input type="checkbox" name="ativo" class="check mt-0.5" <?= ($manual['ativo'] ?? 1) ? 'checked' : '' ?>>
                        <span>
                            <span class="block text-[0.8125rem] font-medium text-slate-800 dark:text-slate-100">Publicado</span>
                            <span class="block text-xs text-slate-500 dark:text-slate-400">Visível para todos que têm acesso à Ajuda.</span>
                        </span>
                    </label>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="form-acoes">
        <a href="<?= url_lista('ajuda') ?>" class="btn btn-outline">Cancelar</a>
        <button type="submit" class="btn btn-primary" :disabled="enviando">
            <span x-show="!enviando"><?= Icon::svg('salvar', 'w-4 h-4') ?></span>
            <span x-show="enviando" x-cloak class="girando"></span>
            <?= $editando ? 'Salvar alterações' : 'Publicar manual' ?>
        </button>
    </div>
</form>

<datalist id="categorias-ajuda">
    <?php foreach ($categorias as $c): ?>
        <option value="<?= e($c) ?>"></option>
    <?php endforeach; ?>
</datalist>
