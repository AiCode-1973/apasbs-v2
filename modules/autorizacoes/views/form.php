<?php
/**
 * Autorização: incluir e editar.
 *
 * @var array $registro
 * @var array $erros, $convenios, $procedimentos, $anexos
 * @var int    $limite_anexo     maior arquivo que este servidor aceita, em bytes
 * @var string $limite_anexo_br  o mesmo limite por extenso, para a tela
 */
declare(strict_types=1);

// O teto vem do Controller, que pergunta ao banco quanto ele aceita receber
// de uma vez. A tela só o repete — em bytes para o componente que mede o
// arquivo escolhido, e por extenso para quem está lendo.
$limiteAnexo = (int) ($limite_anexo ?? 10 * 1024 * 1024);
$limiteBr    = (string) ($limite_anexo_br ?? formatar_tamanho($limiteAnexo));

$editando = !empty($registro['id']);
$erroCls  = fn(string $c): string => isset($erros[$c]) ? 'input-error' : '';
$v        = fn(string $c, string $padrao = ''): string => (string) ($registro[$c] ?? $padrao);

$erroDe = function (string $campo) use ($erros): string {
    return isset($erros[$campo])
        ? '<p class="erro-campo">' . Icon::svg('erro', 'w-3.5 h-3.5') . ' ' . e($erros[$campo]) . '</p>'
        : '';
};
?>

<div class="mb-5 flex items-start gap-3">
    <a href="<?= url_lista('autorizacoes') ?>" class="btn-acao mt-0.5" aria-label="Voltar">
        <?= Icon::svg('anterior', 'w-4 h-4') ?>
    </a>
    <div>
        <h1 class="titulo-pagina"><?= $editando ? 'Editar autorização' : 'Nova autorização' ?></h1>
        <p class="sub-pagina">Um pedido de exame que depende de liberação do convênio</p>
    </div>
</div>

<?php /* enctype multipart porque, numa autorização nova, a guia e o pedido
         vêm no mesmo envio do cadastro. */ ?>
