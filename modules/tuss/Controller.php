<?php
declare(strict_types=1);

require_once BASE_PATH . '/modules/tuss/Model.php';

class TussController extends Controller
{
    protected string $modulo = 'tuss';

    private TussModel $model;

    public function __construct()
    {
        $this->model = new TussModel();
    }

    public function lista(): void
    {
        Auth::requirePermissao('tuss', 'pode_ver');

        $termo    = trim((string) ($_GET['q'] ?? ''));
        $pagina   = max(1, (int) ($_GET['p'] ?? 1));
        $vigentes = ($_GET['vigentes'] ?? '') === '1';

        $this->model->setPorPagina(Paginacao::porPagina(50));

        $total     = $this->model->total($termo, $vigentes);
        $totalPags = max(1, (int) ceil($total / $this->model->getPorPagina()));
        $pagina    = min($pagina, $totalPags);

        View::render('tuss', 'lista', [
            'resultados' => $this->model->buscar($termo, $pagina, $vigentes),
            'termo'      => $termo,
            'vigentes'   => $vigentes,
            'pagina'     => $pagina,
            'totalPags'  => $totalPags,
            'total'      => $total,
            'totalGeral' => $this->model->totalGeral(),
            'porPagina'  => $this->model->getPorPagina(),
        ]);
    }

    public function detalhe(): void
    {
        Auth::requirePermissao('tuss', 'pode_ver');

        $codigo = (int) ($_GET['codigo'] ?? 0);
        $item   = $this->model->buscarPorCodigo($codigo);

        if (!$item) {
            Flash::erro('Procedimento não encontrado.');
            $this->redirect();
        }

        View::render('tuss', 'detalhe', ['item' => $item]);
    }
}
