<?php
/**
 * Entrega de atestado médico: registrar e editar.
 *
 * As seções seguem o formulário em papel — colaborador, atestado, entrega,
 * recebimento — porque quem preenche tem o modelo impresso na mesa.
 *
 * @var array $a
 * @var array $erros, $trabalhadores
 * @var int   $proximo
 */
declare(strict_types=1);

$editando = !empty($a['id']);
$erroCls  = fn(string $c): string => isset($erros[$c]) ? 'input-error' : '';
$v        = fn(string $c, string $padrao = ''): string => (string) ($a[$c] ?? $padrao);

$erroDe = function (string $campo) use ($erros): string {
    return isset($erros[$campo])
        ? '<p class="erro-campo">' . Icon::svg('erro', 'w-3.5 h-3.5') . ' ' . e($erros[$campo]) . '</p>'
        : '';
};

// O estado do Alpine: retorno calculado de início + dias, e a descrição do
// "outro" que só aparece quando ela existe.
$estado = json_encode([
    'inicio'  => $v('inicio'),
    'dias'    => $v('dias'),
    'retorno' => $v('retorno'),
    'forma'   => $v('forma', 'presencial'),
], JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT);
?>

<div class="mb-5 flex items-start gap-3">
    <a href="<?= url('ocupacional', 'atestados') ?>" class="btn-acao mt-0.5" aria-label="Voltar">
        <?= Icon::svg('anterior', 'w-4 h-4') ?>
    </a>
    <div>
        <h1 class="titulo-pagina"><?= $editando ? 'Editar entrega' : 'Registrar entrega de atestado' ?></h1>
        <p class="sub-pagina">
            <?php if ($editando): ?>
                Protocolo <span class="mono"><?= e($v('protocolo')) ?></span> — Setor de Saúde Ocupacional
            <?php else: ?>
                O protocolo sai ao gravar: o próximo é
                <span class="mono"><?= e(sprintf('%04d/%04d', (int) date('Y'), $proximo)) ?></span>
            <?php endif; ?>
        </p>
    </div>
</div>

<?php if (empty($trabalhadores)): ?>
    <div class="card">
        <div class="vazio">
            <span class="vazio-icone"><?= Icon::svg('usuarios', 'w-5 h-5') ?></span>
            <p class="vazio-titulo">Nenhum trabalhador cadastrado</p>
            <p class="vazio-texto">
                A entrega é sempre de alguém. Cadastre a empresa e o colaborador
                primeiro — matrícula, setor e cargo vêm de lá e não se digitam
                a cada atestado.
            </p>
            <a href="<?= url('ocupacional', 'trabalhadores') ?>" class="btn btn-primary btn-sm mt-1">
                <?= Icon::svg('novo', 'w-3.5 h-3.5') ?> Cadastrar trabalhador
            </a>
        </div>
    </div>
<?php else: ?>

