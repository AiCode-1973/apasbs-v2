<?php
/**
 * Uma pessoa do contrato: titular ou dependente.
 *
 * A caixa da direita mostra o plano que a regra indica e por quê, e
 * atualiza enquanto se preenche. Ela consulta o motor no servidor em vez
 * de repetir as regras aqui: duas cópias do regulamento divergiriam na
 * primeira correção, e a errada seria justamente a que aparece na tela.
 *
 * @var array $b, $c, $erros, $planos, $avaliacao
 * @var bool  $temTitular
 */
declare(strict_types=1);

$editando  = !empty($b['id']);
$eTitular  = ($b['tipo'] ?? 'dependente') === 'titular';
$erroCls   = fn(string $x): string => isset($erros[$x]) ? 'input-error' : '';
$v         = fn(string $x, string $padrao = ''): string => (string) ($b[$x] ?? $padrao);
$marcado   = fn(string $x): bool => !empty($b[$x]);

$erroDe = function (string $campo) use ($erros): string {
    if (!isset($erros[$campo])) {
        return '';
    }
    $link = isset($erros['_link'])
        ? ' <a href="' . e($erros['_link']) . '" class="underline">Abrir quem já existe</a>.'
        : '';
    return '<p class="erro-campo">' . Icon::svg('erro', 'w-3.5 h-3.5') . ' <span>'
         . e($erros[$campo]) . $link . '</span></p>';
};

$config = json_encode([
    'url'           => url('cadastro', 'avaliar'),
    'contrato_tipo' => $c['tipo'] ?? 'com_copart',
    'tipo'          => $b['tipo'] ?? 'dependente',
    'inicial'       => $avaliacao,
], JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT);
?>

<div class="mb-5 flex items-start gap-3">
    <a href="<?= url('cadastro', 'ver', ['id' => (int) ($b['contrato_id'] ?? $c['id'])]) ?>"
       class="btn-acao mt-0.5" aria-label="Voltar ao contrato">
        <?= Icon::svg('anterior', 'w-4 h-4') ?>
    </a>
    <div>
        <h1 class="titulo-pagina">
            <?= $editando ? 'Editar' : 'Incluir' ?> <?= $eTitular ? 'titular' : 'dependente' ?>
        </h1>
        <p class="sub-pagina">
            Contrato <?= e($c['numero_proposta'] ?: '#' . (int) $c['id']) ?> ·
            <?= e(Elegibilidade::TIPOS_CONTRATO[$c['tipo']] ?? '') ?>
        </p>
    </div>
</div>

