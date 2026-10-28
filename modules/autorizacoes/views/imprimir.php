<?php
/**
 * Folha dos documentos da autorização: a guia que o convênio devolveu e o
 * pedido médico, prontos para anexar ao prontuário ou mandar para o convênio.
 *
 * Primeiro uma folha de rosto com os dados do pedido — sem ela, uma foto de
 * guia solta na impressora não diz de quem é. Depois cada imagem na sua
 * própria folha, no tamanho que couber.
 *
 * @var array  $registro
 * @var array  $anexos    separados por tipo
 * @var string $tipo      recorte da impressão: '', 'guia' ou 'pedido'
 * @var int    $perdidos  documentos cuja ficha existe mas o arquivo sumiu
 */
declare(strict_types=1);

$tituloPagina  = 'Documentos — ' . $registro['paciente'];
$paraImpressao = true;

$rotulos = AutorizacaoModel::TIPOS_ANEXO;

// As imagens saem no papel. O PDF e a página salva pelo portal do convênio
// não dão para embutir numa folha HTML sem perder o original — são listados
// aqui e abertos no programa de cada um.
$imagens = [];
$aparte  = [];

foreach ($anexos as $t => $lista) {
    foreach ($lista as $a) {
        if (str_starts_with((string) $a['mime'], 'image/')) {
            $imagens[] = $a + ['rotulo' => $rotulos[$t] ?? $t];
        } else {
            $aparte[] = $a + [
                'rotulo' => $rotulos[$t] ?? $t,
                'formato' => AutorizacaoModel::ROTULOS_MIME[(string) $a['mime']] ?? 'arquivo',
            ];
        }
    }
}

$linha = function (string $rotulo, ?string $valor): string {
    return '<div><dt class="text-[0.6875rem] uppercase tracking-wide text-slate-500">' . e($rotulo) . '</dt>'
         . '<dd class="mt-0.5 text-sm font-medium text-marine-900">'
         . ($valor !== null && $valor !== '' ? e($valor) : '<span class="text-slate-400">—</span>')
         . '</dd></div>';
};
?>

<div class="mb-4 flex items-center justify-between gap-2 nao-imprimir">
    <a href="<?= url('autorizacoes', 'editar', ['id' => (int) $registro['id']]) ?>" class="btn btn-outline">
        <?= Icon::svg('anterior', 'w-4 h-4') ?> Voltar à autorização
    </a>
    <button type="button" data-imprimir class="btn btn-primary">
        <?= Icon::svg('imprimir', 'w-4 h-4') ?> Imprimir
    </button>
</div>

