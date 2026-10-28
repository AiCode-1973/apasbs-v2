<?php
/**
 * O questionário de uma pessoa.
 *
 * Uma pessoa por vez, e não a grade de 22 linhas × 7 colunas do papel:
 * quem preenche está perguntando a alguém sobre a saúde dela, e a grade
 * obriga a acertar a coluna a cada resposta. A grade fica para a
 * impressão, que é onde ela faz sentido.
 *
 * @var array $d, $pessoa, $pessoas, $suas
 * @var int|null $idade
 */
declare(strict_types=1);

$itens = DeclaracaoModel::ITENS;

// O estado inicial do Alpine: a resposta de cada item e a descrição, para
// o campo de descrição já aparecer aberto no que estava marcado "sim".
$inicial = [];
foreach ($itens as $n => $def) {
    $r = $suas[$n] ?? null;
    $inicial[$n] = [
        'resposta'  => (string) ($r['resposta'] ?? ''),
        'descricao' => (string) ($r['descricao'] ?? ''),
    ];
}

$config = json_encode(['itens' => $inicial], JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT);

// O item 21 é idade: o sistema já sabe pelo nascimento do cadastro.
$valorDe = function (int $n) use ($suas, $idade): string {
    $gravado = (string) ($suas[$n]['valor'] ?? '');

    if ($n === 21 && $gravado === '' && $idade !== null) {
        return (string) $idade;
    }
    return $gravado;
};
?>

