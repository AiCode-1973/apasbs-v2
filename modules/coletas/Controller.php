<?php
declare(strict_types=1);

require_once BASE_PATH . '/modules/coletas/Model.php';

class ColetasController extends Controller
{
    protected string $modulo = 'coletas';

    private ColetaModel $model;

    public function __construct()
    {
        $this->model = new ColetaModel();
    }

    public function lista(): void
    {
        Auth::requirePermissao('coletas', 'pode_ver');

        $status = (string) ($_GET['status'] ?? '');
        if ($status !== '' && !isset(ColetaModel::STATUS[$status])) {
            $status = '';
        }

        View::render('coletas', 'lista', [
            'coletas' => $this->model->listar($status),
            'contagem'=> $this->model->contarPorStatus(),
            'status'  => $status,
        ]);
    }

    public function ver(): void
    {
        Auth::requirePermissao('coletas', 'pode_ver');

        $id     = $this->id();
        $coleta = $this->model->buscarPorId($id);

        if (!$coleta) {
            Flash::erro('Coleta não encontrada.');
            $this->redirect();
        }

        $motivos     = $this->model->motivos();
        $itensMotivo = $this->model->itens($id, 'motivo');

        View::render('coletas', 'ver', [
            'coleta'          => $coleta,
            'motivos'         => $motivos,
            'itensMotivo'     => $itensMotivo,
            'itensFila'       => $this->model->itens($id, 'fila'),
            'itensAtendente'  => $this->model->itens($id, 'atendente'),
            'sugestoes'       => $this->model->sugerir($itensMotivo, $motivos),
            'fechamentoAtual' => $this->model->fechamentoAtual((int) $coleta['ano'], (int) $coleta['mes']),
        ]);
    }

    public function confirmar(): void
    {
        Auth::requirePermissao('coletas', 'pode_editar');
        $this->exigirPost();

        $id     = $this->id();
        $coleta = $this->model->buscarPorId($id);

        if (!$coleta) {
            Flash::erro('Coleta não encontrada.');
            $this->redirect();
        }

        if ($coleta['status'] !== 'pendente') {
            Flash::erro('Esta coleta já foi resolvida.');
            $this->redirect('ver', ['id' => $id]);
        }

        $decisoes = [];
        foreach ((array) ($_POST['motivo'] ?? []) as $itemId => $valor) {
            $decisoes[(int) $itemId] = (string) $valor;
        }

        try {
            $fechamentoId = $this->model->confirmar($id, $decisoes);
        } catch (Throwable $e) {
            error_log('Coletas::confirmar — ' . $e->getMessage());
            Flash::erro('Não consegui gravar o fechamento: ' . $e->getMessage());
            $this->redirect('ver', ['id' => $id]);
        }

        $this->auditar(
            Audit::CRIOU,
            sprintf('Confirmou a coleta de %02d/%04d — %s atendimentos gravados no fechamento',
                (int) $coleta['mes'], (int) $coleta['ano'], numero((int) $coleta['total_atendimentos'])),
            $id
        );

        Flash::sucesso('Fechamento de ' . competencia_br((int) $coleta['ano'], (int) $coleta['mes'])
            . ' gravado com os números do ReciclaLead.');
        $this->redirect('ver', ['id' => $id]);
    }

    public function descartar(): void
    {
        Auth::requirePermissao('coletas', 'pode_editar');
        $this->exigirPost();

        $id     = $this->id();
        $coleta = $this->model->buscarPorId($id);

        if (!$coleta) {
            Flash::erro('Coleta não encontrada.');
            $this->redirect();
        }

        $this->model->descartar($id);

        $this->auditar(Audit::EDITOU, sprintf('Descartou a coleta de %02d/%04d',
            (int) $coleta['mes'], (int) $coleta['ano']), $id);

        Flash::sucesso('Coleta descartada. O fechamento não foi tocado.');
        $this->redirect();
    }

    public function excluir(): void
    {
        Auth::requirePermissao('coletas', 'pode_excluir');
        $this->exigirPost();

        $id     = $this->id();
        $coleta = $this->model->buscarPorId($id);

        if (!$coleta) {
            Flash::erro('Coleta não encontrada.');
            $this->redirect();
        }

        $this->model->excluir($id);

        $this->auditar(Audit::EXCLUIU, sprintf('Excluiu a coleta de %02d/%04d',
            (int) $coleta['mes'], (int) $coleta['ano']), $id);

        Flash::sucesso('Coleta excluída do histórico.');
        $this->redirect();
    }
}
