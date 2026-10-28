<?php
/**
 * Minha conta: os dados da própria pessoa e a troca de senha.
 *
 * @var array $usuario
 * @var bool  $provisoria
 * @var array $erros
 */
declare(strict_types=1);

$erroCls = fn(string $campo): string => isset($erros[$campo]) ? 'input-error' : '';
?>

<div class="mb-5">
    <h1 class="titulo-pagina">Minha conta</h1>
    <p class="sub-pagina">Seus dados e a senha de acesso ao sistema</p>
</div>

<?php if ($provisoria): ?>
    <div class="card mb-4 border-amber-300 dark:border-amber-500/40">
        <div class="card-corpo flex items-start gap-3 text-[0.8125rem]">
            <span class="mt-0.5 shrink-0 text-amber-600 dark:text-amber-400">
                <?= Icon::svg('aviso', 'w-4 h-4') ?>
            </span>
            <div>
                <p class="font-semibold text-amber-800 dark:text-amber-300">
                    Escolha uma senha sua para continuar
                </p>
                <p class="mt-0.5 text-slate-600 dark:text-slate-400">
                    A senha que você usou para entrar foi criada pelo administrador — ou
                    seja, outra pessoa a conhece. Enquanto ela não for trocada, o resto do
                    sistema fica fechado.
                </p>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="grid gap-4 lg:grid-cols-[20rem_minmax(0,1fr)] lg:items-start">

    <!-- Quem é você para o sistema -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Seus dados</h2>
        </div>
        <div class="card-corpo space-y-3.5 text-[0.8125rem]">
            <div>
                <p class="rotulo-num">Nome</p>
                <p class="celula-principal"><?= e($usuario['nome']) ?></p>
            </div>
            <div>
                <p class="rotulo-num">CPF</p>
                <p class="mono"><?= e(formatar_cpf((string) $usuario['cpf'])) ?></p>
            </div>
            <div>
                <p class="rotulo-num">Setor</p>
                <p><?= e(Auth::setor()) ?></p>
            </div>
            <div>
                <p class="rotulo-num">Perfil</p>
                <p><?= Auth::isAdmin() ? 'Administrador' : 'Usuário' ?></p>
            </div>
            <div>
                <p class="rotulo-num">Senha alterada em</p>
                <p>
                    <?= $usuario['senha_alterada_em']
                        ? e(datahora_br($usuario['senha_alterada_em']))
                        : '<span class="text-slate-400 dark:text-slate-500">nunca — ainda é a senha criada no cadastro</span>' ?>
                </p>
            </div>

            <p class="ajuda border-t border-slate-200 pt-3 dark:border-slate-800">
                Nome, CPF e setor são mantidos pelo administrador do sistema. Se algo
                aqui estiver errado, fale com ele.
            </p>
        </div>
    </div>

    <!-- Troca de senha -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Trocar a senha</h2>
            <span class="text-xs text-slate-500 dark:text-slate-400">
                mínimo 8 caracteres, com letra e número
            </span>
        </div>

        <form method="post" action="<?= url('usuarios', 'trocarSenha') ?>"
              class="card-corpo space-y-4" x-data="envio" @submit="enviar" novalidate>
            <?= Csrf::campo() ?>

            <div x-data="senha">
                <label class="label" for="senha_atual">
                    Senha atual <span class="obrigatorio">*</span>
                </label>
                <div class="relative">
                    <input :type="tipo" name="senha_atual" id="senha_atual" required autofocus
                           autocomplete="current-password"
                           class="input pr-10 <?= $erroCls('senha_atual') ?>"
                           placeholder="<?= $provisoria ? 'A senha que o administrador passou' : 'A senha que você usa hoje' ?>">
                    <button type="button" @click="alternar"
                            class="absolute right-1.5 top-1/2 -translate-y-1/2 rounded-md p-1.5 text-slate-400 transition-colors hover:text-slate-700 dark:hover:text-slate-200"
                            :aria-label="visivel ? 'Ocultar senha' : 'Mostrar senha'">
                        <?= Icon::svg('ver', 'w-[18px] h-[18px]') ?>
                    </button>
                </div>
                <?php if (isset($erros['senha_atual'])): ?>
                    <p class="erro-campo"><?= Icon::svg('erro', 'w-3.5 h-3.5') ?> <?= e($erros['senha_atual']) ?></p>
                <?php endif; ?>
            </div>

            <div class="border-t border-slate-200 pt-4 dark:border-slate-800">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div x-data="senha">
                        <label class="label" for="senha_nova">
                            Senha nova <span class="obrigatorio">*</span>
                        </label>
                        <div class="relative">
                            <input :type="tipo" name="senha_nova" id="senha_nova" required
                                   autocomplete="new-password"
                                   class="input pr-10 <?= $erroCls('senha_nova') ?>"
                                   placeholder="Mínimo 8 caracteres">
                            <button type="button" @click="alternar"
                                    class="absolute right-1.5 top-1/2 -translate-y-1/2 rounded-md p-1.5 text-slate-400 transition-colors hover:text-slate-700 dark:hover:text-slate-200"
                                    :aria-label="visivel ? 'Ocultar senha' : 'Mostrar senha'">
                                <?= Icon::svg('ver', 'w-[18px] h-[18px]') ?>
                            </button>
                        </div>
                        <?php if (isset($erros['senha_nova'])): ?>
                            <p class="erro-campo"><?= Icon::svg('erro', 'w-3.5 h-3.5') ?> <?= e($erros['senha_nova']) ?></p>
                        <?php endif; ?>
                    </div>

                    <div x-data="senha">
                        <label class="label" for="senha_confirmacao">
                            Repita a senha nova <span class="obrigatorio">*</span>
                        </label>
                        <div class="relative">
                            <input :type="tipo" name="senha_confirmacao" id="senha_confirmacao" required
                                   autocomplete="new-password"
                                   class="input pr-10 <?= $erroCls('senha_confirmacao') ?>"
                                   placeholder="A mesma de novo">
                            <button type="button" @click="alternar"
                                    class="absolute right-1.5 top-1/2 -translate-y-1/2 rounded-md p-1.5 text-slate-400 transition-colors hover:text-slate-700 dark:hover:text-slate-200"
                                    :aria-label="visivel ? 'Ocultar senha' : 'Mostrar senha'">
                                <?= Icon::svg('ver', 'w-[18px] h-[18px]') ?>
                            </button>
                        </div>
                        <?php if (isset($erros['senha_confirmacao'])): ?>
                            <p class="erro-campo"><?= Icon::svg('erro', 'w-3.5 h-3.5') ?> <?= e($erros['senha_confirmacao']) ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <p class="ajuda">
                    Uma senha boa é uma que só você sabe e que você lembra sem anotar.
                    Três palavras que façam sentido para você já valem mais que uma
                    palavra curta cheia de símbolos.
                </p>
            </div>

            <div class="flex flex-wrap items-center justify-end gap-2 border-t border-slate-200 pt-4 dark:border-slate-800">
                <?php if (!$provisoria): ?>
                    <a href="<?= url('usuarios', 'painel') ?>" class="btn btn-outline">Voltar ao painel</a>
                <?php endif; ?>
                <button type="submit" class="btn btn-primary" :disabled="enviando">
                    <span x-show="!enviando"><?= Icon::svg('salvar', 'w-4 h-4') ?></span>
                    <span x-show="enviando" x-cloak class="girando"></span>
                    Trocar a senha
                </button>
            </div>
        </form>
    </div>
</div>
