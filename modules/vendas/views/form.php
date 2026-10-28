<?php
/**
 * A proposta: registrar e editar.
 *
 * Só o que a venda precisa saber enquanto é promessa. O resto da família
 * entra no Cadastro depois que a proposta fecha — aqui seria digitação em
 * dobro, e dado em dois lugares diverge.
 *
 * @var array $p, $erros, $vendedores
 * @var int   $trava
 */
declare(strict_types=1);

$editando = !empty($p['id']);
$erroCls  = fn(string $c): string => isset($erros[$c]) ? 'input-error' : '';
$v        = fn(string $c, string $padrao = ''): string => (string) ($p[$c] ?? $padrao);

$erroDe = function (string $campo) use ($erros): string {
    return isset($erros[$campo])
        ? '<p class="erro-campo">' . Icon::svg('erro', 'w-3.5 h-3.5') . ' ' . e($erros[$campo]) . '</p>'
        : '';
};
?>

<div class="mb-5 flex items-start gap-3">
    <a href="<?= $editando ? url('vendas', 'ver', ['id' => (int) $p['id']]) : url_lista('vendas') ?>"
       class="btn-acao mt-0.5" aria-label="Voltar">
        <?= Icon::svg('anterior', 'w-4 h-4') ?>
    </a>
    <div>
        <h1 class="titulo-pagina"><?= $editando ? 'Editar proposta' : 'Nova proposta' ?></h1>
        <p class="sub-pagina">
            <?php if ($editando): ?>
                <span class="mono"><?= e($v('numero')) ?></span> — em negociação
            <?php else: ?>
                O número sai ao gravar
            <?php endif; ?>
        </p>
    </div>
</div>

