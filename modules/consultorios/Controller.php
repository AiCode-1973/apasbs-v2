<?php
declare(strict_types=1);

require_once BASE_PATH . '/modules/consultorios/Model.php';

class ConsultoriosController extends Controller
{
    protected string $modulo = 'consultorios';

    private ConsultorioModel $model;

    public function __construct()
    {
        $this->model = new ConsultorioModel();
    }

    // ── Mapa ──────────────────────────────────────────────────────────────

    public function mapa(): void
    {
        Auth::requirePermissao('consultorios', 'pode_ver');

        $busca = trim((string) ($_GET['q'] ?? ''));

        // Os conflitos são calculados sobre a grade inteira, não sobre o
        // filtro: um choque continua existindo mesmo quando a tela está
        // mostrando só um profissional.
        $todas     = $this->model->ocupacoes();
        $conflitos = $this->model->conflitos($todas);

        $ocupacoes = $busca !== '' ? $this->model->ocupacoes($busca) : $todas;
        $salas     = $this->model->salas();
        $dias      = $this->model->diasVisiveis($todas);

        View::render('consultorios', 'mapa', [
            'salas'          => $salas,
            'dias'           => $dias,
            'grade'          => $this->model->grade($ocupacoes),
            'conflitos'      => $conflitos,
            'emConflito'     => $this->model->idsEmConflito($conflitos),
            'resumo'         => $this->model->resumo($todas, count($salas), $dias),
            'encontradas'    => count($ocupacoes),
            'busca'          => $busca,
            'profissionais'  => array_column($this->model->profissionais(), 'nome'),
        ]);
    }

    /** Folha para imprimir e pendurar na parede do ambulatório. */
    public function imprimir(): void
    {
        Auth::requirePermissao('consultorios', 'pode_ver');

        $turno = (string) ($_GET['turno'] ?? 'todos');
        if (!in_array($turno, ['todos', 'manha', 'tarde'], true)) {
            $turno = 'todos';
        }

        $ocupacoes = $this->model->ocupacoes();
        $salas     = $this->model->salas();
        $dias      = $this->model->diasVisiveis($ocupacoes);

        $this->auditar(Audit::BAIXOU, 'Imprimiu o mapa de sala' .
            ($turno !== 'todos' ? ' — ' . nome_turno($turno) : ''));

        View::render('consultorios', 'imprimir', [
            'salas'     => $salas,
            'dias'      => $dias,
            'grade'     => $this->model->grade($ocupacoes),
            'resumo'    => $this->model->resumo($ocupacoes, count($salas), $dias),
            'conflitos' => $this->model->conflitos($ocupacoes),
            'turno'     => $turno,
        ], 'limpo');
    }

    // ── Ocupação ──────────────────────────────────────────────────────────

    public function novo(): void
    {
        Auth::requirePermissao('consultorios', 'pode_criar');

        $salas = $this->model->salas();
        if (!$salas) {
            Flash::erro('Cadastre pelo menos uma sala antes de montar o mapa.');
            $this->redirect('salas');
        }

        if (!$this->model->profissionaisParaFormulario()) {
            Flash::erro('Cadastre quem ocupa a sala antes de lançar no mapa.');
            $this->redirect('profissionais');
        }

        $this->formulario([
            'sala_id'       => (int) ($_GET['sala'] ?? $salas[0]['id']),
            'dia_semana'    => (int) ($_GET['dia'] ?? 1),
            'turno'         => in_array($_GET['turno'] ?? '', ['manha', 'tarde'], true)
                                 ? (string) $_GET['turno'] : 'manha',
            'hora_inicio'   => ($_GET['turno'] ?? 'manha') === 'tarde' ? '13:00' : '08:00',
            'hora_fim'      => ($_GET['turno'] ?? 'manha') === 'tarde' ? '17:00' : '12:00',
            'periodicidade' => 'semanal',
            'ativo'         => 1,
        ]);
    }

    public function salvar(): void
    {
        Auth::requirePermissao('consultorios', 'pode_criar');
        $this->exigirPost();

        $dados = $this->sanitizar($_POST, true);
        $erros = $this->validar($dados);

        if ($erros) {
            $this->formulario($dados, $erros);
            return;
        }

        $id = $this->model->salvarOcupacao($dados);

        $this->auditar(Audit::CRIOU, $this->descricao($dados), $id);
        Flash::sucesso('Ocupação lançada no mapa.');
        $this->redirect('mapa');
    }

