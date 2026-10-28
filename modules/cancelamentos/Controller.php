<?php
declare(strict_types=1);

require_once BASE_PATH . '/modules/cancelamentos/Model.php';

class CancelamentosController extends Controller
{
    protected string $modulo = 'cancelamentos';

    private CancelamentoMotivoModel $model;

    public function __construct()
    {
        $this->model = new CancelamentoMotivoModel();
    }

    public function lista(): void
    {
        Auth::requirePermissao('cancelamentos', 'pode_ver');

        $busca = trim((string) ($_GET['q'] ?? ''));

        View::render('cancelamentos', 'lista', [
            'motivos' => $this->model->listar($busca),
            'busca'   => $busca,
        ]);
    }

    public function novo(): void
    {
        Auth::requirePermissao('cancelamentos', 'pode_criar');

        View::render('cancelamentos', 'form', [
            'motivo' => ['ordem' => $this->model->proximaOrdem()],
            'erros'  => [],
        ]);
    }

    public function salvar(): void
    {
        Auth::requirePermissao('cancelamentos', 'pode_criar');
        $this->exigirPost();

        $dados = $this->sanitizar($_POST, true);
        $erros = $this->validar($dados);

        if ($erros) {
            View::render('cancelamentos', 'form', ['motivo' => $dados, 'erros' => $erros]);
            return;
        }

        $id = $this->model->criar($dados);

        $this->auditar(Audit::CRIOU, 'Motivo de cancelamento: ' . $dados['nome'], $id);
        Flash::sucesso('Mensagem cadastrada. Já aparece para as atendentes.');
        $this->redirect();
    }

    public function editar(): void
    {
        Auth::requirePermissao('cancelamentos', 'pode_editar');

        $motivo = $this->model->buscarPorId($this->id());

        if (!$motivo) {
            Flash::erro('Mensagem não encontrada.');
            $this->redirect();
        }

        View::render('cancelamentos', 'form', ['motivo' => $motivo, 'erros' => []]);
    }

    public function atualizar(): void
    {
        Auth::requirePermissao('cancelamentos', 'pode_editar');
        $this->exigirPost();

        $id = $this->id();

        if (!$this->model->buscarPorId($id)) {
            Flash::erro('Mensagem não encontrada.');
            $this->redirect();
        }

        $dados = $this->sanitizar($_POST);
        $erros = $this->validar($dados, $id);

        if ($erros) {
            $dados['id'] = $id;
            View::render('cancelamentos', 'form', ['motivo' => $dados, 'erros' => $erros]);
            return;
        }

        $this->model->atualizar($id, $dados);

        $this->auditar(Audit::EDITOU, 'Motivo de cancelamento: ' . $dados['nome'], $id);
        Flash::sucesso('Mensagem atualizada.');
        $this->redirect();
    }

    public function excluir(): void
    {
        Auth::requirePermissao('cancelamentos', 'pode_excluir');
        $this->exigirPost();

        $id     = $this->id();
        $motivo = $this->model->buscarPorId($id);

        if (!$motivo) {
            Flash::erro('Mensagem não encontrada.');
            $this->redirect();
        }

        if ($this->model->possuiUsos($id)) {
            Flash::aviso(
                'Não é possível excluir: esta mensagem já foi usada em cancelamentos. '
                . 'Marque-a como inativa para tirá-la da lista sem apagar o histórico.'
            );
            $this->redirect();
        }

        $this->model->excluir($id);

        $this->auditar(Audit::EXCLUIU, 'Motivo de cancelamento: ' . $motivo['nome'], $id);
        Flash::sucesso('Mensagem excluída.');
        $this->redirect();
    }

    // ── Apoio ─────────────────────────────────────────────────────────────

    private function sanitizar(array $post, bool $criando = false): array
    {
        // Espaços repetidos viram um só: senão "Paciente  desistiu" e
        // "Paciente desistiu" viram duas mensagens que parecem iguais na tela.
        $nome = trim(preg_replace('/\s+/u', ' ', (string) ($post['nome'] ?? '')) ?? '');

        $ordem = (int) ($post['ordem'] ?? 0);
        $ordem = max(0, min($ordem, 9999));

        return [
            'nome'  => $nome,
            'ordem' => $ordem,
            'ativo' => $criando ? 1 : (isset($post['ativo']) ? 1 : 0),
        ];
    }

    private function validar(array $dados, int $id = 0): array
    {
        $erros = [];

        if ($dados['nome'] === '') {
            $erros['nome'] = 'Escreva a mensagem.';
        } elseif (mb_strlen($dados['nome']) > 150) {
            $erros['nome'] = 'A mensagem deve ter no máximo 150 caracteres.';
        } elseif ($this->model->nomeExiste($dados['nome'], $id)) {
            $erros['nome'] = 'Já existe uma mensagem com este texto.';
        }

        return $erros;
    }
}
