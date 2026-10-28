<?php
/**
 * Uma grade de turno: salas nas linhas, dias da semana nas colunas.
 *
 * É uma <table> e não um grid CSS porque a primeira coluna precisa ficar
 * fixa na rolagem horizontal — sem o nome da sala à vista, a linha do meio
 * do mapa não quer dizer nada.
 *
 * Espera: $turno, $salas, $dias, $grade, $emConflito, $podeCriar, $podeEditar
 */
declare(strict_types=1);

$doTurno = $grade[$turno] ?? [];
$curto   = dias_semana_curto();
$longo   = dias_semana();
?>

<div class="overflow-x-auto">
    <table class="mapa">
        <thead class="mapa-cabecalho-<?= e($turno) ?>">
            <tr>
                <th class="mapa-canto">Sala</th>
                <?php foreach ($dias as $d): ?>
                    <th>
                        <span class="hidden sm:inline"><?= e($longo[$d] ?? '') ?></span>
                        <span class="sm:hidden"><?= e($curto[$d] ?? '') ?></span>
                    </th>
                <?php endforeach; ?>
            </tr>
        </thead>

        <tbody>
            <?php foreach ($salas as $s): ?>
                <tr>
                    <th scope="row" class="mapa-sala">
                        <span class="mapa-sala-nome"><?= e($s['nome']) ?></span>
                        <?php if (!empty($s['observacao'])): ?>
                            <span class="mapa-sala-obs"><?= e($s['observacao']) ?></span>
                        <?php endif; ?>
                    </th>

                    <?php foreach ($dias as $d): ?>
                        <?php $itens = $doTurno[(int) $s['id']][$d] ?? []; ?>
                        <td>
                            <?php foreach ($itens as $o): ?>
                                <?php
                                $conflito = isset($emConflito[(int) $o['id']]);
                                $rotulo   = $o['profissional']
                                    . ($o['especialidade'] ? ' (' . $o['especialidade'] . ')' : '')
                                    . ' — ' . hora_curta($o['hora_inicio']) . ' às ' . hora_curta($o['hora_fim'])
                                    . ', ' . ($longo[$d] ?? '') . ', ' . $s['nome']
                                    . ($conflito ? ' — atenção: choque de horário' : '');
                                // A cor do turno vem primeiro; o choque, quando
                                // existe, sobrepõe — problema fala mais alto.
                                $classe = 'mapa-item mapa-item-' . $turno
                                    . ($conflito ? ' mapa-item-conflito' : '');
                                ?>

                                <?php if ($podeEditar): ?>
                                    <a href="<?= url('consultorios', 'editar', ['id' => (int) $o['id']]) ?>"
                                       class="<?= $classe ?>" title="<?= e($rotulo) ?>">
                                <?php else: ?>
                                    <span class="<?= $classe ?>" title="<?= e($rotulo) ?>">
                                <?php endif; ?>

                                    <span class="mapa-prof"><?= e($o['profissional']) ?></span>

                                    <?php if (!empty($o['especialidade'])): ?>
                                        <span class="mapa-esp"><?= e($o['especialidade']) ?></span>
                                    <?php endif; ?>

                                    <span class="mapa-hora">
                                        <?= e(hora_curta($o['hora_inicio'])) ?>–<?= e(hora_curta($o['hora_fim'])) ?>
                                        <?php if ($o['periodicidade'] === 'quinzenal'): ?>
                                            <span class="mapa-tag mapa-tag-<?= e($turno) ?>">15/15</span>
                                        <?php endif; ?>
                                    </span>

                                    <?php if (!empty($o['observacao'])): ?>
                                        <span class="mapa-obs"><?= e($o['observacao']) ?></span>
                                    <?php endif; ?>

                                    <?php if ($conflito): ?>
                                        <span class="mapa-aviso">
                                            <?= Icon::svg('aviso', 'w-2.5 h-2.5') ?> choque
                                        </span>
                                    <?php endif; ?>

                                <?= $podeEditar ? '</a>' : '</span>' ?>
                            <?php endforeach; ?>

                            <?php if (!$itens): ?>
                                <span class="mapa-livre">Livre</span>
                            <?php endif; ?>

                            <?php if ($podeCriar): ?>
                                <a href="<?= url('consultorios', 'novo', ['sala' => (int) $s['id'], 'dia' => $d, 'turno' => $turno]) ?>"
                                   class="mapa-mais nao-imprimir"
                                   title="Lançar ocupação — <?= e($s['nome'] . ', ' . ($longo[$d] ?? '') . ', ' . mb_strtolower(nome_turno($turno))) ?>"
                                   aria-label="Lançar ocupação em <?= e($s['nome'] . ', ' . ($longo[$d] ?? '') . ', ' . mb_strtolower(nome_turno($turno))) ?>">
                                    <?= Icon::svg('novo', 'w-3 h-3') ?>
                                </a>
                            <?php endif; ?>
                        </td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
