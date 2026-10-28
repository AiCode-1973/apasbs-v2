<?php
/** @var array  $cirurgias */
/** @var array  $especialidades */
/** @var string $busca */
/** @var int    $espSelecionada */
declare(strict_types=1);

$podeCriar   = Auth::temPermissao('cirurgias', 'pode_criar');
$podeEditar  = Auth::temPermissao('cirurgias', 'pode_editar');
$podeExcluir = Auth::temPermissao('cirurgias', 'pode_excluir');
$filtrando   = $busca !== '' || $espSelecionada > 0;
?>

<div class="mb-5 flex flex-wrap items-end justify-between gap-3">
    <div>
        <h1 class="titulo-pagina">Cirurgias</h1>
        <p class="sub-pagina">Procedimentos registrados com código TUSS e anexos</p>
    </div>

    <?php if ($podeCriar): ?>
        <a href="<?= url('cirurgias', 'novo') ?>" class="btn btn-primary">
            <?= Icon::svg('novo', 'w-4 h-4') ?> Nova cirurgia
        </a>
    <?php endif; ?>
</div>

<div class="card overflow-hidden">
    <form method="get" action="<?= BASE_URL ?>/" class="toolbar">
        <input type="hidden" name="mod" value="cirurgias">
        <input type="hidden" name="action" value="lista">

        <div class="campo-icone w-full sm:w-80">
            <?= Icon::svg('buscar') ?>
            <input type="search" name="q" value="<?= e($busca) ?>"
                   class="input" placeholder="Protocolo, médico, código ou termo TUSS…" aria-label="Buscar cirurgias">
        </div>

        <select name="esp" class="select w-full sm:w-56" aria-label="Filtrar por especialidade">
            <option value="">Todas as especialidades</option>
            <?php foreach ($especialidades as $es): ?>
                <option value="<?= (int) $es['id'] ?>" <?= $espSelecionada === (int) $es['id'] ? 'selected' : '' ?>>
                    <?= e($es['nome']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button type="submit" class="btn btn-outline">Filtrar</button>

        <?php if ($filtrando): ?>
            <a href="<?= url('cirurgias', 'lista') ?>" class="btn btn-ghost">Limpar</a>
        <?php endif; ?>

        <span class="ml-auto text-xs text-slate-500 dark:text-slate-400">
            <?= count($cirurgias) ?> registro<?= count($cirurgias) === 1 ? '' : 's' ?>
        </span>
    </form>

    <?php if (empty($cirurgias)): ?>
        <div class="vazio">
            <span class="vazio-icone"><?= Icon::svg('cirurgias', 'w-5 h-5') ?></span>
            <p class="vazio-titulo"><?= $filtrando ? 'Nenhuma cirurgia encontrada' : 'Nenhuma cirurgia registrada' ?></p>
            <p class="vazio-texto">
                <?= $filtrando
                    ? 'Ajuste os filtros e tente novamente.'
                    : 'Registre o primeiro procedimento cirúrgico com o código TUSS correspondente.' ?>
            </p>
            <?php if ($podeCriar && !$filtrando): ?>
                <a href="<?= url('cirurgias', 'novo') ?>" class="btn btn-primary btn-sm mt-1">
                    <?= Icon::svg('novo', 'w-3.5 h-3.5') ?> Nova cirurgia
                </a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="tabela">
                <thead>
                    <tr>
                        <th class="w-36">Protocolo</th>
                        <th>Procedimento TUSS</th>
                        <th class="w-48">Médico</th>
                        <th class="w-52">Especialidade</th>
                        <th class="w-20 text-center">Anexos</th>
                        <th class="w-28 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cirurgias as $c): ?>
                        <?php $qtd = (int) $c['total_anexos']; ?>
                        <tr>
                            <td>
                                <a href="<?= url('cirurgias', 'detalhe', ['id' => (int) $c['id']]) ?>"
                                   class="mono font-semibold text-marine-700 hover:underline dark:text-marine-300">
                                    <?= e($c['protocolo']) ?>
                                </a>
                            </td>
                            <td class="max-w-md">
                                <span class="celula-principal block truncate" title="<?= e($c['tuss_termo']) ?>">
                                    <?= e($c['tuss_termo']) ?>
                                </span>
                                <span class="mono celula-suave"><?= e($c['tuss_codigo']) ?></span>
                            </td>
                            <td class="celula-suave"><?= e($c['medico']) ?></td>
                            <td>
                                <span class="badge badge-contorno"><?= e($c['especialidade_nome']) ?></span>
                            </td>
                            <td class="text-center celula-suave">
                                <?= $qtd > 0 ? $qtd : '—' ?>
                            </td>
                            <td>
                                <div class="flex items-center justify-end gap-0.5">
                                    <a href="<?= url('cirurgias', 'detalhe', ['id' => (int) $c['id']]) ?>"
                                       class="btn-acao" title="Ver detalhes" aria-label="Ver cirurgia <?= e($c['protocolo']) ?>">
                                        <?= Icon::svg('ver', 'w-4 h-4') ?>
                                    </a>

                                    <?php if ($podeEditar): ?>
                                        <a href="<?= url('cirurgias', 'editar', ['id' => (int) $c['id']]) ?>"
                                           class="btn-acao" title="Editar" aria-label="Editar cirurgia <?= e($c['protocolo']) ?>">
                                            <?= Icon::svg('editar', 'w-4 h-4') ?>
                                        </a>
                                    <?php endif; ?>

                                    <?php if ($podeExcluir): ?>
                                        <form method="post" action="<?= url('cirurgias', 'excluir') ?>" class="inline"
                                              data-titulo="Excluir cirurgia"
                                              data-texto="A cirurgia de protocolo <?= e($c['protocolo']) ?> e todos os seus anexos serão removidos permanentemente.">
                                            <?= Csrf::campo() ?>
                                            <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                                            <button type="button" @click="abrir" class="btn-acao btn-acao-perigo"
                                                    title="Excluir" aria-label="Excluir cirurgia <?= e($c['protocolo']) ?>">
                                                <?= Icon::svg('excluir', 'w-4 h-4') ?>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
