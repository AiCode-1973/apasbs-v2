<?php
/** @var array|null $usuario */
/** @var array $setores */
/** @var array $erros */
declare(strict_types=1);

$editando  = $usuario && !empty($usuario['id']);
$titulo    = $editando ? 'Editar usuário' : 'Novo usuário';
$actionUrl = url('usuarios', $editando ? 'atualizar' : 'salvar');

$erroCls = fn(string $campo): string => isset($erros[$campo]) ? 'input-error' : '';
?>

<div class="mb-5 flex items-center gap-3">
    <a href="<?= url_lista('usuarios') ?>" class="btn btn-icon btn-outline" aria-label="Voltar">
        <?= Icon::svg('voltar', 'w-4 h-4') ?>
    </a>
    <div>
        <h1 class="titulo-pagina"><?= e($titulo) ?></h1>
        <p class="sub-pagina">
            <?= $editando ? 'Atualize os dados do profissional' : 'Preencha os dados do novo profissional' ?>
        </p>
    </div>
</div>

<form method="post" action="<?= $actionUrl ?>" class="max-w-4xl" novalidate
      x-data="envio" @submit="enviar">
    <?= Csrf::campo() ?>
    <?php if ($editando): ?>
        <input type="hidden" name="id" value="<?= (int) $usuario['id'] ?>">
    <?php endif; ?>

    <div class="card p-5 lg:p-6">

        <!-- Identificação -->
        <div class="form-secao">
            <div>
                <h2 class="form-secao-titulo">Identificação</h2>
                <p class="form-secao-desc">
                    O CPF é a credencial de acesso e não pode se repetir entre usuários.
                </p>
            </div>

            <div class="form-campos">
                <div class="form-largo">
                    <label class="label" for="nome">Nome completo <span class="obrigatorio">*</span></label>
                    <input type="text" name="nome" id="nome" maxlength="150" required autofocus
                           class="input <?= $erroCls('nome') ?>"
                           value="<?= e($usuario['nome'] ?? '') ?>" placeholder="Ex.: Maria Alves dos Santos">
                    <?php if (isset($erros['nome'])): ?>
                        <p class="erro-campo"><?= Icon::svg('erro', 'w-3.5 h-3.5') ?> <?= e($erros['nome']) ?></p>
                    <?php endif; ?>
                </div>

                <div x-data="cpf">
                    <label class="label" for="cpf">CPF <span class="obrigatorio">*</span></label>
                    <input type="text" name="cpf" id="cpf" inputmode="numeric" maxlength="14" required
                           class="input <?= $erroCls('cpf') ?>" @input="formatar"
                           value="<?= e(formatar_cpf((string) ($usuario['cpf'] ?? ''))) ?>" placeholder="000.000.000-00">
                    <?php if (isset($erros['cpf'])): ?>
                        <p class="erro-campo"><?= Icon::svg('erro', 'w-3.5 h-3.5') ?> <?= e($erros['cpf']) ?></p>
                    <?php endif; ?>
                </div>

                <div>
                    <label class="label" for="setor_id">Setor <span class="obrigatorio">*</span></label>
                    <select name="setor_id" id="setor_id" required class="select <?= $erroCls('setor_id') ?>">
                        <option value="">Selecione…</option>
                        <?php foreach ($setores as $s): ?>
                            <option value="<?= (int) $s['id'] ?>"
                                <?= (int) ($usuario['setor_id'] ?? 0) === (int) $s['id'] ? 'selected' : '' ?>>
                                <?= e($s['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($erros['setor_id'])): ?>
                        <p class="erro-campo"><?= Icon::svg('erro', 'w-3.5 h-3.5') ?> <?= e($erros['setor_id']) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Senha -->
        <div class="form-secao">
            <div>
                <h2 class="form-secao-titulo">Senha</h2>
                <p class="form-secao-desc">
                    <?= $editando
                        ? 'Preencha apenas se quiser trocar a senha atual. Em branco, ela permanece como está.'
                        : 'Mínimo de 8 caracteres, contendo ao menos uma letra e um número.' ?>
                </p>
            </div>

            <div class="form-campos">
                <div class="form-largo" x-data="senha">
                    <label class="label" for="senha">
                        Senha <?= $editando ? '' : '<span class="obrigatorio">*</span>' ?>
                    </label>
                    <div class="relative">
                        <input :type="tipo" name="senha" id="senha" autocomplete="new-password"
                               class="input pr-10 <?= $erroCls('senha') ?>"
                               placeholder="<?= $editando ? 'Deixe em branco para manter a atual' : 'Mínimo 8 caracteres, com letra e número' ?>">
                        <button type="button" @click="alternar"
                                class="absolute right-1.5 top-1/2 -translate-y-1/2 rounded-md p-1.5 text-slate-400 transition-colors hover:text-slate-700 dark:hover:text-slate-200"
                                :aria-label="visivel ? 'Ocultar senha' : 'Mostrar senha'">
                            <?= Icon::svg('ver', 'w-[18px] h-[18px]') ?>
                        </button>
                    </div>
                    <?php if (isset($erros['senha'])): ?>
                        <p class="erro-campo"><?= Icon::svg('erro', 'w-3.5 h-3.5') ?> <?= e($erros['senha']) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Situação e acesso -->
        <div class="form-secao">
            <div>
                <h2 class="form-secao-titulo">Situação e acesso</h2>
                <p class="form-secao-desc">
                    Controla se o usuário consegue entrar e qual o alcance do que ele enxerga.
                </p>
            </div>

            <div class="space-y-3">
                <label class="flex cursor-pointer items-start gap-3">
                    <input type="checkbox" name="ativo" class="check mt-0.5"
                        <?= ($usuario['ativo'] ?? 1) ? 'checked' : '' ?>>
                    <span>
                        <span class="block text-[0.8125rem] font-medium text-slate-800 dark:text-slate-100">Usuário ativo</span>
                        <span class="block text-xs text-slate-500 dark:text-slate-400">Usuários inativos não conseguem entrar no sistema.</span>
                    </span>
                </label>

                <?php if (Auth::isAdmin()): ?>
                    <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-marine-200 bg-marine-50/60 p-3 dark:border-marine-900/60 dark:bg-marine-950/30">
                        <input type="checkbox" name="is_admin" class="check mt-0.5"
                            <?= !empty($usuario['is_admin']) ? 'checked' : '' ?>>
                        <span>
                            <span class="flex items-center gap-1.5 text-[0.8125rem] font-medium text-marine-900 dark:text-marine-200">
                                <?= Icon::svg('admin', 'w-4 h-4') ?> Administrador
                            </span>
                            <span class="block text-xs text-marine-700/80 dark:text-marine-300/80">
                                Acesso irrestrito a todos os módulos, ignorando a matriz de permissões.
                                Conceda apenas a quem realmente precisa.
                            </span>
                        </span>
                    </label>
                    <?php if (isset($erros['is_admin'])): ?>
                        <p class="erro-campo"><?= Icon::svg('erro', 'w-3.5 h-3.5') ?> <?= e($erros['is_admin']) ?></p>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="form-acoes">
        <a href="<?= url_lista('usuarios') ?>" class="btn btn-outline">Cancelar</a>
        <button type="submit" class="btn btn-primary" :disabled="enviando">
            <span x-show="!enviando"><?= Icon::svg('salvar', 'w-4 h-4') ?></span>
            <span x-show="enviando" x-cloak class="girando"></span>
            <?= $editando ? 'Salvar alterações' : 'Cadastrar usuário' ?>
        </button>
    </div>
</form>
