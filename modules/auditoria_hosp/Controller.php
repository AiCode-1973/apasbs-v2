<?php
declare(strict_types=1);

require_once BASE_PATH . '/modules/auditoria_hosp/Model.php';

/**
 * Auditoria Hospitalar — contas de internação e de PA/Ambulatório.
 *
 * O módulo trabalha no banco do AuditorHosp, que continua servindo o sistema
 * antigo. O que ele traz de novo não está nas tabelas: está em volta delas —
 * login único, permissão por módulo, token em todo formulário e a trilha de
 * auditoria dizendo quem mexeu em qual conta.
 *
 * O tipo de conta (`internacao` ou `pa`) viaja na URL e é validado contra
 * AuditoriaHospModel::TIPOS: nome de tabela nunca vem de fora.
 */
class Auditoria_hospController extends Controller
{
    protected string $modulo = 'auditoria_hosp';

    private AuditoriaHospModel $model;

    public function __construct()
    {
        $this->model = new AuditoriaHospModel();
    }

    /** O tipo pedido, já validado. Sem nada na URL, internações. */
    private function tipoAtual(): string
    {
        $tipo = (string) ($_POST['tipo'] ?? $_GET['tipo'] ?? 'internacao');

        return isset(AuditoriaHospModel::TIPOS[$tipo]) ? $tipo : 'internacao';
    }

    /**
     * Sem banco não há tela. Em vez de uma pilha de erros do PDO, uma página
     * dizendo o que falta — é quase sempre o GRANT ou o config/env.php.
     */
    private function exigirBanco(): void
    {
        if ($this->model->conectado()) {
            return;
        }

        View::render('auditoria_hosp', 'sem_banco', [
            'motivo' => erroAuditoria() ?? 'sem_acesso',
        ]);
        exit;
    }

    // ── Lista ─────────────────────────────────────────────────────────────

    /**
     * O recorte da lista, lido da URL.
     *
     * Em um lugar só porque a tela e a planilha precisam entender a mesma
     * coisa: um arquivo que traz linhas diferentes das que estavam à vista é
     * um arquivo em que ninguém confia na segunda vez.
     */
    private function recorteLista(): array
    {
        return [
            'busca'       => trim((string) ($_GET['q'] ?? '')),
            'status'      => (string) ($_GET['status'] ?? ''),
            'convenio_id' => (int) ($_GET['convenio'] ?? 0),
            'setor'       => (string) ($_GET['setor'] ?? ''),
            'competencia' => preg_replace('/[^0-9\-]/', '', (string) ($_GET['competencia'] ?? '')) ?? '',
            'so_glosa'    => !empty($_GET['glosa']),
        ];
    }

    public function lista(): void
    {
        Auth::requirePermissao('auditoria_hosp', 'pode_ver');
        $this->exigirBanco();

        $tipo   = $this->tipoAtual();
        $filtro = $this->recorteLista();

        $porPagina = Paginacao::porPagina(25);
        $total     = $this->model->contar($tipo, $filtro);
        $paginas   = max(1, (int) ceil($total / $porPagina));
        $pagina    = min(max(1, (int) ($_GET['pagina'] ?? 1)), $paginas);

        View::render('auditoria_hosp', 'lista', [
            'tipo'          => $tipo,
            'cfg'           => AuditoriaHospModel::tipo($tipo),
            'contas'        => $this->model->listar($tipo, $filtro, $porPagina, ($pagina - 1) * $porPagina),
            'totais'        => $this->model->totais($tipo, $filtro),
            'contagem'      => $this->model->porStatus($tipo),
            'competencias'  => $this->model->competencias($tipo),
            'convenios'     => $this->model->convenios(),
            'filtro'        => $filtro,
            'total'         => $total,
            'pagina'        => $pagina,
            'porPagina'     => $porPagina,
        ]);
    }

    // ── Planilha ──────────────────────────────────────────────────────────

