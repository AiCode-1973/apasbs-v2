<?php
declare(strict_types=1);

require_once BASE_PATH . '/modules/exames/Model.php';

class ExamesController extends Controller
{
    protected string $modulo = 'exames';

    private ExameModel $model;

    public function __construct()
    {
        $this->model = new ExameModel();
    }

    public function lista(): void
    {
        Auth::requirePermissao('exames', 'pode_ver');

        $busca = trim((string) ($_GET['q'] ?? ''));

        View::render('exames', 'lista', [
            'exames' => $this->model->listar($busca),
            'busca'  => $busca,
        ]);
    }

    public function novo(): void
    {
        Auth::requirePermissao('exames', 'pode_criar');

        View::render('exames', 'form', ['exame' => null, 'erros' => []]);
    }

    public function salvar(): void
    {
        Auth::requirePermissao('exames', 'pode_criar');
        $this->exigirPost();

        $dados = $this->sanitizar($_POST, true);
        $erros = $this->validar($dados);

        if ($erros) {
            View::render('exames', 'form', ['exame' => $dados, 'erros' => $erros]);
            return;
        }

        $id = $this->model->criar($dados);

        $this->auditar(Audit::CRIOU, 'Tipo de exame ' . $dados['codigo'] . ' — ' . $dados['nome'], $id);
        Flash::sucesso('Tipo de exame cadastrado com sucesso.');
        $this->redirect();
    }

    public function editar(): void
    {
        Auth::requirePermissao('exames', 'pode_editar');

        $exame = $this->model->buscarPorId($this->id());

        if (!$exame) {
            Flash::erro('Tipo de exame não encontrado.');
            $this->redirect();
        }

        View::render('exames', 'form', ['exame' => $exame, 'erros' => []]);
    }

    public function atualizar(): void
    {
        Auth::requirePermissao('exames', 'pode_editar');
        $this->exigirPost();

        $id = $this->id();

        if (!$this->model->buscarPorId($id)) {
            Flash::erro('Tipo de exame não encontrado.');
            $this->redirect();
        }

        $dados = $this->sanitizar($_POST);
        $erros = $this->validar($dados, $id);

        if ($erros) {
            $dados['id'] = $id;
            View::render('exames', 'form', ['exame' => $dados, 'erros' => $erros]);
            return;
        }

        $this->model->atualizar($id, $dados);

        $this->auditar(Audit::EDITOU, 'Tipo de exame ' . $dados['codigo'] . ' — ' . $dados['nome'], $id);
        Flash::sucesso('Tipo de exame atualizado com sucesso.');
        $this->redirect();
    }

    public function excluir(): void
    {
        Auth::requirePermissao('exames', 'pode_excluir');
        $this->exigirPost();

        $id    = $this->id();
        $exame = $this->model->buscarPorId($id);

        if (!$exame) {
            Flash::erro('Tipo de exame não encontrado.');
            $this->redirect();
        }

        if ($this->model->possuiVagas($id)) {
            Flash::aviso(
                'Não é possível excluir: este tipo de exame já tem vagas lançadas na agenda. '
                . 'Marque-o como inativo para tirá-lo dos próximos lançamentos.'
            );
            $this->redirect();
        }

        $this->model->excluir($id);

        $this->auditar(Audit::EXCLUIU, 'Tipo de exame ' . $exame['codigo'] . ' — ' . $exame['nome'], $id);
        Flash::sucesso('Tipo de exame excluído.');
        $this->redirect();
    }

    // ── Apoio ─────────────────────────────────────────────────────────────

    private function sanitizar(array $post, bool $criando = false): array
    {
        $codigo = mb_strtoupper(trim((string) ($post['codigo'] ?? '')));
        $codigo = preg_replace('/[^A-Z0-9._\-]/u', '', $codigo) ?? '';

        $ordem = (int) ($post['ordem'] ?? 0);
        if ($ordem < 0)    { $ordem = 0; }
        if ($ordem > 999)  { $ordem = 999; }

        return [
            'codigo'    => $codigo,
            'nome'      => trim((string) ($post['nome'] ?? '')),
            'descricao' => trim((string) ($post['descricao'] ?? '')),
            'ordem'     => $ordem,
            'ativo'     => $criando ? 1 : (isset($post['ativo']) ? 1 : 0),
        ];
    }

    private function validar(array $dados, int $id = 0): array
    {
        $erros = [];

        if ($dados['codigo'] === '') {
            $erros['codigo'] = 'Código é obrigatório.';
        } elseif (mb_strlen($dados['codigo']) > 20) {
            $erros['codigo'] = 'Código deve ter no máximo 20 caracteres.';
        } elseif ($this->model->codigoExiste($dados['codigo'], $id)) {
            $erros['codigo'] = 'Já existe um tipo de exame com este código.';
        }

        if ($dados['nome'] === '') {
            $erros['nome'] = 'Nome é obrigatório.';
        } elseif (mb_strlen($dados['nome']) > 120) {
            $erros['nome'] = 'Nome deve ter no máximo 120 caracteres.';
        } elseif ($this->model->nomeExiste($dados['nome'], $id)) {
            $erros['nome'] = 'Já existe um tipo de exame com este nome.';
        }

        if (mb_strlen($dados['descricao']) > 300) {
            $erros['descricao'] = 'Descrição deve ter no máximo 300 caracteres.';
        }

        return $erros;
    }
}
