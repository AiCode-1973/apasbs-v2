<?php
/**
 * A ficha de saúde ocupacional: incluir e editar.
 *
 * As seis seções seguem o formulário em papel, na mesma ordem — quem
 * preenche tem o modelo impresso na cabeça, e mudar a ordem só faria
 * procurar campo.
 *
 * @var array $f
 * @var array $erros, $trabalhadores
 */
declare(strict_types=1);

$editando = !empty($f['id']);
$erroCls  = fn(string $c): string => isset($erros[$c]) ? 'input-error' : '';
$v        = fn(string $c, string $padrao = ''): string => (string) ($f[$c] ?? $padrao);
$marcado  = fn(string $c): bool => !empty($f[$c]);

$erroDe = function (string $campo) use ($erros): string {
    return isset($erros[$campo])
        ? '<p class="erro-campo">' . Icon::svg('erro', 'w-3.5 h-3.5') . ' ' . e($erros[$campo]) . '</p>'
        : '';
};

// O estado que o Alpine precisa: a conclusão governa a restrição, e o
// peso/altura alimentam o IMC calculado na hora.
$estado = json_encode([
    'conclusao' => $v('conclusao', 'pendente'),
    'peso'      => $v('peso'),
    'altura'    => $v('altura'),
], JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT);
?>

<div class="mb-5 flex items-start gap-3">
    <a href="<?= url_lista('ocupacional') ?>" class="btn-acao mt-0.5" aria-label="Voltar">
        <?= Icon::svg('anterior', 'w-4 h-4') ?>
    </a>
    <div>
        <h1 class="titulo-pagina"><?= $editando ? 'Editar ficha' : 'Nova ficha' ?></h1>
        <p class="sub-pagina">Prontuário de saúde ocupacional — NR-7 / PCMSO</p>
    </div>
</div>

<?php if (empty($trabalhadores)): ?>
    <div class="card">
        <div class="vazio">
            <span class="vazio-icone"><?= Icon::svg('usuarios', 'w-5 h-5') ?></span>
            <p class="vazio-titulo">Nenhum trabalhador cadastrado</p>
            <p class="vazio-texto">
                A ficha é sempre de alguém. Cadastre a empresa e o trabalhador
                primeiro — depois cada exame dele vira uma ficha ligada ao cadastro.
            </p>
            <a href="<?= url('ocupacional', 'trabalhadores') ?>" class="btn btn-primary btn-sm mt-1">
                <?= Icon::svg('novo', 'w-3.5 h-3.5') ?> Cadastrar trabalhador
            </a>
        </div>
    </div>
<?php else: ?>