    /**
     * As contas do recorte em uma planilha do Excel.
     *
     * Uma linha por conta, com tudo o que está gravado nela — inclusive o que
     * a tela não mostra por falta de largura: as datas de recebimento, entrada
     * e saída, o faturado, a nota fiscal, a observação e os motivos de glosa
     * escritos por extenso.
     */
    public function excel(): void
    {
        Auth::requirePermissao('auditoria_hosp', 'pode_ver');
        $this->exigirBanco();

        $tipo   = $this->tipoAtual();
        $cfg    = AuditoriaHospModel::tipo($tipo);
        $filtro = $this->recorteLista();
        $contas = $this->model->todas($tipo, $filtro);

        if (!$contas) {
            Flash::aviso('Não há contas neste recorte para baixar.');
            $this->redirect('lista', ['tipo' => $tipo] + array_filter([
                'competencia' => $filtro['competencia'],
            ]));
        }

        $motivos = $this->model->motivosDeVarias($tipo, array_column($contas, 'id'));

        $competencia = $filtro['competencia'] !== ''
            ? $this->mesBr($filtro['competencia'])
            : 'todas as competências';

        $this->auditar(Audit::BAIXOU, sprintf(
            'Planilha de %s — %s (%s %s)',
            $cfg['plural'],
            $competencia,
            numero(count($contas)),
            count($contas) === 1 ? 'conta' : 'contas'
        ));

        $planilha = new Planilha($cfg['plural']);

        $planilha->cabecalho(array_merge(
            ['Nº', 'Competência', 'Recebimento'],
            [$cfg['paciente'] ? 'Paciente' : 'Setor'],
            ['Guia', 'Convênio', 'Entrada', 'Saída',
             'Valor inicial', 'Retirado', '% retirado', 'Acrescentado', '% acrescentado',
             'Valor final', 'Glosado', '% glosado', 'Aceito no recurso', '% aceito',
             'Faturado', 'Situação', 'Falta NF', 'Motivos', 'Motivos da glosa', 'Observações']
        ));

        $tipos = array_merge(
            ['inteiro', 'mes', 'data'],
            ['texto'],
            ['texto', 'texto', 'data', 'data',
             'moeda', 'moeda', 'pct', 'moeda', 'pct',
             'moeda', 'moeda', 'pct', 'moeda', 'pct',
             'moeda', 'texto', 'texto', 'inteiro', 'texto', 'texto']
        );

        foreach ($contas as $c) {
            $p = AuditoriaHospModel::percentuais($c);

            // O Excel guarda percentual como fração: 10% é 0,1. Mandar 10
            // faria a célula mostrar 1000%.
            $pct = static fn (?float $v): ?float => $v === null ? null : round($v / 100, 6);

            $planilha->linha(array_merge(
                [(int) $c['id'], $c['competencia'], $c['data_recebimento']],
                [$cfg['paciente'] ? $c['paciente'] : $c['setor']],
                [
                    $c['guia_paciente'],
                    $c['convenio'],
                    $c['data_entrada'],
                    $c['data_saida'],
                    (float) $c['valor_inicial'],
                    (float) $c['valor_retirado'],
                    $pct($p['retirado']),
                    (float) $c['valor_acrescentado'],
                    $pct($p['acrescentado']),
                    (float) $c['valor_total'],
                    (float) $c['valor_glosado'],
                    $pct($p['glosado']),
                    (float) $c['valor_aceito'],
                    $pct($p['aceito']),
                    (float) $c['valor_faturado'],
                    $c['status'],
                    $c['falta_nf'],
                    (int) $c['motivos'],
                    $motivos[(int) $c['id']] ?? '',
                    $c['observacoes'],
                ]
            ), $tipos);
        }

        $planilha->baixar(sprintf(
            'auditoria-%s-%s.xlsx',
            $tipo === 'pa' ? 'pa-ambulatorio' : 'internacoes',
            $filtro['competencia'] !== '' ? $filtro['competencia'] : 'todas'
        ));
    }

