<?php
declare(strict_types=1);

require_once BASE_PATH . '/modules/agenda/Model.php';
require_once BASE_PATH . '/modules/medicos/Model.php';
require_once BASE_PATH . '/modules/exames/Model.php';

class AgendaController extends Controller
{
    protected string $modulo = 'agenda';

    private AgendaModel $model;
    private MedicoModel $medicos;
    private ExameModel  $exames;

    public function __construct()
    {
        $this->model   = new AgendaModel();
        $this->medicos = new MedicoModel();
        $this->exames  = new ExameModel();
    }

    // ── Visualização ──────────────────────────────────────────────────────

    /** Calendário do mês — visão padrão do módulo. */
    public function mes(): void
    {
        Auth::requirePermissao('agenda', 'pode_ver');
        View::render('agenda', 'mes', $this->dadosDoMes());
    }

    /** Mesma competência, em lista — melhor para conferir contra a planilha. */
    public function lista(): void
    {
        Auth::requirePermissao('agenda', 'pode_ver');
        View::render('agenda', 'lista', $this->dadosDoMes());
    }

    /** Folha de impressão, sem sidebar nem topbar. */
    public function imprimir(): void
    {
        Auth::requirePermissao('agenda', 'pode_ver');

        $dados = $this->dadosDoMes();

        $formato = (string) ($_GET['formato'] ?? 'calendario');
        $dados['formato'] = $formato === 'lista' ? 'lista' : 'calendario';

        $this->auditar(
            Audit::BAIXOU,
            'Imprimiu a agenda de ' . competencia_br($dados['ano'], $dados['mes'])
            . ' (' . $dados['formato'] . ')'
        );

        View::render('agenda', 'imprimir', $dados, 'limpo');
    }

    // ── Bloco ─────────────────────────────────────────────────────────────

    public function novo(): void
    {
        Auth::requirePermissao('agenda', 'pode_criar');

        [$ano, $mes] = $this->competencia();
        $data = (string) ($_GET['data'] ?? '');

        if (!$this->dataValida($data)) {
            $data = sprintf('%04d-%02d-01', $ano, $mes);
        }

        View::render('agenda', 'form', [
            'bloco'   => ['data' => $data, 'turno' => (string) ($_GET['turno'] ?? 'manha')],
            'vagas'   => [],
            'medicos' => $this->medicos->ativos(),
            'exames'  => $this->exames->ativos(),
            'erros'   => [],
            'volta'   => $this->volta(),
        ]);
    }

    public function salvar(): void
    {
        Auth::requirePermissao('agenda', 'pode_criar');
        $this->exigirPost();

        $dados = $this->sanitizar($_POST);
        $vagas = $this->vagasDoPost($_POST);
        $erros = $this->validar($dados, $vagas);

        if ($erros) {
            View::render('agenda', 'form', [
                'bloco'   => $dados,
                'vagas'   => $vagas,
                'medicos' => $this->medicos->paraFormulario($dados['medico_id']),
                'exames'  => $this->exames->paraFormulario(array_keys($vagas)),
                'erros'   => $erros,
                'volta'   => $this->volta(),
            ]);
            return;
        }

        $id = $this->model->salvarBloco($dados, $vagas);

        $this->auditar(Audit::CRIOU, $this->descrever($dados), $id);
        Flash::sucesso('Bloco lançado na agenda.');
        $this->voltarParaOMes($dados['data']);
    }

    public function editar(): void
    {
        Auth::requirePermissao('agenda', 'pode_editar');

        $id    = $this->id();
        $bloco = $this->model->buscarBloco($id);

        if (!$bloco) {
            Flash::erro('Bloco não encontrado.');
            $this->redirect('mes');
        }

        $vagasDoBloco = $this->model->vagasDoBloco($id);

        View::render('agenda', 'form', [
            'bloco'   => $bloco,
            'vagas'   => $vagasDoBloco,
            'medicos' => $this->medicos->paraFormulario((int) $bloco['medico_id']),
            'exames'  => $this->exames->paraFormulario(array_keys($vagasDoBloco)),
            'erros'   => [],
            'volta'   => $this->volta(),
        ]);
    }