<div class="card p-6 print:border-0 print:p-0 print:shadow-none">
    <?php /* O mesmo cabeçalho das outras folhas do sistema: a marca do
             hospital à esquerda, a identificação no meio e a da APAS à
             direita. O print-color-adjust vai no atributo porque sem ele o
             Chrome imprime as marcas lavadas, por economia de tinta. */ ?>
    <header class="mb-5 flex items-center justify-between gap-4 border-b border-slate-200 pb-3">
        <img src="<?= BASE_URL ?>/image/logo_hospital.png" alt="Hospital Santo Expedito"
             class="h-10 w-auto shrink-0 object-contain"
             style="print-color-adjust:exact;-webkit-print-color-adjust:exact">

        <div class="min-w-0 flex-1">
            <p class="mono text-[0.6875rem] font-semibold text-marine-600">
                AUTORIZAÇÃO DE EXAME · Nº <?= (int) $registro['id'] ?>
            </p>
            <h1 class="mt-0.5 text-lg font-semibold leading-tight text-marine-900">
                <?= e($registro['paciente']) ?>
            </h1>
            <p class="mt-0.5 text-sm text-slate-600">
                <?= e($registro['procedimento']) ?> · <?= e($registro['convenio']) ?>
            </p>
        </div>

        <img src="<?= BASE_URL ?>/image/logo_apas.png" alt="APAS Baixada Santista"
             class="h-7 w-auto shrink-0 object-contain"
             style="print-color-adjust:exact;-webkit-print-color-adjust:exact">
    </header>

    <dl class="grid grid-cols-2 gap-x-6 gap-y-3.5 sm:grid-cols-4">
        <?= $linha('CPF', $registro['cpf'] ? formatar_cpf((string) $registro['cpf']) : null) ?>
        <?= $linha('Carteirinha', $registro['carteirinha']) ?>
        <?= $linha('Contato', $registro['contato'] ? telefone_br((string) $registro['contato']) : null) ?>
        <?= $linha('Situação', AutorizacaoModel::STATUS[$registro['status']] ?? $registro['status']) ?>

        <?= $linha('Senha da autorização', $registro['senha']) ?>
        <?= $linha('Solicitação', data_br($registro['data_solicitacao'])) ?>
        <?= $linha('Pedido médico', $registro['data_pedido'] ? data_br($registro['data_pedido']) : null) ?>
        <?= $linha('Autorizado em', $registro['data_autorizacao'] ? data_br($registro['data_autorizacao']) : null) ?>
    </dl>

    <?php if (!empty($registro['data_agendada']) || !empty($registro['observacao'])
              || !empty($registro['info_pendente'])): ?>
        <dl class="mt-3.5 grid grid-cols-2 gap-x-6 gap-y-3.5 sm:grid-cols-4">
            <?= $linha('Data agendada', $registro['data_agendada'] ? data_br($registro['data_agendada']) : null) ?>

            <?php /* A pendência vai na folha: quem leva o papel ao convênio
                     precisa saber o que ainda falta tanto quanto o que já há. */ ?>
            <?php if (!empty($registro['info_pendente'])): ?>
                <div class="col-span-2 sm:col-span-3">
                    <dt class="text-[0.6875rem] uppercase tracking-wide text-slate-500">Informações pendentes</dt>
                    <dd class="mt-0.5 text-sm text-amber-700"><?= e($registro['info_pendente']) ?></dd>
                </div>
            <?php endif; ?>

            <?php if (!empty($registro['observacao'])): ?>
                <div class="col-span-2 sm:col-span-3">
                    <dt class="text-[0.6875rem] uppercase tracking-wide text-slate-500">Observação</dt>
                    <dd class="mt-0.5 text-sm text-slate-700"><?= e($registro['observacao']) ?></dd>
                </div>
            <?php endif; ?>
        </dl>
    <?php endif; ?>

    <?php /* O que vai sair no papel, dito antes de sair: quem recebe a folha
             confere se está completo sem precisar contar as páginas. */ ?>
    <section class="mt-5 border-t border-slate-200 pt-3.5">
        <h2 class="text-[0.6875rem] font-semibold uppercase tracking-wide text-slate-500">
            Documentos nesta impressão
        </h2>

        <ul class="mt-2 space-y-1">
            <?php foreach ($imagens as $i => $img): ?>
                <li class="text-sm text-slate-700">
                    <span class="mono text-xs text-slate-400">folha <?= $i + 2 ?></span>
                    · <?= e($img['rotulo']) ?> — <?= e($img['nome_orig']) ?>
                </li>
            <?php endforeach; ?>

            <?php foreach ($aparte as $doc): ?>
                <li class="text-sm text-slate-700">
                    <span class="mono text-xs text-slate-400">em <?= e($doc['formato']) ?></span>
                    · <?= e($doc['rotulo']) ?> — <?= e($doc['nome_orig']) ?>
                    <span class="text-xs text-slate-500">(não sai nestas folhas)</span>
                </li>
            <?php endforeach; ?>
        </ul>

        <?php /* Falta documento nesta impressão, e o papel tem de dizer. Quem
                 arquiva meses depois não tem como saber se faltou folha na
                 impressora ou se o arquivo nunca mais existiu. */ ?>
        <?php if (($perdidos ?? 0) > 0): ?>
            <p class="mt-3 rounded-lg border border-amber-300 bg-amber-50 p-2.5 text-[0.8125rem] text-amber-900 print:bg-white">
                <?= $perdidos === 1
                    ? 'Mais um documento consta nesta autorização, mas o arquivo dele não está no servidor e não saiu nestas folhas.'
                    : 'Mais ' . numero($perdidos) . ' documentos constam nesta autorização, mas os arquivos deles não estão no servidor e não saíram nestas folhas.' ?>
            </p>
        <?php endif; ?>

        <?php if ($aparte): ?>
            <div class="mt-3 rounded-lg border border-slate-200 bg-slate-50 p-3 print:bg-white">
                <p class="text-[0.8125rem] text-slate-600">
                    <?= count($aparte) === 1 ? 'Um documento sai' : count($aparte) . ' documentos saem' ?>
                    com mais qualidade abertos no tamanho original — o PDF pelo leitor de PDF,
                    a página do convênio pelo navegador. Abra e mande imprimir de lá:
                </p>
                <p class="mt-2 flex flex-wrap gap-2 nao-imprimir">
                    <?php foreach ($aparte as $doc): ?>
                        <a href="<?= url('autorizacoes', 'anexo', ['id' => (int) $doc['id']]) ?>"
                           target="_blank" rel="noopener" class="btn btn-outline btn-sm">
                            <?= Icon::svg('arquivo', 'w-3.5 h-3.5') ?> <?= e($doc['nome_orig']) ?>
                        </a>
                    <?php endforeach; ?>
                </p>
            </div>
        <?php endif; ?>
    </section>

    <footer class="mt-6 border-t border-slate-200 pt-3 text-[0.6875rem] text-slate-400">
        Impresso em <?= e(date('d/m/Y \à\s H:i')) ?> por <?= e((string) Auth::nome()) ?> · APASBS.
        Documento da paciente: trate o papel com o mesmo cuidado do prontuário.
    </footer>
</div>

<?php /* Cada imagem na sua folha. O rótulo vai no alto de cada uma, porque
         folha que se solta da pilha precisa continuar dizendo de quem é. */ ?>
<?php foreach ($imagens as $img): ?>
    <div class="card mt-4 p-6 quebra-pagina print:mt-0 print:border-0 print:p-0 print:shadow-none">
        <div class="mb-3 flex items-baseline justify-between gap-3 border-b border-slate-200 pb-2">
            <div class="min-w-0">
                <h2 class="text-sm font-semibold text-marine-900"><?= e($img['rotulo']) ?></h2>
                <p class="text-xs text-slate-500">
                    <?= e($registro['paciente']) ?>
                    <?php if ($registro['cpf']): ?> · <?= e(formatar_cpf((string) $registro['cpf'])) ?><?php endif; ?>
                    · <?= e($registro['convenio']) ?>
                    <?php if ($registro['senha']): ?> · senha <?= e($registro['senha']) ?><?php endif; ?>
                </p>
            </div>
            <span class="mono shrink-0 text-[0.6875rem] text-slate-400">
                Autorização nº <?= (int) $registro['id'] ?>
            </span>
        </div>

        <img src="<?= url('autorizacoes', 'anexo', ['id' => (int) $img['id']]) ?>"
             alt="<?= e($img['rotulo']) ?>" class="folha-anexo mx-auto block">
    </div>
<?php endforeach; ?>
