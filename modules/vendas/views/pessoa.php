<?php
/**
 * Uma vida da família, dentro da proposta.
 *
 * Só o que a venda precisa saber para o Cadastro classificar a pessoa
 * depois: nome, nascimento, parentesco e as condições que mudam o plano.
 * O resto do cadastro (CNS, nome da mãe, endereço) é do Cadastro, e pedir
 * aqui seria fazer a família responder duas vezes.
 *
 * @var array $v, $p, $erros
 */
declare(strict_types=1);

$editando = !empty($v['id']);
$erroCls  = fn(string $c): string => isset($erros[$c]) ? 'input-error' : '';
$val      = fn(string $c, string $padrao = ''): string => (string) ($v[$c] ?? $padrao);
$marcado  = fn(string $c): bool => !empty($v[$c]);

$erroDe = function (string $campo) use ($erros): string {
    return isset($erros[$campo])
        ? '<p class="erro-campo">' . Icon::svg('erro', 'w-3.5 h-3.5') . ' ' . e($erros[$campo]) . '</p>'
        : '';
};
?>

<div class="mb-5 flex items-start gap-3">
    <a href="<?= url('vendas', 'ver', ['id' => (int) $p['id']]) ?>" class="btn-acao mt-0.5" aria-label="Voltar">
        <?= Icon::svg('anterior', 'w-4 h-4') ?>
    </a>
    <div>
        <h1 class="titulo-pagina"><?= $editando ? 'Editar pessoa' : 'Incluir na família' ?></h1>
        <p class="sub-pagina">
            Proposta <span class="mono"><?= e($p['numero']) ?></span> ·
            titular <?= e($p['titular_nome']) ?>
        </p>
    </div>
</div>

<form method="post" action="<?= url('vendas', 'salvar_pessoa') ?>" class="card"
      x-data="envio" @submit="enviar" novalidate>
    <?= Csrf::campo() ?>
    <input type="hidden" name="proposta_id" value="<?= (int) $p['id'] ?>">
    <?php if ($editando): ?>
        <input type="hidden" name="id" value="<?= (int) $v['id'] ?>">
    <?php endif; ?>

    <div class="card-corpo">
        <div class="form-secao">
            <div>
                <h2 class="form-secao-titulo">Quem é</h2>
                <p class="form-secao-desc">
                    O parentesco é o que define em qual plano a pessoa entra quando a
                    venda fechar. CPF e nascimento podem ficar para depois.
                </p>
                <p class="form-secao-desc">
                    A data de nascimento muda a classificação dos filhos: menor de 18
                    entra no núcleo, maior entra no STANDARD 2.
                </p>
            </div>

            <div class="form-campos">
                <div class="form-largo">
                    <label class="label" for="nome">Nome <span class="obrigatorio">*</span></label>
                    <input type="text" name="nome" id="nome" maxlength="150" required autofocus
                           value="<?= e($val('nome')) ?>" class="input <?= $erroCls('nome') ?>">
                    <?= $erroDe('nome') ?>
                </div>

                <div>
                    <label class="label" for="parentesco">Parentesco com o titular <span class="obrigatorio">*</span></label>
                    <select name="parentesco" id="parentesco" required class="select <?= $erroCls('parentesco') ?>">
                        <option value="">Escolha…</option>
                        <?php foreach (Elegibilidade::PARENTESCOS as $grupo => $itens): ?>
                            <optgroup label="<?= e($grupo) ?>">
                                <?php foreach ($itens as $chave => $dados): ?>
                                    <option value="<?= e($chave) ?>" <?= $val('parentesco') === $chave ? 'selected' : '' ?>>
                                        <?= e($dados['rotulo']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endforeach; ?>
                    </select>
                    <?= $erroDe('parentesco') ?>
                </div>

                <div>
                    <label class="label" for="nascimento">Nascimento</label>
                    <input type="date" name="nascimento" id="nascimento" class="input"
                           value="<?= e(substr($val('nascimento'), 0, 10)) ?>">
                </div>

                <div>
                    <label class="label" for="cpf">CPF</label>
                    <input type="text" name="cpf" id="cpf" inputmode="numeric" maxlength="14"
                           class="input mono <?= $erroCls('cpf') ?>"
                           value="<?= e($val('cpf') !== '' ? formatar_cpf($val('cpf')) : '') ?>"
                           x-data="cpf" @input="formatar" placeholder="000.000.000-00">
                    <?= $erroDe('cpf') ?>
                </div>

                <div>
                    <label class="label" for="observacao">Observação</label>
                    <input type="text" name="observacao" id="observacao" maxlength="300" class="input"
                           value="<?= e($val('observacao')) ?>">
                </div>
            </div>
        </div>

        <div class="form-secao">
            <div>
                <h2 class="form-secao-titulo">O que já está comprovado</h2>
                <p class="form-secao-desc">
                    Marque o que a família já tem documentado. Isso muda a classificação —
                    enteado menor com guarda comprovada, por exemplo, sobe para o
                    STANDARD 1.
                </p>
                <p class="form-secao-desc">
                    Nada aqui trava a venda: o que faltar aparece como pendência no
                    Cadastro.
                </p>
            </div>

            <div class="form-campos">
                <div class="form-largo grid gap-2 sm:grid-cols-2">
                    <?php foreach (Elegibilidade::CONDICOES as $chave => $rotulo): ?>
                        <label class="flex cursor-pointer items-start gap-2 text-sm">
                            <input type="checkbox" name="<?= e($chave) ?>" class="check mt-0.5"
                                   <?= $marcado($chave) ? 'checked' : '' ?>>
                            <span class="text-slate-700 dark:text-slate-300"><?= e(ucfirst($rotulo)) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="form-acoes px-4 pb-4">
        <a href="<?= url('vendas', 'ver', ['id' => (int) $p['id']]) ?>" class="btn btn-outline">Cancelar</a>
        <button type="submit" class="btn btn-primary" :disabled="enviando">
            <span x-show="!enviando"><?= Icon::svg('salvar', 'w-4 h-4') ?></span>
            <span x-show="enviando" x-cloak class="girando"></span>
            <?= $editando ? 'Salvar alterações' : 'Incluir na família' ?>
        </button>
    </div>
</form>
