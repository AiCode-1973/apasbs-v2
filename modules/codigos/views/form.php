<?php
/**
 * Códigos de exame: incluir e editar.
 *
 * @var array $exame
 * @var array $erros, $convenios
 */
declare(strict_types=1);

$editando = !empty($exame['id']);
$erroCls  = fn(string $c): string => isset($erros[$c]) ? 'input-error' : '';
$v        = fn(string $c, string $padrao = ''): string => (string) ($exame[$c] ?? $padrao);

$config = json_encode([
    'url'    => url('codigos', 'buscar_tuss'),
    'linhas' => array_map(fn($c) => [
        'codigo' => (string) $c['codigo'],
        'termo'  => (string) ($c['termo'] ?? ''),
    ], (array) ($exame['codigos'] ?? [])),
], JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT);
?>

<div class="mb-5 flex items-start gap-3">
    <a href="<?= url_lista('codigos') ?>" class="btn-acao mt-0.5" aria-label="Voltar">
        <?= Icon::svg('anterior', 'w-4 h-4') ?>
    </a>
    <div>
        <h1 class="titulo-pagina"><?= $editando ? 'Editar exame' : 'Novo exame' ?></h1>
        <p class="sub-pagina">Com que código este convênio fatura este exame</p>
    </div>
</div>

