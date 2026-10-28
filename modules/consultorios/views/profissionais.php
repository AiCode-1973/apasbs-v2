<?php
/**
 * Cadastro de quem ocupa sala: médicos e atividades fixas.
 *
 * Lista à esquerda, formulário à direita — o mesmo desenho da tela de salas,
 * porque é a mesma natureza de trabalho: cadastro curto, consultado de vez
 * em quando.
 *
 * @var array  $profissionais
 * @var array  $especialidades
 * @var array  $profissional
 * @var array  $erros
 * @var string $busca
 */
declare(strict_types=1);

$podeCriar   = Auth::temPermissao('consultorios', 'pode_criar');
$podeEditar  = Auth::temPermissao('consultorios', 'pode_editar');
$podeExcluir = Auth::temPermissao('consultorios', 'pode_excluir');
$editando    = !empty($profissional['id']);
?>

<div class="mb-5 flex flex-wrap items-end justify-between gap-3">
    <div class="flex items-center gap-3">
        <a href="<?= url('consultorios', 'mapa') ?>" class="btn btn-icon btn-outline" aria-label="Voltar ao mapa">
            <?= Icon::svg('voltar', 'w-4 h-4') ?>
        </a>
        <div>
            <h1 class="titulo-pagina">Profissionais</h1>
            <p class="sub-pagina">Quem pode ser lançado no mapa de sala</p>
        </div>
    </div>

    <a href="<?= url('consultorios', 'especialidades') ?>" class="btn btn-outline">
        <?= Icon::svg('especialidades', 'w-4 h-4') ?> Especialidades
    </a>
</div>

