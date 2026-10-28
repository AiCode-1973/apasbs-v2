<?php
/**
 * Renderiza o corpo de um POP. Usado na tela e na folha impressa.
 * Espera: $blocos (e, opcionalmente, $paraImpressao = true)
 */
declare(strict_types=1);

$paraImpressao = $paraImpressao ?? false;
?>

<?php foreach ($blocos as $b): ?>
    <?php $tipo = (string) $b['tipo']; ?>

    <?php if ($tipo === 'passos'): ?>
        <section class="pop-secao">
            <?php if (!empty($b['titulo'])): ?>
                <h2 class="pop-titulo"><?= e($b['titulo']) ?></h2>
            <?php endif; ?>
            <ol class="pop-passos">
                <?php foreach (preg_split('/\R/u', (string) $b['conteudo']) as $linha): ?>
                    <?php if (trim($linha) === '') { continue; } ?>
                    <li><?= e(trim($linha)) ?></li>
                <?php endforeach; ?>
            </ol>
        </section>

    <?php elseif ($tipo === 'alerta'): ?>
        <section class="pop-alerta">
            <?php if (!empty($b['titulo'])): ?>
                <strong class="pop-alerta-titulo">
                    <?= Icon::svg('aviso', 'w-4 h-4 shrink-0') ?> <?= e($b['titulo']) ?>
                </strong>
            <?php endif; ?>
            <?php foreach (preg_split('/\R\R/u', (string) $b['conteudo']) as $par): ?>
                <?php if (trim($par) === '') { continue; } ?>
                <p><?= nl2br(e(trim($par))) ?></p>
            <?php endforeach; ?>
        </section>

    <?php elseif ($tipo === 'mensagem'): ?>
        <section class="pop-secao" <?= $paraImpressao ? '' : 'x-data="copiar"' ?>>
            <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
                <h2 class="pop-titulo mb-0 min-w-0"><?= e($b['titulo'] ?: 'Mensagem para a paciente') ?></h2>

                <?php if (!$paraImpressao): ?>
                    <?php /* O rótulo do botão não depende do Alpine: se o componente
                              falhar, o botão continua visível em vez de encolher para
                              um traço. A confirmação é que é condicional. */ ?>
                    <div class="flex items-center gap-2 nao-imprimir">
                        <button type="button" class="btn btn-outline btn-sm shrink-0"
                                @click="copiarTexto($refs.msg<?= (int) $b['id'] ?>.textContent)">
                            <?= Icon::svg('anexo', 'w-3.5 h-3.5') ?> Copiar mensagem
                        </button>
                        <span x-show="copiado" x-cloak
                              class="inline-flex items-center gap-1 text-xs font-medium text-emerald-600 dark:text-emerald-400">
                            <?= Icon::svg('check', 'w-3.5 h-3.5') ?> Copiado
                        </span>
                    </div>
                <?php endif; ?>
            </div>

            <pre class="pop-mensagem" <?= $paraImpressao ? '' : 'x-ref="msg' . (int) $b['id'] . '"' ?>><?= e((string) $b['conteudo']) ?></pre>

            <?php if (!$paraImpressao): ?>
                <p class="ajuda mt-1.5">
                    Substitua o que está entre colchetes antes de enviar.
                </p>
            <?php endif; ?>
        </section>

    <?php else: ?>
        <section class="pop-secao">
            <?php if (!empty($b['titulo'])): ?>
                <h2 class="pop-titulo"><?= e($b['titulo']) ?></h2>
            <?php endif; ?>
            <?php foreach (preg_split('/\R\R/u', (string) $b['conteudo']) as $par): ?>
                <?php if (trim($par) === '') { continue; } ?>
                <p class="pop-texto"><?= nl2br(e(trim($par))) ?></p>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>
<?php endforeach; ?>
