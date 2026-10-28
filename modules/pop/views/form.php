<?php
/**
 * @var array $pop
 * @var array $blocos
 * @var array $erros
 * @var array $categorias
 */
declare(strict_types=1);

$editando  = $pop && !empty($pop['id']);
$titulo    = $editando ? 'Editar procedimento' : 'Novo procedimento';
$actionUrl = url('pop', $editando ? 'atualizar' : 'salvar');

// O editor de blocos é montado pelo Alpine a partir deste JSON
$blocosJson = json_encode(
    array_map(fn($b) => [
        'tipo'     => $b['tipo'] ?? 'texto',
        'titulo'   => $b['titulo'] ?? '',
        'conteudo' => $b['conteudo'] ?? '',
    ], $blocos),
    JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT
);
?>

<div class="mb-5 flex items-center gap-3">
    <a href="<?= url_lista('pop') ?>" class="btn btn-icon btn-outline" aria-label="Voltar">
        <?= Icon::svg('voltar', 'w-4 h-4') ?>
    </a>
    <div>
        <h1 class="titulo-pagina"><?= e($titulo) ?></h1>
        <p class="sub-pagina">
            <?= $editando ? 'Ajuste a ficha e o conteúdo' : 'Monte o procedimento em blocos' ?>
        </p>
    </div>
</div>

