<?php
/**
 * Um item da tabela de valores: incluir e editar.
 *
 * O que entra aqui à mão é o que o edital não trouxe — item negociado por
 * fora, correção de uma linha que veio torta do PDF, acordo pontual.
 *
 * @var array $i, $erros, $tabelas, $grupos
 */
declare(strict_types=1);

$editando = !empty($i['id']);
$erroCls  = fn(string $c): string => isset($erros[$c]) ? 'input-error' : '';
$v        = fn(string $c, string $padrao = ''): string => (string) ($i[$c] ?? $padrao);

$erroDe = function (string $campo) use ($erros): string {
    return isset($erros[$campo])
        ? '<p class="erro-campo">' . Icon::svg('erro', 'w-3.5 h-3.5') . ' ' . e($erros[$campo]) . '</p>'
        : '';
};
?>

<div class="mb-5 flex items-start gap-3">
    <a href="<?= $editando ? url('comercial', 'ver', ['id' => (int) $i['id']]) : url_lista('comercial') ?>"
       class="btn-acao mt-0.5" aria-label="Voltar">
        <?= Icon::svg('anterior', 'w-4 h-4') ?>
    </a>
    <div>
        <h1 class="titulo-pagina"><?= $editando ? 'Editar item' : 'Novo item' ?></h1>
        <p class="sub-pagina">Tabela de valores do convênio</p>
    </div>
</div>

<form method="post" action="<?= url('comercial', 'salvar') ?>" class="card"
      x-data="{ enviando: false }" @submit="enviando = true" novalidate>
    <?= Csrf::campo() ?>
    <?php if ($editando): ?>
        <input type="hidden" name="id" value="<?= (int) $i['id'] ?>">
    <?php endif; ?>

    <div class="card-corpo">
        <div class="form-secao">
            <div>
                <h2 class="form-secao-titulo">Onde o item entra</h2>
                <p class="form-secao-desc">
                    A tabela é o edital com a vigência; o tipo é o que mais se usa
                    para filtrar, e o grupo guarda a seção do edital.
                </p>
            </div>

            <div class="form-campos">
                <div>
                    <label class="label" for="tabela_id">Tabela de valores <span class="obrigatorio">*</span></label>
                    <select name="tabela_id" id="tabela_id" required class="select <?= $erroCls('tabela_id') ?>">
                        <?php foreach ($tabelas as $t): ?>
                            <option value="<?= (int) $t['id'] ?>"
                                <?= (int) $v('tabela_id') === (int) $t['id'] ? 'selected' : '' ?>>
                                <?= e($t['convenio']) ?> — <?= e($t['referencia']) ?>
                                (<?= e(data_br($t['vigencia'])) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?= $erroDe('tabela_id') ?>
                </div>

                <div>
                    <label class="label" for="tipo">Tipo <span class="obrigatorio">*</span></label>
                    <select name="tipo" id="tipo" required class="select <?= $erroCls('tipo') ?>">
                        <?php foreach (ComercialModel::TIPOS as $chave => $rotulo): ?>
                            <option value="<?= e($chave) ?>" <?= $v('tipo') === $chave ? 'selected' : '' ?>>
                                <?= e($rotulo) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?= $erroDe('tipo') ?>
                </div>

                <div class="form-largo">
                    <label class="label" for="grupo">Grupo</label>
                    <input type="text" name="grupo" id="grupo" maxlength="120" class="input"
                           list="grupos-existentes" value="<?= e($v('grupo')) ?>"
                           placeholder="Ex.: Atendimentos e SADT">
                    <datalist id="grupos-existentes">
                        <?php foreach ($grupos as $g): ?>
                            <option value="<?= e($g['grupo']) ?>"></option>
                        <?php endforeach; ?>
                    </datalist>
                </div>
            </div>
        </div>

        <div class="form-secao">
            <div>
                <h2 class="form-secao-titulo">O item</h2>
                <p class="form-secao-desc">
                    A variante é para o mesmo item com preço diferente por recorte —
                    PORTE I, PORTE II, Honorários, SADT.
                </p>
                <p class="form-secao-desc">
                    Os dados extras são um JSON livre, do jeito que a carga do edital
                    grava: <span class="mono">{"porte":"3A","pagina":57}</span>.
                </p>
            </div>

            <div class="form-campos">
                <div>
                    <label class="label" for="codigo">Código</label>
                    <input type="text" name="codigo" id="codigo" maxlength="30" class="input mono"
                           value="<?= e($v('codigo')) ?>" placeholder="TUSS, CBHPM ou do edital">
                </div>

                <div>
                    <label class="label" for="variante">Variante</label>
                    <input type="text" name="variante" id="variante" maxlength="60" class="input"
                           value="<?= e($v('variante')) ?>" placeholder="PORTE I, SADT…">
                </div>

                <div class="form-largo">
                    <label class="label" for="descricao">Descrição <span class="obrigatorio">*</span></label>
                    <textarea name="descricao" id="descricao" rows="2" maxlength="400" required
                              class="textarea <?= $erroCls('descricao') ?>"><?= e($v('descricao')) ?></textarea>
                    <?= $erroDe('descricao') ?>
                </div>

                <div>
                    <label class="label" for="valor">Valor</label>
                    <input type="text" name="valor" id="valor" class="input num" inputmode="decimal"
                           value="<?= e($i['valor'] !== null ? number_format((float) $i['valor'], 2, ',', '.') : '') ?>"
                           placeholder="0,00">
                    <?= $erroDe('valor') ?>
                </div>

                <div class="form-largo">
                    <label class="label" for="extras">Dados extras (JSON)</label>
                    <textarea name="extras" id="extras" rows="3"
                              class="textarea font-mono text-xs <?= $erroCls('extras') ?>"><?= e($v('extras')) ?></textarea>
                    <?= $erroDe('extras') ?>
                    <p class="ajuda">
                        Em branco, tudo bem. Campos conhecidos:
                        <?= e(implode(', ', array_keys(ComercialModel::EXTRAS))) ?>.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="form-acoes px-4 pb-4">
        <a href="<?= $editando ? url('comercial', 'ver', ['id' => (int) $i['id']]) : url_lista('comercial') ?>"
           class="btn btn-outline">Cancelar</a>
        <button type="submit" class="btn btn-primary" :disabled="enviando">
            <span x-show="!enviando"><?= Icon::svg('salvar', 'w-4 h-4') ?></span>
            <span x-show="enviando" x-cloak class="girando"></span>
            <?= $editando ? 'Salvar alterações' : 'Incluir item' ?>
        </button>
    </div>
</form>