    /** "2026-08" → "08/2026". */
    private function mesBr(string $mes): string
    {
        [$ano, $m] = array_pad(explode('-', $mes), 2, '');

        return $m !== '' ? $m . '/' . $ano : $mes;
    }

    // ── Consolidado ───────────────────────────────────────────────────────

    /**
     * O recorte do consolidado. Não tem busca por nome: consolidado de um
     * paciente é a conta dele, e isso a lista já faz.
     *
     * A competência tem um padrão diferente dos outros filtros — vem a mais
     * recente, porque é nela que a reunião está. Mas quem escolhe "todas" na
     * tela manda o campo vazio de propósito, e aí vale o que ele escolheu:
     * daí a diferença entre o campo ausente e o campo vazio.
     */
    private function recorteConsolidado(string $tipo): array
    {
        $competencia = isset($_GET['competencia'])
            ? (preg_replace('/[^0-9\-]/', '', (string) $_GET['competencia']) ?? '')
            : (string) ($this->model->competencias($tipo)[0] ?? '');

        return [
            'busca'       => '',
            'status'      => (string) ($_GET['status'] ?? ''),
            'convenio_id' => (int) ($_GET['convenio'] ?? 0),
            'setor'       => (string) ($_GET['setor'] ?? ''),
            'competencia' => $competencia,
            'so_glosa'    => false,
        ];
    }

    public function consolidado(): void
    {
        Auth::requirePermissao('auditoria_hosp', 'pode_ver');
        $this->exigirBanco();

        $tipo     = $this->tipoAtual();
        $cfg      = AuditoriaHospModel::tipo($tipo);
        $filtro   = $this->recorteConsolidado($tipo);
        $porSetor = $cfg['setor'] && !empty($_GET['setores']);
        $linhas   = $this->model->consolidado($tipo, $filtro, $porSetor);

        View::render('auditoria_hosp', 'consolidado', [
            'tipo'         => $tipo,
            'cfg'          => $cfg,
            'linhas'       => $linhas,
            'total'        => AuditoriaHospModel::somar($linhas),
            'setores'      => $cfg['setor'] ? $this->model->consolidadoPorSetor($tipo, $filtro) : [],
            'evolucao'     => $this->model->evolucao($tipo, $filtro),
            'competencias' => $this->model->competencias($tipo),
            'convenios'    => $this->model->convenios(),
            'filtro'       => $filtro,
            'porSetor'     => $porSetor,
        ]);
    }

    /** O mesmo consolidado em folha, para levar impresso à reunião. */
    public function imprimir(): void
    {
        Auth::requirePermissao('auditoria_hosp', 'pode_ver');
        $this->exigirBanco();

        $tipo   = $this->tipoAtual();
        $cfg    = AuditoriaHospModel::tipo($tipo);
        $filtro = $this->recorteConsolidado($tipo);
        $linhas = $this->model->consolidado($tipo, $filtro, $cfg['setor'] && !empty($_GET['setores']));

        // A competência vai para a trilha como quem lê escreve: 10/2026.
        [$ano, $mes] = array_pad(explode('-', $filtro['competencia']), 2, '');

        $this->auditar(Audit::IMPRIMIU, sprintf(
            'Consolidado de %s — competência %s',
            $cfg['plural'],
            $mes !== '' ? $mes . '/' . $ano : 'todas'
        ));

        View::render('auditoria_hosp', 'imprimir', [
            'tipo'      => $tipo,
            'cfg'       => $cfg,
            'linhas'    => $linhas,
            'total'     => AuditoriaHospModel::somar($linhas),
            'setores'   => $cfg['setor'] ? $this->model->consolidadoPorSetor($tipo, $filtro) : [],
            'filtro'    => $filtro,
            'convenios' => $this->model->convenios(),
        ], 'limpo');
    }