    public function editar(): void
    {
        Auth::requirePermissao('consultorios', 'pode_editar');

        $o = $this->model->ocupacao($this->id());
        if (!$o) {
            Flash::erro('Ocupação não encontrada.');
            $this->redirect('mapa');
        }

        $o['hora_inicio'] = substr((string) $o['hora_inicio'], 0, 5);
        $o['hora_fim']    = substr((string) $o['hora_fim'], 0, 5);

        $this->formulario($o);
    }

    public function atualizar(): void
    {
        Auth::requirePermissao('consultorios', 'pode_editar');
        $this->exigirPost();

        $id = $this->id();
        if (!$this->model->ocupacao($id)) {
            Flash::erro('Ocupação não encontrada.');
            $this->redirect('mapa');
        }

        $dados = $this->sanitizar($_POST);
        $erros = $this->validar($dados, $id);

        if ($erros) {
            $dados['id'] = $id;
            $this->formulario($dados, $erros);
            return;
        }

        $this->model->salvarOcupacao($dados, $id);

        $this->auditar(Audit::EDITOU, $this->descricao($dados), $id);
        Flash::sucesso('Ocupação atualizada.');
        $this->redirect('mapa');
    }

    public function excluir(): void
    {
        Auth::requirePermissao('consultorios', 'pode_excluir');
        $this->exigirPost();

        $id = $this->id();
        $o  = $this->model->ocupacao($id);

        if (!$o) {
            Flash::erro('Ocupação não encontrada.');
            $this->redirect('mapa');
        }

        $this->model->excluirOcupacao($id);

        $this->auditar(Audit::EXCLUIU, $this->descricao($o), $id);
        Flash::sucesso('Ocupação removida do mapa. A sala ficou livre nesse horário.');
        $this->redirect('mapa');
    }

    // ── Salas ─────────────────────────────────────────────────────────────

    public function salas(): void
    {
        Auth::requirePermissao('consultorios', 'pode_ver');

        $editar = (int) ($_GET['editar'] ?? 0);
        $sala   = $editar > 0 ? $this->model->sala($editar) : false;

        View::render('consultorios', 'salas', [
            'salas' => $this->model->salas(false),
            'erros' => [],
            'sala'  => $sala ?: ['ordem' => $this->model->proximaOrdemSala(), 'ativo' => 1],
        ]);
    }

    public function salvar_sala(): void
    {
        Auth::requirePermissao('consultorios', 'pode_criar');
        $this->exigirPost();

        $id    = $this->id();
        $dados = [
            'nome'       => trim(preg_replace('/\s+/u', ' ', (string) ($_POST['nome'] ?? '')) ?? ''),
            'observacao' => mb_substr(trim((string) ($_POST['observacao'] ?? '')), 0, 200),
            'ordem'      => max(0, min((int) ($_POST['ordem'] ?? 0), 9999)),
            'ativo'      => isset($_POST['ativo']) ? 1 : 0,
        ];

        $erros = [];
        if ($dados['nome'] === '') {
            $erros['nome'] = 'Informe o nome da sala.';
        } elseif (mb_strlen($dados['nome']) > 60) {
            $erros['nome'] = 'No máximo 60 caracteres.';
        } elseif ($this->model->salaNomeExiste($dados['nome'], $id)) {
            $erros['nome'] = 'Já existe uma sala com este nome.';
        }

        if ($erros) {
            $dados['id'] = $id;
            View::render('consultorios', 'salas', [
                'salas' => $this->model->salas(false),
                'erros' => $erros,
                'sala'  => $dados,
            ]);
            return;
        }

        $novoId = $this->model->salvarSala($dados, $id);

        $this->auditar($id > 0 ? Audit::EDITOU : Audit::CRIOU, 'Sala ' . $dados['nome'], $novoId);
        Flash::sucesso($id > 0 ? 'Sala atualizada.' : 'Sala cadastrada.');
        $this->redirect('salas');
    }

    public function excluir_sala(): void
    {
        Auth::requirePermissao('consultorios', 'pode_excluir');
        $this->exigirPost();

        $id   = $this->id();
        $sala = $this->model->sala($id);

        if (!$sala) {
            Flash::erro('Sala não encontrada.');
            $this->redirect('salas');
        }

        $this->model->excluirSala($id);

        $this->auditar(Audit::EXCLUIU, 'Sala ' . $sala['nome'], $id);
        Flash::sucesso('Sala excluída junto com as ocupações que estavam nela.');
        $this->redirect('salas');
    }

    // ── Profissionais ─────────────────────────────────────────────────────