<form method="post" action="<?= url('ocupacional', 'salvar') ?>" class="card"
      x-data='fichaOcupacional(<?= $estado ?>)' @submit="enviar" novalidate>
    <?= Csrf::campo() ?>
    <?php if ($editando): ?>
        <input type="hidden" name="id" value="<?= (int) $f['id'] ?>">
    <?php endif; ?>

    <div class="card-corpo">

        <!-- 1 + 2. Quem e que exame -->
        <div class="form-secao">
            <div>
                <h2 class="form-secao-titulo">Identificação e tipo de exame</h2>
                <p class="form-secao-desc">
                    Nome, CPF, função, setor e admissão vêm do cadastro do
                    trabalhador — não se digita de novo a cada exame.
                </p>
                <p class="form-secao-desc">
                    Falta alguém na lista?
                    <a href="<?= url('ocupacional', 'trabalhadores') ?>"
                       class="text-marine-700 hover:underline dark:text-marine-300">Cadastre aqui</a>.
                </p>
            </div>

            <div class="form-campos">
                <div class="form-largo">
                    <label class="label" for="trabalhador_id">Trabalhador <span class="obrigatorio">*</span></label>
                    <select name="trabalhador_id" id="trabalhador_id" required autofocus
                            class="select <?= $erroCls('trabalhador_id') ?>">
                        <option value="">Escolha…</option>
                        <?php foreach ($trabalhadores as $t): ?>
                            <option value="<?= (int) $t['id'] ?>"
                                <?= (int) $v('trabalhador_id') === (int) $t['id'] ? 'selected' : '' ?>>
                                <?= e($t['nome']) ?>
                                <?= $t['cpf'] ? ' — ' . e(formatar_cpf((string) $t['cpf'])) : '' ?>
                                (<?= e($t['empresa']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?= $erroDe('trabalhador_id') ?>
                </div>

                <div>
                    <label class="label" for="tipo">Tipo de exame <span class="obrigatorio">*</span></label>
                    <select name="tipo" id="tipo" required class="select <?= $erroCls('tipo') ?>">
                        <?php foreach (OcupacionalModel::TIPOS as $chave => $rotulo): ?>
                            <option value="<?= e($chave) ?>" <?= $v('tipo', 'admissional') === $chave ? 'selected' : '' ?>>
                                <?= e($rotulo) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?= $erroDe('tipo') ?>
                </div>

                <div>
                    <label class="label" for="data_exame">Data do exame <span class="obrigatorio">*</span></label>
                    <input type="date" name="data_exame" id="data_exame" required
                           class="input <?= $erroCls('data_exame') ?>"
                           value="<?= e(substr($v('data_exame', date('Y-m-d')), 0, 10)) ?>">
                    <?= $erroDe('data_exame') ?>
                </div>
            </div>
        </div>

        <!-- 3. Anamnese ocupacional / riscos -->
        <div class="form-secao">
            <div>
                <h2 class="form-secao-titulo">Anamnese ocupacional / riscos (PGR)</h2>
                <p class="form-secao-desc">
                    A que o trabalhador está exposto na função, segundo o PGR da
                    empresa — é o que define quais exames complementares a NR-7 exige.
                </p>
            </div>

            <div class="form-campos">
                <div class="form-largo">
                    <label class="label" for="hist_profissional">Histórico profissional</label>
                    <textarea name="hist_profissional" id="hist_profissional" rows="2" maxlength="600"
                              class="input" placeholder="Onde trabalhou antes e em que função"><?= e($v('hist_profissional')) ?></textarea>
                </div>

                <div class="form-largo">
                    <span class="label">Riscos</span>
                    <div class="mt-1 flex flex-wrap gap-x-5 gap-y-2">
                        <?php foreach (OcupacionalModel::RISCOS as $campo => $rotulo): ?>
                            <label class="flex cursor-pointer items-center gap-2 text-[0.8125rem]">
                                <input type="checkbox" name="<?= e($campo) ?>" value="1" class="check"
                                    <?= $marcado($campo) ? 'checked' : '' ?>>
                                <?= e($rotulo) ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div>
                    <label class="label" for="epi">Usa EPI</label>
                    <?php /* Três estados de propósito: em branco é "não perguntado",
                             que não é a mesma coisa que "não usa". */ ?>
                    <select name="epi" id="epi" class="select">
                        <option value="" <?= $v('epi') === '' ? 'selected' : '' ?>>Não informado</option>
                        <option value="1" <?= $v('epi') === '1' ? 'selected' : '' ?>>Sim</option>
                        <option value="0" <?= $v('epi') === '0' ? 'selected' : '' ?>>Não</option>
                    </select>
                </div>

                <div class="form-largo">
                    <label class="label" for="antecedentes">Antecedentes / medicações / alergias / hábitos</label>
                    <textarea name="antecedentes" id="antecedentes" rows="2" maxlength="600"
                              class="input" placeholder="Doenças anteriores, remédios de uso contínuo, alergias, tabagismo…"><?= e($v('antecedentes')) ?></textarea>
                </div>
            </div>
        </div>

        <!-- 4. Exame físico -->
        <div class="form-secao">
            <div>
                <h2 class="form-secao-titulo">Exame físico — triagem de enfermagem</h2>
                <p class="form-secao-desc">
                    Sinais vitais e medidas. O IMC é calculado sozinho a partir do
                    peso e da altura.
                </p>
            </div>

            <div class="form-campos">
                <div>
                    <span class="label">Pressão arterial</span>
                    <div class="flex items-center gap-2">
                        <input type="number" name="pa_sistolica" min="50" max="300" inputmode="numeric"
                               class="input input-sm <?= $erroCls('pa_sistolica') ?>" placeholder="120"
                               value="<?= e($v('pa_sistolica')) ?>" aria-label="Pressão sistólica">
                        <span class="text-slate-400">×</span>
                        <input type="number" name="pa_diastolica" min="30" max="200" inputmode="numeric"
                               class="input input-sm <?= $erroCls('pa_diastolica') ?>" placeholder="80"
                               value="<?= e($v('pa_diastolica')) ?>" aria-label="Pressão diastólica">
                        <span class="text-xs text-slate-500">mmHg</span>
                    </div>
                    <?= $erroDe('pa_sistolica') ?><?= $erroDe('pa_diastolica') ?>
                </div>

                <div>
                    <label class="label" for="fc">Frequência cardíaca</label>
                    <input type="number" name="fc" id="fc" min="20" max="250" inputmode="numeric"
                           class="input <?= $erroCls('fc') ?>" placeholder="72 bpm" value="<?= e($v('fc')) ?>">
                    <?= $erroDe('fc') ?>
                </div>

                <div>
                    <label class="label" for="peso">Peso (kg)</label>
                    <input type="text" name="peso" id="peso" inputmode="decimal"
                           class="input <?= $erroCls('peso') ?>" placeholder="72,5"
                           x-model="peso" value="<?= e($v('peso')) ?>">
                    <?= $erroDe('peso') ?>
                </div>

                <div>
                    <label class="label" for="altura">Altura (m)</label>
                    <input type="text" name="altura" id="altura" inputmode="decimal"
                           class="input <?= $erroCls('altura') ?>" placeholder="1,72"
                           x-model="altura" value="<?= e($v('altura')) ?>">
                    <?= $erroDe('altura') ?>
                </div>

                <div>
                    <span class="label">IMC</span>
                    <?php /* Só leitura, e não gravado: é conta de peso e altura.
                             Guardar o resultado criaria duas verdades que divergem
                             na primeira correção de digitação. */ ?>
                    <div class="input flex items-center justify-between bg-slate-50 dark:bg-slate-800/60">
                        <span class="font-medium" x-text="imc || '—'"></span>
                        <span class="text-xs text-slate-500 dark:text-slate-400" x-text="faixa"></span>
                    </div>
                    <p class="ajuda">Calculado, não digitado.</p>
                </div>

                <div>
                    <span class="label">Acuidade visual</span>
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-slate-500">OD</span>
                        <input type="text" name="acuidade_od" maxlength="10" class="input input-sm"
                               placeholder="20/20" value="<?= e($v('acuidade_od')) ?>"
                               aria-label="Acuidade do olho direito">
                        <span class="text-xs text-slate-500">OE</span>
                        <input type="text" name="acuidade_oe" maxlength="10" class="input input-sm"
                               placeholder="20/20" value="<?= e($v('acuidade_oe')) ?>"
                               aria-label="Acuidade do olho esquerdo">
                    </div>
                </div>

                <div class="form-largo">
                    <label class="label" for="queixas">Queixas atuais / exame direcionado</label>
                    <textarea name="queixas" id="queixas" rows="2" maxlength="600" class="input"
                              placeholder="O que o trabalhador relata e o que foi examinado por causa do risco da função"><?= e($v('queixas')) ?></textarea>
                </div>
            </div>
        </div>

        <!-- 5. Exames complementares -->
        <div class="form-secao">
            <div>
                <h2 class="form-secao-titulo">Exames complementares anexados</h2>
                <p class="form-secao-desc">
                    A data de cada exame que veio junto. Em branco quer dizer que
                    aquele exame não foi pedido nesta ficha.
                </p>
            </div>

            <div class="form-campos">
                <div>
                    <label class="label" for="audiometria_em">Audiometria</label>
                    <input type="date" name="audiometria_em" id="audiometria_em" class="input"
                           value="<?= e(substr($v('audiometria_em'), 0, 10)) ?>">
                </div>

                <div>
                    <label class="label" for="espirometria_em">Espirometria</label>
                    <input type="date" name="espirometria_em" id="espirometria_em" class="input"
                           value="<?= e(substr($v('espirometria_em'), 0, 10)) ?>">
                </div>

                <div>
                    <label class="label" for="laboratorial_em">Laboratorial</label>
                    <input type="date" name="laboratorial_em" id="laboratorial_em" class="input"
                           value="<?= e(substr($v('laboratorial_em'), 0, 10)) ?>">
                </div>

                <div>
                    <label class="label" for="outros_exames">Outros</label>
                    <input type="text" name="outros_exames" id="outros_exames" maxlength="200" class="input"
                           value="<?= e($v('outros_exames')) ?>" placeholder="Ex.: raio-X de tórax 12/03">
                </div>

                <div class="form-largo">
                    <label class="label" for="obs_enfermagem">Observações da enfermagem</label>
                    <textarea name="obs_enfermagem" id="obs_enfermagem" rows="2" maxlength="600" class="input"
                              placeholder="O que o médico do trabalho precisa saber antes de concluir"><?= e($v('obs_enfermagem')) ?></textarea>
                </div>
            </div>
        </div>

        <!-- 6. Encaminhamento e conclusão -->
        <div class="form-secao">
            <div>
                <h2 class="form-secao-titulo">Encaminhamento e conclusão médica</h2>
                <p class="form-secao-desc">
                    A enfermagem entrega a triagem; quem define apto ou inapto é o
                    médico do trabalho. Enquanto ele não concluir, a ficha fica como
                    <em>Aguardando médico</em>.
                </p>
            </div>

            <div class="form-campos">
                <div>
                    <label class="label" for="encaminhado_em">Encaminhado ao médico em</label>
                    <input type="date" name="encaminhado_em" id="encaminhado_em" class="input"
                           value="<?= e(substr($v('encaminhado_em'), 0, 10)) ?>">
                </div>

                <div>
                    <label class="label" for="conclusao">Conclusão médica</label>
                    <select name="conclusao" id="conclusao" class="select <?= $erroCls('conclusao') ?>"
                            x-model="conclusao">
                        <?php foreach (OcupacionalModel::CONCLUSOES as $chave => $rotulo): ?>
                            <option value="<?= e($chave) ?>" <?= $v('conclusao', 'pendente') === $chave ? 'selected' : '' ?>>
                                <?= e($rotulo) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?= $erroDe('conclusao') ?>
                </div>

                <div>
                    <label class="label" for="validade">Validade do exame</label>
                    <input type="date" name="validade" id="validade" class="input"
                           value="<?= e(substr($v('validade'), 0, 10)) ?>">
                    <p class="ajuda">É ela que faz o sistema avisar quando refazer.</p>
                </div>

                <div class="form-largo" x-show="conclusao === 'apto_restricao'" x-cloak>
                    <label class="label" for="restricoes">Qual é a restrição</label>
                    <input type="text" name="restricoes" id="restricoes" maxlength="400" class="input"
                           value="<?= e($v('restricoes')) ?>"
                           placeholder="Ex.: não pode levantar peso acima de 10 kg">
                    <p class="ajuda">
                        Sem isso, quem ler a ficha depois sabe que há restrição mas não sabe qual.
                    </p>
                </div>
            </div>
        </div>

        <!-- Assinaturas -->
        <div class="form-secao">
            <div>
                <h2 class="form-secao-titulo">Quem assina</h2>
                <p class="form-secao-desc">
                    Os nomes que saem na ficha impressa, junto das linhas de
                    assinatura e carimbo.
                </p>
            </div>

            <div class="form-campos">
                <div>
                    <label class="label" for="enfermeiro_nome">Enfermeiro do trabalho</label>
                    <input type="text" name="enfermeiro_nome" id="enfermeiro_nome" maxlength="150" class="input"
                           value="<?= e($v('enfermeiro_nome', Auth::nome())) ?>">
                </div>

                <div>
                    <label class="label" for="enfermeiro_coren">COREN</label>
                    <input type="text" name="enfermeiro_coren" id="enfermeiro_coren" maxlength="30"
                           class="input mono" value="<?= e($v('enfermeiro_coren')) ?>">
                </div>

                <div>
                    <label class="label" for="medico_nome">Médico do trabalho</label>
                    <input type="text" name="medico_nome" id="medico_nome" maxlength="150" class="input"
                           value="<?= e($v('medico_nome')) ?>">
                </div>

                <div>
                    <label class="label" for="medico_crm">CRM</label>
                    <input type="text" name="medico_crm" id="medico_crm" maxlength="30" class="input mono"
                           value="<?= e($v('medico_crm')) ?>">
                </div>

                <div>
                    <label class="label" for="ciencia_em">Ciência do trabalhador em</label>
                    <input type="date" name="ciencia_em" id="ciencia_em" class="input"
                           value="<?= e(substr($v('ciencia_em'), 0, 10)) ?>">
                    <p class="ajuda">A data em que ele assinou a via dele.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="form-acoes px-4 pb-4">
        <a href="<?= $editando ? url('ocupacional', 'ver', ['id' => (int) $f['id']]) : url_lista('ocupacional') ?>"
           class="btn btn-outline">Cancelar</a>
        <button type="submit" class="btn btn-primary" :disabled="enviando">
            <span x-show="!enviando"><?= Icon::svg('salvar', 'w-4 h-4') ?></span>
            <span x-show="enviando" x-cloak class="girando"></span>
            <?= $editando ? 'Salvar alterações' : 'Registrar ficha' ?>
        </button>
    </div>
</form>

<?php endif; ?>
