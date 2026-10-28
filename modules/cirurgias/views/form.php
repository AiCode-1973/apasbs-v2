<?php
/** @var array|null $cirurgia */
/** @var array $especialidades */
/** @var array $anexos */
/** @var array $erros */
declare(strict_types=1);

$editando  = $cirurgia && !empty($cirurgia['id']);
$titulo    = $editando ? 'Editar cirurgia' : 'Nova cirurgia';
$actionUrl = url('cirurgias', $editando ? 'atualizar' : 'salvar');

$codigoAtual = (string) ($cirurgia['tuss_codigo'] ?? '');
$termoAtual  = (string) ($cirurgia['tuss_termo'] ?? '');
$rotuloAtual = $codigoAtual !== '' ? $codigoAtual . ' — ' . $termoAtual : '';

$configTuss = json_encode([
    'url'    => url('cirurgias', 'buscar_tuss'),
    'codigo' => $codigoAtual,
    'termo'  => $termoAtual,
    'rotulo' => $rotuloAtual,
], JSON_UNESCAPED_UNICODE);
?>

<div class="mb-5 flex items-center gap-3">
    <a href="<?= url_lista('cirurgias') ?>" class="btn btn-icon btn-outline" aria-label="Voltar">
        <?= Icon::svg('voltar', 'w-4 h-4') ?>
    </a>
    <div>
        <h1 class="titulo-pagina"><?= e($titulo) ?></h1>
        <p class="sub-pagina"><?= $editando ? 'Atualize os dados do procedimento' : 'Preencha os dados do novo procedimento' ?></p>
    </div>
</div>

