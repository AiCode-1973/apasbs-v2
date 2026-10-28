<?php
/**
 * Um exame por dentro: os códigos e onde mais cada um aparece.
 *
 * @var array $exame
 * @var array $tambem  código => [ ['convenio','descricao','id'], … ]
 */
declare(strict_types=1);

$podeEditar  = Auth::temPermissao('codigos', 'pode_editar');
$podeExcluir = Auth::temPermissao('codigos', 'pode_excluir');
?>

<div class="mb-5 flex flex-wrap items-start justify-between gap-3">
    <div class="flex items-start gap-3">
        <a href="<?= url_lista('codigos') ?>" class="btn-acao mt-0.5" aria-label="Voltar">
            <?= Icon::svg('anterior', 'w-4 h-4') ?>
        </a>
        <div>
            <p class="text-xs text-slate-500 dark:text-slate-400"><?= e($exame['convenio']) ?></p>
            <h1 class="titulo-pagina"><?= e($exame['descricao']) ?></h1>
            <p class="sub-pagina">
                <?= count($exame['codigos']) ?>
                código<?= count($exame['codigos']) === 1 ? '' : 's' ?>
                · <?= $exame['ativo'] ? 'ativo' : 'inativo' ?>
            </p>
        </div>
    </div>

    <div class="flex flex-wrap items-center gap-2">
        <?php if ($podeEditar): ?>
            <a href="<?= url('codigos', 'editar', ['id' => (int) $exame['id']]) ?>" class="btn btn-outline">
                <?= Icon::svg('editar', 'w-4 h-4') ?> Editar
            </a>
        <?php endif; ?>

        <?php if ($podeExcluir): ?>
            <form method="post" action="<?= url('codigos', 'excluir') ?>" class="inline"
                  data-titulo="Excluir exame"
                  data-texto="&quot;<?= e($exame['descricao']) ?>&quot; do convênio <?= e($exame['convenio']) ?> e os <?= count($exame['codigos']) ?> código(s) dele serão removidos.">
                <?= Csrf::campo() ?>
                <input type="hidden" name="id" value="<?= (int) $exame['id'] ?>">
                <button type="button" @click="abrir" class="btn btn-danger-outline">
                    <?= Icon::svg('excluir', 'w-4 h-4') ?> Excluir
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php if ($exame['observacao']): ?>
    <div class="card mb-4">
        <div class="card-corpo text-[0.8125rem]">
            <span class="rotulo-num">Observação</span>
            <p class="mt-0.5"><?= e($exame['observacao']) ?></p>
        </div>
    </div>
<?php endif; ?>

<div class="card overflow-hidden">
    <div class="card-header">
        <h2 class="card-title">Códigos</h2>
        <span class="text-xs text-slate-500 dark:text-slate-400">
            na ordem em que foram cadastrados
        </span>
    </div>

    <?php if (empty($exame['codigos'])): ?>
        <div class="vazio">
            <p class="vazio-titulo">Nenhum código cadastrado</p>
            <p class="vazio-texto">
                O exame está registrado, mas ainda não diz com que código o convênio o fatura.
            </p>
            <?php if ($podeEditar): ?>
                <a href="<?= url('codigos', 'editar', ['id' => (int) $exame['id']]) ?>"
                   class="btn btn-primary btn-sm mt-1">
                    <?= Icon::svg('novo', 'w-3.5 h-3.5') ?> Acrescentar códigos
                </a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="tabela">
                <thead>
                    <tr>
                        <th class="w-40">Código</th>
                        <th>Descrição</th>
                        <th class="w-72">Também usado em</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($exame['codigos'] as $c): ?>
                        <tr>
                            <td class="mono celula-principal"><?= e($c['codigo']) ?></td>

                            <td class="celula-suave text-[0.8125rem]">
                                <?= $c['termo']
                                    ? e($c['termo'])
                                    : '<span class="text-slate-400 dark:text-slate-600">sem descrição</span>' ?>
                            </td>

                            <td class="text-xs">
                                <?php /* O mesmo código em outro convênio. É a pergunta que a
                                         tabela em papel não respondia: com que código a outra
                                         operadora paga este mesmo exame. */ ?>
                                <?php if (empty($tambem[$c['codigo']])): ?>
                                    <span class="text-slate-400 dark:text-slate-600">só aqui</span>
                                <?php else: ?>
                                    <?php foreach ($tambem[$c['codigo']] as $o): ?>
                                        <a href="<?= url('codigos', 'ver', ['id' => (int) $o['id']]) ?>"
                                           class="block truncate text-marine-700 hover:underline dark:text-marine-300">
                                            <?= e($o['convenio']) ?>
                                            <span class="text-slate-500 dark:text-slate-400">· <?= e($o['descricao']) ?></span>
                                        </a>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <div class="toolbar-rodape">
        <span>
            <?php if ($exame['usuario_nome']): ?>
                Último lançamento por <?= e($exame['usuario_nome']) ?>
                em <?= e(datahora_br($exame['updated_at'])) ?>
            <?php endif; ?>
        </span>
        <a href="<?= url('codigos', 'lista', ['convenio' => (int) $exame['convenio_id']]) ?>"
           class="text-xs text-marine-700 hover:underline dark:text-marine-300">
            Ver tudo de <?= e($exame['convenio']) ?> →
        </a>
    </div>
</div>
