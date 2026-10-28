<?php
/** @var array $cirurgia */
/** @var array $anexos */
declare(strict_types=1);

$podeEditar  = Auth::temPermissao('cirurgias', 'pode_editar');
$podeExcluir = Auth::temPermissao('cirurgias', 'pode_excluir');
?>

<!-- Cabeçalho que só existe no papel -->
<div class="so-impressao mb-6 border-b border-slate-300 pb-4">
    <div class="flex items-start justify-between gap-6">
        <img src="<?= BASE_URL ?>/image/logo_apas.png" alt="APAS Baixada Santista" style="height: 42px">
        <div style="text-align: right; font-size: 10pt; line-height: 1.5">
            <strong>Ficha de procedimento cirúrgico</strong><br>
            Protocolo <?= e($cirurgia['protocolo']) ?><br>
            Emitida em <?= date('d/m/Y \à\s H:i') ?> por <?= e(Auth::nome()) ?>
        </div>
    </div>
</div>

<div class="mb-5 flex flex-wrap items-center justify-between gap-3">
    <div class="flex items-center gap-3">
        <a href="<?= url_lista('cirurgias') ?>" class="btn btn-icon btn-outline nao-imprimir" aria-label="Voltar">
            <?= Icon::svg('voltar', 'w-4 h-4') ?>
        </a>
        <div>
            <h1 class="titulo-pagina">Protocolo <?= e($cirurgia['protocolo']) ?></h1>
            <p class="sub-pagina">Registrado em <?= datahora_br($cirurgia['created_at']) ?></p>
        </div>
    </div>

    <div class="flex items-center gap-2 nao-imprimir">
        <button type="button" @click="window.print()" class="btn btn-outline">
            <?= Icon::svg('arquivo', 'w-4 h-4') ?> Imprimir
        </button>

        <?php if ($podeEditar): ?>
            <a href="<?= url('cirurgias', 'editar', ['id' => (int) $cirurgia['id']]) ?>" class="btn btn-outline">
                <?= Icon::svg('editar', 'w-4 h-4') ?> Editar
            </a>
        <?php endif; ?>

        <?php if ($podeExcluir): ?>
            <form method="post" action="<?= url('cirurgias', 'excluir') ?>"
                  data-titulo="Excluir cirurgia"
                  data-texto="A cirurgia de protocolo <?= e($cirurgia['protocolo']) ?> e todos os seus anexos serão removidos permanentemente.">
                <?= Csrf::campo() ?>
                <input type="hidden" name="id" value="<?= (int) $cirurgia['id'] ?>">
                <button type="button" @click="abrir" class="btn btn-danger-outline">
                    <?= Icon::svg('excluir', 'w-4 h-4') ?> Excluir
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>

