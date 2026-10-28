<?php
/**
 * A conta auditada: identificação, os sete valores e os motivos da glosa.
 *
 * Os percentuais não são campos — são consequência. Aparecem ao lado dos
 * valores enquanto se digita, e o servidor refaz a mesma conta ao gravar.
 *
 * @var string $tipo
 * @var array  $cfg, $conta, $erros, $convenios, $motivos, $catalogo
 * @var float  $somaMotivos
 */
declare(strict_types=1);

$editando = !empty($conta['id']);
$erroCls  = fn (string $c): string => isset($erros[$c]) ? 'input-error' : '';
$v        = fn (string $c, string $padrao = ''): string => (string) ($conta[$c] ?? $padrao);

$erroDe = function (string $campo) use ($erros): string {
    return isset($erros[$campo])
        ? '<p class="erro-campo">' . Icon::svg('erro', 'w-3.5 h-3.5') . ' ' . e($erros[$campo]) . '</p>'
        : '';
};

// Os valores voltam do banco como "1234.56"; na tela, com vírgula. Numa conta
// nova não há valor nenhum, e campo ausente é campo vazio — não um aviso.
$moeda = static function (string $campo) use ($conta): string {
    $valor = $conta[$campo] ?? null;

    return ($valor === null || $valor === '')
        ? ''
        : number_format((float) $valor, 2, ',', '.');
};

$inicialJs = json_encode([
    'inicial'  => $moeda('valor_inicial'),
    'retirado' => $moeda('valor_retirado'),
    'final'    => $moeda('valor_total'),
    'glosado'  => $moeda('valor_glosado'),
    'aceito'   => $moeda('valor_aceito'),
], JSON_UNESCAPED_UNICODE);

// A regra do valor inicial vai pronta para a tela, montada do mesmo lugar que
// o servidor consulta — assim o asterisco nunca promete uma coisa e a
// gravação cobra outra.
$regraInicialJs = json_encode([
    'tipoExige'      => $cfg['exige_inicial'],
    'temSetor'       => $cfg['setor'],
    'setoresIsentos' => AuditoriaHospModel::SETORES_SEM_INICIAL,
    'setor'          => $cfg['setor'] ? $v('setor', 'PA') : '',
    'auditado'       => AuditoriaHospModel::SITUACAO_AUDITADO,
    'situacao'       => $v('status', 'Pré análise'),
    // Conta que já estava auditada é a exceção da regra, aqui e no servidor:
    // ela veio assim do AuditorHosp e continua gravável.
    'jaAuditada'     => $v('status') === AuditoriaHospModel::SITUACAO_AUDITADO,
], JSON_UNESCAPED_UNICODE);

// A competência é um mês; o input month quer "AAAA-MM".
$competencia = substr($v('competencia'), 0, 7);

$situacoes = AuditoriaHospModel::SITUACOES;
if ($v('status') !== '' && !in_array($v('status'), $situacoes, true)) {
    // Conta antiga com uma situação que o formulário não oferece mais: a tela
    // mostra o que está gravado em vez de trocar por outra coisa sem avisar.
    $situacoes[] = $v('status');
}
?>

<div class="mb-5 flex items-start gap-3">
    <a href="<?= url('auditoria_hosp', 'lista', ['tipo' => $tipo]) ?>" class="btn-acao mt-0.5" aria-label="Voltar">
        <?= Icon::svg('anterior', 'w-4 h-4') ?>
    </a>
    <div>
        <h1 class="titulo-pagina"><?= $editando ? 'Conta auditada' : 'Nova conta' ?></h1>
        <p class="sub-pagina">
            <?= e($cfg['rotulo']) ?>
            <?php if ($editando): ?> · nº <?= (int) $conta['id'] ?><?php endif; ?>
        </p>
    </div>
</div>

