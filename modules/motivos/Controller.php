<?php
declare(strict_types=1);

require_once BASE_PATH . '/modules/motivos/Model.php';

class MotivosController extends Controller
{
    protected string $modulo = 'motivos';

    private MotivoModel $model;

    public function __construct()
    {
        $this->model = new MotivoModel();
    }

    public function lista(): void
    {
        Auth::requirePermissao('motivos', 'pode_ver');

        $busca = trim((string) ($_GET['q'] ?? ''));

        View::render('motivos', 'lista', [
            'motivos' => $this->model->listar($busca),
            'busca'   => $busca,
        ]);
    }

    public function novo(): void
    {
        Auth::requirePermissao('motivos', 'pode_criar');

        View::render('motivos', 'form', ['motivo' => null, 'erros' => []]);
    }

    public function salvar(): void
    {
        Auth::requirePermissao('motivos', 'pode_criar');
        $this->exigirPost();

        $dados = $this->sanitizar($_POST, true);
        $erros = $this->validar($dados);

        if ($erros) {
            View::render('motivos', 'form', ['motivo' => $dados, 'erros' => $erros]);
            return;
        }

        $id = $this->model->criar($dados);

        $this->auditar(Audit::CRIOU, 'Motivo ' . $dados['codigo'] . ' — ' . $dados['nome'], $id);
        Flash::sucesso('Motivo cadastrado com sucesso.');
        $this->redirect();
    }

    public function editar(): void
    {
        Auth::requirePermissao('motivos', 'pode_editar');

        $motivo = $this->model->buscarPorId($this->id());

        if (!$motivo) {
            Flash::erro('Motivo não encontrado.');
            $this->redirect();
        }

        View::render('motivos', 'form', ['motivo' => $motivo, 'erros' => []]);
    }

    public function atualizar(): void
    {
        Auth::requirePermissao('motivos', 'pode_editar');
        $this->exigirPost();

        $id = $this->id();

        if (!$this->model->buscarPorId($id)) {
            Flash::erro('Motivo não encontrado.');
            $this->redirect();
        }

        $dados = $this->sanitizar($_POST);
        $erros = $this->validar($dados, $id);

        if ($erros) {
            $dados['id'] = $id;
            View::render('motivos', 'form', ['motivo' => $dados, 'erros' => $erros]);
            return;
        }

        $this->model->atualizar($id, $dados);

        $this->auditar(Audit::EDITOU, 'Motivo ' . $dados['codigo'] . ' — ' . $dados['nome'], $id);
        Flash::sucesso('Motivo atualizado com sucesso.');
        $this->redirect();
    }

    public function excluir(): void
    {
        Auth::requirePermissao('motivos', 'pode_excluir');
        $this->exigirPost();

        $id     = $this->id();
        $motivo = $this->model->buscarPorId($id);

        if (!$motivo) {
            Flash::erro('Motivo não encontrado.');
            $this->redirect();
        }

        if ($this->model->possuiLancamentos($id)) {
            Flash::aviso(
                'Não é possível excluir: este motivo já tem quantidade lançada em algum fechamento. '
                . 'Marque-o como inativo para tirá-lo das próximas listagens.'
            );
            $this->redirect();
        }

        $this->model->excluir($id);

        $this->auditar(Audit::EXCLUIU, 'Motivo ' . $motivo['codigo'] . ' — ' . $motivo['nome'], $id);
        Flash::sucesso('Motivo excluído.');
        $this->redirect();
    }

    // ── Apoio ─────────────────────────────────────────────────────────────

    private function sanitizar(array $post, bool $criando = false): array
    {
        // Código sempre em caixa alta, sem espaço — fica estável em relatório
        $codigo = mb_strtoupper(trim((string) ($post['codigo'] ?? '')));
        $codigo = preg_replace('/[^A-Z0-9._\-]/u', '', $codigo) ?? '';

        return [
            'codigo'    => $codigo,
            'nome'      => trim((string) ($post['nome'] ?? '')),
            'descricao' => trim((string) ($post['descricao'] ?? '')),
            'ativo'     => $criando ? 1 : (isset($post['ativo']) ? 1 : 0),
        ];
    }

    private function validar(array $dados, int $id = 0): array
    {
        $erros = [];

        if ($dados['codigo'] === '') {
            $erros['codigo'] = 'Código é obrigatório.';
        } elseif (mb_strlen($dados['codigo']) > 30) {
            $erros['codigo'] = 'Código deve ter no máximo 30 caracteres.';
        } elseif ($this->model->codigoExiste($dados['codigo'], $id)) {
            $erros['codigo'] = 'Já existe um motivo com este código.';
        }

        if ($dados['nome'] === '') {
            $erros['nome'] = 'Nome é obrigatório.';
        } elseif (mb_strlen($dados['nome']) > 150) {
            $erros['nome'] = 'Nome deve ter no máximo 150 caracteres.';
        } elseif ($this->model->nomeExiste($dados['nome'], $id)) {
            $erros['nome'] = 'Já existe um motivo com este nome.';
        }

        if (mb_strlen($dados['descricao']) > 500) {
            $erros['descricao'] = 'Descrição deve ter no máximo 500 caracteres.';
        }

        return $erros;
    }
}