<form method="post" action="<?= url('cadastro', 'salvar_pessoa') ?>"
      x-data='beneficiario(<?= $config ?>)' @submit="enviar" novalidate>
    <?= Csrf::campo() ?>
    <input type="hidden" name="contrato_id" value="<?= (int) ($b['contrato_id'] ?? $c['id']) ?>">
    <input type="hidden" name="tipo" value="<?= e($b['tipo'] ?? 'dependente') ?>">
    <?php if ($editando): ?>
        <input type="hidden" name="id" value="<?= (int) $b['id'] ?>">
    <?php endif; ?>

    <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_22rem] lg:items-start">

        <div class="card">
            <div class="card-corpo">

                <!-- Quem é -->
                <div class="form-secao">
                    <div>
                        <h2 class="form-secao-titulo">Identificação</h2>
                        <p class="form-secao-desc">
                            A data de nascimento não é só cadastro: é ela que define a
                            faixa etária, e a faixa define quanto a pessoa paga.
                        </p>
                    </div>

                    <div class="form-campos">
                        <div class="form-largo">
                            <label class="label" for="nome">Nome completo <span class="obrigatorio">*</span></label>
                            <input type="text" name="nome" id="nome" maxlength="150" required autofocus
                                   class="input <?= $erroCls('nome') ?>" value="<?= e($v('nome')) ?>">
                            <?= $erroDe('nome') ?>
                        </div>

                        <div>
                            <label class="label" for="cpf">CPF</label>
                            <input type="text" name="cpf" id="cpf" inputmode="numeric" maxlength="14"
                                   class="input mono <?= $erroCls('cpf') ?>"
                                   value="<?= e($v('cpf') !== '' ? formatar_cpf($v('cpf')) : '') ?>"
                                   x-data="cpf" @input="formatar" placeholder="000.000.000-00">
                            <?= $erroDe('cpf') ?>
                        </div>

                        <div>
                            <label class="label" for="nascimento">
                                Nascimento
                                <?php if (!$eTitular): ?><span class="obrigatorio">*</span><?php endif; ?>
                            </label>
                            <input type="date" name="nascimento" id="nascimento"
                                   class="input <?= $erroCls('nascimento') ?>"
                                   value="<?= e(substr($v('nascimento'), 0, 10)) ?>"
                                   x-model="p.nascimento" @change="avaliar">
                            <?= $erroDe('nascimento') ?>
                            <p class="ajuda" x-show="idade !== null" x-cloak>
                                <span x-text="idade"></span> anos · faixa <span x-text="faixa"></span>
                            </p>
                        </div>

                        <div>
                            <label class="label" for="sexo">Sexo</label>
                            <select name="sexo" id="sexo" class="select">
                                <option value="">Não informado</option>
                                <option value="F" <?= $v('sexo') === 'F' ? 'selected' : '' ?>>Feminino</option>
                                <option value="M" <?= $v('sexo') === 'M' ? 'selected' : '' ?>>Masculino</option>
                                <option value="outro" <?= $v('sexo') === 'outro' ? 'selected' : '' ?>>Outro</option>
                            </select>
                        </div>

                        <div>
                            <label class="label" for="cns">Cartão Nacional de Saúde</label>
                            <input type="text" name="cns" id="cns" maxlength="20" class="input mono"
                                   value="<?= e($v('cns')) ?>" placeholder="000 0000 0000 0000">
                        </div>

                        <div class="form-largo">
                            <label class="label" for="nome_mae">Nome da mãe</label>
                            <input type="text" name="nome_mae" id="nome_mae" maxlength="150" class="input"
                                   value="<?= e($v('nome_mae')) ?>">
                        </div>

                        <div>
                            <label class="label" for="contato">Telefone</label>
                            <input type="tel" name="contato" id="contato" inputmode="numeric" maxlength="15"
                                   class="input <?= $erroCls('contato') ?>"
                                   value="<?= e(telefone_br($v('contato'))) ?>"
                                   x-data="telefone" @input="formatar" placeholder="(13) 99999-9999">
                            <?= $erroDe('contato') ?>
                        </div>

                        <div>
                            <label class="label" for="email">E-mail</label>
                            <input type="email" name="email" id="email" maxlength="150"
                                   class="input <?= $erroCls('email') ?>" value="<?= e($v('email')) ?>">
                            <?= $erroDe('email') ?>
                        </div>
                    </div>
                </div>

                <!-- O vínculo -->
                <div class="form-secao">
                    <div>
                        <h2 class="form-secao-titulo">Vínculo</h2>
                        <?php if ($eTitular): ?>
                            <p class="form-secao-desc">
                                Só Policial Militar ativo, Policial Militar inativo ou
                                pensionista pode ser titular. É a primeira regra do
                                regulamento, e sem ela não há contrato.
                            </p>
                        <?php else: ?>
                            <p class="form-secao-desc">
                                O grau de parentesco com o titular, e o que já está
                                comprovado. É daqui que sai o plano.
                            </p>
                        <?php endif; ?>
                    </div>

                    <div class="form-campos">
                        <?php if ($eTitular): ?>
                            <div>
                                <label class="label" for="categoria">Categoria <span class="obrigatorio">*</span></label>
                                <select name="categoria" id="categoria" required
                                        class="select <?= $erroCls('categoria') ?>"
                                        x-model="p.categoria" @change="avaliar">
                                    <option value="">Escolha…</option>
                                    <?php foreach (Elegibilidade::CATEGORIAS as $chave => $rotulo): ?>
                                        <option value="<?= e($chave) ?>" <?= $v('categoria') === $chave ? 'selected' : '' ?>>
                                            <?= e($rotulo) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?= $erroDe('categoria') ?>
                            </div>

                            <div>
                                <label class="label" for="matricula">RE / matrícula</label>
                                <input type="text" name="matricula" id="matricula" maxlength="30"
                                       class="input mono" value="<?= e($v('matricula')) ?>">
                            </div>
                        <?php else: ?>
                            <div class="form-largo">
                                <label class="label" for="parentesco">
                                    Grau de parentesco <span class="obrigatorio">*</span>
                                </label>
                                <select name="parentesco" id="parentesco" required
                                        class="select <?= $erroCls('parentesco') ?>"
                                        x-model="p.parentesco" @change="avaliar">
                                    <option value="">Escolha…</option>
                                    <?php foreach (Elegibilidade::PARENTESCOS as $grupo => $itens): ?>
                                        <optgroup label="<?= e($grupo) ?>">
                                            <?php foreach ($itens as $chave => $dados): ?>
                                                <option value="<?= e($chave) ?>"
                                                    <?= $v('parentesco') === $chave ? 'selected' : '' ?>>
                                                    <?= e($dados['rotulo']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                    <?php endforeach; ?>
                                </select>
                                <?= $erroDe('parentesco') ?>
                            </div>
                        <?php endif; ?>

                        <div class="form-largo">
                            <span class="label">O que está comprovado</span>
                            <p class="ajuda mb-1">
                                Marque só o que tem documento em mãos. O que faltar vira
                                pendência na tela do contrato — não impede o cadastro.
                            </p>

                            <div class="grid gap-2 sm:grid-cols-2">
                                <?php foreach (Elegibilidade::CONDICOES as $campo => $rotulo): ?>
                                    <label class="flex cursor-pointer items-start gap-2 text-[0.8125rem]">
                                        <input type="checkbox" name="<?= e($campo) ?>" value="1" class="check mt-0.5"
                                               x-model="p.<?= e($campo) ?>" @change="avaliar"
                                            <?= $marcado($campo) ? 'checked' : '' ?>>
                                        <span class="capitalize-first"><?= e(ucfirst($rotulo)) ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Plano e situação -->
                <div class="form-secao">
                    <div>
                        <h2 class="form-secao-titulo">Plano e situação</h2>
                        <?php if ($eTitular): ?>
                            <p class="form-secao-desc">
                                O plano vem preenchido com o que a regra indicou. Dá para
                                trocar — o sistema grava a sua escolha e registra que ela
                                diferiu da sugestão.
                            </p>
                            <p class="form-secao-desc">
                                <strong>O plano do titular manda no contrato.</strong> Ao
                                trocá-lo, o sistema pergunta se leva os dependentes junto e
                                mostra quanto a mensalidade da família passa a ser.
                            </p>
                        <?php else: ?>
                            <p class="form-secao-desc">
                                O plano vem preenchido com o <strong>plano do titular</strong>,
                                que é o do contrato. Dá para trocar, e a indicação da regra
                                continua ao lado como referência.
                            </p>
                        <?php endif; ?>
                    </div>

                    <div class="form-campos">
                        <div>
                            <label class="label" for="plano_id">Plano</label>
                            <select name="plano_id" id="plano_id" class="select" x-model="planoEscolhido">
                                <option value="0"><?= $eTitular
                                    ? 'Usar o plano indicado pela regra'
                                    : 'Usar o plano do titular' ?></option>
                                <?php foreach ($planos as $p): ?>
                                    <option value="<?= (int) $p['id'] ?>"
                                        <?= (int) $v('plano_id') === (int) $p['id'] ? 'selected' : '' ?>>
                                        <?= e($p['nome']) ?>
                                        (<?= $p['coparticipacao'] ? 'com' : 'sem' ?> coparticipação)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="label" for="data_inclusao">Inclusão no plano</label>
                            <input type="date" name="data_inclusao" id="data_inclusao" class="input"
                                   value="<?= e(substr($v('data_inclusao', date('Y-m-d')), 0, 10)) ?>">
                        </div>

                        <div>
                            <label class="label" for="status">Situação</label>
                            <select name="status" id="status" class="select" x-model="status">
                                <option value="ativo" <?= $v('status', 'ativo') === 'ativo' ? 'selected' : '' ?>>Ativo</option>
                                <option value="inativo" <?= $v('status') === 'inativo' ? 'selected' : '' ?>>Inativo (excluído do plano)</option>
                            </select>
                        </div>

                        <?php /* Data e motivo da saída só existem para quem saiu. */ ?>
                        <div x-show="status === 'inativo'" x-cloak>
                            <label class="label" for="data_exclusao">Saída do plano</label>
                            <input type="date" name="data_exclusao" id="data_exclusao" class="input"
                                   value="<?= e(substr($v('data_exclusao'), 0, 10)) ?>">
                        </div>

                        <div class="form-largo" x-show="status === 'inativo'" x-cloak>
                            <label class="label" for="motivo_exclusao">Motivo da saída</label>
                            <input type="text" name="motivo_exclusao" id="motivo_exclusao" maxlength="200"
                                   class="input" value="<?= e($v('motivo_exclusao')) ?>">
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
                <a href="<?= url('cadastro', 'ver', ['id' => (int) ($b['contrato_id'] ?? $c['id'])]) ?>"
                   class="btn btn-outline">Cancelar</a>
                <button type="submit" class="btn btn-primary" :disabled="enviando">
                    <span x-show="!enviando"><?= Icon::svg('salvar', 'w-4 h-4') ?></span>
                    <span x-show="enviando" x-cloak class="girando"></span>
                    <?= $editando ? 'Salvar alterações' : 'Incluir no contrato' ?>
                </button>
            </div>
        </div>

        <!-- O que a regra diz -->
        <div class="card self-start lg:sticky lg:top-4">
            <div class="card-header">
                <h2 class="card-title">O que a regra indica</h2>
                <span x-show="carregando" x-cloak class="girando"></span>
            </div>

            <div class="card-corpo">
                <template x-if="r.elegivel">
                    <div>
                        <p class="rotulo-num">Plano indicado</p>
                        <p class="text-lg font-semibold text-marine-800 dark:text-marine-200"
                           x-text="r.plano"></p>
                        <p class="mt-2 text-[0.8125rem] leading-relaxed text-slate-600 dark:text-slate-400"
                           x-text="r.motivo"></p>
                    </div>
                </template>

                <template x-if="!r.elegivel">
                    <div class="flex items-start gap-2 rounded-lg border border-amber-200 bg-amber-50/60 p-3 text-[0.8125rem] text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/5 dark:text-amber-300">
                        <?= Icon::svg('aviso', 'w-4 h-4 mt-0.5 shrink-0') ?>
                        <span x-text="r.impedimento"></span>
                    </div>
                </template>

                <template x-if="r.pendencias && r.pendencias.length">
                    <div class="mt-3 border-t border-slate-100 pt-3 dark:border-slate-800">
                        <p class="rotulo-num">Falta comprovar</p>
                        <ul class="mt-1 space-y-1">
                            <template x-for="pend in r.pendencias" :key="pend">
                                <li class="flex items-start gap-1.5 text-[0.8125rem] text-slate-600 dark:text-slate-400">
                                    <span class="mt-1.5 h-1 w-1 shrink-0 rounded-full bg-amber-500"></span>
                                    <span x-text="pend"></span>
                                </li>
                            </template>
                        </ul>
                        <p class="ajuda mt-2">Pendência não impede gravar.</p>
                    </div>
                </template>

                <div class="mt-3 border-t border-slate-100 pt-3 text-xs text-slate-500 dark:border-slate-800 dark:text-slate-400">
                    Contrato <strong><?= e(mb_strtolower(Elegibilidade::TIPOS_CONTRATO[$c['tipo']] ?? '')) ?></strong>.
                    Mudar isso no contrato muda o plano de todos.
                </div>
            </div>
        </div>
    </div>
</form>
