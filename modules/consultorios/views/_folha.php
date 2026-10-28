<?php
/**
 * Uma grade do mapa no papel.
 *
 * Sem cor: a impressora da casa é preto e branco, e o que identifica é o
 * nome escrito. As bordas vão em milímetro no CSS de impressão — 1px de
 * cinza claro some no papel.
 *
 * Espera: $turnoFolha, $salas, $dias, $grade
 */
declare(strict_types=1);

$doTurno = $grade[$turnoFolha] ?? [];
$longo   = dias_semana();
?>

<table class="folha-mapa folha-mapa-<?= e($turnoFolha) ?>">
    <thead>
        <tr>
            <th class="folha-mapa-canto">Sala</th>
            <?php foreach ($dias as $d): ?>
                <th><?= e($longo[$d] ?? '') ?></th>
            <?php endforeach; ?>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($salas as $s): ?>
            <tr>
                <th scope="row" class="folha-mapa-sala"><?= e($s['nome']) ?></th>

                <?php foreach ($dias as $d): ?>
                    <?php $itens = $doTurno[(int) $s['id']][$d] ?? []; ?>
                    <td>
                        <?php foreach ($itens as $o): ?>
                            <span class="folha-mapa-item folha-mapa-item-<?= e($turnoFolha) ?>">
                                <span class="folha-mapa-prof"><?= e($o['profissional']) ?></span>
                                <?php if (!empty($o['especialidade'])): ?>
                                    <span class="folha-mapa-esp"><?= e($o['especialidade']) ?></span>
                                <?php endif; ?>
                                <span class="folha-mapa-hora">
                                    <?= e(hora_curta($o['hora_inicio'])) ?>–<?= e(hora_curta($o['hora_fim'])) ?><?php
                                        if ($o['periodicidade'] === 'quinzenal'): ?> · 15/15<?php endif; ?>
                                </span>
                                <?php if (!empty($o['observacao'])): ?>
                                    <span class="folha-mapa-obs"><?= e($o['observacao']) ?></span>
                                <?php endif; ?>
                            </span>
                        <?php endforeach; ?>

                        <?php if (!$itens): ?>
                            <span class="folha-mapa-livre">Livre</span>
                        <?php endif; ?>
                    </td>
                <?php endforeach; ?>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
