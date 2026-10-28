<?php
/** @var array      $usuarios */
/** @var array      $modulos */
/** @var array      $permissoes */
/** @var int        $usuarioId */
/** @var array|null $usuarioAlvo */
declare(strict_types=1);

$podeEditar = Auth::temPermissao('permissoes', 'pode_editar');

$tipos = [
    'pode_ver'     => 'Ver',
    'pode_criar'   => 'Criar',
    'pode_editar'  => 'Editar',
    'pode_excluir' => 'Excluir',
];
?>

<div class="mb-5">
    <h1 class="titulo-pagina">Permissões de módulos</h1>
    <p class="sub-pagina">Defina o que cada usuário pode ver e fazer em cada módulo do sistema</p>
</div>

<form method="get" action="<?= BASE_URL ?>/" class="card mb-4 flex flex-wrap items-center gap-2 p-3">
    <input type="hidden" name="mod" value="permissoes">
    <input type="hidden" name="action" value="gerenciar">

    <label class="label mb-0 shrink-0" for="usuario_id">Usuário</label>
    <select name="usuario_id" id="usuario_id" class="select w-full sm:max-w-md">
        <option value="">Selecione um usuário…</option>
        <?php foreach ($usuarios as $u): ?>
            <option value="<?= (int) $u['id'] ?>" <?= $usuarioId === (int) $u['id'] ? 'selected' : '' ?>>
                <?= e($u['nome']) ?> · <?= e(formatar_cpf((string) $u['cpf'])) ?><?= !empty($u['is_admin']) ? ' · administrador' : '' ?>
            </option>
        <?php endforeach; ?>
    </select>

    <button type="submit" class="btn btn-outline">Carregar</button>
</form>

<?php if (!$usuarioAlvo): ?>
    <div class="card">
        <div class="vazio">
            <span class="vazio-icone"><?= Icon::svg('permissoes', 'w-5 h-5') ?></span>
            <p class="vazio-titulo">Selecione um usuário</p>
            <p class="vazio-texto">
                Escolha acima de quem você quer ajustar o acesso. Administradores têm
                acesso irrestrito e ignoram esta matriz.
            </p>
        </div>
    </div>
<?php else: ?>

    <?php if (!empty($usuarioAlvo['is_admin'])): ?>
        <div class="alerta alerta-info mb-4">
            <span class="mt-0.5 shrink-0"><?= Icon::svg('admin', 'w-4 h-4') ?></span>
            <p>
                <strong><?= e($usuarioAlvo['nome']) ?></strong> é administrador e tem acesso irrestrito.
                As permissões abaixo só passam a valer se o status de administrador for removido no cadastro do usuário.
            </p>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= url('permissoes', 'salvar') ?>" x-data="matrizPermissoes" @submit="enviar">
        <?= Csrf::campo() ?>
        <input type="hidden" name="usuario_id" value="<?= $usuarioId ?>">

        <div class="card overflow-hidden">
            <div class="card-header">
                <h2 class="card-title">Acesso de <?= e($usuarioAlvo['nome']) ?></h2>

                <?php if ($podeEditar): ?>
                    <div class="flex items-center gap-1">
                        <button type="button" class="btn btn-sm btn-outline" @click="marcarTudo(true)">Marcar tudo</button>
                        <button type="button" class="btn btn-sm btn-outline" @click="marcarTudo(false)">Limpar tudo</button>
                    </div>
                <?php endif; ?>
            </div>

            <!-- tabela-fixa: a coluna do módulo acompanha a rolagem horizontal -->
            <div class="overflow-x-auto">
                <table class="tabela tabela-fixa min-w-[46rem]">
                    <thead>
                        <tr>
                            <th class="min-w-[16rem]">Módulo</th>
                            <?php foreach ($tipos as $rotulo): ?>
                                <th class="w-24 text-center"><?= e($rotulo) ?></th>
                            <?php endforeach; ?>
                            <th class="w-28">Resumo</th>
                            <?php if ($podeEditar): ?><th class="w-32 text-right">Linha</th><?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($modulos as $m): ?>
                            <?php
                            $id   = (int) $m['id'];
                            $perm = $permissoes[$id] ?? [];
                            $info = Modules::get((string) $m['slug']);
                            ?>
                            <tr>
                                <td>
                                    <div class="flex items-center gap-2.5">
                                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-md bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400">
                                            <?= Icon::svg((string) ($info['icone'] ?? $m['slug']), 'w-4 h-4') ?>
                                        </span>
                                        <span class="min-w-0">
                                            <span class="celula-principal block"><?= e($m['nome']) ?></span>
                                            <span class="celula-suave block truncate text-xs"><?= e($info['desc'] ?? $m['slug']) ?></span>
                                        </span>
                                    </div>
                                </td>

                                <?php foreach ($tipos as $tipo => $rotulo): ?>
                                    <td class="text-center">
                                        <label class="check-alvo">
                                            <input type="checkbox"
                                                   name="permissoes[<?= $id ?>][<?= $tipo ?>]"
                                                   value="1"
                                                   data-tipo="<?= $tipo ?>"
                                                   class="check"
                                                   aria-label="<?= e($rotulo . ' em ' . $m['nome']) ?>"
                                                   <?= !empty($perm[$tipo]) ? 'checked' : '' ?>
                                                   <?= $podeEditar ? '' : 'disabled' ?>
                                                   <?= $tipo === 'pode_ver' ? '@change="aoMudar"' : '@change="garantirVer"' ?>>
                                        </label>
                                    </td>
                                <?php endforeach; ?>

                                <td>
                                    <span data-resumo data-vazio="1"
                                          class="text-xs font-medium text-slate-500 data-[vazio=1]:text-slate-400 dark:text-slate-300 dark:data-[vazio=1]:text-slate-500">
                                    </span>
                                </td>

                                <?php if ($podeEditar): ?>
                                    <td>
                                        <div class="flex items-center justify-end gap-1">
                                            <button type="button" data-acao="marcar" @click="marcarLinha"
                                                    class="btn btn-sm btn-outline" title="Marcar todas desta linha">Todas</button>
                                            <button type="button" data-acao="limpar" @click="marcarLinha"
                                                    class="btn btn-sm btn-outline" title="Desmarcar todas desta linha">Nenhuma</button>
                                        </div>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($podeEditar): ?>
                <div class="toolbar-rodape">
                    <p>Marcar Criar, Editar ou Excluir liga o Ver automaticamente.</p>
                    <button type="submit" class="btn btn-primary" :disabled="enviando">
                        <span x-show="!enviando"><?= Icon::svg('salvar', 'w-4 h-4') ?></span>
                        <span x-show="enviando" x-cloak class="girando"></span>
                        Salvar permissões
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </form>
<?php endif; ?>