<form method="post" action="<?= url('auditoria_hosp', 'salvar') ?>" class="card"
      x-data="contaAuditoria(<?= e($inicialJs) ?>, <?= e($regraInicialJs) ?>)"
      @submit="enviar" novalidate>
    <?= Csrf::campo() ?>
    <input type="hidden" name="tipo" value="<?= e($tipo) ?>">
    <?php if ($editando): ?>
        <input type="hidden" name="id" value="<?= (int) $conta['id'] ?>">
    <?php endif; ?>

    <div class="card-corpo">

        <!-- De quem é a conta -->
        <div class="form-secao">
            <div>
                <h2 class="form-secao-titulo">A conta</h2>
                <p class="form-secao-desc">
                    Quem foi atendido, por qual convênio e em que competência a conta entra.
                </p>
            </div>

            <div class="form-campos">
                <?php if ($cfg['paciente']): ?>
                    <div class="form-largo">
                        <label class="label" for="paciente">Paciente <span class="obrigatorio">*</span></label>
                        <input type="text" name="paciente" id="paciente" maxlength="255" required autofocus
                               class="input <?= $erroCls('paciente') ?>" value="<?= e($v('paciente')) ?>"
                               placeholder="Nome como está na guia">
                        <?= $erroDe('paciente') ?>
                    </div>
                <?php else: ?>
                    <div>
                        <label class="label" for="setor">Setor <span class="obrigatorio">*</span></label>
                        <select name="setor" id="setor" required class="select <?= $erroCls('setor') ?>"
                                x-model="setor">
                            <?php foreach (AuditoriaHospModel::SETORES as $s): ?>
                                <option value="<?= e($s) ?>" <?= $v('setor', 'PA') === $s ? 'selected' : '' ?>>
                                    <?= e($s) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="ajuda">NC marca o que veio como não conformidade.</p>
                        <?= $erroDe('setor') ?>
                    </div>
                <?php endif; ?>

                <div>
                    <label class="label" for="guia_paciente">Guia</label>
                    <input type="text" name="guia_paciente" id="guia_paciente" maxlength="100"
                           class="input mono" value="<?= e($v('guia_paciente')) ?>"
                           placeholder="Número da guia do convênio">
                </div>

                <div>
                    <label class="label" for="convenio_id">Convênio <span class="obrigatorio">*</span></label>
                    <select name="convenio_id" id="convenio_id" required class="select <?= $erroCls('convenio_id') ?>">
                        <option value="">Escolha…</option>
                        <?php foreach ($convenios as $c): ?>
                            <option value="<?= (int) $c['id'] ?>"
                                <?= (int) $v('convenio_id') === (int) $c['id'] ? 'selected' : '' ?>>
                                <?= e($c['nome_convenio']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?= $erroDe('convenio_id') ?>
                </div>

                <div>
                    <label class="label" for="competencia">Competência <span class="obrigatorio">*</span></label>
                    <input type="month" name="competencia" id="competencia" required
                           class="input <?= $erroCls('competencia') ?>" value="<?= e($competencia) ?>">
                    <p class="ajuda">O mês em que a conta entra no faturamento.</p>
                    <?= $erroDe('competencia') ?>
                </div>

                <div>
                    <label class="label" for="data_recebimento">Recebida em</label>
                    <input type="date" name="data_recebimento" id="data_recebimento" class="input"
                           value="<?= e(substr($v('data_recebimento'), 0, 10)) ?>">
                </div>

                <div>
                    <label class="label" for="data_entrada">Entrada</label>
                    <input type="date" name="data_entrada" id="data_entrada" class="input"
                           value="<?= e(substr($v('data_entrada'), 0, 10)) ?>">
                </div>

                <div>
                    <label class="label" for="data_saida">Saída</label>
                    <input type="date" name="data_saida" id="data_saida"
                           class="input <?= $erroCls('data_saida') ?>"
                           value="<?= e(substr($v('data_saida'), 0, 10)) ?>">
                    <?= $erroDe('data_saida') ?>
                </div>
            </div>
        </div>

        <!-- O dinheiro -->
        <div class="form-secao">
            <div>
                <h2 class="form-secao-titulo">Os valores</h2>
                <p class="form-secao-desc">
                    O acrescentado não se digita: sai de
                    <em>final + retirado − inicial</em>. Pode dar negativo, e aí
                    a conta saiu da auditoria menor do que o retirado explica.
                </p>
                <p class="form-secao-desc">
                    Os percentuais aparecem ao lado de cada campo, cada um sobre a sua base —
                    é isso que faz a conta significar alguma coisa.
                </p>
            </div>

            <div class="form-campos">
                <?php /* O asterisco acompanha o setor: trocar para PA/NC ou
                         AMB/NC tira a exigência na hora, porque nesses setores
                         a conta chega zerada e ganha valor na auditoria. A
                         regra vem do Model, uma vez só, e o servidor refaz a
                         mesma pergunta ao gravar. */ ?>
                <div>
                    <?php /* Sem x-cloak: o estado que o PHP desenha já é o certo
                             para o setor que está escolhido, e o Alpine só
                             assume dali em diante. Um asterisco que depende de
                             script para aparecer é um asterisco que some. */ ?>
                    <label class="label" for="valor_inicial">
                        Valor inicial
                        <?php if (AuditoriaHospModel::exigeInicial($tipo, $v('setor', 'PA'))): ?>
                            <span class="obrigatorio"<?= $cfg['setor'] ? ' x-show="exigeInicial"' : '' ?>>*</span>
                        <?php elseif ($cfg['setor']): ?>
                            <span class="obrigatorio" x-show="exigeInicial" style="display:none">*</span>
                        <?php endif; ?>
                    </label>
                    <input type="text" name="valor_inicial" id="valor_inicial" inputmode="decimal"
                           class="input num <?= $erroCls('valor_inicial') ?>"
                           x-model="inicialV" placeholder="0,00">
                    <p class="ajuda">Como a conta chegou do faturamento.</p>
                    <?php if ($cfg['setor']): ?>
                        <p class="ajuda" x-show="!exigeInicial" x-cloak>
                            Em <strong>PA/NC</strong> e <strong>AMB/NC</strong> a conta pode
                            entrar sem valor: ele chega na auditoria.
                        </p>
                    <?php endif; ?>
                    <?= $erroDe('valor_inicial') ?>
                </div>

                <div>
                    <label class="label" for="valor_retirado">Retirado</label>
                    <input type="text" name="valor_retirado" id="valor_retirado" inputmode="decimal"
                           class="input num <?= $erroCls('valor_retirado') ?>"
                           x-model="retirado" placeholder="0,00">
                    <p class="ajuda">
                        <span x-text="pctRetirado"></span> do inicial
                    </p>
                    <p x-show="retirouDemais" x-cloak class="erro-campo">
                        <?= Icon::svg('erro', 'w-3.5 h-3.5') ?> Passou do valor inicial.
                    </p>
                    <?= $erroDe('valor_retirado') ?>
                </div>

                <div>
                    <label class="label" for="valor_total">Valor final</label>
                    <input type="text" name="valor_total" id="valor_total" inputmode="decimal"
                           class="input num <?= $erroCls('valor_total') ?>"
                           x-model="finalV" placeholder="0,00"
                           :class="auditadaSemFinal ? 'input-error' : ''">
                    <p class="ajuda">Como a conta saiu da auditoria.</p>
                    <p x-show="auditadaSemFinal" x-cloak class="erro-campo">
                        <?= Icon::svg('erro', 'w-3.5 h-3.5') ?>
                        Preencha para marcar a conta como auditada.
                    </p>
                    <?= $erroDe('valor_total') ?>
                </div>

                <?php /* O acrescentado é o que fecha a diferença entre o que
                         entrou e o que saiu. Em cinza, com a palavra
                         "calculado", pelo mesmo motivo que o final estava antes:
                         campo que o sistema preenche não pode parecer campo
                         que espera digitação. */ ?>
                <div>
                    <label class="label">Acrescentado</label>
                    <div class="input flex items-center justify-between bg-slate-50 dark:bg-slate-800/60">
                        <span class="num font-semibold text-marine-900 dark:text-white"
                              :class="acrescentadoCalc < 0 ? 'text-amber-600 dark:text-amber-400' : ''">
                            R$ <span x-text="acrescentadoBr"></span>
                        </span>
                        <span class="text-[0.6875rem] uppercase tracking-wide text-slate-400">calculado</span>
                    </div>
                    <p class="ajuda">
                        final + retirado − inicial ·
                        <span x-text="pctAcresc"></span> do inicial
                    </p>
                </div>

                <div>
                    <label class="label" for="valor_glosado">Glosado</label>
                    <input type="text" name="valor_glosado" id="valor_glosado" inputmode="decimal"
                           class="input num <?= $erroCls('valor_glosado') ?>"
                           x-model="glosado" placeholder="0,00">
                    <p class="ajuda"><span x-text="pctGlosado"></span> do valor final</p>
                    <?= $erroDe('valor_glosado') ?>
                </div>

                <div>
                    <label class="label" for="valor_aceito">Aceito no recurso</label>
                    <input type="text" name="valor_aceito" id="valor_aceito" inputmode="decimal"
                           class="input num <?= $erroCls('valor_aceito') ?>"
                           x-model="aceito" placeholder="0,00">
                    <p class="ajuda"><span x-text="pctAceito"></span> do glosado</p>
                    <?= $erroDe('valor_aceito') ?>
                </div>

                <div>
                    <label class="label" for="valor_faturado">Faturado</label>
                    <input type="text" name="valor_faturado" id="valor_faturado" inputmode="decimal"
                           class="input num <?= $erroCls('valor_faturado') ?>"
                           value="<?= e($moeda('valor_faturado')) ?>" placeholder="0,00">
                    <p class="ajuda">O que de fato foi faturado ao convênio.</p>
                    <?= $erroDe('valor_faturado') ?>
                </div>
            </div>
        </div>

        <!-- Situação -->
        <div class="form-secao">
            <div>
                <h2 class="form-secao-titulo">Situação</h2>
                <p class="form-secao-desc">Em que ponto a conta está, e o que falta nela.</p>
            </div>

            <div class="form-campos">
                <div>
                    <label class="label" for="status">Situação <span class="obrigatorio">*</span></label>
                    <select name="status" id="status" required class="select <?= $erroCls('status') ?>"
                            x-model="situacao">
                        <?php foreach ($situacoes as $s): ?>
                            <option value="<?= e($s) ?>" <?= $v('status', 'Pré análise') === $s ? 'selected' : '' ?>>
                                <?= e($s) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php /* O servidor recusa auditada sem valor final. A tela diz
                             antes, e no campo em que a pessoa está mexendo. */ ?>
                    <p x-show="auditadaSemFinal" x-cloak class="erro-campo">
                        <?= Icon::svg('erro', 'w-3.5 h-3.5') ?>
                        A conta só passa a auditada com o valor final preenchido.
                    </p>
                    <?= $erroDe('status') ?>
                </div>

                <div>
                    <label class="label" for="falta_nf">Nota fiscal</label>
                    <select name="falta_nf" id="falta_nf" class="select">
                        <option value="Não" <?= $v('falta_nf', 'Não') !== 'Sim' ? 'selected' : '' ?>>Está completa</option>
                        <option value="Sim" <?= $v('falta_nf') === 'Sim' ? 'selected' : '' ?>>Falta nota fiscal</option>
                    </select>
                </div>

                <div class="form-largo">
                    <label class="label" for="observacoes">Observação</label>
                    <textarea name="observacoes" id="observacoes" rows="2" class="input"
                              placeholder="O que explica esta conta para quem abrir depois"><?= e($v('observacoes')) ?></textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="form-acoes px-4 pb-4">
        <a href="<?= url('auditoria_hosp', 'lista', ['tipo' => $tipo]) ?>" class="btn btn-outline">Cancelar</a>
        <button type="submit" class="btn btn-primary" :disabled="enviando">
            <span x-show="!enviando"><?= Icon::svg('salvar', 'w-4 h-4') ?></span>
            <span x-show="enviando" x-cloak class="girando"></span>
            <?= $editando ? 'Salvar alterações' : 'Registrar conta' ?>
        </button>
    </div>
</form>

<?php /*
    Motivos da glosa.
    Fora do formulário de cima porque dependem de a conta já existir, e porque
    incluir um motivo não deveria obrigar a regravar a conta inteira.
*/ ?>
<div class="card mt-5">
    <div class="card-header">
        <h2 class="card-title">Motivos da glosa</h2>
        <?php if ($editando && $motivos): ?>
            <span class="text-xs <?= abs($somaMotivos - (float) $v('valor_glosado', '0')) < 0.01
                    ? 'text-slate-500 dark:text-slate-400'
                    : 'text-amber-600 dark:text-amber-400' ?>">
                somam <?= e(dinheiro_br($somaMotivos)) ?>
                de <?= e(dinheiro_br((float) $v('valor_glosado', '0'))) ?> glosados
            </span>
        <?php endif; ?>
    </div>

    <?php if (!$editando): ?>
        <div class="vazio">
            <span class="vazio-icone"><?= Icon::svg('motivos', 'w-5 h-5') ?></span>
            <p class="vazio-titulo">Primeiro registre a conta</p>
            <p class="vazio-texto">
                Os motivos pertencem a uma conta. Grave o que já tem e a tela volta aqui.
            </p>
        </div>
    <?php else: ?>
        <?php if ($motivos): ?>
            <div class="overflow-x-auto">
                <table class="tabela">
                    <thead>
                        <tr>
                            <th class="w-20">Código</th>
                            <th>Motivo</th>
                            <th class="w-32 text-right">Valor</th>
                            <th class="w-12 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($motivos as $m): ?>
                            <tr>
                                <td class="mono text-xs"><?= e($m['codigo']) ?></td>
                                <td>
                                    <span class="celula-principal block"><?= e($m['descricao']) ?></span>
                                    <span class="block text-xs text-slate-500 dark:text-slate-400">
                                        <?= $m['grupo'] ? e($m['grupo']) : '' ?>
                                        <?php if ($m['observacao']): ?>
                                            <?= $m['grupo'] ? ' · ' : '' ?><?= e($m['observacao']) ?>
                                        <?php endif; ?>
                                    </span>
                                </td>
                                <td class="text-right text-xs"><?= e(dinheiro_br((float) $m['valor_glosa'], false)) ?></td>
                                <td>
                                    <?php if (Auth::temPermissao('auditoria_hosp', 'pode_editar')): ?>
                                        <form method="post" action="<?= url('auditoria_hosp', 'excluir_motivo') ?>"
                                              class="flex justify-end"
                                              data-titulo="Remover motivo"
                                              data-acao="Remover"
                                              data-texto="<?= e($m['codigo'] . ' — ' . $m['descricao']) ?> sai desta conta.">
                                            <?= Csrf::campo() ?>
                                            <input type="hidden" name="id" value="<?= (int) $m['id'] ?>">
                                            <input type="hidden" name="conta_id" value="<?= (int) $conta['id'] ?>">
                                            <input type="hidden" name="tipo" value="<?= e($tipo) ?>">
                                            <button type="button" @click="abrir" class="btn-acao btn-acao-perigo"
                                                    aria-label="Remover motivo">
                                                <?= Icon::svg('excluir', 'w-3.5 h-3.5') ?>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <?php if (Auth::temPermissao('auditoria_hosp', 'pode_editar')): ?>
            <form method="post" action="<?= url('auditoria_hosp', 'motivo') ?>"
                  class="flex flex-wrap items-end gap-2 border-t border-slate-200 p-4 dark:border-slate-800">
                <?= Csrf::campo() ?>
                <input type="hidden" name="conta_id" value="<?= (int) $conta['id'] ?>">
                <input type="hidden" name="tipo" value="<?= e($tipo) ?>">

                <div class="min-w-64 flex-1">
                    <label class="label" for="motivo_glosa_id">Motivo</label>
                    <select name="motivo_glosa_id" id="motivo_glosa_id" required class="select">
                        <option value="">Escolha no cadastro…</option>
                        <?php foreach ($catalogo as $m): ?>
                            <option value="<?= (int) $m['id'] ?>">
                                <?= e($m['codigo']) ?> — <?= e(mb_strimwidth((string) $m['descricao'], 0, 90, '…')) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="w-36">
                    <label class="label" for="valor_glosa">Valor</label>
                    <input type="text" name="valor_glosa" id="valor_glosa" inputmode="decimal"
                           class="input num" placeholder="0,00">
                </div>

                <div class="min-w-48 flex-1">
                    <label class="label" for="observacao">Observação</label>
                    <input type="text" name="observacao" id="observacao" maxlength="300" class="input"
                           placeholder="O que o convênio alegou">
                </div>

                <button type="submit" class="btn btn-outline">
                    <?= Icon::svg('novo', 'w-4 h-4') ?> Incluir
                </button>
            </form>
        <?php endif; ?>
    <?php endif; ?>
</div>