    public function profissionais(): void
    {
        Auth::requirePermissao('consultorios', 'pode_ver');

        $busca  = trim((string) ($_GET['q'] ?? ''));
        $editar = (int) ($_GET['editar'] ?? 0);
        $prof   = $editar > 0 ? $this->model->profissional($editar) : false;

        View::render('consultorios', 'profissionais', [
            'profissionais'  => $this->model->profissionais(false, $busca),
            'especialidades' => $this->model->especialidades(),
            'busca'          => $busca,
            'erros'          => [],
            'profissional'   => $prof ?: ['ativo' => 1, 'atividade' => 0],
        ]);
    }

    public function salvar_profissional(): void
    {
        Auth::requirePermissao('consultorios', 'pode_criar');
        $this->exigirPost();

        $id    = $this->id();
        $dados = [
            'nome'             => trim(preg_replace('/\s+/u', ' ', (string) ($_POST['nome'] ?? '')) ?? ''),
            'especialidade_id' => (int) ($_POST['especialidade_id'] ?? 0),
            'atividade'        => isset($_POST['atividade']) ? 1 : 0,
            'observacao'       => mb_substr(trim((string) ($_POST['observacao'] ?? '')), 0, 200),
            'ativo'            => isset($_POST['ativo']) ? 1 : 0,
        ];

        $erros = [];
        if ($dados['nome'] === '') {
            $erros['nome'] = 'Informe o nome.';
        } elseif (mb_strlen($dados['nome']) > 120) {
            $erros['nome'] = 'No máximo 120 caracteres.';
        } elseif ($this->model->profissionalNomeExiste($dados['nome'], $id)) {
            $erros['nome'] = 'Já existe alguém com este nome no cadastro.';
        }

        if ($dados['especialidade_id'] > 0 && !$this->model->especialidade($dados['especialidade_id'])) {
            $erros['especialidade_id'] = 'Especialidade não encontrada.';
        }

        if ($erros) {
            $dados['id'] = $id;
            View::render('consultorios', 'profissionais', [
                'profissionais'  => $this->model->profissionais(false),
                'especialidades' => $this->model->especialidades(),
                'busca'          => '',
                'erros'          => $erros,
                'profissional'   => $dados,
            ]);
            return;
        }

        $novoId = $this->model->salvarProfissional($dados, $id);

        $this->auditar($id > 0 ? Audit::EDITOU : Audit::CRIOU, 'Profissional ' . $dados['nome'], $novoId);
        Flash::sucesso($id > 0 ? 'Cadastro atualizado.' : 'Cadastrado. Já pode ser lançado no mapa.');
        $this->redirect('profissionais');
    }

    public function excluir_profissional(): void
    {
        Auth::requirePermissao('consultorios', 'pode_excluir');
        $this->exigirPost();

        $id   = $this->id();
        $prof = $this->model->profissional($id);

        if (!$prof) {
            Flash::erro('Cadastro não encontrado.');
            $this->redirect('profissionais');
        }

        $this->model->excluirProfissional($id);

        $this->auditar(Audit::EXCLUIU, 'Profissional ' . $prof['nome'], $id);
        Flash::sucesso('Excluído do cadastro, junto com as ocupações que estavam no nome dele.');
        $this->redirect('profissionais');
    }

    // ── Especialidades ────────────────────────────────────────────────────

    public function especialidades(): void
    {
        Auth::requirePermissao('consultorios', 'pode_ver');

        $editar = (int) ($_GET['editar'] ?? 0);
        $esp    = $editar > 0 ? $this->model->especialidade($editar) : false;

        View::render('consultorios', 'especialidades', [
            'especialidades' => $this->model->especialidades(false),
            'erros'          => [],
            'especialidade'  => $esp ?: ['ativo' => 1],
        ]);
    }

    public function salvar_especialidade(): void
    {
        Auth::requirePermissao('consultorios', 'pode_criar');
        $this->exigirPost();

        $id    = $this->id();
        $dados = [
            'nome'       => trim(preg_replace('/\s+/u', ' ', (string) ($_POST['nome'] ?? '')) ?? ''),
            'observacao' => mb_substr(trim((string) ($_POST['observacao'] ?? '')), 0, 200),
            'ativo'      => isset($_POST['ativo']) ? 1 : 0,
        ];

        $erros = [];
        if ($dados['nome'] === '') {
            $erros['nome'] = 'Informe o nome da especialidade.';
        } elseif (mb_strlen($dados['nome']) > 80) {
            $erros['nome'] = 'No máximo 80 caracteres.';
        } elseif ($this->model->especialidadeNomeExiste($dados['nome'], $id)) {
            $erros['nome'] = 'Já existe uma especialidade com este nome.';
        }

        if ($erros) {
            $dados['id'] = $id;
            View::render('consultorios', 'especialidades', [
                'especialidades' => $this->model->especialidades(false),
                'erros'          => $erros,
                'especialidade'  => $dados,
            ]);
            return;
        }

        $novoId = $this->model->salvarEspecialidade($dados, $id);

        $this->auditar($id > 0 ? Audit::EDITOU : Audit::CRIOU, 'Especialidade ' . $dados['nome'], $novoId);
        Flash::sucesso($id > 0 ? 'Especialidade atualizada.' : 'Especialidade cadastrada.');
        $this->redirect('especialidades');
    }