    // ── Formulário ────────────────────────────────────────────────────────

    public function novo(): void
    {
        Auth::requirePermissao('auditoria_hosp', 'pode_criar');
        $this->exigirBanco();

        $this->formulario($this->tipoAtual(), [
            'id'               => 0,
            'data_recebimento' => date('Y-m-d'),
            'competencia'      => date('Y-m-01'),
            'status'           => 'Pré análise',
            'falta_nf'         => 'Não',
            'setor'            => 'PA',
        ]);
    }

    public function editar(): void
    {
        Auth::requirePermissao('auditoria_hosp', 'pode_editar');
        $this->exigirBanco();

        $tipo  = $this->tipoAtual();
        $conta = $this->model->porId($tipo, $this->id());

        if (!$conta) {
            Flash::erro('Conta não encontrada.');
            $this->redirect('lista', ['tipo' => $tipo]);
        }

        $this->formulario($tipo, $conta);
    }

    private function formulario(string $tipo, array $conta, array $erros = []): void
    {
        $id = (int) ($conta['id'] ?? 0);

        View::render('auditoria_hosp', 'form', [
            'tipo'       => $tipo,
            'cfg'        => AuditoriaHospModel::tipo($tipo),
            'conta'      => $conta,
            'erros'      => $erros,
            'convenios'  => $this->model->convenios(),
            'motivos'    => $id > 0 ? $this->model->motivosDaConta($tipo, $id) : [],
            'somaMotivos'=> $id > 0 ? $this->model->somaMotivos($tipo, $id) : 0.0,
            'catalogo'   => $this->model->motivosDisponiveis(),
        ]);
    }

    public function salvar(): void
    {
        $id   = $this->id();
        $tipo = $this->tipoAtual();

        Auth::requirePermissao('auditoria_hosp', $id > 0 ? 'pode_editar' : 'pode_criar');
        $this->exigirPost();
        $this->exigirBanco();

        // A conta como está gravada, antes de qualquer mudança. A validação
        // precisa dela para saber o que é zero digitado agora e o que é zero
        // que já estava no banco — são coisas diferentes.
        $antes = $id > 0 ? $this->model->porId($tipo, $id) : false;

        $dados = $this->doPost($tipo);
        $erros = $this->validar($tipo, $dados, $antes ?: null);

        if ($erros) {
            $this->formulario($tipo, $dados + ['id' => $id], $erros);
            return;
        }

        if ($id > 0) {
            $this->model->atualizar($tipo, $id, $dados);
            $this->auditar(Audit::EDITOU, $this->descricao($tipo, $dados), $id);
            Flash::sucesso('Conta atualizada.');
        } else {
            $id = $this->model->criar($tipo, $dados);
            $this->auditar(Audit::CRIOU, $this->descricao($tipo, $dados), $id);
            Flash::sucesso('Conta registrada.');
        }

        // Final zerado numa conta que chegou com valor: a identidade manda o
        // acrescentado fechar em menos a conta inteira, e esse número vai
        // parar no consolidado. Não trava — conta esperando auditoria é
        // situação normal — mas quem gravou precisa saber o que ficou escrito.
        if ($dados['valor_total'] <= 0 && $dados['valor_inicial'] > 0) {
            Flash::aviso(sprintf(
                'O valor final ficou zerado, então o acrescentado fechou em %s.'
                . ' Se a auditoria ainda não aconteceu, volte e preencha o valor final'
                . ' quando ele chegar.',
                dinheiro_br(AuditoriaHospModel::valorAcrescentado($dados))
            ));
        }

        // Conta antiga que continua sem valor inicial: gravou, mas a falta
        // precisa ficar dita. Sem o inicial, o percentual de retirada não tem
        // base, e a conta entra no consolidado inflando o percentual de glosa.
        if (AuditoriaHospModel::exigeInicial($tipo, $dados['setor']) && $dados['valor_inicial'] <= 0) {
            Flash::aviso('Esta conta continua sem valor inicial. Ela já estava assim,'
                . ' então gravei — mas enquanto o valor não entrar, o percentual de'
                . ' retirada fica sem base e o de glosa sai maior do que é.');
        }

        // O glosado da conta e a soma dos motivos deveriam contar a mesma
        // história. Quando não contam, quem audita precisa saber — mas não
        // na forma de uma trava, porque o motivo às vezes entra depois.
        $soma = $this->model->somaMotivos($tipo, $id);

        if ($soma > 0 && abs($soma - (float) $dados['valor_glosado']) >= 0.01) {
            Flash::aviso(sprintf(
                'O valor glosado da conta (%s) não bate com a soma dos motivos de glosa (%s).',
                dinheiro_br((float) $dados["valor_glosado"]),
                dinheiro_br($soma)
            ));
        }

        $this->redirect('editar', ['tipo' => $tipo, 'id' => $id]);
    }