<form method="post" action="<?= url('codigos', 'salvar') ?>" class="card"
      x-data='codigosTuss(<?= $config ?>)' @submit="enviar" novalidate>
    <?= Csrf::campo() ?>
    <?php if ($editando): ?>
        <input type="hidden" name="id" value="<?= (int) $exame['id'] ?>">
    <?php endif; ?>

    <div class="card-corpo">

        <!-- O exame -->
        <div class="form-secao">
            <div>
                <h2 class="form-secao-titulo">O exame</h2>
                <p class="form-secao-desc">
                    De qual convênio, e com que nome ele aparece na tabela dele.
                </p>
                <p class="form-secao-desc">
                    Falta um convênio na lista?
                    <a href="<?= url('autorizacoes', 'convenios') ?>"
                       class="text-marine-700 hover:underline dark:text-marine-300">Cadastre aqui</a>.
                </p>
            </div>

            <div class="form-campos">
                <div>
                    <label class="label" for="convenio_id">Convênio <span class="obrigatorio">*</span></label>
                    <select name="convenio_id" id="convenio_id" required class="select <?= $erroCls('convenio_id') ?>">
                        <option value="">Escolha…</option>
                        <?php foreach ($convenios as $c): ?>
                            <option value="<?= (int) $c['id'] ?>"
                                <?= (int) $v('convenio_id') === (int) $c['id'] ? 'selected' : '' ?>>
                                <?= e($c['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($erros['convenio_id'])): ?>
                        <p class="erro-campo"><?= Icon::svg('erro', 'w-3.5 h-3.5') ?> <?= e($erros['convenio_id']) ?></p>
                    <?php endif; ?>
                </div>

                <div>
                    <label class="label" for="ativo_wrap">Situação</label>
                    <label id="ativo_wrap" class="flex cursor-pointer items-start gap-2 pt-1.5">
                        <input type="checkbox" name="ativo" value="1" class="check mt-0.5"
                            <?= !$editando || !empty($exame['ativo']) ? 'checked' : '' ?>>
                        <span class="text-[0.8125rem]">
                            Ativo
                            <span class="block text-xs text-slate-500 dark:text-slate-400">
                                Desmarque quando o convênio parar de aceitar este código.
                            </span>
                        </span>
                    </label>
                </div>

                <div class="form-largo">
                    <label class="label" for="descricao">
                        Descrição do exame <span class="obrigatorio">*</span>
                    </label>
                    <input type="text" name="descricao" id="descricao" maxlength="200" required autofocus
                           class="input <?= $erroCls('descricao') ?>" value="<?= e($v('descricao')) ?>"
                           placeholder="Como o convênio chama — ex.: ECO TRANSTORÁCICO C/ DOPPLER">
                    <?php if (isset($erros['descricao'])): ?>
                        <p class="erro-campo">
                            <?= Icon::svg('erro', 'w-3.5 h-3.5') ?>
                            <span>
                                <?= e($erros['descricao']) ?>
                                <?php if (isset($erros['_link'])): ?>
                                    <a href="<?= e($erros['_link']) ?>" class="underline">Abrir o que já existe</a>.
                                <?php endif; ?>
                            </span>
                        </p>
                    <?php endif; ?>
                    <p class="ajuda">
                        Vale escrever como está na tabela do convênio, mesmo que não
                        seja o nome que a casa usa.
                    </p>
                </div>

                <div class="form-largo">
                    <label class="label" for="observacao">Observação</label>
                    <input type="text" name="observacao" id="observacao" maxlength="400" class="input"
                           value="<?= e($v('observacao')) ?>"
                           placeholder="Ex.: só autoriza com pedido do cardiologista">
                </div>
            </div>
        </div>

        <!-- Os códigos -->
        <div class="form-secao">
            <div>
                <h2 class="form-secao-titulo">Códigos</h2>
                <p class="form-secao-desc">
                    Um exame pode ter mais de um código — alguns convênios faturam
                    o mesmo exame em vários lançamentos.
                </p>
                <p class="form-secao-desc">
                    Digite o número ou o nome do procedimento: o sistema sugere da
                    Tabela 22 da ANS e traz a descrição oficial junto.
                </p>

                <div class="mt-3 rounded-lg bg-slate-50 p-3 text-xs dark:bg-slate-800/60">
                    <span class="rotulo-num">Nesta tela</span>
                    <p class="mt-0.5 text-[0.8125rem] text-slate-700 dark:text-slate-300">
                        <span x-text="quantos"></span>
                        <span x-text="quantos === 1 ? 'código' : 'códigos'"></span>
                    </p>
                </div>

                <?php if (isset($erros['codigos'])): ?>
                    <p class="erro-campo"><?= Icon::svg('erro', 'w-3.5 h-3.5') ?> <?= e($erros['codigos']) ?></p>
                <?php endif; ?>
            </div>

            <div>
                <?php /* Linhas soltas, e não uma <table> com overflow-x-auto:
                         aquele contêiner recorta a lista de sugestões, que é
                         posicionada por cima. Aqui ela aparece inteira, e em
                         tela estreita os campos empilham em vez de rolar. */ ?>
                <div class="space-y-2">
                    <div class="hidden gap-2 px-1 text-[0.6875rem] font-medium uppercase tracking-wider text-slate-500 sm:flex dark:text-slate-400">
                        <span class="w-40 shrink-0">Código</span>
                        <span class="flex-1">Descrição</span>
                        <span class="w-8 shrink-0"></span>
                    </div>

                    <template x-for="(l, i) in linhas" :key="i">
                        <div class="flex flex-col gap-2 rounded-lg border border-slate-200 p-2 sm:flex-row sm:items-start sm:border-0 sm:p-0 dark:border-slate-800">
                            <div class="relative w-full shrink-0 sm:w-40">
                                <input type="text" name="codigo[]" maxlength="30"
                                       class="input input-sm mono" x-model="l.codigo"
                                       @input="digitou(l)" @blur="fechar(l)"
                                       placeholder="40901165" autocomplete="off"
                                       :aria-label="'Código ' + (i + 1)">

                                <?php /* A lista da Tabela 22. Escolher preenche a descrição
                                         oficial; ignorar e seguir digitando também vale,
                                         para código próprio do convênio. */ ?>
                                <div x-show="l.aberto" x-cloak
                                     class="absolute left-0 top-full z-20 mt-1 max-h-64 w-full min-w-[22rem] overflow-y-auto rounded-lg border border-slate-200 bg-white shadow-pop dark:border-slate-700 dark:bg-slate-900">
                                    <template x-for="s in l.sugestoes" :key="s.codigo">
                                        <button type="button" @mousedown.prevent="escolher(l, s)"
                                                class="block w-full border-b border-slate-100 px-3 py-2 text-left text-xs last:border-0 hover:bg-slate-50 dark:border-slate-800 dark:hover:bg-slate-800">
                                            <span class="mono font-semibold text-marine-700 dark:text-marine-300"
                                                  x-text="s.codigo"></span>
                                            <span class="block text-slate-600 dark:text-slate-400"
                                                  x-text="s.termo"></span>
                                        </button>
                                    </template>
                                </div>
                            </div>

                            <?php /* @input marca a linha como escrita à mão: a partir
                                     daí o autocomplete não troca mais a descrição. */ ?>
                            <input type="text" name="termo[]" maxlength="300"
                                   class="input input-sm flex-1" x-model="l.termo"
                                   @input="escreveu(l)"
                                   placeholder="Vem da Tabela 22 sozinha — ou escreva"
                                   :aria-label="'Descrição do código ' + (i + 1)">

                            <button type="button" class="btn-acao btn-acao-perigo shrink-0"
                                    title="Remover este código" @click="remover(i)"
                                    :aria-label="'Remover o código ' + (i + 1)">
                                <?= Icon::svg('excluir', 'w-4 h-4') ?>
                            </button>
                        </div>
                    </template>
                </div>

                <button type="button" class="btn btn-ghost btn-sm mt-2" @click="acrescentar">
                    <?= Icon::svg('novo', 'w-3.5 h-3.5') ?> Acrescentar um código
                </button>

                <p class="ajuda">
                    Código que não está na Tabela 22 é aceito — nem todo convênio
                    usa TUSS. A descrição, nesse caso, escreva você.
                </p>
            </div>
        </div>
    </div>

    <div class="form-acoes px-4 pb-4">
        <a href="<?= $editando ? url('codigos', 'ver', ['id' => (int) $exame['id']]) : url_lista('codigos') ?>"
           class="btn btn-outline">Cancelar</a>
        <button type="submit" class="btn btn-primary" :disabled="enviando">
            <span x-show="!enviando"><?= Icon::svg('salvar', 'w-4 h-4') ?></span>
            <span x-show="enviando" x-cloak class="girando"></span>
            <?= $editando ? 'Salvar alterações' : 'Cadastrar exame' ?>
        </button>
    </div>
</form>