    public function excluir_especialidade(): void
    {
        Auth::requirePermissao('consultorios', 'pode_excluir');
        $this->exigirPost();

        $id  = $this->id();
        $esp = $this->model->especialidade($id);

        if (!$esp) {
            Flash::erro('Especialidade não encontrada.');
            $this->redirect('especialidades');
        }

        $this->model->excluirEspecialidade($id);

        $this->auditar(Audit::EXCLUIU, 'Especialidade ' . $esp['nome'], $id);
        Flash::sucesso('Especialidade excluída. Quem estava nela ficou sem especialidade, mas continua no mapa.');
        $this->redirect('especialidades');
    }

    // ── Apoio ─────────────────────────────────────────────────────────────

    private function formulario(array $dados, array $erros = []): void
    {
        View::render('consultorios', 'form', [
            'ocupacao'       => $dados,
            'erros'          => $erros,
            'salas'          => $this->model->salas(),
            'profissionais'  => $this->model->profissionaisParaFormulario((int) ($dados['profissional_id'] ?? 0)),
            'especialidades' => $this->model->especialidades(),
        ]);
    }

    private function sanitizar(array $post, bool $criando = false): array
    {
        $hora = function (string $valor, string $padrao): string {
            $valor = trim($valor);
            return preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $valor) ? $valor : $padrao;
        };

        $turno = in_array($post['turno'] ?? '', ['manha', 'tarde'], true)
            ? (string) $post['turno'] : 'manha';

        $dia = (int) ($post['dia_semana'] ?? 1);

        return [
            'sala_id'          => (int) ($post['sala_id'] ?? 0),
            'profissional_id'  => (int) ($post['profissional_id'] ?? 0),
            'especialidade_id' => (int) ($post['especialidade_id'] ?? 0),
            'dia_semana'       => ($dia >= 0 && $dia <= 6) ? $dia : 1,
            'turno'            => $turno,
            'hora_inicio'      => $hora((string) ($post['hora_inicio'] ?? ''), $turno === 'tarde' ? '13:00' : '08:00'),
            'hora_fim'         => $hora((string) ($post['hora_fim'] ?? ''), $turno === 'tarde' ? '17:00' : '12:00'),
            'periodicidade'    => isset(ConsultorioModel::PERIODICIDADES[$post['periodicidade'] ?? ''])
                                    ? (string) $post['periodicidade'] : 'semanal',
            'observacao'       => mb_substr(trim((string) ($post['observacao'] ?? '')), 0, 200),
            'ativo'            => $criando ? 1 : (isset($post['ativo']) ? 1 : 0),
        ];
    }

    private function validar(array $d, int $id = 0): array
    {
        $erros = [];

        if (!$this->model->profissional($d['profissional_id'])) {
            $erros['profissional_id'] = 'Escolha quem ocupa a sala.';
        }

        if ($d['especialidade_id'] > 0 && !$this->model->especialidade($d['especialidade_id'])) {
            $erros['especialidade_id'] = 'Especialidade não encontrada.';
        }

        if (!$this->model->sala($d['sala_id'])) {
            $erros['sala_id'] = 'Escolha uma sala.';
        }

        if ($d['hora_fim'] <= $d['hora_inicio']) {
            $erros['hora_fim'] = 'O fim precisa ser depois do início.';
        }

        if (!$erros && $this->model->duplicada($d, $id)) {
            $erros['profissional_id'] = 'Esta ocupação já está lançada nesta sala, dia e horário.';
        }

        return $erros;
    }

    private function descricao(array $d): string
    {
        $sala  = $this->model->sala((int) $d['sala_id']);
        $prof  = $d['profissional']
            ?? ($this->model->profissional((int) ($d['profissional_id'] ?? 0))['nome'] ?? '?');

        return $prof
            . ' — ' . ($sala['nome'] ?? 'sala ?')
            . ', ' . (dias_semana()[(int) $d['dia_semana']] ?? '?')
            . ' ' . nome_turno((string) $d['turno'])
            . ', ' . substr((string) $d['hora_inicio'], 0, 5)
            . ' às ' . substr((string) $d['hora_fim'], 0, 5);
    }
}