    public function atualizar(): void
    {
        Auth::requirePermissao('agenda', 'pode_editar');
        $this->exigirPost();

        $id = $this->id();

        if (!$this->model->buscarBloco($id)) {
            Flash::erro('Bloco não encontrado.');
            $this->redirect('mes');
        }

        $dados = $this->sanitizar($_POST);
        $vagas = $this->vagasDoPost($_POST);
        $erros = $this->validar($dados, $vagas, $id);

        if ($erros) {
            $dados['id'] = $id;
            View::render('agenda', 'form', [
                'bloco'   => $dados,
                'vagas'   => $vagas,
                'medicos' => $this->medicos->paraFormulario($dados['medico_id']),
                'exames'  => $this->exames->paraFormulario(array_keys($vagas)),
                'erros'   => $erros,
                'volta'   => $this->volta(),
            ]);
            return;
        }

        $this->model->salvarBloco($dados, $vagas, $id);

        $this->auditar(Audit::EDITOU, $this->descrever($dados), $id);
        Flash::sucesso('Bloco atualizado.');
        $this->voltarParaOMes($dados['data']);
    }

    public function excluir(): void
    {
        Auth::requirePermissao('agenda', 'pode_excluir');
        $this->exigirPost();

        $id    = $this->id();
        $bloco = $this->model->buscarBloco($id);

        if (!$bloco) {
            Flash::erro('Bloco não encontrado.');
            $this->redirect('mes');
        }

        $marcadas = $this->model->marcacoesAtivas($id);

        if ($marcadas > 0) {
            Flash::aviso(
                'Não é possível excluir: este bloco tem ' . $marcadas . ' paciente'
                . ($marcadas === 1 ? '' : 's') . ' com exame marcado. Cancele '
                . ($marcadas === 1 ? 'a marcação' : 'as marcações') . ' antes de remover o bloco.'
            );
            $this->voltarParaOMes($bloco['data']);
        }

        $this->model->excluirBloco($id);

        $this->auditar(
            Audit::EXCLUIU,
            $bloco['medico_nome'] . ' — ' . data_br($bloco['data']) . ' ' . nome_turno($bloco['turno']),
            $id
        );
        Flash::sucesso('Bloco removido da agenda.');
        $this->voltarParaOMes($bloco['data']);
    }

    // ── Marcações ─────────────────────────────────────────────────────────

    /** Tela do bloco: quem já está marcado e o formulário para marcar. */
    public function marcacoes(): void
    {
        Auth::requirePermissao('agenda', 'pode_ver');

        $blocoId = (int) ($_GET['bloco'] ?? $_POST['bloco'] ?? 0);
        $bloco   = $this->model->buscarBloco($blocoId);

        if (!$bloco) {
            Flash::erro('Bloco não encontrado.');
            $this->redirect('mes');
        }

        View::render('agenda', 'marcacoes', $this->dadosDoBloco($bloco));
    }

    /** Tudo que a tela de marcações precisa. */
    private function dadosDoBloco(array $bloco, array $erros = [], array $preenchido = []): array
    {
        $blocoId = (int) $bloco['id'];
        $vagas   = $this->model->vagasDoBloco($blocoId);
        $ts      = (int) strtotime($bloco['data']);

        return [
            'bloco'      => $bloco,
            'vagas'      => $vagas,
            'exames'     => $this->exames->paraFormulario(array_keys($vagas)),
            'marcacoes'  => $this->model->marcacoesDoBloco($blocoId),
            'motivos'    => $this->model->motivosDeCancelamento(),
            'ano'        => (int) date('Y', $ts),
            'mes'        => (int) date('n', $ts),
            'volta'      => $this->volta(),
            'erros'      => $erros,
            'preenchido' => $preenchido,
        ];
    }