    public function excluir(): void
    {
        Auth::requirePermissao('auditoria_hosp', 'pode_excluir');
        $this->exigirPost();
        $this->exigirBanco();

        $tipo  = $this->tipoAtual();
        $conta = $this->model->porId($tipo, $this->id());

        if (!$conta) {
            Flash::erro('Conta não encontrada.');
            $this->redirect('lista', ['tipo' => $tipo]);
        }

        $this->model->excluir($tipo, (int) $conta['id']);
        $this->auditar(Audit::EXCLUIU, $this->descricao($tipo, $conta), (int) $conta['id']);

        Flash::sucesso('Conta excluída.');
        $this->redirect('lista', ['tipo' => $tipo]);
    }

    // ── Motivos de glosa da conta ─────────────────────────────────────────

    public function motivo(): void
    {
        Auth::requirePermissao('auditoria_hosp', 'pode_editar');
        $this->exigirPost();
        $this->exigirBanco();

        $tipo  = $this->tipoAtual();
        $id    = (int) ($_POST['conta_id'] ?? 0);
        $conta = $this->model->porId($tipo, $id);

        if (!$conta) {
            Flash::erro('Conta não encontrada.');
            $this->redirect('lista', ['tipo' => $tipo]);
        }

        $motivoId = (int) ($_POST['motivo_glosa_id'] ?? 0);
        $valor    = $this->numero((string) ($_POST['valor_glosa'] ?? '')) ?? 0.0;
        $obs      = trim((string) ($_POST['observacao'] ?? ''));

        if ($motivoId <= 0) {
            Flash::erro('Escolha o motivo da glosa.');
            $this->redirect('editar', ['tipo' => $tipo, 'id' => $id]);
        }

        $this->model->adicionarMotivo($tipo, $id, $motivoId, $valor, $obs);
        $this->auditar(Audit::EDITOU, sprintf(
            'Motivo de glosa na conta #%d (%s)', $id, AuditoriaHospModel::TIPOS[$tipo]['rotulo']
        ), $id);

        Flash::sucesso('Motivo de glosa incluído.');
        $this->redirect('editar', ['tipo' => $tipo, 'id' => $id]);
    }

    public function excluir_motivo(): void
    {
        Auth::requirePermissao('auditoria_hosp', 'pode_editar');
        $this->exigirPost();
        $this->exigirBanco();

        $tipo   = $this->tipoAtual();
        $motivo = $this->model->motivoPorId($tipo, $this->id());
        $contaId = (int) ($_POST['conta_id'] ?? 0);

        $cfg = AuditoriaHospModel::tipo($tipo);

        if (!$motivo || (int) $motivo[$cfg['chave']] !== $contaId) {
            Flash::erro('Motivo não encontrado.');
            $this->redirect('lista', ['tipo' => $tipo]);
        }

        $this->model->excluirMotivo($tipo, (int) $motivo['id']);
        $this->auditar(Audit::EXCLUIU, sprintf(
            'Motivo de glosa %s da conta #%d', $motivo['codigo'], $contaId
        ), $contaId);

        Flash::sucesso('Motivo removido.');
        $this->redirect('editar', ['tipo' => $tipo, 'id' => $contaId]);
    }

