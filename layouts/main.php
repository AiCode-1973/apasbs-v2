<?php
/**
 * Casca da aplicação: sidebar, topbar, mensagens e conteúdo.
 * Recebe $content já renderizado pela View.
 */
declare(strict_types=1);

$modAtual  = (string) ($_GET['mod'] ?? 'usuarios');
$acaoAtual = (string) ($_GET['action'] ?? '');
$noPainel  = $modAtual === 'usuarios' && $acaoAtual === 'painel';
$info      = Modules::get($modAtual);
$flashes   = Flash::consumir();

/**
 * Trilha de navegação: Grupo › Módulo › Página atual.
 * Cada nível com destino vira link; o último é sempre texto.
 */
$trilha = [];

$naConta = $modAtual === 'usuarios' && in_array($acaoAtual, ['minhaConta', 'trocarSenha'], true);

if ($noPainel) {
    $trilha[] = ['rotulo' => 'Painel', 'url' => null];
    $tituloPag = 'Painel';
} elseif ($naConta) {
    // A própria conta não é o cadastro de usuários: sem ela a trilha diria
    // "Cadastros › Usuários" para quem talvez nem enxergue esse módulo.
    $trilha[] = ['rotulo' => 'Minha conta', 'url' => null];
    $tituloPag = 'Minha conta';
} elseif ($info) {
    $grupos = Modules::grupos();
    if (!empty($info['grupo']) && isset($grupos[$info['grupo']])) {
        $trilha[] = ['rotulo' => $grupos[$info['grupo']], 'url' => null];
    }

    $paginas = [
        'novo'          => 'Novo registro',
        'salvar'        => 'Novo registro',
        'editar'        => 'Edição',
        'atualizar'     => 'Edição',
        'detalhe'       => 'Detalhes',
        'excluir_anexo' => 'Detalhes',
    ];
    $subPagina = $paginas[$acaoAtual] ?? null;

    $trilha[] = [
        'rotulo' => $info['page_title'] ?? $info['nome'],
        'url'    => $subPagina ? url($modAtual, $info['action']) : null,
    ];

    if ($subPagina) {
        $trilha[] = ['rotulo' => $subPagina, 'url' => null];
    }

    $tituloPag = $info['page_title'] ?? $info['nome'];
} else {
    $trilha[] = ['rotulo' => 'Sistema', 'url' => null];
    $tituloPag = 'Sistema';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#061027">
    <title><?= e($tituloPag) ?> · APASBS</title>

    <link rel="icon" type="image/png" href="<?= BASE_URL ?>/image/favicon.png">
    <link rel="preload" as="font" type="font/woff2" crossorigin
          href="<?= BASE_URL ?>/assets/fonts/inter-latin-wght-normal.woff2">
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
    <script src="<?= asset('js/tema.js') ?>"></script>
</head>
<body x-data="appShell" class="min-h-screen">

<!-- ── Sidebar ────────────────────────────────────────────────────────── -->
<aside id="sidebar"
       class="fixed inset-y-0 left-0 z-50 flex w-60 flex-col bg-marine-950 lg:translate-x-0"
       :class="gaveta ? 'translate-x-0' : '-translate-x-full'">

    <!-- Sem logo por ora: só o nome do sistema. Para voltar a exibir a marca,
         troque este bloco pela imagem desejada (os arquivos seguem em image/). -->
    <a href="<?= url('usuarios', 'painel') ?>"
       class="sb-brand flex h-[3.75rem] shrink-0 items-center gap-2.5 border-b border-white/10 px-4"
       aria-label="APASBS — ir para o painel">
        <span class="sb-logo-marca h-9 w-9 items-center justify-center rounded-lg bg-marine-600 text-sm font-bold text-white">
            A
        </span>
        <span class="sb-logo-completo leading-tight">
            <span class="block text-sm font-bold text-white">APASBS</span>
            <span class="block text-[0.6rem] font-medium uppercase tracking-[0.1em] text-marine-300">Gestão em Saúde</span>
        </span>
    </a>

    <nav class="flex-1 overflow-y-auto px-2.5 py-3">
        <?php /* Com senha provisória tudo devolve para a troca. Mostrar o menu
                 seria convidar a pessoa a clicar em links que só a trazem de
                 volta — melhor a sidebar dizer a verdade sobre onde dá para ir. */ ?>
        <?php if (Auth::senhaProvisoria()): ?>
            <p class="nav-secao">Acesso</p>
            <p class="px-3 py-2 text-[0.6875rem] leading-relaxed text-marine-300/80">
                O sistema abre assim que você escolher uma senha sua.
            </p>
        <?php else: ?>
        <a href="<?= url('usuarios', 'painel') ?>"
           class="nav-link <?= $noPainel ? 'nav-link-ativo' : '' ?>" title="Painel">
            <?= Icon::svg('painel', 'w-[18px] h-[18px] shrink-0') ?>
            <span class="sb-label">Painel</span>
        </a>

        <?php foreach (Modules::grupos() as $grupo => $rotulo): ?>
            <?php
            $visiveis = array_filter(Modules::byGroup($grupo), fn($m) => Auth::temPermissao($m['slug']));
            if (!$visiveis) {
                continue;
            }
            ?>
            <p class="nav-secao"><?= e($rotulo) ?></p>
            <?php foreach ($visiveis as $m): ?>
                <a href="<?= url($m['slug'], $m['action']) ?>"
                   class="nav-link <?= $modAtual === $m['slug'] && !$noPainel ? 'nav-link-ativo' : '' ?>"
                   title="<?= e($m['nome']) ?>">
                    <?= Icon::svg($m['icone'], 'w-[18px] h-[18px] shrink-0') ?>
                    <span class="sb-label"><?= e($m['nome']) ?></span>
                </a>
            <?php endforeach; ?>
        <?php endforeach; ?>
        <?php endif; ?>
    </nav>

    <div class="border-t border-white/10 p-2.5">
        <?php /* O bloco de identificação leva para a própria conta: é onde
                 quem quer trocar a senha procura primeiro. */ ?>
        <a href="<?= url('usuarios', 'minhaConta') ?>"
           class="mb-1.5 flex items-center gap-2.5 rounded-lg px-2 py-1.5 transition-colors hover:bg-white/5"
           title="Minha conta">
            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-marine-700 text-[0.7rem] font-semibold text-marine-100">
                <?= e(Auth::iniciais()) ?>
            </span>
            <span class="sb-label min-w-0">
                <span class="block truncate text-xs font-medium text-slate-100"><?= e(Auth::nome()) ?></span>
                <span class="block truncate text-[0.65rem] text-marine-300/80">
                    <?= Auth::isAdmin() ? 'Administrador' : e(Auth::setor()) ?>
                </span>
            </span>
        </a>

        <a href="<?= url('usuarios', 'minhaConta') ?>"
           class="nav-link <?= $modAtual === 'usuarios' && ($_GET['action'] ?? '') === 'minhaConta' ? 'nav-link-ativo' : 'text-marine-300' ?>"
           title="Minha conta">
            <?= Icon::svg('senha', 'w-[18px] h-[18px] shrink-0') ?>
            <span class="sb-label">Minha conta</span>
        </a>

        <form method="post" action="<?= url('usuarios', 'logout') ?>">
            <?= Csrf::campo() ?>
            <button type="submit" class="nav-link w-full text-marine-300 hover:bg-rose-400/10 hover:text-rose-200" title="Sair do sistema">
                <?= Icon::svg('sair', 'w-[18px] h-[18px] shrink-0') ?>
                <span class="sb-label">Sair do sistema</span>
            </button>
        </form>
    </div>
</aside>

<!-- Fundo escuro da gaveta no celular -->
<div x-show="gaveta" x-cloak x-transition.opacity
     @click="fecharGaveta"
     class="fixed inset-0 z-40 bg-slate-950/60 backdrop-blur-sm lg:hidden"></div>

<!-- ── Área principal ─────────────────────────────────────────────────── -->
<div id="conteudo" class="flex min-h-screen flex-col">

    <header class="topbar sticky top-0 z-30 flex h-[3.75rem] items-center justify-between gap-4 border-b border-slate-200 bg-white/90 px-4 backdrop-blur lg:px-6 dark:border-slate-800 dark:bg-slate-900/90">
        <div class="flex min-w-0 items-center gap-2">
            <button type="button" @click="abrirGaveta"
                    class="btn btn-icon btn-ghost lg:hidden" aria-label="Abrir menu">
                <?= Icon::svg('menu', 'w-5 h-5') ?>
            </button>
            <button type="button" @click="alternarSidebar"
                    class="btn btn-icon btn-ghost -ml-1 hidden lg:inline-flex" aria-label="Recolher menu">
                <?= Icon::svg('menu', 'w-5 h-5') ?>
            </button>

            <nav class="flex min-w-0 items-center gap-1.5 text-[0.8125rem]" aria-label="Trilha de navegação">
                <?php foreach ($trilha as $i => $n): ?>
                    <?php if ($i > 0): ?>
                        <span class="shrink-0 text-slate-300 dark:text-slate-600" aria-hidden="true">/</span>
                    <?php endif; ?>

                    <?php if ($n['url']): ?>
                        <a href="<?= $n['url'] ?>" class="hidden shrink-0 text-slate-500 transition-colors hover:text-marine-700 sm:inline dark:text-slate-400 dark:hover:text-marine-300">
                            <?= e($n['rotulo']) ?>
                        </a>
                    <?php elseif ($i === count($trilha) - 1): ?>
                        <span class="truncate font-semibold text-marine-900 dark:text-white" aria-current="page">
                            <?= e($n['rotulo']) ?>
                        </span>
                    <?php else: ?>
                        <span class="hidden shrink-0 text-slate-500 sm:inline dark:text-slate-400"><?= e($n['rotulo']) ?></span>
                    <?php endif; ?>
                <?php endforeach; ?>
            </nav>
        </div>

        <button type="button" @click="alternarTema"
                class="btn btn-icon btn-ghost shrink-0" :aria-label="escuro ? 'Usar tema claro' : 'Usar tema escuro'">
            <span x-show="!escuro"><?= Icon::svg('escuro', 'w-[18px] h-[18px]') ?></span>
            <span x-show="escuro" x-cloak><?= Icon::svg('claro', 'w-[18px] h-[18px]') ?></span>
        </button>
    </header>

    <div x-data="confirmacao" class="flex flex-1 flex-col">
    <main class="flex-1 px-4 py-5 lg:px-6 lg:py-6">
        <?php if ($flashes): ?>
            <div class="mb-4 space-y-2 nao-imprimir">
                <?php foreach ($flashes as $f): ?>
                    <?php
                    $classe = match ($f['tipo']) {
                        'sucesso' => 'alerta-sucesso',
                        'erro'    => 'alerta-erro',
                        'aviso'   => 'alerta-aviso',
                        default   => 'alerta-info',
                    };
                    $icone = match ($f['tipo']) {
                        'sucesso' => 'sucesso',
                        'erro'    => 'erro',
                        'aviso'   => 'aviso',
                        default   => 'info',
                    };
                    ?>
                    <div x-data="alerta(<?= $f['tipo'] === 'sucesso' ? 6 : 0 ?>)" x-show="visivel" x-transition
                         class="alerta <?= $classe ?>" role="status">
                        <span class="mt-0.5 shrink-0"><?= Icon::svg($icone, 'w-4 h-4') ?></span>
                        <p class="flex-1"><?= e($f['texto']) ?></p>
                        <button type="button" @click="visivel = false" class="shrink-0 opacity-50 transition hover:opacity-100" aria-label="Fechar">
                            <?= Icon::svg('fechar', 'w-4 h-4') ?>
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?= $content ?>
    </main>

    <!-- Diálogo de confirmação. Serve para excluir, mas também para
         qualquer ação que mereça uma segunda olhada: o formulário diz o
         título, o texto, o nome do botão (data-acao) e o tom (data-tom). -->
    <div x-show="aberto" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         role="dialog" aria-modal="true" @keydown.escape.window="fechar">

        <div x-show="aberto" x-transition.opacity @click="fechar"
             class="absolute inset-0 bg-slate-950/60 backdrop-blur-sm"></div>

        <div x-show="aberto" x-transition
             class="card relative w-full max-w-md p-5 shadow-pop">
            <div class="flex items-start gap-3.5">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full"
                      :class="perigo
                          ? 'bg-rose-50 text-rose-600 dark:bg-rose-500/10 dark:text-rose-400'
                          : 'bg-marine-50 text-marine-700 dark:bg-marine-500/10 dark:text-marine-300'">
                    <?= Icon::svg('aviso', 'w-5 h-5') ?>
                </span>
                <div class="min-w-0">
                    <h2 class="text-[0.9375rem] font-semibold text-marine-900 dark:text-white" x-text="titulo"></h2>
                    <p class="mt-1 text-[0.8125rem] leading-relaxed text-slate-500 dark:text-slate-400" x-text="texto"></p>
                </div>
            </div>

            <div class="mt-5 flex justify-end gap-2">
                <button type="button" class="btn btn-outline" @click="fechar">Cancelar</button>
                <button type="button" class="btn" x-ref="confirmar" @click="confirmar"
                        :class="perigo ? 'btn-danger' : 'btn-primary'">
                    <span x-show="perigo"><?= Icon::svg('excluir', 'w-4 h-4') ?></span>
                    <span x-show="!perigo"><?= Icon::svg('check', 'w-4 h-4') ?></span>
                    <span x-text="acao"></span>
                </button>
            </div>
        </div>
    </div>
    </div>
</div>

<script src="<?= asset('js/app.js') ?>"></script>
<script src="<?= asset('js/alpine.min.js') ?>" defer></script>
</body>
</html>