    public function marcar(): void
    {
        Auth::requirePermissao('agenda', 'pode_criar');
        $this->exigirPost();

        $blocoId = (int) ($_POST['bloco'] ?? 0);
        $bloco   = $this->model->buscarBloco($blocoId);

        if (!$bloco) {
            Flash::erro('Bloco não encontrado.');
            $this->redirect('mes');
        }

        $exameId  = (int) ($_POST['exame_id'] ?? 0);
        $cpf      = so_digitos((string) ($_POST['paciente_cpf'] ?? ''));
        $paciente = trim((string) ($_POST['paciente_nome'] ?? ''));
        $obs      = mb_substr(trim((string) ($_POST['observacao'] ?? '')), 0, 300);

        $erros = [];

        if ($cpf === '') {
            $erros['paciente_cpf'] = 'Informe o CPF da paciente.';
        } elseif (!cpf_valido($cpf)) {
            $erros['paciente_cpf'] = 'CPF inválido. Confira os números digitados.';
        }

        if ($paciente === '') {
            $erros['paciente_nome'] = 'Informe o nome da paciente.';
        } elseif (mb_strlen($paciente) > 150) {
            $erros['paciente_nome'] = 'Nome deve ter no máximo 150 caracteres.';
        }

        $capacidade = $exameId > 0 ? $this->model->capacidadeDaVaga($blocoId, $exameId) : 0;

        if ($exameId <= 0 || $capacidade <= 0) {
            $erros['exame_id'] = 'Escolha um tipo de exame que este bloco atende.';
        } elseif ($this->model->marcadasDaVaga($blocoId, $exameId) >= $capacidade) {
            $erros['exame_id'] = 'Não há mais vaga deste tipo de exame neste bloco.';
        } elseif ($cpf !== '' && $this->model->cpfJaMarcado($blocoId, $exameId, $cpf)) {
            $erros['paciente_cpf'] = 'Esta paciente já está marcada para este exame neste bloco.';
        }

        if ($erros) {
            View::render('agenda', 'marcacoes', $this->dadosDoBloco($bloco, $erros, [
                'exame_id'      => $exameId,
                'paciente_cpf'  => $cpf,
                'paciente_nome' => $paciente,
                'observacao'    => $obs,
            ]));
            return;
        }

        $id = $this->model->marcar([
            'bloco_id'       => $blocoId,
            'exame_id'       => $exameId,
            'paciente_cpf'   => $cpf,
            'paciente_nome'  => $paciente,
            'observacao'     => $obs,
            'atendente_id'   => Auth::id(),
            'atendente_nome' => (string) Auth::nome(),
        ]);

        if ($id === false) {
            Flash::aviso('A última vaga deste tipo de exame acabou de ser preenchida. Confira a lista.');
            $this->redirect('marcacoes', ['bloco' => $blocoId, 'vista' => $this->volta()]);
        }

        $this->auditar(
            Audit::CRIOU,
            'Marcou ' . $paciente . ' (CPF ' . formatar_cpf($cpf) . ') — '
            . $bloco['medico_nome'] . ', ' . data_br($bloco['data'])
            . ' ' . nome_turno($bloco['turno']),
            $id
        );
        Flash::sucesso('Exame marcado para ' . $paciente . '.');
        $this->redirect('marcacoes', ['bloco' => $blocoId, 'vista' => $this->volta()]);
    }

    public function cancelar(): void
    {
        Auth::requirePermissao('agenda', 'pode_editar');
        $this->exigirPost();

        $id       = $this->id();
        $marcacao = $this->model->buscarMarcacao($id);

        if (!$marcacao) {
            Flash::erro('Marcação não encontrada.');
            $this->redirect('mes');
        }

        $blocoId = (int) $marcacao['bloco_id'];

        if ($marcacao['status'] !== 'marcada') {
            Flash::aviso('Esta marcação já estava cancelada.');
            $this->redirect('marcacoes', ['bloco' => $blocoId, 'vista' => $this->volta()]);
        }

        // A atendente escolhe da lista, ou marca "Outro motivo" e escreve.
        $escolha = (string) ($_POST['motivo_id'] ?? '');
        $escrito = trim(preg_replace('/\s+/u', ' ', (string) ($_POST['motivo'] ?? '')) ?? '');

        if ($escolha !== '' && $escolha !== 'outro') {
            $motivoId = (int) $escolha;

            if (!$this->model->motivoExiste($motivoId)) {
                Flash::erro('Motivo de cancelamento inválido. Escolha de novo.');
                $this->redirect('marcacoes', ['bloco' => $blocoId, 'vista' => $this->volta()]);
            }

            $motivo = $this->model->nomeDoMotivo($motivoId);
        } else {
            if ($escrito === '') {
                Flash::erro('Escolha um motivo da lista ou escreva um — ele fica registrado junto com a marcação.');
                $this->redirect('marcacoes', ['bloco' => $blocoId, 'vista' => $this->volta()]);
            }

            $motivo   = mb_substr($escrito, 0, 150);
            $motivoId = $this->model->motivoDeCancelamento($motivo, Auth::id());
        }

        $this->model->cancelar($id, $motivoId, Auth::id(), (string) Auth::nome());

        $this->auditar(
            Audit::EDITOU,
            'Cancelou ' . $marcacao['paciente_nome'] . ' (' . $marcacao['exame_nome'] . ', '
            . data_br($marcacao['data']) . ') — ' . $motivo,
            $id
        );
        Flash::sucesso('Marcação cancelada. A vaga voltou a ficar livre.');
        $this->redirect('marcacoes', ['bloco' => $blocoId, 'vista' => $this->volta()]);
    }