<div class="grid gap-4 lg:grid-cols-[1.6fr_1fr]">

    <div class="card overflow-hidden">
        <form method="get" action="<?= BASE_URL ?>/" class="toolbar">
            <input type="hidden" name="mod" value="consultorios">
            <input type="hidden" name="action" value="profissionais">

            <div class="campo-icone w-full sm:w-72">
                <?= Icon::svg('buscar') ?>
                <input type="search" name="q" value="<?= e($busca) ?>" class="input"
                       placeholder="Buscar por nome ou especialidade…" aria-label="Buscar profissionais">
            </div>

            <button type="submit" class="btn btn-outline">Buscar</button>

            <?php if ($busca !== ''): ?>
                <a href="<?= url('consultorios', 'profissionais') ?>" class="btn btn-ghost">Limpar</a>
            <?php endif; ?>

            <span class="ml-auto text-xs text-slate-500 dark:text-slate-400">
                <?= count($profissionais) ?> cadastrado<?= count($profissionais) === 1 ? '' : 's' ?>
            </span>
        </form>

        <?php if (empty($profissionais)): ?>
            <div class="vazio">
                <span class="vazio-icone"><?= Icon::svg('usuarios', 'w-5 h-5') ?></span>
                <p class="vazio-titulo">
                    <?= $busca !== '' ? 'Ninguém encontrado' : 'Nenhum profissional cadastrado' ?>
                </p>
                <p class="vazio-texto">
                    <?= $busca !== ''
                        ? 'Nenhum resultado para “' . e($busca) . '”. Tente só o primeiro nome.'
                        : 'O mapa se monta a partir daqui: cadastre quem atende e depois lance os horários.' ?>
                </p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="tabela">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th class="w-44">Especialidade</th>
                            <th class="w-28 text-right">No mapa</th>
                            <th class="w-24">Situação</th>
                            <th class="w-24 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($profissionais as $p): ?>
                            <?php $qtd = (int) $p['qtd_ocupacoes']; ?>
                            <tr>
                                <td>
                                    <span class="celula-principal"><?= e($p['nome']) ?></span>
                                    <?php if (!empty($p['atividade'])): ?>
                                        <span class="badge badge-ceu ml-1.5">Atividade</span>
                                    <?php endif; ?>
                                    <?php if (!empty($p['observacao'])): ?>
                                        <span class="block text-xs text-slate-500 dark:text-slate-400">
                                            <?= e($p['observacao']) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td class="celula-suave">
                                    <?= $p['especialidade_nome'] ? e($p['especialidade_nome']) : '—' ?>
                                </td>

                                <td class="celula-suave text-right text-xs">
                                    <?= $qtd > 0 ? numero($qtd) . ' horário' . ($qtd === 1 ? '' : 's') : '—' ?>
                                </td>

                                <td>
                                    <span class="badge <?= $p['ativo'] ? 'badge-verde' : 'badge-cinza' ?>">
                                        <?= $p['ativo'] ? 'Ativo' : 'Inativo' ?>
                                    </span>
                                </td>

                                <td>
                                    <div class="flex items-center justify-end gap-0.5">
                                        <?php if ($podeEditar): ?>
                                            <a href="<?= url('consultorios', 'profissionais', ['editar' => (int) $p['id']]) ?>"
                                               class="btn-acao" title="Editar" aria-label="Editar <?= e($p['nome']) ?>">
                                                <?= Icon::svg('editar', 'w-4 h-4') ?>
                                            </a>
                                        <?php endif; ?>

                                        <?php if ($podeExcluir): ?>
                                            <form method="post" action="<?= url('consultorios', 'excluir_profissional') ?>" class="inline"
                                                  data-titulo="Excluir do cadastro"
                                                  data-texto="<?= e($p['nome']) ?> será removido<?= $qtd > 0
                                                      ? ', junto com os ' . $qtd . ' horários dele no mapa'
                                                      : '' ?>. Para só tirar do mapa sem perder o histórico, use Inativo.">
                                                <?= Csrf::campo() ?>
                                                <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                                                <button type="button" @click="abrir" class="btn-acao btn-acao-perigo"
                                                        title="Excluir" aria-label="Excluir <?= e($p['nome']) ?>">
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
                <h2 class="card-title"><?= $editando ? 'Editar profissional' : 'Novo profissional' ?></h2>
                <?php if ($editando): ?>
                    <a href="<?= url('consultorios', 'profissionais') ?>" class="btn btn-ghost btn-sm">Cancelar</a>
                <?php endif; ?>
            </div>

            <form method="post" action="<?= url('consultorios', 'salvar_profissional') ?>"
                  class="card-corpo space-y-4" novalidate x-data="envio" @submit="enviar">
                <?= Csrf::campo() ?>
                <?php if ($editando): ?>
                    <input type="hidden" name="id" value="<?= (int) $profissional['id'] ?>">
                <?php endif; ?>

                <div>
                    <label class="label" for="nome">Nome <span class="obrigatorio">*</span></label>
                    <input type="text" name="nome" id="nome" maxlength="120" required
                           class="input <?= isset($erros['nome']) ? 'input-error' : '' ?>"
                           value="<?= e($profissional['nome'] ?? '') ?>" placeholder="Ex.: Maria Fernanda">
                    <?php if (isset($erros['nome'])): ?>
                        <p class="erro-campo"><?= Icon::svg('erro', 'w-3.5 h-3.5') ?> <?= e($erros['nome']) ?></p>
                    <?php endif; ?>
                    <p class="ajuda">É este nome que aparece na célula do mapa. Curto é melhor.</p>
                </div>

                <div>
                    <label class="label" for="especialidade_id">Especialidade habitual</label>
                    <select name="especialidade_id" id="especialidade_id" class="select">
                        <option value="">Sem especialidade</option>
                        <?php foreach ($especialidades as $x): ?>
                            <option value="<?= (int) $x['id'] ?>"
                                <?= (int) ($profissional['especialidade_id'] ?? 0) === (int) $x['id'] ? 'selected' : '' ?>>
                                <?= e($x['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="ajuda">
                        Vem preenchida quando a pessoa é escolhida no mapa. Cada
                        ocupação pode ter outra, quando for o caso.
                    </p>
                </div>

                <label class="flex cursor-pointer items-start gap-3">
                    <input type="checkbox" name="atividade" class="check mt-0.5" <?= !empty($profissional['atividade']) ? 'checked' : '' ?>>
                    <span>
                        <span class="block text-[0.8125rem] font-medium text-slate-800 dark:text-slate-100">
                            É uma atividade, não uma pessoa
                        </span>
                        <span class="block text-xs text-slate-500 dark:text-slate-400">
                            Acupuntura, anestesista, prova ventilatória. Atividade pode
                            acontecer em duas salas ao mesmo tempo, então fica fora do
                            aviso de “mesma pessoa em dois lugares”.
                        </span>
                    </span>
                </label>

                <div>
                    <label class="label" for="observacao">Observação</label>
                    <input type="text" name="observacao" id="observacao" maxlength="200" class="input"
                           value="<?= e($profissional['observacao'] ?? '') ?>"
                           placeholder="Ex.: atende só encaixe">
                </div>

                <label class="flex cursor-pointer items-start gap-3">
                    <input type="checkbox" name="ativo" class="check mt-0.5" <?= ($profissional['ativo'] ?? 1) ? 'checked' : '' ?>>
                    <span>
                        <span class="block text-[0.8125rem] font-medium text-slate-800 dark:text-slate-100">Ativo</span>
                        <span class="block text-xs text-slate-500 dark:text-slate-400">
                            Inativo some do mapa e da lista de escolha, sem apagar nada.
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
