<?php
/**
 * Casca das telas públicas (login). Sem sidebar e sem topbar.
 */
declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#061027">
    <title>Acesso · APASBS</title>

    <link rel="icon" type="image/png" href="<?= BASE_URL ?>/image/favicon.png">
    <link rel="preload" as="font" type="font/woff2" crossorigin
          href="<?= BASE_URL ?>/assets/fonts/inter-latin-wght-normal.woff2">
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
    <script src="<?= asset('js/tema.js') ?>"></script>
</head>
<body class="min-h-screen bg-white dark:bg-slate-950">

<div class="grid min-h-screen lg:grid-cols-[1.1fr_1fr]">

    <!-- Painel institucional: o hospital aqui, a APAS do lado do formulário -->
    <section class="relative hidden flex-col justify-between overflow-hidden bg-marine-950 p-10 lg:flex">
        <div class="pointer-events-none absolute -right-28 -top-28 h-[26rem] w-[26rem] rounded-full bg-marine-600/20 blur-3xl"></div>
        <div class="pointer-events-none absolute -bottom-32 -left-24 h-[26rem] w-[26rem] rounded-full bg-apas-500/10 blur-3xl"></div>

        <!-- Desenhado em tons claros, o logo do hospital vive bem sobre o navy -->
        <?php /* Sem srcset: o @2x apontava para um arquivo que nunca existiu, e
                 cada tela de login gerava um 404 no log do servidor. */ ?>
        <img src="<?= BASE_URL ?>/image/logo_hospital.png"
             alt="Hospital Santo Expedito"
             class="relative -ml-2 w-44 object-contain object-left">

        <div class="relative max-w-md">
            <h1 class="text-[1.75rem] font-semibold leading-tight tracking-tight text-white">
                Gestão em saúde,<br>do agendamento ao centro cirúrgico.
            </h1>
            <p class="mt-3.5 text-[0.875rem] leading-relaxed text-marine-200">
                Agenda do ambulatório, marcação de exames, registro de cirurgias
                com anexos e consulta à Tabela TUSS — em um só lugar, com controle
                de acesso por módulo.
            </p>

            <?php /* Três itens que quem trabalha aqui reconhece. Nada de recurso
                     que a pessoa nunca vai abrir. */ ?>
            <ul class="mt-8 space-y-2.5 text-[0.8125rem] text-marine-100">
                <li class="flex items-center gap-3">
                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-white/10 text-apas-500"><?= Icon::svg('agenda', 'w-4 h-4') ?></span>
                    Agenda de exames com controle de vagas
                </li>
                <li class="flex items-center gap-3">
                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-white/10 text-apas-500"><?= Icon::svg('permissoes', 'w-4 h-4') ?></span>
                    Permissões por usuário e por módulo
                </li>
                <li class="flex items-center gap-3">
                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-white/10 text-apas-500"><?= Icon::svg('auditoria', 'w-4 h-4') ?></span>
                    Histórico de tudo que foi feito
                </li>
            </ul>
        </div>

        <p class="relative text-[0.7rem] text-marine-300/70">
            &copy; <?= date('Y') ?> APAS Baixada Santista &mdash; Todos os direitos reservados
        </p>
    </section>

    <!-- Formulário -->
    <section class="flex items-center justify-center px-6 py-12">
        <div class="w-full max-w-[22rem]">
            <?= $content ?>
        </div>
    </section>
</div>

<script src="<?= asset('js/app.js') ?>"></script>
<script src="<?= asset('js/alpine.min.js') ?>" defer></script>
</body>
</html>
