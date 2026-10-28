<?php
/**
 * O cabeçalho da declaração: de qual contrato, quando, quem orientou e
 * quem assina. O questionário vem depois, pessoa a pessoa.
 *
 * @var array $d, $erros, $contratos, $pessoas
 * @var array|null $contrato
 */
declare(strict_types=1);

$editando = !empty($d['id']);
$erroCls  = fn(string $x): string => isset($erros[$x]) ? 'input-error' : '';
$v        = fn(string $x, string $padrao = ''): string => (string) ($d[$x] ?? $padrao);

$erroDe = function (string $campo) use ($erros): string {
    if (!isset($erros[$campo])) {
        return '';
    }
    $link = isset($erros['_link'])
        ? ' <a href="' . e($erros['_link']) . '" class="underline">Abrir a que já existe</a>.'
        : '';
    return '<p class="erro-campo">' . Icon::svg('erro', 'w-3.5 h-3.5') . ' <span>'
         . e($erros[$campo]) . $link . '</span></p>';
};

$config = json_encode([
    'orientacao' => $v('orientacao', 'dispensa'),
    'status'     => $v('status', 'em_preenchimento'),
], JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT);
?>

<div class="mb-5 flex items-start gap-3">
    <a href="<?= $editando ? url('declaracoes', 'ver', ['id' => (int) $d['id']]) : url_lista('declaracoes') ?>"
       class="btn-acao mt-0.5" aria-label="Voltar">
        <?= Icon::svg('anterior', 'w-4 h-4') ?>
    </a>
    <div>
        <h1 class="titulo-pagina"><?= $editando ? 'Editar declaração' : 'Nova declaração de saúde' ?></h1>
        <p class="sub-pagina">O documento da família, assinado uma vez</p>
    </div>
</div>

<?php if (!$editando && empty($contratos) && !$contrato): ?>
    <div class="card">
        <div class="vazio">
            <span class="vazio-icone"><?= Icon::svg('cadastro', 'w-5 h-5') ?></span>
            <p class="vazio-titulo">Nenhum contrato disponível</p>
            <p class="vazio-texto">
                Ou todos os contratos já têm declaração, ou ainda não há contrato com
                pessoas cadastradas. A declaração é sempre de uma família existente.
            </p>
            <a href="<?= url('cadastro', 'lista') ?>" class="btn btn-primary btn-sm mt-1">
                <?= Icon::svg('cadastro', 'w-3.5 h-3.5') ?> Ir para o cadastro
            </a>
        </div>
    </div>
<?php else: ?>