<form method="post" action="<?= url('vendas', 'salvar') ?>" class="card"
      x-data="envio" @submit="enviar" novalidate>
    <?= Csrf::campo() ?>
    <?php if ($editando): ?>
        <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
    <?php endif; ?>

    <div class="card-corpo">
        <div class="form-secao">
            <div>
                <h2 class="form-secao-titulo">Quem vendeu</h2>
                <p class="form-secao-desc">
                    O vendedor responde pela proposta e é por ele que a comissão é
                    calculada depois do fechamento.
                </p>
            </div>

            <div class="form-campos">
                <div class="form-largo">
                    <label class="label" for="vendedor_id">Vendedor <span class="obrigatorio">*</span></label>
                    <?php if ($trava): ?>
                        <?php /* Vendedor com login não escolhe: a venda é dele. O
                                 controller impõe isso de novo, porque campo
                                 desabilitado não é trava. */ ?>
                        <input type="text" class="input" disabled
                               value="<?= e(current(array_filter($vendedores, fn($x) => (int) $x['id'] === $trava))['nome'] ?? 'Você') ?>">
                    <?php else: ?>
                        <select name="vendedor_id" id="vendedor_id" required autofocus
                                class="select <?= $erroCls('vendedor_id') ?>">
                            <option value="">Escolha…</option>
                            <?php foreach ($vendedores as $vend): ?>
                                <option value="<?= (int) $vend['id'] ?>"
                                    <?= (int) $v('vendedor_id') === (int) $vend['id'] ? 'selected' : '' ?>>
                                    <?= e($vend['nome']) ?> — <?= e($vend['empresa']) ?>
                                    (<?= e(number_format((float) $vend['comissao_percent'], 2, ',', '')) ?>%)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?= $erroDe('vendedor_id') ?>
                    <?php endif; ?>
                </div>

                <div>
                    <label class="label" for="data_proposta">Data da proposta <span class="obrigatorio">*</span></label>
                    <input type="date" name="data_proposta" id="data_proposta" required
                           value="<?= e(substr($v('data_proposta', date('Y-m-d')), 0, 10)) ?>"
                           class="input <?= $erroCls('data_proposta') ?>">
                    <?= $erroDe('data_proposta') ?>
                </div>
            </div>
        </div>

        <div class="form-secao">
            <div>
                <h2 class="form-secao-titulo">Quem será titular</h2>
                <p class="form-secao-desc">
                    Só Policial Militar ativo, inativo ou pensionista pode ser titular.
                    A categoria é obrigatória para fechar — sem ela o contrato não nasce.
                </p>
                <p class="form-secao-desc">
                    CPF e nascimento podem ficar para depois: na primeira conversa nem
                    sempre se tem o documento na mão.
                </p>
            </div>

            <div class="form-campos">
                <div class="form-largo">
                    <label class="label" for="titular_nome">Nome <span class="obrigatorio">*</span></label>
                    <input type="text" name="titular_nome" id="titular_nome" maxlength="150" required
                           value="<?= e($v('titular_nome')) ?>" class="input <?= $erroCls('titular_nome') ?>">
                    <?= $erroDe('titular_nome') ?>
                </div>

                <div>
                    <label class="label" for="titular_cpf">CPF</label>
                    <input type="text" name="titular_cpf" id="titular_cpf" inputmode="numeric" maxlength="14"
                           class="input mono <?= $erroCls('titular_cpf') ?>"
                           value="<?= e($v('titular_cpf') !== '' ? formatar_cpf($v('titular_cpf')) : '') ?>"
                           x-data="cpf" @input="formatar" placeholder="000.000.000-00">
                    <?= $erroDe('titular_cpf') ?>
                </div>

                <div>
                    <label class="label" for="titular_nascimento">Nascimento</label>
                    <input type="date" name="titular_nascimento" id="titular_nascimento" class="input"
                           value="<?= e(substr($v('titular_nascimento'), 0, 10)) ?>">
                </div>

                <div>
                    <label class="label" for="titular_categoria">Categoria</label>
                    <select name="titular_categoria" id="titular_categoria"
                            class="select <?= $erroCls('titular_categoria') ?>">
                        <option value="">A confirmar</option>
                        <?php foreach (Elegibilidade::CATEGORIAS as $chave => $rotulo): ?>
                            <option value="<?= e($chave) ?>" <?= $v('titular_categoria') === $chave ? 'selected' : '' ?>>
                                <?= e($rotulo) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?= $erroDe('titular_categoria') ?>
                </div>

                <div>
                    <label class="label" for="contato">Telefone</label>
                    <input type="tel" name="contato" id="contato" inputmode="numeric" maxlength="15"
                           class="input" value="<?= e(telefone_br($v('contato'))) ?>"
                           x-data="telefone" @input="formatar" placeholder="(13) 99999-9999">
                </div>

                <div>
                    <label class="label" for="email">E-mail</label>
                    <input type="email" name="email" id="email" maxlength="150"
                           class="input <?= $erroCls('email') ?>" value="<?= e($v('email')) ?>">
                    <?= $erroDe('email') ?>
                </div>
            </div>
        </div>

        <div class="form-secao">
            <div>
                <h2 class="form-secao-titulo">O que está sendo vendido</h2>
                <p class="form-secao-desc">
                    O tipo do contrato decide o plano da família: sem coparticipação é
                    STANDARD 4; com coparticipação, o titular entra no STANDARD 1.
                </p>
                <p class="form-secao-desc">
                    Vidas é quanta gente a família prevê incluir — serve para acompanhar
                    a venda, e não trava nada no Cadastro.
                </p>
            </div>

            <div class="form-campos">
                <div>
                    <label class="label" for="tipo">Tipo de contrato <span class="obrigatorio">*</span></label>
                    <select name="tipo" id="tipo" required class="select <?= $erroCls('tipo') ?>">
                        <?php foreach (Elegibilidade::TIPOS_CONTRATO as $chave => $rotulo): ?>
                            <option value="<?= e($chave) ?>" <?= $v('tipo', 'com_copart') === $chave ? 'selected' : '' ?>>
                                <?= e($rotulo) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?= $erroDe('tipo') ?>
                </div>

                <div>
                    <label class="label" for="vidas">Vidas previstas</label>
                    <input type="number" name="vidas" id="vidas" min="1" max="30" class="input num"
                           value="<?= e($v('vidas', '1')) ?>">
                    <?= $erroDe('vidas') ?>
                </div>

                <div class="form-largo">
                    <label class="label" for="observacao">Observação</label>
                    <textarea name="observacao" id="observacao" rows="2" maxlength="400" class="textarea"
                              placeholder="O que ficou combinado, o que falta de documento…"><?= e($v('observacao')) ?></textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="form-acoes px-4 pb-4">
        <a href="<?= $editando ? url('vendas', 'ver', ['id' => (int) $p['id']]) : url_lista('vendas') ?>"
           class="btn btn-outline">Cancelar</a>
        <button type="submit" class="btn btn-primary" :disabled="enviando">
            <span x-show="!enviando"><?= Icon::svg('salvar', 'w-4 h-4') ?></span>
            <span x-show="enviando" x-cloak class="girando"></span>
            <?= $editando ? 'Salvar alterações' : 'Registrar proposta' ?>
        </button>
    </div>
</form>
