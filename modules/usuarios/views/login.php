<?php
/** @var string $erro */
/** @var string $cpf */
declare(strict_types=1);
?>
<div class="mb-7 text-center">
    <img src="<?= BASE_URL ?>/image/logo_apas.png" alt="APAS Baixada Santista"
         class="mx-auto mb-7 h-12 w-auto object-contain">
    <h1 class="text-xl font-semibold tracking-tight text-marine-900 dark:text-white">Acessar o sistema</h1>
    <p class="mt-1 text-[0.8125rem] text-slate-500 dark:text-slate-400">Informe suas credenciais para continuar</p>
</div>

<?php if ($erro !== ''): ?>
    <div class="alerta alerta-erro mb-4" role="alert">
        <span class="mt-0.5 shrink-0"><?= Icon::svg('erro', 'w-4 h-4') ?></span>
        <p><?= e($erro) ?></p>
    </div>
<?php endif; ?>

<form method="post" action="<?= url('usuarios', 'login') ?>" class="space-y-3.5" novalidate>
    <?= Csrf::campo() ?>

    <div x-data="cpf">
        <label class="label" for="cpf">CPF</label>
        <div class="campo-icone">
            <?= Icon::svg('cpf') ?>
            <input type="text" name="cpf" id="cpf" inputmode="numeric" autocomplete="username"
                   class="input" placeholder="000.000.000-00" maxlength="14"
                   value="<?= e(formatar_cpf($cpf)) ?>"
                   @input="formatar" autofocus required>
        </div>
    </div>

    <div x-data="senha">
        <label class="label" for="senha">Senha</label>
        <div class="campo-icone">
            <?= Icon::svg('senha') ?>
            <input :type="tipo" name="senha" id="senha" autocomplete="current-password"
                   class="input pr-10" placeholder="Sua senha" required>
            <button type="button" @click="alternar"
                    class="absolute right-1.5 top-1/2 -translate-y-1/2 rounded-md p-1.5 text-slate-400 transition-colors hover:text-slate-700 dark:hover:text-slate-200"
                    :aria-label="visivel ? 'Ocultar senha' : 'Mostrar senha'">
                <?= Icon::svg('ver', 'w-[18px] h-[18px]') ?>
            </button>
        </div>
    </div>

    <button type="submit" class="btn btn-primary w-full py-2">
        Entrar
        <?= Icon::svg('proximo', 'w-4 h-4') ?>
    </button>
</form>

<p class="mt-8 text-center text-xs text-slate-400 dark:text-slate-500">
    Problemas para entrar? Procure o administrador do sistema.
</p>

<p class="mt-4 border-t border-slate-100 pt-4 text-center text-[0.6875rem] text-slate-400
          dark:border-slate-800 dark:text-slate-500">
    Desenvolvido por
    <a href="https://aicode.dev.br" target="_blank" rel="noopener noreferrer"
       class="font-medium text-slate-500 underline decoration-slate-300 underline-offset-2
              transition-colors hover:text-marine-600
              dark:text-slate-400 dark:decoration-slate-700 dark:hover:text-marine-300">
        Demetrius Figueiredo - AiCode
    </a>
</p>