<form method="post" action="<?= url('ocupacional', 'salvar_atestado') ?>" class="card"
      x-data='entregaAtestado(<?= $estado ?>)' @submit="enviar" novalidate>
    <?= Csrf::campo() ?>
    <?php if ($editando): ?>
        <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
    <?php endif; ?>

    <div class="card-corpo">

        <!-- Colaborador -->
        <div class="form-secao">
            <div>
                <h2 class="form-secao-titulo">Dados do colaborador</h2>
                <p class="form-secao-desc">
                    Matrícula, setor e cargo vêm do cadastro do trabalhador e saem
                    impressos no comprovante — não se digitam aqui.
                </p>
                <p class="form-secao-desc">
                    Falta alguém na lista?
                    <a href="<?= url('ocupacional', 'trabalhadores') ?>"
                       class="text-marine-700 hover:underline dark:text-marine-300">Cadastre aqui</a>.
                </p>
            </div>

            <div class="form-campos">
                <div class="form-largo">
                    <label class="label" for="trabalhador_id">Colaborador <span class="obrigatorio">*</span></label>
                    <select name="trabalhador_id" id="trabalhador_id" required autofocus
                            class="select <?= $erroCls('trabalhador_id') ?>">
                        <option value="">Escolha…</option>
                        <?php foreach ($trabalhadores as $t): ?>
                            <option value="<?= (int) $t['id'] ?>"
                                <?= (int) $v('trabalhador_id') === (int) $t['id'] ? 'selected' : '' ?>>
                                <?= e($t['nome']) ?>
                                <?= $t['matricula'] ? ' — mat. ' . e($t['matricula']) : '' ?>
                                <?= $t['setor'] ? ' · ' . e($t['setor']) : '' ?>
                                (<?= e($t['empresa']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?= $erroDe('trabalhador_id') ?>
                </div>
            </div>
        </div>

        <!-- Atestado -->
        <div class="form-secao">
            <div>
                <h2 class="form-secao-titulo">Dados do atestado</h2>
                <p class="form-secao-desc">
                    O que está escrito no papel do médico. O retorno previsto é
                    calculado de início + dias, e pode ser corrigido.
                </p>
                <p class="form-secao-desc">
                    O CID não é pedido: para comprovar a entrega ele não é
                    necessário, e diagnóstico é o dado mais sensível que existe.
                </p>
            </div>

            <div class="form-campos">
                <div>
                    <label class="label" for="emissao">Data de emissão <span class="obrigatorio">*</span></label>
                    <input type="date" name="emissao" id="emissao" required
                           value="<?= e($v('emissao')) ?>" class="input <?= $erroCls('emissao') ?>">
                    <?= $erroDe('emissao') ?>
                </div>

                <div>
                    <label class="label" for="inicio">Início do afastamento</label>
                    <input type="date" name="inicio" id="inicio" x-model="inicio" @change="calcular"
                           value="<?= e($v('inicio')) ?>" class="input">
                </div>

                <div>
                    <label class="label" for="dias">Quantidade de dias</label>
                    <input type="number" name="dias" id="dias" min="0" max="365" x-model="dias" @input="calcular"
                           value="<?= e($v('dias')) ?>" class="input <?= $erroCls('dias') ?>">
                    <?= $erroDe('dias') ?>
                </div>

                <div>
                    <label class="label" for="retorno">Retorno previsto</label>
                    <input type="date" name="retorno" id="retorno" x-model="retorno"
                           @input="mexeuNoRetorno = true"
                           value="<?= e($v('retorno')) ?>" class="input">
                    <p class="ajuda" x-show="retorno && !mexeuNoRetorno" x-cloak>
                        Calculado: volta em <span x-text="previsao"></span>. Dá para trocar.
                    </p>
                </div>

                <div>
                    <label class="label" for="medico_nome">Médico / profissional emitente</label>
                    <input type="text" name="medico_nome" id="medico_nome" maxlength="150"
                           value="<?= e($v('medico_nome')) ?>" class="input"
                           placeholder="Como está assinado no atestado">
                </div>

                <div>
                    <label class="label" for="medico_registro">CRM / registro profissional</label>
                    <input type="text" name="medico_registro" id="medico_registro" maxlength="40"
                           value="<?= e($v('medico_registro')) ?>" class="input"
                           placeholder="Ex.: CRM-SP 123456">
                </div>
            </div>
        </div>

        <!-- Entrega -->
        <div class="form-secao">
            <div>
                <h2 class="form-secao-titulo">Entrega do atestado</h2>
                <p class="form-secao-desc">
                    Quando o papel chegou ao setor e por qual caminho. Data e hora
                    vêm preenchidas com agora.
                </p>
            </div>

            <div class="form-campos">
                <div>
                    <label class="label" for="entrega_data">Data da entrega <span class="obrigatorio">*</span></label>
                    <input type="date" name="entrega_data" id="entrega_data" required
                           value="<?= e($v('entrega_data')) ?>" class="input <?= $erroCls('entrega_data') ?>">
                    <?= $erroDe('entrega_data') ?>
                </div>

                <div>
                    <label class="label" for="entrega_hora">Horário</label>
                    <input type="time" name="entrega_hora" id="entrega_hora"
                           value="<?= e(substr($v('entrega_hora'), 0, 5)) ?>" class="input">
                </div>

                <div class="form-largo">
                    <span class="label">Forma de entrega</span>
                    <div class="flex flex-wrap gap-4">
                        <?php foreach (OcupacionalModel::FORMAS as $chave => $rotulo): ?>
                            <label class="flex cursor-pointer items-center gap-2 text-sm">
                                <input type="radio" name="forma" value="<?= e($chave) ?>" class="check"
                                       x-model="forma"
                                       <?= $v('forma', 'presencial') === $chave ? 'checked' : '' ?>>
                                <?= e($rotulo) ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <?= $erroDe('forma') ?>
                </div>

                <div class="form-largo" x-show="forma === 'outro'" x-cloak>
                    <label class="label" for="forma_outro">Qual?</label>
                    <input type="text" name="forma_outro" id="forma_outro" maxlength="120"
                           value="<?= e($v('forma_outro')) ?>" class="input <?= $erroCls('forma_outro') ?>"
                           placeholder="Ex.: entregue pelo chefe imediato">
                    <?= $erroDe('forma_outro') ?>
                </div>
            </div>
        </div>

        <!-- Recebimento -->
        <div class="form-secao">
            <div>
                <h2 class="form-secao-titulo">Recebimento — Saúde Ocupacional</h2>
                <p class="form-secao-desc">
                    Quem recebeu o papel no setor. Vem preenchido com quem está
                    usando o sistema, e pode ser trocado — às vezes quem digita
                    não é quem recebeu.
                </p>
                <p class="form-secao-desc">
                    Em branco, o comprovante sai com a linha para assinar à mão.
                </p>
            </div>

            <div class="form-campos">
                <div class="form-largo">
                    <label class="label" for="recebido_por">Recebido por</label>
                    <input type="text" name="recebido_por" id="recebido_por" maxlength="150"
                           value="<?= e($v('recebido_por')) ?>" class="input">
                </div>

                <div>
                    <label class="label" for="recebido_data">Data do recebimento</label>
                    <input type="date" name="recebido_data" id="recebido_data"
                           value="<?= e($v('recebido_data')) ?>" class="input">
                </div>

                <div>
                    <label class="label" for="recebido_hora">Horário</label>
                    <input type="time" name="recebido_hora" id="recebido_hora"
                           value="<?= e(substr($v('recebido_hora'), 0, 5)) ?>" class="input">
                </div>

                <div class="form-largo">
                    <label class="label" for="observacao">Observação</label>
                    <textarea name="observacao" id="observacao" rows="2" maxlength="400"
                              class="input"
                              placeholder="O que precisa ficar registrado sobre esta entrega"><?= e($v('observacao')) ?></textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="form-acoes px-4 pb-4">
        <a href="<?= $editando ? url('ocupacional', 'ver_atestado', ['id' => (int) $a['id']]) : url('ocupacional', 'atestados') ?>"
           class="btn btn-outline">Cancelar</a>
        <button type="submit" class="btn btn-primary" :disabled="enviando">
            <span x-show="!enviando"><?= Icon::svg('salvar', 'w-4 h-4') ?></span>
            <span x-show="enviando" x-cloak class="girando"></span>
            <?= $editando ? 'Salvar alterações' : 'Registrar entrega' ?>
        </button>
    </div>
</form>

<?php endif; ?>
