<?php
/**
 * Os trabalhadores acompanhados.
 *
 * Uma linha por vínculo: a mesma pessoa em duas empresas são dois cadastros,
 * porque função, setor e admissão são do vínculo, não da pessoa.
 *
 * @var array      $trabalhadores, $empresas, $filtro, $erros
 * @var array|null $editando
 */
declare(strict_types=1);

$podeCriar   = Auth::temPermissao('ocupacional', 'pode_criar');
$podeEditar  = Auth::temPermissao('ocupacional', 'pode_editar');

$emEdicao = $editando !== null;
$erroCls  = fn(string $c): string => isset($erros[$c]) ? 'input-error' : '';
$v        = fn(string $c): string => (string) ($editando[$c] ?? '');

$hoje      = new DateTimeImmutable('today');
$temFiltro = $filtro['busca'] !== '' || $filtro['empresa_id'] || $filtro['so_ativos'];
?>

<div class="mb-5 flex flex-wrap items-end justify-between gap-3">
    <div class="flex items-start gap-3">
        <a href="<?= url_lista('ocupacional') ?>" class="btn-acao mt-0.5" aria-label="Voltar às fichas">
            <?= Icon::svg('anterior', 'w-4 h-4') ?>
        </a>
        <div>
            <h1 class="titulo-pagina">Trabalhadores</h1>
            <p class="sub-pagina">Quem é acompanhado pelo PCMSO</p>
        </div>
    </div>

    <a href="<?= url('ocupacional', 'empresas') ?>" class="btn btn-outline">
        <?= Icon::svg('setores', 'w-4 h-4') ?> Empresas
    </a>
</div>