<div class="imprimir-em-bloco grid gap-4 lg:grid-cols-3">

    <section class="card lg:col-span-2">
        <div class="card-header"><h2 class="card-title">Dados do procedimento</h2></div>

        <dl class="grid gap-4 p-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <dt class="label">Procedimento TUSS</dt>
                <dd class="flex flex-wrap items-start gap-3">
                    <span class="rounded-lg bg-marine-50 px-2.5 py-1 font-mono text-sm font-semibold text-marine-700 dark:bg-marine-500/10 dark:text-marine-300">
                        <?= e($cirurgia['tuss_codigo']) ?>
                    </span>
                    <span class="flex-1 text-[0.875rem] leading-snug text-slate-700 dark:text-slate-200"><?= e($cirurgia['tuss_termo']) ?></span>
                </dd>
                <?php if (Auth::temPermissao('tuss')): ?>
                    <a href="<?= url('tuss', 'detalhe', ['codigo' => (int) $cirurgia['tuss_codigo']]) ?>"
                       class="nao-imprimir mt-2 inline-flex items-center gap-1 text-xs font-medium text-marine-600 hover:underline dark:text-marine-300">
                        <?= Icon::svg('ver', 'w-3.5 h-3.5') ?> Ver na tabela TUSS
                    </a>
                <?php endif; ?>
            </div>

            <div>
                <dt class="label">Médico responsável</dt>
                <dd class="text-[0.875rem] font-medium text-slate-800 dark:text-slate-100"><?= e($cirurgia['medico']) ?></dd>
            </div>

            <div>
                <dt class="label">Especialidade</dt>
                <dd><span class="badge badge-contorno"><?= e($cirurgia['especialidade_nome']) ?></span></dd>
            </div>

            <div>
                <dt class="label">Protocolo</dt>
                <dd class="mono text-[0.875rem] font-semibold text-slate-800 dark:text-slate-100"><?= e($cirurgia['protocolo']) ?></dd>
            </div>

            <div>
                <dt class="label">Última atualização</dt>
                <dd class="text-[0.875rem] text-slate-600 dark:text-slate-300"><?= datahora_br($cirurgia['updated_at'] ?? null) ?></dd>
            </div>
        </dl>
    </section>

    <section class="card">
        <div class="card-header">
            <h2 class="card-title">Anexos</h2>
            <span class="badge badge-cinza"><?= count($anexos) ?></span>
        </div>

        <?php if (empty($anexos)): ?>
            <div class="vazio py-10 print:py-4">
                <span class="vazio-icone nao-imprimir"><?= Icon::svg('anexo', 'w-5 h-5') ?></span>
                <p class="vazio-texto">Nenhum anexo enviado.</p>
                <?php if ($podeEditar): ?>
                    <a href="<?= url('cirurgias', 'editar', ['id' => (int) $cirurgia['id']]) ?>" class="btn btn-sm btn-outline nao-imprimir mt-1">
                        <?= Icon::svg('upload', 'w-3.5 h-3.5') ?> Enviar anexos
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <ul class="divide-y divide-slate-100 dark:divide-slate-800">
                <?php foreach ($anexos as $a): ?>
                    <?php $imagem = str_starts_with((string) $a['mime'], 'image/'); ?>
                    <li class="flex items-center gap-3 px-4 py-2.5">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400">
                            <?= Icon::svg($imagem ? 'imagem' : 'arquivo', 'w-4 h-4') ?>
                        </span>

                        <span class="min-w-0 flex-1">
                            <a href="<?= url('cirurgias', 'download', ['id' => (int) $a['id']]) ?>" target="_blank" rel="noopener"
                               class="block truncate text-[0.8125rem] font-medium text-marine-700 hover:underline dark:text-marine-300"
                               title="<?= e($a['nome_orig']) ?>">
                                <?= e($a['nome_orig']) ?>
                            </a>
                            <span class="text-[0.68rem] text-slate-400">
                                <?= formatar_tamanho((int) $a['tamanho']) ?> · <?= datahora_br($a['created_at']) ?>
                            </span>
                        </span>

                        <a href="<?= url('cirurgias', 'download', ['id' => (int) $a['id'], 'baixar' => 1]) ?>"
                           class="btn-acao nao-imprimir" title="Baixar" aria-label="Baixar <?= e($a['nome_orig']) ?>">
                            <?= Icon::svg('download', 'w-4 h-4') ?>
                        </a>

                        <?php if ($podeEditar): ?>
                            <form method="post" action="<?= url('cirurgias', 'excluir_anexo') ?>" class="nao-imprimir inline"
                                  data-titulo="Remover anexo"
                                  data-texto="O arquivo &quot;<?= e($a['nome_orig']) ?>&quot; será apagado do servidor.">
                                <?= Csrf::campo() ?>
                                <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
                                <input type="hidden" name="cirurgia_id" value="<?= (int) $cirurgia['id'] ?>">
                                <button type="button" @click="abrir" class="btn-acao btn-acao-perigo"
                                        title="Remover" aria-label="Remover <?= e($a['nome_orig']) ?>">
                                    <?= Icon::svg('excluir', 'w-4 h-4') ?>
                                </button>
                            </form>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</div>

<!-- Assinaturas: só aparecem no papel -->
<div class="so-impressao" style="margin-top: 22mm">
    <table style="width: 100%; border-collapse: collapse; font-size: 10pt">
        <tr>
            <td style="width: 45%; border-top: 1px solid #64748b; padding-top: 6px; text-align: center">
                Médico responsável
            </td>
            <td style="width: 10%"></td>
            <td style="width: 45%; border-top: 1px solid #64748b; padding-top: 6px; text-align: center">
                Responsável pelo registro
            </td>
        </tr>
    </table>
</div>