    // ── Apoio ─────────────────────────────────────────────────────────────

    private function doPost(string $tipo): array
    {
        $cfg = AuditoriaHospModel::tipo($tipo);

        $d = [
            'data_recebimento'  => $this->data((string) ($_POST['data_recebimento'] ?? '')),
            'competencia'       => $this->competencia((string) ($_POST['competencia'] ?? '')),
            'convenio_id'       => (int) ($_POST['convenio_id'] ?? 0),
            'guia_paciente'     => trim((string) ($_POST['guia_paciente'] ?? '')),
            'data_entrada'      => $this->data((string) ($_POST['data_entrada'] ?? '')),
            'data_saida'        => $this->data((string) ($_POST['data_saida'] ?? '')),
            'valor_inicial'     => $this->numero((string) ($_POST['valor_inicial'] ?? '')) ?? 0.0,
            'valor_retirado'    => $this->numero((string) ($_POST['valor_retirado'] ?? '')) ?? 0.0,
            // O final é digitado; o acrescentado é que sai da conta, e quem
            // faz essa conta é o Model, na hora de gravar. O campo só entra
            // aqui porque o formulário o devolve quando volta com erro.
            'valor_total'       => $this->numero((string) ($_POST['valor_total'] ?? '')) ?? 0.0,
            'valor_glosado'     => $this->numero((string) ($_POST['valor_glosado'] ?? '')) ?? 0.0,
            'valor_aceito'      => $this->numero((string) ($_POST['valor_aceito'] ?? '')) ?? 0.0,
            'valor_faturado'    => $this->numero((string) ($_POST['valor_faturado'] ?? '')) ?? 0.0,
            'falta_nf'          => (string) ($_POST['falta_nf'] ?? 'Não'),
            'status'            => trim((string) ($_POST['status'] ?? '')),
            'observacoes'       => trim((string) ($_POST['observacoes'] ?? '')),
        ];

        $d['paciente'] = $cfg['paciente'] ? trim((string) ($_POST['paciente'] ?? '')) : '';
        $d['setor']    = $cfg['setor']    ? (string) ($_POST['setor'] ?? '') : '';

        return $d;
    }