<form method="post" action="<?= url('autorizacoes', 'salvar') ?>" class="card"
      id="form-autorizacao" enctype="multipart/form-data"
      x-data="autorizacao(<?= e(json_encode($v('status', 'aguardando'))) ?>,
                          <?= e(json_encode($v('info_pendente'))) ?>)"
      @submit="enviar" novalidate>
    <?= Csrf::campo() ?>
    <?php if ($editando): ?>
        <input type="hidden" name="id" value="<?= (int) $registro['id'] ?>">
    <?php endif; ?>

    <div class="card-corpo">

        <!-- Quem e quando -->
        <div class="form-secao">
            <div>
                <h2 class="form-secao-titulo">A paciente</h2>
                <p class="form-secao-desc">
                    Quem precisa do exame e como falar com ela quando o convênio responder.
                </p>
                <p class="form-secao-desc">
                    Só o nome e a data são obrigatórios. CPF, carteirinha e contato
                    podem entrar depois, quando alguém tiver os documentos em mãos.
                </p>
            </div>

            <div class="form-campos">
                <div class="form-largo">
                    <label class="label" for="paciente">Nome da paciente <span class="obrigatorio">*</span></label>
                    <input type="text" name="paciente" id="paciente" maxlength="150" required autofocus
                           class="input <?= $erroCls('paciente') ?>" value="<?= e($v('paciente')) ?>"
                           placeholder="Nome completo, como está na carteirinha">
                    <?= $erroDe('paciente') ?>
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
                    <label class="label" for="carteirinha">Carteirinha</label>
                    <input type="text" name="carteirinha" id="carteirinha" maxlength="40"
                           class="input mono <?= $erroCls('carteirinha') ?>"
                           value="<?= e($v('carteirinha')) ?>"
                           placeholder="O número no cartão do convênio">
                    <?= $erroDe('carteirinha') ?>
                </div>

                <div>
                    <label class="label" for="contato">Contato</label>
                    <input type="tel" name="contato" id="contato" inputmode="numeric" maxlength="15"
                           class="input <?= $erroCls('contato') ?>" value="<?= e(telefone_br($v('contato'))) ?>"
                           x-data="telefone" @input="formatar" placeholder="(13) 99999-9999">
                    <?= $erroDe('contato') ?>
                </div>

                <div>
                    <label class="label" for="data_solicitacao">
                        Data da solicitação <span class="obrigatorio">*</span>
                    </label>
                    <input type="date" name="data_solicitacao" id="data_solicitacao" required
                           class="input <?= $erroCls('data_solicitacao') ?>"
                           value="<?= e(substr($v('data_solicitacao', date('Y-m-d')), 0, 10)) ?>">
                    <?= $erroDe('data_solicitacao') ?>
                    <p class="ajuda">O dia em que a paciente pediu.</p>
                </div>
            </div>
        </div>

        <!-- O que foi pedido -->
        <div class="form-secao">
            <div>
                <h2 class="form-secao-titulo">O pedido</h2>
                <p class="form-secao-desc">
                    Convênio, procedimento e a data do pedido médico.
                </p>
                <p class="form-secao-desc">
                    Falta algum na lista?
                    <a href="<?= url('autorizacoes', 'convenios') ?>" class="text-marine-700 hover:underline dark:text-marine-300">Convênios</a> ·
                    <a href="<?= url('autorizacoes', 'procedimentos') ?>" class="text-marine-700 hover:underline dark:text-marine-300">Procedimentos</a>
                </p>
            </div>

            <div class="form-campos">
                <div>
                    <label class="label" for="convenio_id">Convênio <span class="obrigatorio">*</span></label>
                    <select name="convenio_id" id="convenio_id" required class="select <?= $erroCls('convenio_id') ?>">
                        <option value="">Escolha…</option>
                        <?php foreach ($convenios as $c): ?>
                            <option value="<?= (int) $c['id'] ?>"
                                <?= (int) $v('convenio_id') === (int) $c['id'] ? 'selected' : '' ?>>
                                <?= e($c['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?= $erroDe('convenio_id') ?>
                </div>

                <div>
                    <label class="label" for="procedimento_id">Procedimento <span class="obrigatorio">*</span></label>
                    <select name="procedimento_id" id="procedimento_id" required class="select <?= $erroCls('procedimento_id') ?>">
                        <option value="">Escolha…</option>
                        <?php foreach ($procedimentos as $p): ?>
                            <option value="<?= (int) $p['id'] ?>"
                                <?= (int) $v('procedimento_id') === (int) $p['id'] ? 'selected' : '' ?>>
                                <?= e($p['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?= $erroDe('procedimento_id') ?>
                </div>

                <div class="form-largo">
                    <label class="label" for="data_pedido">Data do pedido médico</label>
                    <input type="date" name="data_pedido" id="data_pedido"
                           class="input <?= $erroCls('data_pedido') ?>"
                           value="<?= e(substr($v('data_pedido'), 0, 10)) ?>">
                    <?= $erroDe('data_pedido') ?>
                    <p class="ajuda">
                        Pode ficar em branco: o pedido às vezes chega sem data escrita.
                    </p>
                </div>
            </div>
        </div>

        <!-- Como está -->
        <div class="form-secao">
            <div>
                <h2 class="form-secao-titulo">Situação</h2>
                <p class="form-secao-desc">
                    Em que ponto está o pedido. A data agendada pode ser
                    preenchida a qualquer momento — inclusive antes de o
                    convênio responder, quando já existe um dia combinado.
                    O que estiver escrito em <em>Informações pendentes</em>
                    manda na situação enquanto a resposta não chega.
                </p>
            </div>

            <div class="form-campos">
                <div>
                    <label class="label" for="status">Situação <span class="obrigatorio">*</span></label>
                    <select name="status" id="status" required class="select <?= $erroCls('status') ?>"
                            x-model="status">
                        <?php foreach (AutorizacaoModel::STATUS as $chave => $rotulo): ?>
                            <option value="<?= e($chave) ?>"
                                <?= $v('status', 'aguardando') === $chave ? 'selected' : '' ?>>
                                <?= e($rotulo) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?= $erroDe('status') ?>
                </div>

                <div>
                    <label class="label" for="senha">Senha da autorização</label>
                    <input type="text" name="senha" id="senha" maxlength="60" class="input mono"
                           value="<?= e($v('senha')) ?>" placeholder="O número que o convênio passou">
                    <p class="ajuda">É o que o exame vai pedir no dia.</p>
                </div>

                <?php /* A data em que o convênio liberou — digitada à mão quando a
                         resposta chega. Só aparece no que está autorizado ou já
                         está na fila: em "aguardando" ela ainda
                         não existe, e em "negado" não houve autorização nenhuma. */ ?>
                <div x-show="status === 'agendado' || status === 'autorizado'" x-cloak>
                    <label class="label" for="data_autorizacao">Data da autorização</label>
                    <input type="date" name="data_autorizacao" id="data_autorizacao"
                           class="input <?= $erroCls('data_autorizacao') ?>"
                           value="<?= e(substr($v('data_autorizacao'), 0, 10)) ?>">
                    <?= $erroDe('data_autorizacao') ?>
                    <p class="ajuda">O dia em que o convênio liberou o exame.</p>
                </div>

                <?php /* Sempre à vista, em qualquer situação: acontece de a paciente
                         já ter dia combinado antes de o convênio responder, e
                         esconder o campo obrigava a anotar num papel para digitar
                         depois — que é o que o sistema veio evitar. */ ?>
                <div>
                    <label class="label" for="data_agendada">Data agendada</label>
                    <input type="date" name="data_agendada" id="data_agendada"
                           class="input <?= $erroCls('data_agendada') ?>"
                           value="<?= e(substr($v('data_agendada'), 0, 10)) ?>">
                    <?= $erroDe('data_agendada') ?>
                    <p class="ajuda" x-show="status !== 'agendado'" x-cloak>
                        Preenchida sem a situação <em>Para Autorizar</em>, vale como data
                        prevista — a lista mostra em cinza.
                    </p>
                </div>

                <div x-show="status === 'negado'" x-cloak>
                    <label class="label" for="motivo_negativa">Motivo da negativa</label>
                    <input type="text" name="motivo_negativa" id="motivo_negativa" maxlength="300"
                           class="input" value="<?= e($v('motivo_negativa')) ?>"
                           placeholder="O que o convênio alegou">
                    <p class="ajuda">Escrever aqui evita repetir o mesmo pedido daqui a um mês.</p>
                </div>

                <?php /* O que falta para o pedido andar. É o campo que muda a
                         situação sozinho — por isso vem antes da observação e
                         diz, ao vivo, o que vai acontecer na gravação. */ ?>
                <div class="form-largo">
                    <label class="label" for="info_pendente">Informações pendentes</label>
                    <input type="text" name="info_pendente" id="info_pendente" maxlength="400"
                           class="input <?= $erroCls('info_pendente') ?>"
                           value="<?= e($v('info_pendente')) ?>" x-model="pendencia"
                           placeholder="Ex.: falta o laudo do médico assistente">
                    <?= $erroDe('info_pendente') ?>

                    <p class="ajuda"
                       x-show="!viraPendente && !pendenteVazia && !pendenciaSemEfeito && !pendenteAtiva" x-cloak>
                        O que falta para o pedido andar. Escrevendo aqui, enquanto o
                        convênio ainda não respondeu, a situação passa a ser
                        <em>Informações pendentes</em>.
                    </p>

                    <p class="ajuda" x-show="pendenteAtiva" x-cloak>
                        Enquanto houver algo escrito aqui, a situação continua como
                        <em>Informações pendentes</em>. Apague para escolher outra.
                    </p>

                    <p class="ajuda text-violet-600 dark:text-violet-400" x-show="viraPendente" x-cloak>
                        Ao gravar, a situação vai ficar como <strong>Informações pendentes</strong>.
                        Apague este campo para escolher outra.
                    </p>

                    <p class="ajuda text-amber-600 dark:text-amber-400" x-show="pendenteVazia" x-cloak>
                        A situação é <em>Informações pendentes</em> e este campo está vazio.
                        Escreva o que falta — ou escolha outra situação.
                    </p>

                    <p class="ajuda" x-show="pendenciaSemEfeito" x-cloak>
                        O convênio já respondeu, então a situação não muda. O texto
                        aparece na lista, abaixo do selo.
                    </p>
                </div>

                <div class="form-largo">
                    <label class="label" for="observacao">Observação</label>
                    <input type="text" name="observacao" id="observacao" maxlength="400" class="input"
                           value="<?= e($v('observacao')) ?>"
                           placeholder="Ex.: paciente prefere pela manhã">
                    <p class="ajuda">Recado livre sobre o pedido. Não muda a situação.</p>
                </div>
            </div>
        </div>

    <?php if (!$editando): ?>
        <!-- Documentos, já na inclusão: quem está com a guia na mão não
             precisa gravar primeiro e voltar depois. Como os campos estão
             dentro deste formulário, o arquivo sobe no mesmo envio do
             cadastro — por isso o enctype multipart lá em cima. -->
        <div class="form-secao">
            <div>
                <h2 class="form-secao-titulo">Documentos</h2>
                <p class="form-secao-desc">
                    A guia que o convênio devolveu e o pedido do médico. Foto,
                    PDF ou a página que o portal do convênio salva (.htm e
                    .html), até <?= e($limiteBr) ?> cada — pelo celular, o botão abre a câmera.
                </p>
                <p class="form-secao-desc">
                    Sobem junto quando você gravar. Se faltar alguma coisa no
                    cadastro, o formulário volta com o aviso e os arquivos
                    precisam ser escolhidos de novo: o navegador não guarda o
                    que foi selecionado.
                </p>
            </div>

            <div class="form-campos">
                <?php foreach (AutorizacaoModel::TIPOS_ANEXO as $tipo => $rotulo): ?>
                    <section x-data="anexos(<?= (int) $limiteAnexo ?>)" class="space-y-2.5">
                        <h3 class="text-sm font-semibold text-marine-900 dark:text-white"><?= e($rotulo) ?></h3>

                        <label for="novo_<?= e($tipo) ?>"
                               class="flex cursor-pointer flex-col items-center justify-center gap-1 rounded-xl border-2 border-dashed border-slate-300 px-4 py-4 text-center transition-colors hover:border-marine-400 hover:bg-marine-50/40 dark:border-slate-700 dark:hover:border-marine-600 dark:hover:bg-slate-800/40">
                            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400">
                                <?= Icon::svg('upload', 'w-[18px] h-[18px]') ?>
                            </span>
                            <span class="text-[0.8125rem] font-medium text-slate-700 dark:text-slate-200">
                                Escolher ou fotografar
                            </span>
                            <span class="text-xs text-slate-500 dark:text-slate-400">
                                Pode mandar frente e verso de uma vez
                            </span>
                            <input type="file" name="doc_<?= e($tipo) ?>[]" id="novo_<?= e($tipo) ?>" multiple
                                   class="sr-only"
                                   accept="image/jpeg,image/png,image/webp,image/gif,application/pdf,text/html,.htm,.html"
                                   @change="selecionou">
                        </label>

                        <template x-if="arquivos.length">
                            <ul class="space-y-1">
                                <template x-for="a in arquivos" :key="a.nome">
                                    <li class="flex items-center gap-2 rounded-lg border border-slate-200 px-2.5 py-1 text-xs dark:border-slate-700">
                                        <span class="min-w-0 flex-1 truncate text-slate-700 dark:text-slate-200" x-text="a.nome"></span>
                                        <span class="shrink-0" :class="a.grande ? 'font-semibold text-rose-600' : 'text-slate-400'" x-text="a.tamanho"></span>
                                    </li>
                                </template>
                            </ul>
                        </template>

                        <div x-show="algumGrande" x-cloak class="alerta alerta-aviso">
                            <span class="mt-0.5 shrink-0"><?= Icon::svg('aviso', 'w-4 h-4') ?></span>
                            <p>Arquivos acima de <?= e($limiteBr) ?> não serão enviados.</p>
                        </div>
                    </section>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
    </div>

    <div class="form-acoes px-4 pb-4">
        <a href="<?= url_lista('autorizacoes') ?>" class="btn btn-outline">Cancelar</a>
        <button type="submit" class="btn btn-primary" :disabled="enviando">
            <span x-show="!enviando"><?= Icon::svg('salvar', 'w-4 h-4') ?></span>
            <span x-show="enviando" x-cloak class="girando"></span>
            <?= $editando ? 'Salvar alterações' : 'Registrar autorização' ?>
        </button>
    </div>
</form>

<!--
    Documentos de uma autorização que já existe.
    Aqui cada tipo tem o seu próprio envio: anexar a guia que chegou hoje não
    deve exigir regravar o cadastro inteiro, nem perder o que estiver digitado
    se um arquivo for recusado. Na inclusão é o contrário — lá os campos vão
    dentro do formulário, num envio só.
-->
<?php if ($editando): ?>
<div class="card mt-5">
    <div class="card-header">
        <h2 class="card-title">Documentos</h2>

        <?php
        $temGuia   = !empty($anexos['guia']);
        $temPedido = !empty($anexos['pedido']);
        ?>
        <?php if ($temGuia || $temPedido): ?>
            <div class="flex flex-wrap items-center gap-1.5">
                <?php /* Imprimir tudo é o caso comum; os dois recortes
                         existem porque às vezes o convênio só quer a guia,
                         e o prontuário só quer o pedido. */ ?>
                <?php if ($temGuia && $temPedido): ?>
                    <a href="<?= url('autorizacoes', 'imprimir', ['id' => (int) $registro['id']]) ?>"
                       target="_blank" rel="noopener" class="btn btn-outline btn-sm">
                        <?= Icon::svg('imprimir', 'w-3.5 h-3.5') ?> Imprimir tudo
                    </a>
                <?php endif; ?>

                <?php foreach (['guia' => 'Só a guia', 'pedido' => 'Só o pedido'] as $t => $rotulo): ?>
                    <?php if (!empty($anexos[$t])): ?>
                        <a href="<?= url('autorizacoes', 'imprimir', ['id' => (int) $registro['id'], 'tipo' => $t]) ?>"
                           target="_blank" rel="noopener" class="btn btn-ghost btn-sm">
                            <?= Icon::svg('imprimir', 'w-3.5 h-3.5') ?> <?= e($rotulo) ?>
                        </a>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <span class="text-xs text-slate-500 dark:text-slate-400">
                Foto, PDF ou página do convênio, até <?= e($limiteBr) ?> cada
            </span>
        <?php endif; ?>
    </div>

        <div class="card-corpo grid gap-6 md:grid-cols-2">
            <?php foreach (AutorizacaoModel::TIPOS_ANEXO as $tipo => $rotulo):
                $lista = $anexos[$tipo] ?? [];
                $ajuda = $tipo === 'guia'
                    ? 'A autorização que o convênio devolveu.'
                    : 'O pedido do médico que originou a solicitação.';
                ?>
                <section x-data="anexos(<?= (int) $limiteAnexo ?>)" class="space-y-3">
                    <div class="flex items-baseline justify-between gap-2">
                        <h3 class="text-sm font-semibold text-marine-900 dark:text-white"><?= e($rotulo) ?></h3>
                        <span class="text-xs <?= $lista ? 'text-slate-500 dark:text-slate-400' : 'text-amber-600 dark:text-amber-400' ?>">
                            <?= $lista ? numero(count($lista)) . ' arquivo' . (count($lista) === 1 ? '' : 's') : 'nenhum ainda' ?>
                        </span>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400"><?= e($ajuda) ?></p>

                    <?php if ($lista): ?>
                        <ul class="grid grid-cols-2 gap-2.5">
                            <?php foreach ($lista as $a):
                                $imagem  = str_starts_with((string) $a['mime'], 'image/');
                                $href    = url('autorizacoes', 'anexo', ['id' => (int) $a['id']]);
                                $perdido = (bool) ($a['perdido'] ?? false);
                                ?>
                                <li class="overflow-hidden rounded-lg border <?= $perdido
                                        ? 'border-amber-300 dark:border-amber-700/60'
                                        : 'border-slate-200 dark:border-slate-700' ?>">
                                    <?php /* Documento perdido não vira link: clicar levaria a
                                             um 404, e um link que só dá erro faz a pessoa
                                             tentar de novo achando que foi a internet. No
                                             lugar da miniatura, a frase que resolve — este
                                             precisa ser pedido outra vez. */ ?>
                                    <?php if ($perdido): ?>
                                        <div class="flex h-24 w-full flex-col items-center justify-center gap-1 bg-amber-50 px-2 text-center dark:bg-amber-950/30">
                                            <span class="text-amber-600 dark:text-amber-400">
                                                <?= Icon::svg('aviso', 'w-5 h-5') ?>
                                            </span>
                                            <span class="text-[0.6875rem] font-medium leading-tight text-amber-800 dark:text-amber-300">
                                                Arquivo não está<br>no servidor
                                            </span>
                                        </div>
                                    <?php else: ?>
                                    <a href="<?= $href ?>" target="_blank" rel="noopener"
                                       class="block bg-slate-50 dark:bg-slate-800"
                                       title="Abrir <?= e($a['nome_orig']) ?>">
                                        <?php if ($imagem): ?>
                                            <img src="<?= $href ?>" alt="<?= e($rotulo) ?>"
                                                 class="h-24 w-full object-cover" loading="lazy">
                                        <?php else: ?>
                                            <?php /* O que não é foto não tem miniatura: no
                                                     lugar dela, o tipo escrito, que agora
                                                     pode ser PDF ou página do convênio. */ ?>
                                            <span class="flex h-24 w-full flex-col items-center justify-center gap-1 text-slate-400">
                                                <?= Icon::svg('arquivo', 'w-5 h-5') ?>
                                                <span class="text-[0.6875rem] font-medium uppercase">
                                                    <?= e(AutorizacaoModel::ROTULOS_MIME[(string) $a['mime']] ?? 'Arquivo') ?>
                                                </span>
                                            </span>
                                        <?php endif; ?>
                                    </a>
                                    <?php endif; ?>

                                    <div class="flex items-center gap-1.5 px-2 py-1.5">
                                        <span class="min-w-0 flex-1 truncate text-[0.6875rem] text-slate-500 dark:text-slate-400"
                                              title="<?= e($a['nome_orig']) ?><?= $perdido ? ' — o arquivo não está mais no servidor; peça outra via e anexe de novo' : '' ?>">
                                            <?= e($a['nome_orig']) ?>
                                        </span>
                                        <span class="shrink-0 text-[0.625rem] text-slate-400">
                                            <?= formatar_tamanho((int) $a['tamanho']) ?>
                                        </span>
                                        <?php if (Auth::temPermissao('autorizacoes', 'pode_editar')): ?>
                                            <form method="post" action="<?= url('autorizacoes', 'excluir_anexo') ?>"
                                                  class="shrink-0"
                                                  data-titulo="Remover documento"
                                                  data-acao="Remover"
                                                  data-texto="<?= e($a['nome_orig']) ?> sai da autorização e o arquivo é apagado do servidor.">
                                                <?= Csrf::campo() ?>
                                                <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
                                                <button type="button" @click="abrir" class="btn-acao btn-acao-perigo"
                                                        aria-label="Remover documento">
                                                    <?= Icon::svg('excluir', 'w-3.5 h-3.5') ?>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>

                    <?php if (Auth::temPermissao('autorizacoes', 'pode_editar')): ?>
                        <form method="post" action="<?= url('autorizacoes', 'anexar') ?>"
                              enctype="multipart/form-data" class="space-y-2">
                            <?= Csrf::campo() ?>
                            <input type="hidden" name="autorizacao_id" value="<?= (int) $registro['id'] ?>">
                            <input type="hidden" name="tipo" value="<?= e($tipo) ?>">

                            <label for="arq_<?= e($tipo) ?>"
                                   class="flex cursor-pointer flex-col items-center justify-center gap-1 rounded-xl border-2 border-dashed border-slate-300 px-4 py-4 text-center transition-colors hover:border-marine-400 hover:bg-marine-50/40 dark:border-slate-700 dark:hover:border-marine-600 dark:hover:bg-slate-800/40">
                                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400">
                                    <?= Icon::svg('upload', 'w-[18px] h-[18px]') ?>
                                </span>
                                <span class="text-[0.8125rem] font-medium text-slate-700 dark:text-slate-200">
                                    Escolher ou fotografar
                                </span>
                                <span class="text-xs text-slate-500 dark:text-slate-400">
                                    Pode mandar frente e verso de uma vez
                                </span>
                                <input type="file" name="arquivos[]" id="arq_<?= e($tipo) ?>" multiple class="sr-only"
                                       accept="image/jpeg,image/png,image/webp,image/gif,application/pdf,text/html,.htm,.html"
                                       @change="selecionou">
                            </label>

                            <template x-if="arquivos.length">
                                <ul class="space-y-1">
                                    <template x-for="a in arquivos" :key="a.nome">
                                        <li class="flex items-center gap-2 rounded-lg border border-slate-200 px-2.5 py-1 text-xs dark:border-slate-700">
                                            <span class="min-w-0 flex-1 truncate text-slate-700 dark:text-slate-200" x-text="a.nome"></span>
                                            <span class="shrink-0" :class="a.grande ? 'font-semibold text-rose-600' : 'text-slate-400'" x-text="a.tamanho"></span>
                                        </li>
                                    </template>
                                </ul>
                            </template>

                            <div x-show="algumGrande" x-cloak class="alerta alerta-aviso">
                                <span class="mt-0.5 shrink-0"><?= Icon::svg('aviso', 'w-4 h-4') ?></span>
                                <p>Arquivos acima de <?= e($limiteBr) ?> não serão enviados.</p>
                            </div>

                            <button type="submit" class="btn btn-outline btn-sm w-full" x-show="arquivos.length" x-cloak>
                                <?= Icon::svg('upload', 'w-3.5 h-3.5') ?>
                                Anexar <?= e(mb_strtolower($rotulo)) ?>
                            </button>
                        </form>
                    <?php endif; ?>
                </section>
            <?php endforeach; ?>
        </div>
</div>
<?php endif; ?>
