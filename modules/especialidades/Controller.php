<?php
declare(strict_types=1);

require_once BASE_PATH . '/modules/especialidades/Model.php';

class EspecialidadesController extends Controller
{
    protected string $modulo = 'especialidades';

    private EspecialidadeModel $model;

    public function __construct()
    {
        $this->model = new EspecialidadeModel();
    }

    public function lista(): void
    {
        Auth::requirePermissao('especialidades', 'pode_ver');

        View::render('especialidades', 'lista', [
            'especialidades' => $this->model->listar(),
        ]);
    }

    public function novo(): void
    {
        Auth::requirePermissao('especialidades', 'pode_criar');

        View::render('especialidades', 'form', [
            'especialidade' => null,
            'erros'         => [],
        ]);
    }

    public function salvar(): void
    {
        Auth::requirePermissao('especialidades', 'pode_criar');
        $this->exigirPost();

        $dados = $this->sanitizar($_POST, true);
        $erros = $this->validar($dados);

        if ($erros) {
            View::render('especialidades', 'form', ['especialidade' => $dados, 'erros' => $erros]);
            return;
        }

        $id = $this->model->criar($dados);

        $this->auditar(Audit::CRIOU, 'Especialidade ' . $dados['nome'], $id);
        Flash::sucesso('Especialidade cadastrada com sucesso.');
        $this->redirect();
    }

    public function editar(): void
    {
        Auth::requirePermissao('especialidades', 'pode_editar');

        $especialidade = $this->model->buscarPorId($this->id());

        if (!$especialidade) {
            Flash::erro('Especialidade não encontrada.');
            $this->redirect();
        }

        View::render('especialidades', 'form', ['especialidade' => $especialidade, 'erros' => []]);
    }

    public function atualizar(): void
    {
        Auth::requirePermissao('especialidades', 'pode_editar');
        $this->exigirPost();

        $id = $this->id();

        if (!$this->model->buscarPorId($id)) {
            Flash::erro('Especialidade não encontrada.');
            $this->redirect();
        }

        $dados = $this->sanitizar($_POST);
        $erros = $this->validar($dados, $id);

        if ($erros) {
            $dados['id'] = $id;
            View::render('especialidades', 'form', ['especialidade' => $dados, 'erros' => $erros]);
            return;
        }

        $this->model->atualizar($id, $dados);

        $this->auditar(Audit::EDITOU, 'Especialidade ' . $dados['nome'], $id);
        Flash::sucesso('Especialidade atualizada com sucesso.');
        $this->redirect();
    }

    public function excluir(): void
    {
        Auth::requirePermissao('especialidades', 'pode_excluir');
        $this->exigirPost();

        $id            = $this->id();
        $especialidade = $this->model->buscarPorId($id);

        if (!$especialidade) {
            Flash::erro('Especialidade não encontrada.');
            $this->redirect();
        }

        if ($this->model->possuiCirurgias($id)) {
            Flash::aviso('Não é possível excluir: existem cirurgias registradas nesta especialidade.');
            $this->redirect();
        }

        $this->model->excluir($id);

        $this->auditar(Audit::EXCLUIU, 'Especialidade ' . $especialidade['nome'], $id);
        Flash::sucesso('Especialidade excluída.');
        $this->redirect();
    }

    // ── Apoio ─────────────────────────────────────────────────────────────

    private function sanitizar(array $post, bool $criando = false): array
    {
        return [
            'nome'  => trim((string) ($post['nome'] ?? '')),
            'ativo' => $criando ? 1 : (isset($post['ativo']) ? 1 : 0),
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
            $erros['nome'] = 'Já existe uma especialidade com este nome.';
        }

        return $erros;
    }
}
