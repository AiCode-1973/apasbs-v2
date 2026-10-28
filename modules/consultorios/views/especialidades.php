<?php
/**
 * Cadastro das especialidades do ambulatório.
 *
 * São as etiquetas curtas que cabem na célula do mapa — "Cardio",
 * "Ortop. Joelho" —, separadas das especialidades do CFM usadas em
 * Cirurgias, que são os nomes oficiais por extenso.
 *
 * @var array $especialidades
 * @var array $especialidade
 * @var array $erros
 */
declare(strict_types=1);

$podeCriar   = Auth::temPermissao('consultorios', 'pode_criar');
$podeEditar  = Auth::temPermissao('consultorios', 'pode_editar');
$podeExcluir = Auth::temPermissao('consultorios', 'pode_excluir');
$editando    = !empty($especialidade['id']);
?>

<div class="mb-5 flex flex-wrap items-end justify-between gap-3">
    <div class="flex items-center gap-3">
        <a href="<?= url('consultorios', 'mapa') ?>" class="btn btn-icon btn-outline" aria-label="Voltar ao mapa">
            <?= Icon::svg('voltar', 'w-4 h-4') ?>
        </a>
        <div>
            <h1 class="titulo-pagina">Especialidades do ambulatório</h1>
            <p class="sub-pagina">As etiquetas que aparecem embaixo do nome, no mapa</p>
        </div>
    </div>

    <a href="<?= url('consultorios', 'profissionais') ?>" class="btn btn-outline">
        <?= Icon::svg('usuarios', 'w-4 h-4') ?> Profissionais
    </a>
</div>

