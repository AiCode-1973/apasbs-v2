<?php
declare(strict_types=1);

require_once BASE_PATH . '/modules/setores/Model.php';

class SetoresController extends Controller
{
    protected string $modulo = 'setores';

    private SetorModel $model;

    public function __construct()
    {
        $this->model = new SetorModel();
    }

    public function lista(): void
    {
        Auth::requirePermissao('setores', 'pode_ver');

        View::render('setores', 'lista', [
            'setores' => $this->model->listar(),
        ]);
    }

    public function novo(): void
    {
        Auth::requirePermissao('setores', 'pode_criar');

        View::render('setores', 'form', [
            'setor' => null,
            'erros' => [],
        ]);
    }

    public function salvar(): void
    {
        Auth::requirePermissao('setores', 'pode_criar');
        $this->exigirPost();

        $dados = $this->sanitizar($_POST, true);
        $erros = $this->validar($dados);

        if ($erros) {
            View::render('setores', 'form', ['setor' => $dados, 'erros' => $erros]);
            return;
        }

        $id = $this->model->criar($dados);

        $this->auditar(Audit::CRIOU, 'Setor ' . $dados['nome'], $id);
        Flash::sucesso('Setor cadastrado com sucesso.');
        $this->redirect();
    }

    public function editar(): void
    {
        Auth::requirePermissao('setores', 'pode_editar');

        $setor = $this->model->buscarPorId($this->id());

        if (!$setor) {
            Flash::erro('Setor não encontrado.');
            $this->redirect();
        }

        View::render('setores', 'form', ['setor' => $setor, 'erros' => []]);
    }

    public function atualizar(): void
    {
        Auth::requirePermissao('setores', 'pode_editar');
        $this->exigirPost();

        $id = $this->id();

        if (!$this->model->buscarPorId($id)) {
            Flash::erro('Setor não encontrado.');
            $this->redirect();
        }

        $dados = $this->sanitizar($_POST);
        $erros = $this->validar($dados, $id);

        if ($erros) {
            $dados['id'] = $id;
            View::render('setores', 'form', ['setor' => $dados, 'erros' => $erros]);
            return;
        }

        $this->model->atualizar($id, $dados);

        $this->auditar(Audit::EDITOU, 'Setor ' . $dados['nome'], $id);
        Flash::sucesso('Setor atualizado com sucesso.');
        $this->redirect();
    }

    public function excluir(): void
    {
        Auth::requirePermissao('setores', 'pode_excluir');
        $this->exigirPost();

        $id    = $this->id();
        $setor = $this->model->buscarPorId($id);

        if (!$setor) {
            Flash::erro('Setor não encontrado.');
            $this->redirect();
        }

        if ($this->model->possuiUsuarios($id)) {
            Flash::aviso('Não é possível excluir: existem usuários vinculados a este setor.');
            $this->redirect();
        }

        $this->model->excluir($id);

        $this->auditar(Audit::EXCLUIU, 'Setor ' . $setor['nome'], $id);
        Flash::sucesso('Setor excluído.');
        $this->redirect();
    }

    // ── Apoio ─────────────────────────────────────────────────────────────

    private function sanitizar(array $post, bool $criando = false): array
    {
        return [
            'nome'  => trim((string) ($post['nome'] ?? '')),
            // Na criação não há checkbox: o setor nasce sempre ativo
            'ativo' => $criando ? 1 : (isset($post['ativo']) ? 1 : 0),
        ];
    }

    private function validar(array $dados, int $id = 0): array
    {
        $erros = [];

        if ($dados['nome'] === '') {
            $erros['nome'] = 'Nome é obrigatório.';
        } elseif (mb_strlen($dados['nome']) > 100) {
            $erros['nome'] = 'Nome deve ter no máximo 100 caracteres.';
        } elseif ($this->model->nomeExiste($dados['nome'], $id)) {
            $erros['nome'] = 'Já existe um setor com este nome.';
        }

        return $erros;
    }
}