    /**
     * @param array|null $antes a conta como está gravada, ou null se é nova
     */
    private function validar(string $tipo, array $d, ?array $antes = null): array
    {
        $cfg   = AuditoriaHospModel::tipo($tipo);
        $erros = [];

        if ($cfg['paciente'] && $d['paciente'] === '') {
            $erros['paciente'] = 'O nome do paciente é obrigatório.';
        }

        // O valor inicial é obrigatório onde o tipo e o setor exigem — PA/NC e
        // AMB/NC ficam de fora, porque ali a conta chega zerada e ganha valor
        // na auditoria. A trava vale para a conta nova e para quem zera uma
        // conta que tinha valor; a conta que já chegou zerada do AuditorHosp
        // continua podendo ser gravada, senão ninguém conseguiria nem trocar
        // a situação dela sem antes digitar um valor que talvez não tenha em
        // mãos. Nesse caso o aviso aparece depois de gravar.
        if (AuditoriaHospModel::exigeInicial($tipo, $d['setor'])
            && $d['valor_inicial'] <= 0
            && !($antes !== null && (float) ($antes['valor_inicial'] ?? 0) <= 0)) {
            $erros['valor_inicial'] = 'Informe o valor inicial da conta.';
        }

        if ($cfg['setor'] && !in_array($d['setor'], AuditoriaHospModel::SETORES, true)) {
            $erros['setor'] = 'Escolha o setor.';
        }

        if ($d['convenio_id'] <= 0) {
            $erros['convenio_id'] = 'Escolha o convênio.';
        } elseif (!$this->model->convenioExiste($d['convenio_id'])) {
            $erros['convenio_id'] = 'Convênio inválido.';
        }

        if (!$d['competencia']) {
            $erros['competencia'] = 'Informe a competência.';
        }

        if ($d['status'] === '') {
            $erros['status'] = 'Informe a situação.';
        }

        // Auditada é o fim da fila: quer dizer que a auditoria aconteceu, e o
        // valor final é o resultado dela. Marcar auditada sem o final seria
        // dizer que terminou sem dizer em quanto deu — e esse número entra no
        // consolidado, no percentual de glosa e na reunião com o convênio.
        //
        // A trava é na *passagem* para auditada. A conta que já estava
        // auditada com o final zerado, vinda do AuditorHosp, continua gravável:
        // senão ninguém conseguiria corrigir nem o nome do paciente nela sem
        // antes arrumar um valor que talvez não tenha em mãos.
        if ($d['status'] === AuditoriaHospModel::SITUACAO_AUDITADO && $d['valor_total'] <= 0
            && ($antes === null || ($antes['status'] ?? '') !== AuditoriaHospModel::SITUACAO_AUDITADO)) {
            $erros['valor_total'] = 'Para marcar a conta como auditada, informe o valor final.';
            $erros['status']      = 'A conta só passa a auditada com o valor final preenchido.';
        }

        // O acrescentado fica de fora: ele é calculado, e dá negativo de
        // verdade quando a conta final ficou abaixo de inicial − retirado.
        // Recusá-lo seria recusar uma combinação legítima dos três digitados.
        foreach (['valor_inicial', 'valor_retirado', 'valor_total',
                  'valor_glosado', 'valor_aceito', 'valor_faturado'] as $campo) {
            if ($d[$campo] < 0) {
                $erros[$campo] = 'O valor não pode ser negativo.';
            }
        }

        // Retirar mais do que a conta tem é erro de digitação, não auditoria.
        if ($d['valor_retirado'] > $d['valor_inicial'] && $d['valor_inicial'] > 0) {
            $erros['valor_retirado'] = 'O retirado passou do valor inicial da conta.';
        }

        if ($d['data_entrada'] && $d['data_saida'] && $d['data_saida'] < $d['data_entrada']) {
            $erros['data_saida'] = 'A saída é anterior à entrada.';
        }

        return $erros;
    }

    private function descricao(string $tipo, array $d): string
    {
        $cfg = AuditoriaHospModel::tipo($tipo);

        return sprintf(
            '%s — %s (guia %s)',
            $cfg['rotulo'],
            $cfg['paciente'] ? ($d['paciente'] ?: 'sem nome') : ($d['setor'] ?: 'sem setor'),
            $d['guia_paciente'] !== '' ? $d['guia_paciente'] : 'sem número'
        );
    }

    /** Data vinda da tela: vazia é NULL, não "0000-00-00". */
    private function data(string $valor): ?string
    {
        $valor = trim($valor);

        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $valor) ? $valor : null;
    }

    /** A competência é um mês; no banco, o dia 1 dele. */
    private function competencia(string $valor): ?string
    {
        $valor = trim($valor);

        if (preg_match('/^\d{4}-\d{2}$/', $valor)) {
            return $valor . '-01';
        }

        return $this->data($valor);
    }

    /**
     * O que foi digitado vira número pelo helper do sistema — ele já conhece
     * as duas formas que aparecem na prática ("1.234,56" e "1234.56") e o caso
     * do "4.450", que é quatro mil e quatrocentos e cinquenta, não quatro reais.
     */
    private function numero(string $valor): ?float
    {
        return trim($valor) === '' ? null : valor_br($valor);
    }
}