<div class="grid gap-4 lg:grid-cols-[24rem_minmax(0,1fr)] lg:items-start">

    <?php if ($podeEditar && empty($empresas)): ?>
        <div class="card">
            <div class="vazio">
                <span class="vazio-icone"><?= Icon::svg('setores', 'w-5 h-5') ?></span>
                <p class="vazio-titulo">Cadastre uma empresa primeiro</p>
                <p class="vazio-texto">Todo trabalhador pertence a uma empregadora.</p>
                <a href="<?= url('ocupacional', 'empresas') ?>" class="btn btn-primary btn-sm mt-1">
                    <?= Icon::svg('novo', 'w-3.5 h-3.5') ?> Cadastrar empresa
                </a>
            </div>
        </div>
    <?php elseif ($podeEditar): ?>
        <div class="card">
            <div class="card-header">
                <h2 class="card-title"><?= $emEdicao ? 'Editar trabalhador' : 'Incluir trabalhador' ?></h2>
                <?php if ($emEdicao): ?>
                    <a href="<?= url('ocupacional', 'trabalhadores') ?>" class="btn btn-ghost btn-sm">Cancelar</a>
                <?php endif; ?>
            </div>

            <form method="post" action="<?= url('ocupacional', 'salvar_trabalhador') ?>"
                  class="card-corpo space-y-3.5" x-data="envio" @submit="enviar" novalidate>
                <?= Csrf::campo() ?>
                <?php if ($emEdicao): ?>
                    <input type="hidden" name="id" value="<?= (int) $editando['id'] ?>">
                <?php endif; ?>

                <div>
                    <label class="label" for="nome">Nome <span class="obrigatorio">*</span></label>
                    <input type="text" name="nome" id="nome" maxlength="150" required
                           class="input <?= $erroCls('nome') ?>" value="<?= e($v('nome')) ?>"
                           placeholder="Nome completo">
                    <?php if (isset($erros['nome'])): ?>
                        <p class="erro-campo"><?= Icon::svg('erro', 'w-3.5 h-3.5') ?> <?= e($erros['nome']) ?></p>
                    <?php endif; ?>
                </div>

                <div>
                    <label class="label" for="empresa_id">Empresa <span class="obrigatorio">*</span></label>
                    <select name="empresa_id" id="empresa_id" required class="select <?= $erroCls('empresa_id') ?>">
                        <option value="">Escolha…</option>
                        <?php foreach ($empresas as $emp): ?>
                            <option value="<?= (int) $emp['id'] ?>"
                                <?= (int) $v('empresa_id') === (int) $emp['id'] ? 'selected' : '' ?>>
                                <?= e($emp['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($erros['empresa_id'])): ?>
                        <p class="erro-campo"><?= Icon::svg('erro', 'w-3.5 h-3.5') ?> <?= e($erros['empresa_id']) ?></p>
                    <?php endif; ?>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="label" for="cpf">CPF</label>
                        <input type="text" name="cpf" id="cpf" inputmode="numeric" maxlength="14"
                               class="input mono <?= $erroCls('cpf') ?>"
                               value="<?= e($v('cpf') !== '' ? formatar_cpf($v('cpf')) : '') ?>"
                               x-data="cpf" @input="formatar" placeholder="000.000.000-00">
                        <?php if (isset($erros['cpf'])): ?>
                            <p class="erro-campo">
                                <?= Icon::svg('erro', 'w-3.5 h-3.5') ?>
                                <span>
                                    <?= e($erros['cpf']) ?>
                                    <?php if (isset($erros['_link'])): ?>
                                        <a href="<?= e($erros['_link']) ?>" class="underline">Abrir quem já existe</a>.
                                    <?php endif; ?>
                                </span>
                            </p>
                        <?php endif; ?>
                    </div>

                    <div>
                        <label class="label" for="nascimento">Nascimento</label>
                        <input type="date" name="nascimento" id="nascimento" class="input"
                               value="<?= e(substr($v('nascimento'), 0, 10)) ?>">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <?php /* A matrícula é do vínculo, como função e setor: digitada
                             aqui uma vez, ela sai sozinha em toda entrega de
                             atestado desta pessoa. */ ?>
                    <div>
                        <label class="label" for="matricula">Matrícula</label>
                        <input type="text" name="matricula" id="matricula" maxlength="30" class="input"
                               value="<?= e($v('matricula')) ?>" placeholder="Número funcional">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="label" for="funcao">Função</label>
                        <input type="text" name="funcao" id="funcao" maxlength="120" class="input"
                               value="<?= e($v('funcao')) ?>" placeholder="Ex.: Auxiliar de limpeza">
                    </div>

                    <div>
                        <label class="label" for="setor">Setor</label>
                        <input type="text" name="setor" id="setor" maxlength="120" class="input"
                               value="<?= e($v('setor')) ?>" placeholder="Ex.: Centro cirúrgico">
                    </div>
                </div>

                <div>
                    <label class="label" for="admissao">Admissão</label>
                    <input type="date" name="admissao" id="admissao" class="input"
                           value="<?= e(substr($v('admissao'), 0, 10)) ?>">
                </div>

                <div>
                    <label class="label" for="observacao">Observação</label>
                    <input type="text" name="observacao" id="observacao" maxlength="400" class="input"
                           value="<?= e($v('observacao')) ?>">
                </div>

                <label class="flex cursor-pointer items-start gap-2">
                    <input type="checkbox" name="ativo" value="1" class="check mt-0.5"
                        <?= !$emEdicao || !empty($editando['ativo']) ? 'checked' : '' ?>>
                    <span class="text-[0.8125rem]">
                        Ativo
                        <span class="block text-xs text-slate-500 dark:text-slate-400">
                            Desmarque quando for desligado: sai das listas do dia a dia
                            e o prontuário continua guardado.
                        </span>
                    </span>
                </label>

                <button type="submit" class="btn btn-primary w-full" :disabled="enviando">
                    <?= Icon::svg('salvar', 'w-4 h-4') ?>
                    <?= $emEdicao ? 'Salvar alterações' : 'Incluir trabalhador' ?>
                </button>
            </form>
        </div>
    <?php endif; ?>

    <div class="card overflow-hidden">
        <form method="get" action="<?= BASE_URL ?>/" class="toolbar">
            <input type="hidden" name="mod" value="ocupacional">
            <input type="hidden" name="action" value="trabalhadores">

            <div class="campo-icone w-full sm:w-64">
                <?= Icon::svg('buscar') ?>
                <input type="search" name="q" value="<?= e($filtro['busca']) ?>" class="input"
                       placeholder="Nome, CPF, função ou setor…" aria-label="Buscar trabalhadores">
            </div>

            <select name="empresa" class="select w-auto" aria-label="Empresa">
                <option value="">Todas as empresas</option>
                <?php foreach ($empresas as $emp): ?>
                    <option value="<?= (int) $emp['id'] ?>" <?= $filtro['empresa_id'] === (int) $emp['id'] ? 'selected' : '' ?>>
                        <?= e($emp['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label class="flex cursor-pointer items-center gap-1.5 text-xs text-slate-600 dark:text-slate-400">
                <input type="checkbox" name="ativos" value="1" class="check" <?= $filtro['so_ativos'] ? 'checked' : '' ?>>
                Só os ativos
            </label>

            <button type="submit" class="btn btn-outline">Filtrar</button>

            <?php if ($temFiltro): ?>
                <a href="<?= url('ocupacional', 'trabalhadores') ?>" class="btn btn-ghost">Limpar</a>
            <?php endif; ?>
        </form>

        <?php if (empty($trabalhadores)): ?>
            <div class="vazio">
                <span class="vazio-icone"><?= Icon::svg('usuarios', 'w-5 h-5') ?></span>
                <p class="vazio-titulo"><?= $temFiltro ? 'Ninguém encontrado' : 'Nenhum trabalhador cadastrado' ?></p>
                <p class="vazio-texto">
                    <?= $temFiltro
                        ? 'Nenhum resultado com esses filtros. Tente limpar e buscar de novo.'
                        : 'Cadastre ao lado quem será acompanhado pelo PCMSO.' ?>
                </p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="tabela">
                    <thead>
                        <tr>
                            <th>Trabalhador</th>
                            <th class="w-36">Empresa</th>
                            <th class="w-40">Função / setor</th>
                            <th class="w-24">Fichas</th>
                            <th class="w-32">Próxima validade</th>
                            <th class="w-24 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($trabalhadores as $t): ?>
                            <?php
                            $semExame = (int) $t['fichas'] === 0;
                            $perto = $t['proxima_validade']
                                     && $t['proxima_validade'] <= $hoje->modify('+30 days')->format('Y-m-d');
                            ?>
                            <tr>
                                <td>
                                    <a href="<?= url('ocupacional', 'ver_trabalhador', ['id' => (int) $t['id']]) ?>"
                                       class="celula-principal block hover:underline"><?= e($t['nome']) ?></a>
                                    <span class="block text-xs text-slate-500 dark:text-slate-400">
                                        <?php if ($t['cpf']): ?>
                                            <span class="mono"><?= e(formatar_cpf((string) $t['cpf'])) ?></span>
                                        <?php endif; ?>
                                        <?php if (!$t['ativo']): ?>
                                            <span class="badge badge-cinza ml-1">Desligado</span>
                                        <?php endif; ?>
                                    </span>
                                </td>

                                <td class="celula-suave"><?= e($t['empresa']) ?></td>

                                <td class="celula-suave text-xs">
                                    <?= $t['funcao'] ? e($t['funcao']) : '<span class="text-slate-400 dark:text-slate-600">—</span>' ?>
                                    <?php if ($t['setor']): ?>
                                        <span class="block text-[0.6875rem] text-slate-400 dark:text-slate-500">
                                            <?= e($t['setor']) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td class="text-xs">
                                    <?php if ($semExame): ?>
                                        <span class="text-amber-600 dark:text-amber-400">nenhuma</span>
                                    <?php else: ?>
                                        <span class="celula-principal"><?= numero((int) $t['fichas']) ?></span>
                                        <span class="block text-[0.625rem] text-slate-400 dark:text-slate-500">
                                            última em <?= e(data_br($t['ultimo_exame'])) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td class="text-xs">
                                    <?php if (!$t['proxima_validade']): ?>
                                        <span class="text-slate-400 dark:text-slate-600">—</span>
                                    <?php elseif ($perto): ?>
                                        <span class="font-medium text-amber-600 dark:text-amber-400">
                                            <?= e(data_br($t['proxima_validade'])) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="celula-suave"><?= e(data_br($t['proxima_validade'])) ?></span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <div class="flex items-center justify-end gap-0.5">
                                        <?php if ($podeCriar): ?>
                                            <a href="<?= url('ocupacional', 'novo', ['trabalhador' => (int) $t['id']]) ?>"
                                               class="btn-acao" title="Nova ficha para este trabalhador"
                                               aria-label="Nova ficha para <?= e($t['nome']) ?>">
                                                <?= Icon::svg('novo', 'w-4 h-4') ?>
                                            </a>
                                        <?php endif; ?>

                                        <?php if ($podeEditar): ?>
                                            <a href="<?= url('ocupacional', 'trabalhadores', ['id' => (int) $t['id']]) ?>"
                                               class="btn-acao" title="Editar cadastro"
                                               aria-label="Editar <?= e($t['nome']) ?>">
                                                <?= Icon::svg('editar', 'w-4 h-4') ?>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