    /** Apagar de vez — serve para erro de digitação, não para desmarcar. */
    public function excluir_marcacao(): void
    {
        Auth::requirePermissao('agenda', 'pode_excluir');
        $this->exigirPost();

        $id       = $this->id();
        $marcacao = $this->model->buscarMarcacao($id);

        if (!$marcacao) {
            Flash::erro('Marcação não encontrada.');
            $this->redirect('mes');
        }

        $blocoId = (int) $marcacao['bloco_id'];

        $this->model->excluirMarcacao($id);

        $this->auditar(
            Audit::EXCLUIU,
            'Apagou a marcação de ' . $marcacao['paciente_nome']
            . ' (' . $marcacao['exame_nome'] . ', ' . data_br($marcacao['data']) . ')',
            $id
        );
        Flash::sucesso('Marcação apagada.');
        $this->redirect('marcacoes', ['bloco' => $blocoId, 'vista' => $this->volta()]);
    }

    // ── Bloqueio de dia ───────────────────────────────────────────────────

    public function bloquear(): void
    {
        Auth::requirePermissao('agenda', 'pode_editar');
        $this->exigirPost();

        $data   = (string) ($_POST['data'] ?? '');
        $motivo = trim((string) ($_POST['motivo'] ?? ''));

        if (!$this->dataValida($data)) {
            Flash::erro('Data inválida.');
            $this->redirect('mes');
        }

        if ($motivo === '') {
            $motivo = 'Dia sem atendimento';
        }
        $motivo = mb_substr($motivo, 0, 150);

        $this->model->bloquear($data, $motivo, Auth::id());

        $this->auditar(Audit::EDITOU, 'Bloqueou ' . data_br($data) . ' — ' . $motivo);

        $abertos = $this->model->blocosNoDia($data);
        if ($abertos > 0) {
            Flash::aviso(
                'Dia bloqueado, mas ele ainda tem ' . $abertos . ' bloco'
                . ($abertos === 1 ? '' : 's') . ' lançado'
                . ($abertos === 1 ? '' : 's') . '. Remova o que não vai acontecer.'
            );
        } else {
            Flash::sucesso('Dia bloqueado.');
        }

        $this->voltarParaOMes($data);
    }

    public function desbloquear(): void
    {
        Auth::requirePermissao('agenda', 'pode_editar');
        $this->exigirPost();

        $data = (string) ($_POST['data'] ?? '');

        if (!$this->dataValida($data)) {
            Flash::erro('Data inválida.');
            $this->redirect('mes');
        }

        $this->model->desbloquear($data);

        $this->auditar(Audit::EDITOU, 'Liberou ' . data_br($data));
        Flash::sucesso('Dia liberado.');
        $this->voltarParaOMes($data);
    }

    // ── Apoio ─────────────────────────────────────────────────────────────

    /** Competência pedida na URL, com o mês corrente como padrão. */
    private function competencia(): array
    {
        $ano = (int) ($_GET['ano'] ?? $_POST['ano'] ?? 0);
        $mes = (int) ($_GET['mes'] ?? $_POST['mes'] ?? 0);

        if ($ano < 2000 || $ano > 2100) { $ano = (int) date('Y'); }
        if ($mes < 1 || $mes > 12)      { $mes = (int) date('n'); }

        return [$ano, $mes];
    }

    /** Tudo que as três visões (calendário, lista e impressão) precisam. */
    private function dadosDoMes(): array
    {
        [$ano, $mes] = $this->competencia();

        $medicoId = (int) ($_GET['medico'] ?? 0);
        $exameId  = (int) ($_GET['exame'] ?? 0);

        $blocos = $this->model->blocosDoMes($ano, $mes, $medicoId, $exameId);

        // Agrupa por dia — as três visões percorrem o mês dia a dia
        $porDia = [];
        foreach ($blocos as $b) {
            $porDia[$b['data']][] = $b;
        }

        $inicio = sprintf('%04d-%02d-01', $ano, $mes);
        $ts     = (int) strtotime($inicio);

        return [
            'ano'          => $ano,
            'mes'          => $mes,
            'blocos'       => $blocos,
            'porDia'       => $porDia,
            'bloqueios'    => $this->model->bloqueiosDoMes($ano, $mes),
            'porMedico'    => $this->model->resumoPorMedico($ano, $mes),
            'porExame'     => $this->model->resumoPorExame($ano, $mes),
            'medicos'      => $this->medicos->ativos(),
            'exames'       => $this->exames->ativos(),
            'medicoFiltro' => $medicoId,
            'exameFiltro'  => $exameId,
            'diasNoMes'    => (int) date('t', $ts),
            'primeiroDiaW' => (int) date('w', $ts),
            'anos'         => $this->model->anosComAgenda(),
        ];
    }

