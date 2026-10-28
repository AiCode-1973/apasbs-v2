<?php
declare(strict_types=1);

require_once BASE_PATH . '/modules/auditoria/Model.php';

class AuditoriaController extends Controller
{
    protected string $modulo = 'auditoria';

    private AuditoriaModel $model;

    public function __construct()
    {
        $this->model = new AuditoriaModel();
    }

    public function lista(): void
    {
        Auth::requirePermissao('auditoria', 'pode_ver');

        $filtros = [
            'q'      => trim((string) ($_GET['q'] ?? '')),
            'modulo' => $this->validarModulo((string) ($_GET['modulo'] ?? '')),
            'acao'   => preg_replace('/[^a-z_]/', '', strtolower((string) ($_GET['acao'] ?? ''))) ?? '',
            'de'     => $this->validarData((string) ($_GET['de'] ?? '')),
            'ate'    => $this->validarData((string) ($_GET['ate'] ?? '')),
        ];

        $pagina = max(1, (int) ($_GET['p'] ?? 1));

        try {
            $this->model->setPorPagina(Paginacao::porPagina(50));
            $total     = $this->model->total($filtros);
            $totalPags = max(1, (int) ceil($total / $this->model->getPorPagina()));
            $pagina    = min($pagina, $totalPags);
            $registros = $this->model->listar($filtros, $pagina);
            $acoes     = $this->model->acoes();
            $indisponivel = false;
        } catch (PDOException $e) {
            error_log('Auditoria: ' . $e->getMessage());
            $total = 0;
            $totalPags = 1;
            $registros = [];
            $acoes = [];
            $indisponivel = true;
        }

        View::render('auditoria', 'lista', [
            'registros'    => $registros,
            'filtros'      => $filtros,
            'acoes'        => $acoes,
            'pagina'       => $pagina,
            'totalPags'    => $totalPags,
            'total'        => $total,
            'porPagina'    => $this->model->getPorPagina(),
            'indisponivel' => $indisponivel,
        ]);
    }

    /** Remove registros antigos — só administradores. */
    public function limpar(): void
    {
        Auth::requirePermissao('auditoria', 'pode_excluir');
        $this->exigirPost();

        if (!Auth::isAdmin()) {
            Flash::erro('Apenas administradores podem limpar o log de auditoria.');
            $this->redirect();
        }

        $dias     = max(1, (int) ($_POST['dias'] ?? 180));
        $removidos = $this->model->limpar($dias);

        $this->auditar(Audit::EXCLUIU, "Limpeza do log: {$removidos} registro(s) anteriores a {$dias} dias");
        Flash::sucesso("{$removidos} registro(s) removido(s) do log.");
        $this->redirect();
    }

    // ── Apoio ─────────────────────────────────────────────────────────────

    private function validarModulo(string $slug): string
    {
        return in_array($slug, Modules::slugs(), true) ? $slug : '';
    }

    private function validarData(string $data): string
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $data) === 1 ? $data : '';
    }
}
