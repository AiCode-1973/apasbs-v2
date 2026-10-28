<?php
/** @var array  $medicos */
/** @var string $busca */
declare(strict_types=1);

$podeCriar   = Auth::temPermissao('medicos', 'pode_criar');
$podeEditar  = Auth::temPermissao('medicos', 'pode_editar');
$podeExcluir = Auth::temPermissao('medicos', 'pode_excluir');
?>

<div class="mb-5 flex flex-wrap items-end justify-between gap-3">
    <div>
        <h1 class="titulo-pagina">Médicos do Ambulatório</h1>
        <p class="sub-pagina">Quem atende no ambulatório — cada um com sua cor fixa na agenda</p>
    </div>

    <?php if ($podeCriar): ?>
        <a href="<?= url('medicos', 'novo') ?>" class="btn btn-primary">
            <?= Icon::svg('novo', 'w-4 h-4') ?> Novo médico
        </a>
    <?php endif; ?>
</div>

<div class="card overflow-hidden">
    <form method="get" action="<?= BASE_URL ?>/" class="toolbar">
        <input type="hidden" name="mod" value="medicos">
        <input type="hidden" name="action" value="lista">

        <div class="campo-icone w-full sm:w-80">
            <?= Icon::svg('buscar') ?>
            <input type="search" name="q" value="<?= e($busca) ?>"
                   class="input" placeholder="Buscar por nome, CRM ou especialidade…" aria-label="Buscar médicos">
        </div>

        <button type="submit" class="btn btn-outline">Buscar</button>

        <?php if ($busca !== ''): ?>
            <a href="<?= url('medicos', 'lista') ?>" class="btn btn-ghost">Limpar</a>
        <?php endif; ?>

        <span class="ml-auto text-xs text-slate-500 dark:text-slate-400">
            <?= count($medicos) ?> registro<?= count($medicos) === 1 ? '' : 's' ?>
        </span>
    </form>

    <?php if (empty($medicos)): ?>
        <div class="vazio">
            <span class="vazio-icone"><?= Icon::svg('medicos', 'w-5 h-5') ?></span>
            <p class="vazio-titulo">
                <?= $busca !== '' ? 'Nenhum médico encontrado' : 'Nenhum médico cadastrado' ?>
            </p>
            <p class="vazio-texto">
                <?= $busca !== ''
                    ? 'Nenhum resultado para “' . e($busca) . '”. Tente outro termo.'
                    : 'Cadastre os médicos do ambulatório antes de montar a escala do mês — a agenda usa esta lista.' ?>
            </p>
            <?php if ($podeCriar && $busca === ''): ?>
                <a href="<?= url('medicos', 'novo') ?>" class="btn btn-primary btn-sm mt-1">
                    <?= Icon::svg('novo', 'w-3.5 h-3.5') ?> Novo médico
                </a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="tabela">
                <thead>
                    <tr>
                        <th>Médico</th>
                        <th class="w-32">CRM</th>
                        <th class="w-56">Especialidade</th>
                        <th class="w-36">Na agenda</th>
                        <th class="w-28">Situação</th>
                        <th class="w-24 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($medicos as $m): ?>
                        <?php $blocos = (int) $m['qtd_blocos']; ?>
                        <tr>
                            <td>
                                <span class="flex items-center gap-2 medico-<?= (int) $m['cor'] ?>">
                                    <span class="med-ponto" aria-hidden="true"></span>
                                    <span class="celula-principal"><?= e($m['nome']) ?></span>
                                </span>
                            </td>

                            <td class="celula-suave mono text-xs">
                                <?= $m['crm'] !== null && $m['crm'] !== '' ? e($m['crm']) : '—' ?>
                            </td>

                            <td class="celula-suave">
                                <?= $m['especialidade'] !== null && $m['especialidade'] !== '' ? e($m['especialidade']) : '—' ?>
                            </td>

                            <td class="celula-suave text-xs">
                                <?= $blocos > 0
                                    ? numero($blocos) . ' bloco' . ($blocos === 1 ? '' : 's')
                                    : 'Sem lançamento' ?>
                            </td>

                            <td>
                                <span class="badge <?= $m['ativo'] ? 'badge-verde' : 'badge-cinza' ?>">
                                    <?= $m['ativo'] ? 'Ativo' : 'Inativo' ?>
                                </span>
                            </td>

                            <td>
                                <div class="flex items-center justify-end gap-0.5">
                                    <?php if ($podeEditar): ?>
                                        <a href="<?= url('medicos', 'editar', ['id' => (int) $m['id']]) ?>"
                                           class="btn-acao" title="Editar"
                                           aria-label="Editar <?= e($m['nome']) ?>">
                                            <?= Icon::svg('editar', 'w-4 h-4') ?>
                                        </a>
                                    <?php endif; ?>

                                    <?php if ($podeExcluir): ?>
                                        <?php if ($blocos > 0): ?>
                                            <span class="btn-acao-off"
                                                  title="Não pode excluir: já tem blocos lançados na agenda">
                                                <?= Icon::svg('excluir', 'w-4 h-4') ?>
                                            </span>
                                        <?php else: ?>
                                            <form method="post" action="<?= url('medicos', 'excluir') ?>" class="inline"
                                                  data-titulo="Excluir médico"
                                                  data-texto="<?= e($m['nome']) ?> será removido permanentemente do cadastro da central.">
                                                <?= Csrf::campo() ?>
                                                <input type="hidden" name="id" value="<?= (int) $m['id'] ?>">
                                                <button type="button" @click="abrir" class="btn-acao btn-acao-perigo"
                                                        title="Excluir" aria-label="Excluir <?= e($m['nome']) ?>">
                                                    <?= Icon::svg('excluir', 'w-4 h-4') ?>
                                                </button>
                                            </form>
                                        <?php endif; ?>
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