    private function dataValida(string $data): bool
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
            return false;
        }
        [$a, $m, $d] = array_map('intval', explode('-', $data));
        return checkdate($m, $d, $a);
    }

    private function sanitizar(array $post): array
    {
        $turno = (string) ($post['turno'] ?? '');
        if (!array_key_exists($turno, turnos())) {
            $turno = 'manha';
        }

        return [
            'medico_id'  => (int) ($post['medico_id'] ?? 0),
            'data'       => (string) ($post['data'] ?? ''),
            'turno'      => $turno,
            'observacao' => mb_substr(trim((string) ($post['observacao'] ?? '')), 0, 300),
            'techsallus' => isset($post['techsallus']) ? 1 : 0,
            'usuario_id' => Auth::id(),
        ];
    }

    /** capacidade[exame_id] → array normalizado */
    private function vagasDoPost(array $post): array
    {
        $capacidades = (array) ($post['capacidade'] ?? []);

        $validos = array_column($this->exames->ativos(), 'id');
        $validos = array_map('intval', $validos);

        // Um exame inativo que já estava no bloco continua editável
        foreach (array_keys($capacidades) as $chave) {
            $validos[] = (int) $chave;
        }
        $validos = array_values(array_unique($validos));

        $vagas = [];
        foreach ($validos as $exameId) {
            if ($exameId <= 0) {
                continue;
            }
            $vagas[$exameId] = ['capacidade' => $this->inteiro($capacidades[$exameId] ?? 0)];
        }
        return $vagas;
    }

    private function inteiro(mixed $valor): int
    {
        $n = (int) preg_replace('/\D/', '', (string) $valor);
        return max(0, min($n, 999));
    }

    private function validar(array $dados, array $vagas, int $id = 0): array
    {
        $erros = [];

        if ($dados['medico_id'] <= 0) {
            $erros['medico_id'] = 'Escolha o médico.';
        } elseif (!$this->medicos->buscarPorId($dados['medico_id'])) {
            $erros['medico_id'] = 'Médico não encontrado.';
        }

        if (!$this->dataValida($dados['data'])) {
            $erros['data'] = 'Informe uma data válida.';
        } elseif ($dados['medico_id'] > 0
            && $this->model->blocoExiste($dados['medico_id'], $dados['data'], $dados['turno'], $id)) {
            $erros['data'] = 'Este médico já tem bloco neste dia e turno. Edite o bloco existente.';
        }

        $temCapacidade = false;
        foreach ($vagas as $exameId => $v) {
            if ($v['capacidade'] > 0) {
                $temCapacidade = true;
            }

            // Não dá para encolher a capacidade abaixo do que já foi marcado —
            // as pacientes já estão avisadas.
            if ($id > 0) {
                $jaMarcadas = $this->model->marcadasDaVaga($id, (int) $exameId);
                if ($v['capacidade'] < $jaMarcadas) {
                    $erros['vaga_' . $exameId] = 'Já há ' . $jaMarcadas . ' '
                        . ($jaMarcadas === 1 ? 'marcação' : 'marcações') . ' neste tipo de exame. '
                        . 'Cancele antes de reduzir a capacidade.';
                }
            }
        }

        if (!$temCapacidade) {
            $erros['vagas'] = 'Informe a capacidade de pelo menos um tipo de exame.';
        }

        return $erros;
    }

    private function descrever(array $dados): string
    {
        $medico = $this->medicos->buscarPorId($dados['medico_id']);
        return ($medico['nome'] ?? 'Médico') . ' — ' . data_br($dados['data'])
             . ' ' . nome_turno($dados['turno']);
    }

    /** Para onde o botão Cancelar do formulário volta. */
    private function volta(): string
    {
        $vista = (string) ($_GET['vista'] ?? $_POST['vista'] ?? 'mes');
        return $vista === 'lista' ? 'lista' : 'mes';
    }

    private function voltarParaOMes(string $data): never
    {
        $ts = (int) strtotime($data);
        $this->redirect($this->volta(), [
            'ano' => (int) date('Y', $ts),
            'mes' => (int) date('n', $ts),
        ]);
    }
}