<div class="grid gap-4 lg:grid-cols-[1.6fr_1fr]">

    <div class="card overflow-hidden">
        <div class="card-header">
            <h2 class="card-title">Cadastradas</h2>
            <span class="text-xs text-slate-500 dark:text-slate-400">
                <?= count($especialidades) ?> especialidade<?= count($especialidades) === 1 ? '' : 's' ?>
            </span>
        </div>

        <?php if (empty($especialidades)): ?>
            <div class="vazio">
                <span class="vazio-icone"><?= Icon::svg('especialidades', 'w-5 h-5') ?></span>
                <p class="vazio-titulo">Nenhuma especialidade cadastrada</p>
                <p class="vazio-texto">
                    Use a forma curta, do jeito que a casa fala: “Cardio”, “Gastro”,
                    “Ortop. Joelho”. É o que cabe na célula do mapa.
                </p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="tabela">
                    <thead>
                        <tr>
                            <th>Especialidade</th>
                            <th class="w-32 text-right">Profissionais</th>
                            <th class="w-28 text-right">No mapa</th>
                            <th class="w-24">Situação</th>
                            <th class="w-24 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($especialidades as $x): ?>
                            <?php
                            $profs = (int) $x['qtd_profissionais'];
                            $ocup  = (int) $x['qtd_ocupacoes'];
                            ?>
                            <tr>
                                <td>
                                    <span class="celula-principal"><?= e($x['nome']) ?></span>
                                    <?php if (!empty($x['observacao'])): ?>
                                        <span class="block text-xs text-slate-500 dark:text-slate-400">
                                            <?= e($x['observacao']) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td class="celula-suave text-right text-xs">
                                    <?= $profs > 0 ? numero($profs) : '—' ?>
                                </td>

                                <td class="celula-suave text-right text-xs">
                                    <?= $ocup > 0 ? numero($ocup) : '—' ?>
                                </td>

                                <td>
                                    <span class="badge <?= $x['ativo'] ? 'badge-verde' : 'badge-cinza' ?>">
                                        <?= $x['ativo'] ? 'Ativa' : 'Inativa' ?>
                                    </span>
                                </td>

                                <td>
                                    <div class="flex items-center justify-end gap-0.5">
                                        <?php if ($podeEditar): ?>
                                            <a href="<?= url('consultorios', 'especialidades', ['editar' => (int) $x['id']]) ?>"
                                               class="btn-acao" title="Editar" aria-label="Editar <?= e($x['nome']) ?>">
                                                <?= Icon::svg('editar', 'w-4 h-4') ?>
                                            </a>
                                        <?php endif; ?>

                                        <?php if ($podeExcluir): ?>
                                            <form method="post" action="<?= url('consultorios', 'excluir_especialidade') ?>" class="inline"
                                                  data-titulo="Excluir especialidade"
                                                  data-texto="<?= e($x['nome']) ?> será removida do cadastro.<?= $profs > 0 || $ocup > 0
                                                      ? ' Quem estava nela fica sem especialidade, mas continua no mapa.'
                                                      : '' ?>">
                                                <?= Csrf::campo() ?>
                                                <input type="hidden" name="id" value="<?= (int) $x['id'] ?>">
                                                <button type="button" @click="abrir" class="btn-acao btn-acao-perigo"
                                                        title="Excluir" aria-label="Excluir <?= e($x['nome']) ?>">
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

    <?php if ($podeCriar || $podeEditar): ?>
        <div class="card self-start">
            <div class="card-header">
                <h2 class="card-title"><?= $editando ? 'Editar especialidade' : 'Nova especialidade' ?></h2>
                <?php if ($editando): ?>
                    <a href="<?= url('consultorios', 'especialidades') ?>" class="btn btn-ghost btn-sm">Cancelar</a>
                <?php endif; ?>
            </div>

            <form method="post" action="<?= url('consultorios', 'salvar_especialidade') ?>"
                  class="card-corpo space-y-4" novalidate x-data="envio" @submit="enviar">
                <?= Csrf::campo() ?>
                <?php if ($editando): ?>
                    <input type="hidden" name="id" value="<?= (int) $especialidade['id'] ?>">
                <?php endif; ?>

                <div>
                    <label class="label" for="nome">Nome <span class="obrigatorio">*</span></label>
                    <input type="text" name="nome" id="nome" maxlength="80" required
                           class="input <?= isset($erros['nome']) ? 'input-error' : '' ?>"
                           value="<?= e($especialidade['nome'] ?? '') ?>" placeholder="Ex.: Cardio">
                    <?php if (isset($erros['nome'])): ?>
                        <p class="erro-campo"><?= Icon::svg('erro', 'w-3.5 h-3.5') ?> <?= e($erros['nome']) ?></p>
                    <?php endif; ?>
                    <p class="ajuda">
                        Forma curta. Trocar o nome aqui muda em todas as células do
                        mapa de uma vez.
                    </p>
                </div>

                <div>
                    <label class="label" for="observacao">Observação</label>
                    <input type="text" name="observacao" id="observacao" maxlength="200" class="input"
                           value="<?= e($especialidade['observacao'] ?? '') ?>"
                           placeholder="Ex.: exige sala com maca">
                </div>

                <label class="flex cursor-pointer items-start gap-3">
                    <input type="checkbox" name="ativo" class="check mt-0.5" <?= ($especialidade['ativo'] ?? 1) ? 'checked' : '' ?>>
                    <span>
                        <span class="block text-[0.8125rem] font-medium text-slate-800 dark:text-slate-100">Ativa</span>
                        <span class="block text-xs text-slate-500 dark:text-slate-400">
                            Inativa some da lista de escolha. Quem já está com ela no
                            mapa continua aparecendo.
                        </span>
                    </span>
                </label>

                <div class="flex justify-end">
                    <button type="submit" class="btn btn-primary" :disabled="enviando">
                        <span x-show="!enviando"><?= Icon::svg('salvar', 'w-4 h-4') ?></span>
                        <span x-show="enviando" x-cloak class="girando"></span>
                        <?= $editando ? 'Salvar' : 'Cadastrar' ?>
                    </button>
                </div>
            </form>
        </div>
    <?php endif; ?>
</div>
