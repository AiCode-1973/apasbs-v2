<?php
/**
 * O contrato: criar e editar.
 *
 * Tela curta de propósito. O que ela decide — com ou sem coparticipação —
 * é a escolha mais importante do módulo, e merece uma tela em que ela não
 * divida a atenção com mais nada.
 *
 * @var array $c, $erros
 */
declare(strict_types=1);

$editando = !empty($c['id']);
$erroCls  = fn(string $x): string => isset($erros[$x]) ? 'input-error' : '';
$v        = fn(string $x, string $padrao = ''): string => (string) ($c[$x] ?? $padrao);

$erroDe = function (string $campo) use ($erros): string {
    return isset($erros[$campo])
        ? '<p class="erro-campo">' . Icon::svg('erro', 'w-3.5 h-3.5') . ' ' . e($erros[$campo]) . '</p>'
        : '';
};
?>

<div class="mb-5 flex items-start gap-3">
    <a href="<?= $editando ? url('cadastro', 'ver', ['id' => (int) $c['id']]) : url_lista('cadastro') ?>"
       class="btn-acao mt-0.5" aria-label="Voltar">
        <?= Icon::svg('anterior', 'w-4 h-4') ?>
    </a>
    <div>
        <h1 class="titulo-pagina"><?= $editando ? 'Editar contrato' : 'Novo contrato' ?></h1>
        <p class="sub-pagina">A família e o tipo de plano contratado</p>
    </div>
</div>

<form method="post" action="<?= url('cadastro', 'salvar') ?>" class="card"
      x-data="envio" @submit="enviar" novalidate>
    <?= Csrf::campo() ?>
    <?php if ($editando): ?>
        <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
    <?php endif; ?>

    <div class="card-corpo">
        <div class="form-secao">
            <div>
                <h2 class="form-secao-titulo">Coparticipação</h2>
                <p class="form-secao-desc">
                    É a escolha que define em qual plano cada pessoa da família entra.
                </p>
                <p class="form-secao-desc">
                    <strong>Sem coparticipação</strong> coloca todo o grupo no
                    STANDARD 4. <strong>Com coparticipação</strong> separa o titular e
                    o núcleo próximo (STANDARD 1) dos demais parentes (STANDARD 2).
                </p>
            </div>

            <div class="form-campos">
                <div class="form-largo">
                    <span class="label">Tipo do contrato <span class="obrigatorio">*</span></span>

                    <div class="mt-1 grid gap-2 sm:grid-cols-2">
                        <?php
                        $explica = [
                            'com_copart' => 'Titular e núcleo no STANDARD 1; demais parentes no STANDARD 2.',
                            'sem_copart' => 'Todos no STANDARD 4, inclusive menor sob guarda judicial.',
                        ];
                        foreach (Elegibilidade::TIPOS_CONTRATO as $chave => $rotulo): ?>
                            <label class="flex cursor-pointer items-start gap-2 rounded-lg border p-3 transition-colors
                                          <?= $v('tipo', 'com_copart') === $chave
                                              ? 'border-marine-400 bg-marine-50 dark:border-marine-500 dark:bg-marine-500/10'
                                              : 'border-slate-200 hover:bg-slate-50 dark:border-slate-800 dark:hover:bg-slate-800/60' ?>">
                                <input type="radio" name="tipo" value="<?= e($chave) ?>" class="check mt-0.5"
                                    <?= $v('tipo', 'com_copart') === $chave ? 'checked' : '' ?>>
                                <span class="text-[0.8125rem]">
                                    <strong><?= e($rotulo) ?></strong>
                                    <span class="mt-0.5 block text-xs text-slate-500 dark:text-slate-400">
                                        <?= e($explica[$chave]) ?>
                                    </span>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <?= $erroDe('tipo') ?>

                    <?php if ($editando): ?>
                        <p class="ajuda">
                            Mudar isto reclassifica a família inteira — confira o plano de
                            cada pessoa depois de salvar.
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="form-secao">
            <div>
                <h2 class="form-secao-titulo">Proposta</h2>
                <p class="form-secao-desc">
                    Os dados administrativos do contrato. O número da proposta pode
                    entrar depois, quando a papelada voltar assinada.
                </p>
            </div>

            <div class="form-campos">
                <div>
                    <label class="label" for="numero_proposta">Nº da proposta</label>
                    <input type="text" name="numero_proposta" id="numero_proposta" maxlength="30"
                           class="input mono <?= $erroCls('numero_proposta') ?>"
                           value="<?= e($v('numero_proposta')) ?>" placeholder="Ex.: 2026/0431">
                    <?= $erroDe('numero_proposta') ?>
                </div>

                <div>
                    <label class="label" for="data_adesao">Data de adesão</label>
                    <input type="date" name="data_adesao" id="data_adesao" class="input"
                           value="<?= e(substr($v('data_adesao', date('Y-m-d')), 0, 10)) ?>">
                </div>

                <div>
                    <label class="label" for="status">Situação <span class="obrigatorio">*</span></label>
                    <select name="status" id="status" required class="select <?= $erroCls('status') ?>">
                        <?php foreach (CadastroModel::STATUS as $chave => $rotulo): ?>
                            <option value="<?= e($chave) ?>" <?= $v('status', 'em_analise') === $chave ? 'selected' : '' ?>>
                                <?= e($rotulo) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?= $erroDe('status') ?>
                </div>

                <div class="form-largo">
                    <label class="label" for="observacao">Observação</label>
                    <input type="text" name="observacao" id="observacao" maxlength="400" class="input"
                           value="<?= e($v('observacao')) ?>"
                           placeholder="Ex.: proposta recebida pelo posto de Santos">
                </div>
            </div>
        </div>
    </div>

    <div class="form-acoes px-4 pb-4">
        <a href="<?= $editando ? url('cadastro', 'ver', ['id' => (int) $c['id']]) : url_lista('cadastro') ?>"
           class="btn btn-outline">Cancelar</a>
        <button type="submit" class="btn btn-primary" :disabled="enviando">
            <span x-show="!enviando"><?= Icon::svg('salvar', 'w-4 h-4') ?></span>
            <span x-show="enviando" x-cloak class="girando"></span>
            <?= $editando ? 'Salvar alterações' : 'Criar contrato e cadastrar o titular' ?>
        </button>
    </div>
</form>