<div class="mb-5 flex flex-wrap items-start justify-between gap-3">
    <div class="flex items-start gap-3">
        <a href="<?= url('declaracoes', 'ver', ['id' => (int) $d['id']]) ?>"
           class="btn-acao mt-0.5" aria-label="Voltar à declaração">
            <?= Icon::svg('anterior', 'w-4 h-4') ?>
        </a>
        <div>
            <h1 class="titulo-pagina"><?= e($pessoa['nome']) ?></h1>
            <p class="sub-pagina">
                <?= $pessoa['tipo'] === 'titular'
                    ? 'Titular'
                    : e(Elegibilidade::rotuloParentesco($pessoa['parentesco'])) ?>
                <?php if ($idade !== null): ?> · <?= (int) $idade ?> anos<?php endif; ?>
                · Declaração de <?= e(data_br($d['data_preenchimento'])) ?>
            </p>
        </div>
    </div>

    <?php /* Trocar de pessoa sem passar pela tela da declaração: com a
             família na frente, é o gesto que mais se repete. */ ?>
    <?php if (count($pessoas) > 1): ?>
        <select class="select w-auto" data-navegar aria-label="Trocar de pessoa">
            <?php foreach ($pessoas as $p): ?>
                <option value="<?= url('declaracoes', 'responder', [
                        'declaracao' => (int) $d['id'], 'pessoa' => (int) $p['id'],
                   ]) ?>" <?= (int) $p['id'] === (int) $pessoa['id'] ? 'selected' : '' ?>>
                    <?= e($p['nome']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    <?php endif; ?>
</div>

<div class="mb-4 flex items-start gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-[0.8125rem] text-slate-600 dark:border-slate-800 dark:bg-slate-900/60 dark:text-slate-400">
    <?= Icon::svg('info', 'w-4 h-4 mt-0.5 shrink-0') ?>
    <span>
        Declare apenas doenças ou lesões de que a pessoa <strong>tenha
        conhecimento ou diagnóstico confirmado</strong> agora. Omitir o que se
        sabe pode levar à suspensão do contrato; declarar dá direito à cobertura
        depois da carência, com a suspensão temporária apenas dos procedimentos
        de alta complexidade ligados àquela doença.
    </span>
</div>

<form method="post" action="<?= url('declaracoes', 'salvar_respostas') ?>"
      x-data='questionario(<?= $config ?>)' @submit="enviar" novalidate>
    <?= Csrf::campo() ?>
    <input type="hidden" name="declaracao_id" value="<?= (int) $d['id'] ?>">
    <input type="hidden" name="beneficiario_id" value="<?= (int) $pessoa['id'] ?>">

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Questionário</h2>
            <span class="text-xs text-slate-500 dark:text-slate-400">
                <span x-text="respondidos"></span> de <?= count($itens) ?> respondidos
                <template x-if="quantosSim > 0">
                    <span class="text-amber-600 dark:text-amber-400">
                        · <span x-text="quantosSim"></span> declarad<span x-text="quantosSim === 1 ? 'a' : 'as'"></span>
                    </span>
                </template>
            </span>
        </div>

        <div class="divide-y divide-slate-100 dark:divide-slate-800">
            <?php foreach ($itens as $n => $def): ?>
                <?php /* Item condicional (o grau, que só existe se houver doença
                         nos olhos) aparece e some junto da resposta do item pai. */ ?>
                <div class="px-4 py-3"
                     <?= isset($def['depende'])
                         ? 'x-show="itens[' . (int) $def['depende'] . '].resposta === \'S\'" x-cloak'
                         : '' ?>>
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:gap-4">
                        <div class="flex-1">
                            <p class="text-[0.8125rem] leading-relaxed">
                                <span class="mono text-slate-400 dark:text-slate-500"><?= $n ?>.</span>
                                <strong class="text-slate-800 dark:text-slate-200"><?= e($def['titulo']) ?></strong>
                                <span class="block text-xs text-slate-500 dark:text-slate-400">
                                    <?= e($def['texto']) ?>
                                </span>
                            </p>
                        </div>

                        <?php if ($def['tipo'] === 'sn'): ?>
                            <?php /* Dois botões grandes em vez de um select: são 22
                                     respostas por pessoa, e cada clique a menos conta. */ ?>
                            <div class="flex shrink-0 gap-1.5">
                                <?php foreach ([['S', 'Sim'], ['N', 'Não']] as [$valor, $rotulo]): ?>
                                    <label class="cursor-pointer">
                                        <input type="radio" name="resposta[<?= $n ?>]" value="<?= $valor ?>"
                                               class="sr-only peer" x-model="itens[<?= $n ?>].resposta"
                                            <?= ($inicial[$n]['resposta'] ?? '') === $valor ? 'checked' : '' ?>>
                                        <?php /* peer-focus-visible: o input é sr-only, então sem
                                                 isto quem navega por teclado não enxerga onde
                                                 está o foco entre 44 botões. */ ?>
                                        <span class="block rounded-lg border px-4 py-1.5 text-[0.8125rem] font-medium transition-colors
                                                     peer-focus-visible:ring-2 peer-focus-visible:ring-marine-500 peer-focus-visible:ring-offset-1
                                                     <?= $valor === 'S'
                                                         ? 'border-slate-200 peer-checked:border-amber-400 peer-checked:bg-amber-50 peer-checked:text-amber-800 dark:border-slate-700 dark:peer-checked:border-amber-500/50 dark:peer-checked:bg-amber-500/10 dark:peer-checked:text-amber-300'
                                                         : 'border-slate-200 peer-checked:border-emerald-400 peer-checked:bg-emerald-50 peer-checked:text-emerald-800 dark:border-slate-700 dark:peer-checked:border-emerald-500/50 dark:peer-checked:bg-emerald-500/10 dark:peer-checked:text-emerald-300' ?>">
                                            <?= $rotulo ?>
                                        </span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="w-full shrink-0 sm:w-56">
                                <input type="text" name="valor[<?= $n ?>]" maxlength="60"
                                       class="input input-sm" value="<?= e($valorDe($n)) ?>"
                                       placeholder="<?= e($def['dica'] ?? '') ?>"
                                       aria-label="<?= e($def['titulo']) ?>">
                                <?php if ($n === 21 && $idade !== null): ?>
                                    <p class="ajuda">Calculada do nascimento.</p>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if ($def['tipo'] === 'sn'): ?>
                        <?php /* A descrição só aparece no "sim" — e é ela que dá valor
                                 à declaração: "sim" sem dizer qual doença não protege
                                 nem a operadora nem o beneficiário. */ ?>
                        <div x-show="itens[<?= $n ?>].resposta === 'S'" x-cloak class="mt-2 pl-6">
                            <label class="label" for="descricao_<?= $n ?>">
                                Qual? <span class="obrigatorio">*</span>
                            </label>
                            <textarea name="descricao[<?= $n ?>]" id="descricao_<?= $n ?>" rows="2" maxlength="600"
                                      class="input" x-model="itens[<?= $n ?>].descricao"
                                      placeholder="A doença, há quanto tempo, se faz tratamento e qual"><?= e($inicial[$n]['descricao']) ?></textarea>
                            <p class="ajuda" x-show="!itens[<?= $n ?>].descricao" x-cloak>
                                Sem isto, a declaração não protege quem declarou.
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="form-acoes px-4 py-4">
            <a href="<?= url('declaracoes', 'ver', ['id' => (int) $d['id']]) ?>"
               class="btn btn-outline">Voltar à declaração</a>
            <button type="submit" class="btn btn-primary" :disabled="enviando">
                <span x-show="!enviando"><?= Icon::svg('salvar', 'w-4 h-4') ?></span>
                <span x-show="enviando" x-cloak class="girando"></span>
                Gravar respostas
            </button>
        </div>
    </div>
</form>