<form method="post" action="<?= $actionUrl ?>" class="max-w-4xl" novalidate
      x-data="popBlocos(<?= e($blocosJson) ?>)" @submit="enviar">
    <?= Csrf::campo() ?>
    <?php if ($editando): ?>
        <input type="hidden" name="id" value="<?= (int) $pop['id'] ?>">
    <?php endif; ?>

    <div class="card p-5 lg:p-6">

        <div class="form-secao">
            <div>
                <h2 class="form-secao-titulo">Identificação</h2>
                <p class="form-secao-desc">
                    O código é o que aparece nas referências cruzadas entre procedimentos.
                    Mantenha o padrão POP-CA-000.
                </p>
            </div>

            <div class="form-campos">
                <div>
                    <label class="label" for="codigo">Código <span class="obrigatorio">*</span></label>
                    <input type="text" name="codigo" id="codigo" maxlength="30" required
                           class="input mono uppercase <?= isset($erros['codigo']) ? 'input-error' : '' ?>"
                           value="<?= e($pop['codigo'] ?? '') ?>" placeholder="POP-CA-014">
                    <?php if (isset($erros['codigo'])): ?>
                        <p class="erro-campo"><?= Icon::svg('erro', 'w-3.5 h-3.5') ?> <?= e($erros['codigo']) ?></p>
                    <?php endif; ?>
                </div>

                <div>
                    <label class="label" for="versao">Versão</label>
                    <input type="text" name="versao" id="versao" maxlength="10" class="input mono"
                           value="<?= e($pop['versao'] ?? '1.0') ?>">
                </div>

                <div class="form-largo">
                    <label class="label" for="titulo">Título <span class="obrigatorio">*</span></label>
                    <input type="text" name="titulo" id="titulo" maxlength="180" required
                           class="input <?= isset($erros['titulo']) ? 'input-error' : '' ?>"
                           value="<?= e($pop['titulo'] ?? '') ?>"
                           placeholder="Ex.: Endoscopia — sem internação (manhã)">
                    <?php if (isset($erros['titulo'])): ?>
                        <p class="erro-campo"><?= Icon::svg('erro', 'w-3.5 h-3.5') ?> <?= e($erros['titulo']) ?></p>
                    <?php endif; ?>
                </div>

                <div class="form-largo">
                    <label class="label" for="resumo">Resumo</label>
                    <textarea name="resumo" id="resumo" rows="2" maxlength="400" class="textarea"
                              placeholder="Uma ou duas linhas dizendo em que situação este procedimento se aplica."><?= e($pop['resumo'] ?? '') ?></textarea>
                </div>

                <div>
                    <label class="label" for="categoria">Categoria</label>
                    <input type="text" name="categoria" id="categoria" maxlength="80" class="input"
                           list="categorias-pop" value="<?= e($pop['categoria'] ?? 'Geral') ?>">
                </div>

                <div>
                    <label class="label" for="revisado_em">Revisado em</label>
                    <input type="date" name="revisado_em" id="revisado_em" class="input"
                           value="<?= e($pop['revisado_em'] ?? '') ?>">
                </div>

                <div>
                    <label class="label" for="ordem">Ordem</label>
                    <input type="number" name="ordem" id="ordem" min="0" max="9999" class="input num"
                           value="<?= (int) ($pop['ordem'] ?? 0) ?>">
                    <p class="ajuda">Menor primeiro. 0 entra no fim.</p>
                </div>
            </div>
        </div>

        <div class="form-secao">
            <div>
                <h2 class="form-secao-titulo">Conteúdo</h2>
                <p class="form-secao-desc">
                    O corpo é montado em blocos. Cada tipo tem uma função de leitura
                    diferente, e é isso que faz a atendente achar o que precisa em
                    segundos em vez de ler tudo.
                </p>

                <ul class="mt-3 space-y-1.5 text-xs text-slate-500 dark:text-slate-400">
                    <li><strong>Parágrafo</strong> — explicação corrida.</li>
                    <li><strong>Lista de passos</strong> — uma linha vira um passo numerado.</li>
                    <li><strong>Alerta</strong> — caixa destacada, para o que não pode ser esquecido.</li>
                    <li><strong>Mensagem</strong> — texto pronto, com botão de copiar na tela.</li>
                </ul>
            </div>

            <div>
                <?php if (isset($erros['blocos'])): ?>
                    <p class="erro-campo mb-2"><?= Icon::svg('erro', 'w-3.5 h-3.5') ?> <?= e($erros['blocos']) ?></p>
                <?php endif; ?>

                <div class="space-y-3">
                    <template x-for="(b, i) in blocos" :key="i">
                        <div class="rounded-lg border border-slate-200 p-3 dark:border-slate-800">
                            <div class="mb-2 flex flex-wrap items-end gap-2">
                                <div class="w-44">
                                    <label class="label">Tipo</label>
                                    <select class="select input-sm" name="tipo[]" x-model="b.tipo">
                                        <?php foreach (PopModel::TIPOS as $valor => $rotulo): ?>
                                            <option value="<?= e($valor) ?>"><?= e($rotulo) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="min-w-[12rem] flex-1">
                                    <label class="label">Título do bloco</label>
                                    <input type="text" name="titulo_bloco[]" maxlength="120"
                                           class="input input-sm" x-model="b.titulo"
                                           placeholder="Opcional. Ex.: O que fazer">
                                </div>

                                <div class="flex items-center gap-0.5 pb-0.5">
                                    <button type="button" class="btn-acao" title="Subir"
                                            @click="mover(i, -1)" :disabled="i === 0">
                                        <?= Icon::svg('subiu', 'w-4 h-4') ?>
                                    </button>
                                    <button type="button" class="btn-acao" title="Descer"
                                            @click="mover(i, 1)" :disabled="i === blocos.length - 1">
                                        <?= Icon::svg('desceu', 'w-4 h-4') ?>
                                    </button>
                                    <button type="button" class="btn-acao btn-acao-perigo" title="Remover"
                                            @click="remover(i)">
                                        <?= Icon::svg('excluir', 'w-4 h-4') ?>
                                    </button>
                                </div>
                            </div>

                            <textarea name="conteudo[]" rows="5" class="textarea font-mono text-xs"
                                      x-model="b.conteudo"
                                      :placeholder="b.tipo === 'passos'
                                          ? 'Uma linha por passo.'
                                          : (b.tipo === 'mensagem'
                                              ? 'Texto que a atendente vai copiar. Use [COLCHETES] no que ela precisa substituir.'
                                              : 'Escreva aqui.')"></textarea>
                        </div>
                    </template>
                </div>

                <div class="mt-3 flex flex-wrap gap-2">
                    <?php foreach (PopModel::TIPOS as $valor => $rotulo): ?>
                        <button type="button" class="btn btn-outline btn-sm" @click="adicionar('<?= e($valor) ?>')">
                            <?= Icon::svg('novo', 'w-3.5 h-3.5') ?> <?= e($rotulo) ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <?php if ($editando): ?>
            <div class="form-secao">
                <div>
                    <h2 class="form-secao-titulo">Publicação</h2>
                    <p class="form-secao-desc">
                        Despublicar tira da vista de quem só lê, sem apagar. Serve para
                        um procedimento que está sendo revisado.
                    </p>
                </div>

                <div>
                    <label class="flex cursor-pointer items-start gap-3">
                        <input type="checkbox" name="ativo" class="check mt-0.5" <?= ($pop['ativo'] ?? 1) ? 'checked' : '' ?>>
                        <span>
                            <span class="block text-[0.8125rem] font-medium text-slate-800 dark:text-slate-100">Publicado</span>
                            <span class="block text-xs text-slate-500 dark:text-slate-400">Visível para toda a central.</span>
                        </span>
                    </label>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="form-acoes">
        <a href="<?= url_lista('pop') ?>" class="btn btn-outline">Cancelar</a>
        <button type="submit" class="btn btn-primary" :disabled="enviando">
            <span x-show="!enviando"><?= Icon::svg('salvar', 'w-4 h-4') ?></span>
            <span x-show="enviando" x-cloak class="girando"></span>
            <?= $editando ? 'Salvar alterações' : 'Publicar procedimento' ?>
        </button>
    </div>
</form>

<datalist id="categorias-pop">
    <?php foreach ($categorias as $c): ?>
        <option value="<?= e($c) ?>"></option>
    <?php endforeach; ?>
</datalist>