<form method="post" action="<?= url('declaracoes', 'salvar') ?>" class="card"
      x-data='declaracao(<?= $config ?>)' @submit="enviar" novalidate>
    <?= Csrf::campo() ?>
    <?php if ($editando): ?>
        <input type="hidden" name="id" value="<?= (int) $d['id'] ?>">
    <?php endif; ?>

    <div class="card-corpo">

        <div class="form-secao">
            <div>
                <h2 class="form-secao-titulo">De quem é</h2>
                <p class="form-secao-desc">
                    A declaração cobre o contrato inteiro — o titular e todos os
                    dependentes ativos. Cada um responde sobre si na etapa seguinte.
                </p>
            </div>

            <div class="form-campos">
                <div class="form-largo">
                    <label class="label" for="contrato_id">Contrato <span class="obrigatorio">*</span></label>
                    <?php if ($editando): ?>
                        <?php /* Trocar o contrato de uma declaração já respondida
                                 deixaria as respostas apontando para pessoas de
                                 outra família. */ ?>
                        <input type="hidden" name="contrato_id" value="<?= (int) $d['contrato_id'] ?>">
                        <p class="input bg-slate-50 dark:bg-slate-800/60">
                            <?= e($d['numero_proposta'] ?: '#' . (int) $d['contrato_id']) ?>
                        </p>
                        <p class="ajuda">O contrato não muda depois de criada a declaração.</p>
                    <?php else: ?>
                        <select name="contrato_id" id="contrato_id" required
                                class="select <?= $erroCls('contrato_id') ?>">
                            <option value="">Escolha…</option>
                            <?php foreach ($contratos as $c): ?>
                                <option value="<?= (int) $c['id'] ?>"
                                    <?= (int) $v('contrato_id') === (int) $c['id'] ? 'selected' : '' ?>>
                                    <?= e($c['titular'] ?: 'sem titular') ?>
                                    — <?= e($c['numero_proposta'] ?: '#' . (int) $c['id']) ?>
                                    (<?= (int) $c['pessoas'] ?> pessoa<?= (int) $c['pessoas'] === 1 ? '' : 's' ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?= $erroDe('contrato_id') ?>
                        <p class="ajuda">Só aparecem contratos que ainda não têm declaração.</p>
                    <?php endif; ?>
                </div>

                <div>
                    <label class="label" for="data_preenchimento">
                        Data do preenchimento <span class="obrigatorio">*</span>
                    </label>
                    <input type="date" name="data_preenchimento" id="data_preenchimento" required
                           class="input <?= $erroCls('data_preenchimento') ?>"
                           value="<?= e(substr($v('data_preenchimento', date('Y-m-d')), 0, 10)) ?>">
                    <?= $erroDe('data_preenchimento') ?>
                </div>

                <div>
                    <label class="label" for="data_contratacao">Data da contratação</label>
                    <input type="date" name="data_contratacao" id="data_contratacao" class="input"
                           value="<?= e(substr($v('data_contratacao', (string) ($contrato['data_adesao'] ?? '')), 0, 10)) ?>">
                    <p class="ajuda">
                        É dela que correm os <?= DeclaracaoModel::MESES_CPT ?> meses de
                        cobertura parcial temporária.
                    </p>
                </div>
            </div>
        </div>

        <div class="form-secao">
            <div>
                <h2 class="form-secao-titulo">Orientação médica</h2>
                <p class="form-secao-desc">
                    A ANS exige que o proponente possa ser orientado por médico ao
                    preencher a declaração, e que a escolha fique registrada no
                    documento.
                </p>
            </div>

            <div class="form-campos">
                <div class="form-largo">
                    <span class="label">Como foi <span class="obrigatorio">*</span></span>
                    <div class="mt-1 space-y-2">
                        <?php foreach (DeclaracaoModel::ORIENTACOES as $chave => $rotulo): ?>
                            <label class="flex cursor-pointer items-start gap-2 rounded-lg border p-3 transition-colors
                                          <?= $v('orientacao', 'dispensa') === $chave
                                              ? 'border-marine-400 bg-marine-50 dark:border-marine-500 dark:bg-marine-500/10'
                                              : 'border-slate-200 hover:bg-slate-50 dark:border-slate-800 dark:hover:bg-slate-800/60' ?>">
                                <input type="radio" name="orientacao" value="<?= e($chave) ?>" class="check mt-0.5"
                                       x-model="orientacao"
                                    <?= $v('orientacao', 'dispensa') === $chave ? 'checked' : '' ?>>
                                <span class="text-[0.8125rem]"><?= e($rotulo) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <?= $erroDe('orientacao') ?>
                </div>

                <?php /* Médico e CRM só existem quando houve orientação. */ ?>
                <div x-show="orientacao !== 'dispensa'" x-cloak>
                    <label class="label" for="medico_nome">
                        Médico orientador <span class="obrigatorio">*</span>
                    </label>
                    <input type="text" name="medico_nome" id="medico_nome" maxlength="150"
                           class="input <?= $erroCls('medico_nome') ?>" value="<?= e($v('medico_nome')) ?>">
                    <?= $erroDe('medico_nome') ?>
                </div>

                <div x-show="orientacao !== 'dispensa'" x-cloak>
                    <label class="label" for="medico_crm">CRM</label>
                    <input type="text" name="medico_crm" id="medico_crm" maxlength="30"
                           class="input mono" value="<?= e($v('medico_crm')) ?>">
                </div>
            </div>
        </div>

        <div class="form-secao">
            <div>
                <h2 class="form-secao-titulo">Assinatura</h2>
                <p class="form-secao-desc">
                    Quem assina o documento. Nem sempre é o titular: menor de idade
                    ou incapaz é representado, e é a assinatura do representante legal
                    que vale.
                </p>
            </div>

            <div class="form-campos">
                <div>
                    <label class="label" for="assinante_nome">Nome de quem assina</label>
                    <input type="text" name="assinante_nome" id="assinante_nome" maxlength="150"
                           class="input" value="<?= e($v('assinante_nome')) ?>">
                </div>

                <div>
                    <label class="label" for="assinante_cpf">CPF</label>
                    <input type="text" name="assinante_cpf" id="assinante_cpf" inputmode="numeric" maxlength="14"
                           class="input mono <?= $erroCls('assinante_cpf') ?>"
                           value="<?= e($v('assinante_cpf') !== '' ? formatar_cpf($v('assinante_cpf')) : '') ?>"
                           x-data="cpf" @input="formatar" placeholder="000.000.000-00">
                    <?= $erroDe('assinante_cpf') ?>
                </div>

                <div>
                    <label class="label" for="data_assinatura">Data da assinatura</label>
                    <input type="date" name="data_assinatura" id="data_assinatura" class="input"
                           value="<?= e(substr($v('data_assinatura'), 0, 10)) ?>">
                </div>

                <div>
                    <label class="label" for="status">Situação</label>
                    <select name="status" id="status" class="select <?= $erroCls('status') ?>" x-model="status">
                        <?php foreach (DeclaracaoModel::STATUS as $chave => $rotulo): ?>
                            <option value="<?= e($chave) ?>"
                                <?= $v('status', 'em_preenchimento') === $chave ? 'selected' : '' ?>>
                                <?= e($rotulo) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?= $erroDe('status') ?>
                    <p class="ajuda" x-show="status === 'concluida'" x-cloak>
                        Concluída, ela não pode mais ser excluída — é o documento que
                        protege as duas partes.
                    </p>
                </div>

                <div class="form-largo">
                    <label class="label" for="observacao">Observação</label>
                    <input type="text" name="observacao" id="observacao" maxlength="400" class="input"
                           value="<?= e($v('observacao')) ?>">
                </div>
            </div>
        </div>
    </div>

    <div class="form-acoes px-4 pb-4">
        <a href="<?= $editando ? url('declaracoes', 'ver', ['id' => (int) $d['id']]) : url_lista('declaracoes') ?>"
           class="btn btn-outline">Cancelar</a>
        <button type="submit" class="btn btn-primary" :disabled="enviando">
            <span x-show="!enviando"><?= Icon::svg('salvar', 'w-4 h-4') ?></span>
            <span x-show="enviando" x-cloak class="girando"></span>
            <?= $editando ? 'Salvar alterações' : 'Criar e responder o questionário' ?>
        </button>
    </div>
</form>

<?php endif; ?>
