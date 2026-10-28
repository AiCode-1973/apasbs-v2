<?php
/**
 * Casca mínima para folhas de impressão: sem sidebar, sem topbar, sem menu.
 * A tela mostra os mesmos elementos que o papel — o que estiver marcado
 * com .nao-imprimir some na hora de imprimir.
 *
 * @var string $content
 */
declare(strict_types=1);

$tituloPagina    = $tituloPagina ?? 'Impressão';
$paginaPaisagem  = $paginaPaisagem ?? false;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e($tituloPagina) ?> · APASBS</title>

    <link rel="icon" type="image/png" href="<?= BASE_URL ?>/image/favicon.png">
    <link rel="preload" as="font" type="font/woff2" crossorigin
          href="<?= BASE_URL ?>/assets/fonts/inter-latin-wght-normal.woff2">
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
    <script src="<?= asset('js/imprimir.js') ?>" defer></script>

    <?php if ($paginaPaisagem): ?>
        <?php /* @page não aceita seletor de classe, então a regra vem daqui,
                 só nas folhas que realmente precisam de paisagem. */ ?>
        <style>@page { size: A4 landscape; margin: 8mm; }</style>
    <?php endif; ?>
</head>
<body class="bg-slate-100 print:bg-white">

<main class="mx-auto <?= $paginaPaisagem ? 'max-w-7xl' : 'max-w-5xl' ?> p-4 sm:p-8 print:max-w-none print:p-0">
    <?= $content ?>
</main>

</body>
</html>