<form method="post" action="<?= $actionUrl ?>" enctype="multipart/form-data" class="max-w-4xl" novalidate
      x-data="envio" @submit="enviar">
    <?= Csrf::campo() ?>
    <?php if ($editando): ?>
        <input type="hidden" name="id" value="<?= (int) $cirurgia['id'] ?>">
    <?php endif; ?>

    <div class="card p-5 lg:p-6">

        <!-- Identificação -->
        <div class="form-secao">
            <div>
                <h2 class="form-secao-titulo">Identificação</h2>
                <p class="form-secao-desc">
                    O protocolo é único e serve de referência para localizar o procedimento.
                </p>
            </div>

            <div class="form-campos">
                <div>
                    <label class="label" for="protocolo">Número de protocolo <span class="obrigatorio">*</span></label>
                    <input type="text" name="protocolo" id="protocolo" maxlength="50" required autofocus
                           class="input <?= isset($erros['protocolo']) ? 'input-error' : '' ?>"
                           value="<?= e($cirurgia['protocolo'] ?? '') ?>" placeholder="Ex.: 2026/000123">
                    <?php if (isset($erros['protocolo'])): ?>
                        <p class="erro-campo"><?= Icon::svg('erro', 'w-3.5 h-3.5') ?> <?= e($erros['protocolo']) ?></p>
                    <?php endif; ?>
                </div>

                <div>
                    <label class="label" for="especialidade_id">Especialidade <span class="obrigatorio">*</span></label>
                    <select name="especialidade_id" id="especialidade_id" required
                            class="select <?= isset($erros['especialidade_id']) ? 'input-error' : '' ?>">
                        <option value="">Selecione…</option>
                        <?php foreach ($especialidades as $es): ?>
                            <option value="<?= (int) $es['id'] ?>"
                                <?= (int) ($cirurgia['especialidade_id'] ?? 0) === (int) $es['id'] ? 'selected' : '' ?>>
                                <?= e($es['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($erros['especialidade_id'])): ?>
                        <p class="erro-campo"><?= Icon::svg('erro', 'w-3.5 h-3.5') ?> <?= e($erros['especialidade_id']) ?></p>
                    <?php endif; ?>
                </div>

                <div class="form-largo">
                    <label class="label" for="medico">Médico responsável <span class="obrigatorio">*</span></label>
                    <div class="campo-icone">
                        <?= Icon::svg('usuario') ?>
                        <input type="text" name="medico" id="medico" maxlength="150" required
                               class="input <?= isset($erros['medico']) ? 'input-error' : '' ?>"
                               value="<?= e($cirurgia['medico'] ?? '') ?>" placeholder="Ex.: Dr. João Pereira">
                    </div>
                    <?php if (isset($erros['medico'])): ?>
                        <p class="erro-campo"><?= Icon::svg('erro', 'w-3.5 h-3.5') ?> <?= e($erros['medico']) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Procedimento TUSS -->
        <div class="form-secao">
            <div>
                <h2 class="form-secao-titulo">Procedimento TUSS</h2>
                <p class="form-secao-desc">
                    Busque na Tabela 22 da ANS pelo código ou pela descrição. O termo escolhido
                    é gravado junto com o código, preservando o registro histórico.
                </p>
            </div>

            <div x-data='tussAutocomplete(<?= $configTuss ?>)' @click.outside="aberto = false">
                <label class="label" for="tuss_busca">Código ou descrição <span class="obrigatorio">*</span></label>

                <input type="hidden" name="tuss_codigo" :value="codigo">
                <input type="hidden" name="tuss_termo"  :value="termo">

                <div class="relative">
                    <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">
                        <?= Icon::svg('buscar', 'w-4 h-4') ?>
                    </span>

                    <input type="text" id="tuss_busca" x-ref="entrada" x-model="busca" autocomplete="off"
                           class="input pl-9 pr-10 <?= isset($erros['tuss_codigo']) ? 'input-error' : '' ?>"
                           placeholder="Digite o código ou a descrição do procedimento…"
                           @input="digitou"
                           @keydown.arrow-down.prevent="desce"
                           @keydown.arrow-up.prevent="sobe"
                           @keydown.enter="enter"
                           @keydown.escape="aberto = false"
                           @blur="fechar">

                    <span class="absolute right-3 top-1/2 -translate-y-1/2">
                        <span x-show="carregando" x-cloak class="block h-4 w-4 animate-spin rounded-full border-2 border-marine-200 border-t-marine-600"></span>
                        <button type="button" x-show="!carregando && busca" x-cloak @click="limpar"
                                class="text-slate-400 transition-colors hover:text-slate-600 dark:hover:text-slate-200" aria-label="Limpar">
                            <?= Icon::svg('fechar', 'w-4 h-4') ?>
                        </button>
                    </span>

                    <!-- Sugestões -->
                    <div x-show="aberto" x-cloak x-transition.opacity
                         class="absolute z-20 mt-1 max-h-72 w-full overflow-y-auto rounded-lg border border-slate-200 bg-white shadow-pop dark:border-slate-700 dark:bg-slate-900">
                        <template x-for="(item, i) in itens" :key="item.codigo">
                            <button type="button" @mousedown.prevent="escolher(item)" @mouseenter="indice = i"
                                    class="flex w-full items-start gap-3 px-4 py-2 text-left transition-colors"
                                    :class="indice === i ? 'bg-marine-50 dark:bg-slate-800' : ''">
                                <span class="mt-0.5 shrink-0 font-mono text-[0.68rem] font-semibold text-marine-600 dark:text-marine-300" x-text="item.codigo"></span>
                                <span class="text-xs leading-snug text-slate-700 dark:text-slate-200" x-text="item.termo"></span>
                            </button>
                        </template>
                    </div>
                </div>

                <div x-show="selecionado" x-cloak class="mt-2 flex items-start gap-2 rounded-lg bg-emerald-50 px-3 py-2 text-xs text-emerald-800 dark:bg-emerald-500/10 dark:text-emerald-300">
                    <span class="mt-0.5 shrink-0"><?= Icon::svg('sucesso', 'w-4 h-4') ?></span>
                    <span><strong x-text="codigo"></strong> — <span x-text="termo"></span></span>
                </div>

                <?php if (isset($erros['tuss_codigo'])): ?>
                    <p class="erro-campo"><?= Icon::svg('erro', 'w-3.5 h-3.5') ?> <?= e($erros['tuss_codigo']) ?></p>
                <?php else: ?>
                    <p class="ajuda">Digite ao menos 2 caracteres e escolha uma opção da lista.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Anexos -->
        <div class="form-secao" x-data="anexos(10)">
            <div>
                <h2 class="form-secao-titulo">Anexos</h2>
                <p class="form-secao-desc">
                    PDF, JPG, PNG, GIF ou WEBP, até 10 MB cada. O tipo é verificado pelo
                    conteúdo do arquivo, não pela extensão do nome.
                </p>
            </div>

            <div class="space-y-3">
                <label for="anexos" class="flex cursor-pointer flex-col items-center justify-center gap-1.5 rounded-xl border-2 border-dashed border-slate-300 px-6 py-6 text-center transition-colors hover:border-marine-400 hover:bg-marine-50/40 dark:border-slate-700 dark:hover:border-marine-600 dark:hover:bg-slate-800/40">
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400">
                        <?= Icon::svg('upload', 'w-[18px] h-[18px]') ?>
                    </span>
                    <span class="text-[0.8125rem] font-medium text-slate-700 dark:text-slate-200">Clique para escolher os arquivos</span>
                    <span class="text-xs text-slate-500 dark:text-slate-400">Você pode selecionar vários de uma vez</span>
                    <input type="file" name="anexos[]" id="anexos" multiple class="sr-only"
                           accept="application/pdf,image/jpeg,image/png,image/gif,image/webp"
                           @change="selecionou">
                </label>

                <template x-if="arquivos.length">
                    <ul class="space-y-1.5">
                        <template x-for="a in arquivos" :key="a.nome">
                            <li class="flex items-center gap-2.5 rounded-lg border border-slate-200 px-3 py-1.5 text-[0.8125rem] dark:border-slate-700">
                                <span class="shrink-0 text-slate-400"><?= Icon::svg('arquivo', 'w-4 h-4') ?></span>
                                <span class="min-w-0 flex-1 truncate text-slate-700 dark:text-slate-200" x-text="a.nome"></span>
                                <span class="shrink-0 text-xs" :class="a.grande ? 'font-semibold text-rose-600' : 'text-slate-400'" x-text="a.tamanho"></span>
                            </li>
                        </template>
                    </ul>
                </template>

                <div x-show="algumGrande" x-cloak class="alerta alerta-aviso">
                    <span class="mt-0.5 shrink-0"><?= Icon::svg('aviso', 'w-4 h-4') ?></span>
                    <p>Arquivos acima de 10 MB não serão enviados.</p>
                </div>

                <?php if ($editando && $anexos): ?>
                    <div>
                        <p class="label">Já enviados</p>
                        <ul class="space-y-1.5">
                            <?php foreach ($anexos as $a): ?>
                                <li class="flex items-center gap-2.5 rounded-lg border border-slate-200 px-3 py-1.5 text-[0.8125rem] dark:border-slate-700">
                                    <span class="shrink-0 text-slate-400">
                                        <?= Icon::svg(str_starts_with((string) $a['mime'], 'image/') ? 'imagem' : 'arquivo', 'w-4 h-4') ?>
                                    </span>
                                    <a href="<?= url('cirurgias', 'download', ['id' => (int) $a['id']]) ?>" target="_blank" rel="noopener"
                                       class="min-w-0 flex-1 truncate text-marine-700 hover:underline dark:text-marine-300">
                                        <?= e($a['nome_orig']) ?>
                                    </a>
                                    <span class="shrink-0 text-xs text-slate-400"><?= formatar_tamanho((int) $a['tamanho']) ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <p class="ajuda">
                            Para remover um anexo, abra a
                            <a href="<?= url('cirurgias', 'detalhe', ['id' => (int) $cirurgia['id']]) ?>" class="text-marine-600 hover:underline dark:text-marine-300">tela de detalhes</a>.
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="form-acoes">
        <a href="<?= url_lista('cirurgias') ?>" class="btn btn-outline">Cancelar</a>
        <button type="submit" class="btn btn-primary" :disabled="enviando">
            <span x-show="!enviando"><?= Icon::svg('salvar', 'w-4 h-4') ?></span>
            <span x-show="enviando" x-cloak class="girando"></span>
            <?= $editando ? 'Salvar alterações' : 'Registrar cirurgia' ?>
        </button>
    </div>
</form>
