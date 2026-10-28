<?php
declare(strict_types=1);

require_once BASE_PATH . '/modules/medicos/Model.php';

class MedicosController extends Controller
{
    protected string $modulo = 'medicos';

    private MedicoModel $model;

    public function __construct()
    {
        $this->model = new MedicoModel();
    }

    public function lista(): void
    {
        Auth::requirePermissao('medicos', 'pode_ver');

        $busca = trim((string) ($_GET['q'] ?? ''));

        View::render('medicos', 'lista', [
            'medicos' => $this->model->listar($busca),
            'busca'   => $busca,
        ]);
    }

    public function novo(): void
    {
        Auth::requirePermissao('medicos', 'pode_criar');

        View::render('medicos', 'form', [
            'medico'     => ['cor' => $this->model->proximaCorLivre()],
            'erros'      => [],
            'coresEmUso' => $this->model->coresEmUso(),
        ]);
    }

    public function salvar(): void
    {
        Auth::requirePermissao('medicos', 'pode_criar');
        $this->exigirPost();

        $dados = $this->sanitizar($_POST, true);
        $erros = $this->validar($dados);

        if ($erros) {
            View::render('medicos', 'form', [
                'medico'     => $dados,
                'erros'      => $erros,
                'coresEmUso' => $this->model->coresEmUso(),
            ]);
            return;
        }

        $id = $this->model->criar($dados);

        $this->auditar(Audit::CRIOU, 'Médico ' . $dados['nome'], $id);
        Flash::sucesso('Médico cadastrado com sucesso.');
        $this->redirect();
    }

    public function editar(): void
    {
        Auth::requirePermissao('medicos', 'pode_editar');

        $id     = $this->id();
        $medico = $this->model->buscarPorId($id);

        if (!$medico) {
            Flash::erro('Médico não encontrado.');
            $this->redirect();
        }

        View::render('medicos', 'form', [
            'medico'     => $medico,
            'erros'      => [],
            'coresEmUso' => $this->model->coresEmUso($id),
        ]);
    }

    public function atualizar(): void
    {
        Auth::requirePermissao('medicos', 'pode_editar');
        $this->exigirPost();

        $id = $this->id();

        if (!$this->model->buscarPorId($id)) {
            Flash::erro('Médico não encontrado.');
            $this->redirect();
        }

        $dados = $this->sanitizar($_POST);
        $erros = $this->validar($dados, $id);

        if ($erros) {
            $dados['id'] = $id;
            View::render('medicos', 'form', [
                'medico'     => $dados,
                'erros'      => $erros,
                'coresEmUso' => $this->model->coresEmUso($id),
            ]);
            return;
        }

        $this->model->atualizar($id, $dados);

        $this->auditar(Audit::EDITOU, 'Médico ' . $dados['nome'], $id);
        Flash::sucesso('Médico atualizado com sucesso.');
        $this->redirect();
    }

    public function excluir(): void
    {
        Auth::requirePermissao('medicos', 'pode_excluir');
        $this->exigirPost();

        $id     = $this->id();
        $medico = $this->model->buscarPorId($id);

        if (!$medico) {
            Flash::erro('Médico não encontrado.');
            $this->redirect();
        }

        if ($this->model->possuiAgenda($id)) {
            Flash::aviso(
                'Não é possível excluir: este médico já tem blocos lançados na agenda. '
                . 'Marque-o como inativo para tirá-lo das próximas escalas sem perder o histórico.'
            );
            $this->redirect();
        }

        $this->model->excluir($id);

        $this->auditar(Audit::EXCLUIU, 'Médico ' . $medico['nome'], $id);
        Flash::sucesso('Médico excluído.');
        $this->redirect();
    }

    // ── Apoio ─────────────────────────────────────────────────────────────

    private function sanitizar(array $post, bool $criando = false): array
    {
        $cor = (int) ($post['cor'] ?? 1);
        if ($cor < 1 || $cor > MedicoModel::CORES) {
            $cor = 1;
        }

        return [
            'nome'          => trim((string) ($post['nome'] ?? '')),
            'crm'           => trim((string) ($post['crm'] ?? '')),
            'especialidade' => trim((string) ($post['especialidade'] ?? '')),
            'cor'           => $cor,
            'ativo'         => $criando ? 1 : (isset($post['ativo']) ? 1 : 0),
        ];
    }

    private function validar(array $dados, int $id = 0): array
    {
        $erros = [];

        if ($dados['nome'] === '') {
            $erros['nome'] = 'Nome é obrigatório.';
        } elseif (mb_strlen($dados['nome']) > 150) {
            $erros['nome'] = 'Nome deve ter no máximo 150 caracteres.';
        } elseif ($this->model->nomeExiste($dados['nome'], $id)) {
            $erros['nome'] = 'Já existe um médico com este nome.';
        }

        if (mb_strlen($dados['crm']) > 20) {
            $erros['crm'] = 'CRM deve ter no máximo 20 caracteres.';
        }

        if (mb_strlen($dados['especialidade']) > 120) {
            $erros['especialidade'] = 'Especialidade deve ter no máximo 120 caracteres.';
        }

        return $erros;
    }
}
